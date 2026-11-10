<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasRolePermission;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DBTableController extends Controller
{
    use HasRolePermission;

    // table ដែលអនុញ្ញាត (whitelist)
    protected $tables = [
        'MKT_PD_DATE',
        'MKT_LOAN_CONTRACT',
        'MKT_REP_SCHEDULE',
        'MKT_REP_SCHEDULE_AS',
        'MKT_GL_BALANCE_BACKUP',
        'MKT_JOURNAL',
        'MKT_COLLATERAL',
        'MKT_BORROWING_REP_SCHEDULE_AS',
    ];

    protected $exportLimit = 500000;

    protected function conn()
    {
        return DB::connection('pgsql');
    }

    /* ---------------------------------------------------------
     | Page
     --------------------------------------------------------- */
    public static function getBranchs()
    {
        return DB::connection('pgsql')->table('MKT_BRANCH')->get();
    }

    public function index(Request $request)
    {
        if (!$this->denyPermission('MKT DB Table View')) {
            return view('page.access_page');
        }

        return view('mkt-reports.db-tables.index');
    }

    /* ---------------------------------------------------------
     | Helpers
     --------------------------------------------------------- */
    protected function checkTable(Request $request): string
    {
        $name = $request->input('table_name');
        abort_unless(is_string($name) && in_array($name, $this->tables, true), 422, 'Invalid table');
        return $name;
    }

    protected function realTableName(string $name): string
    {
        $real = $this->conn()
            ->table('information_schema.tables')
            ->whereRaw('LOWER(table_name) = ?', [strtolower($name)])
            ->whereNotIn('table_schema', ['pg_catalog', 'information_schema'])
            ->value('table_name');

        abort_unless($real, 404, 'Table not found in database');
        return $real;
    }

    // [ {column_name, data_type}, ... ] តាមលំដាប់ដើម
    protected function columnInfo(string $name)
    {
        return $this->conn()
            ->table('information_schema.columns')
            ->select('column_name', 'data_type')
            ->where('table_name', $this->realTableName($name))
            ->whereNotIn('table_schema', ['pg_catalog', 'information_schema'])
            ->orderBy('ordinal_position')
            ->get();
    }

    protected function columnList(string $name): array
    {
        return $this->columnInfo($name)->pluck('column_name')->all();
    }

    protected function selectedColumns(Request $request, string $name): array
    {
        $all = $this->columnList($name);
        $sel = array_values(array_intersect($all, (array) $request->input('select_cols', [])));
        return $sel ?: $all;
    }

    // column សម្រាប់ filter ថ្ងៃ ដែលអ្នកប្រើជ្រើស (ត្រឡប់ [ឈ្មោះ, data_type] ឬ null)
    protected function dateColumn(Request $request, string $name): ?array
    {
        $wanted = $request->input('date_col');
        if (!is_string($wanted) || $wanted === '') {
            return null;
        }

        // អនុញ្ញាតតែ column ដែលមានពិតក្នុង table
        $found = $this->columnInfo($name)->first(fn ($c) => $c->column_name === $wanted);

        return $found ? [$found->column_name, $found->data_type] : null;
    }

    // Query មូលដ្ឋាន + filter ថ្ងៃ + filter តម្លៃ
    protected function baseQuery(Request $request, string $name)
    {
        $query = $this->baseQueryDate($request, $name);
        return $this->applyColumnFilters($query, $request, $name);
    }

    protected function baseQueryDate(Request $request, string $name)
    {
        $table = $this->realTableName($name);
        $query = $this->conn()->table($table);
        $date  = $this->dateColumn($request, $name);

        if (!$date || !$request->filled('from_date') || !$request->filled('to_date')) {
            return $query;
        }

        [$col, $type] = $date;

        try {
            $from = Carbon::createFromFormat('d-m-Y', $request->from_date)->startOfDay();
            $to   = Carbon::createFromFormat('d-m-Y', $request->to_date)->endOfDay();
        } catch (\Exception $e) {
            abort(422, 'Invalid date format, expected d-m-Y');
        }

        // column ជា date / timestamp
        if (in_array($type, ['date', 'timestamp without time zone', 'timestamp with time zone'])) {
            $query->whereBetween($col, [$from, $to]);
            return $query;
        }

        // column ជា text: មើលតម្លៃគំរូមួយ ដើម្បីដឹង format
        $sample = $this->conn()->table($table)
            ->whereNotNull($col)->where($col, '<>', '')->value($col);
        $sample = $sample !== null ? trim((string) $sample) : null;

        $wrapped = $this->conn()->getQueryGrammar()->wrap($col);

        if ($sample !== null && preg_match('/^\d{4}-\d{2}-\d{2}/', $sample)) {
            // 2026-11-10 ឬ 2026-11-10 08:30:00
            $query->whereBetween($col, [$from->format('Y-m-d'), $to->format('Y-m-d') . ' 23:59:59']);
        } elseif ($sample !== null && preg_match('/^\d{8}/', $sample)) {
            // 20261110 ឬ 20261110083000
            $query->whereBetween($col, [$from->format('Ymd'), $to->format('Ymd') . '235959']);
        } elseif ($sample !== null && preg_match('/^\d{2}-\d{2}-\d{4}/', $sample)) {
            // 10-11-2026
            $query->whereRaw(
                "TO_DATE(LEFT($wrapped, 10), 'DD-MM-YYYY') BETWEEN ? AND ?",
                [$from->format('Y-m-d'), $to->format('Y-m-d')]
            );
        }
        // format មិនស្គាល់: មិនប្រើ filter ថ្ងៃ

        return $query;
    }

    // Value filters (column + operator + value)
    protected function applyColumnFilters($query, Request $request, string $name)
    {
        $filters = (array) $request->input('col_filters', []);
        if (!$filters) {
            return $query;
        }

        $allowed = $this->columnList($name);
        $grammar = $this->conn()->getQueryGrammar();

        foreach ($filters as $f) {
            $col = $f['col'] ?? null;
            $op  = $f['op']  ?? '=';
            $val = $f['val'] ?? '';

            // សុវត្ថិភាព: column ត្រូវមានពិត
            if (!is_string($col) || !in_array($col, $allowed, true) || !is_scalar($val) || $val === '') {
                continue;
            }

            $wrapped = $grammar->wrap($col);

            switch ($op) {
                case 'contains':
                    $query->whereRaw("CAST($wrapped AS TEXT) ILIKE ?", ['%' . $val . '%']);
                    break;
                case '=':
                    $query->whereRaw("CAST($wrapped AS TEXT) = ?", [$val]);
                    break;
                case '!=':
                    $query->whereRaw("CAST($wrapped AS TEXT) <> ?", [$val]);
                    break;
                case '>':
                    $query->whereRaw("CAST($wrapped AS TEXT) > ?", [$val]);
                    break;
                case '<':
                    $query->whereRaw("CAST($wrapped AS TEXT) < ?", [$val]);
                    break;
            }
        }

        return $query;
    }

    /* ---------------------------------------------------------
     | 1) Column names -> checkboxes
     --------------------------------------------------------- */
    public function columns(Request $request)
    {
        $name = $this->checkTable($request);

        return response()->json(['columns' => $this->columnList($name)]);
    }

    /* ---------------------------------------------------------
     | 2) DataTables server-side data
     --------------------------------------------------------- */
    public function data(Request $request)
    {
        try {
            $name    = $this->checkTable($request);
            $columns = $this->selectedColumns($request, $name);
            $grammar = $this->conn()->getQueryGrammar();

            $query = $this->baseQuery($request, $name);
            $total = (clone $query)->count();

            // Global search លើ column ដែលបាន check
            $search = $request->input('search.value');
            if ($search) {
                $query->where(function ($q) use ($columns, $search, $grammar) {
                    foreach ($columns as $col) {
                        $q->orWhereRaw('CAST(' . $grammar->wrap($col) . ' AS TEXT) ILIKE ?', ['%' . $search . '%']);
                    }
                });
            }
            $filtered = $search ? (clone $query)->count() : $total;

            // Ordering
            $orderIdx = (int) $request->input('order.0.column', 0);
            $orderDir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';
            if (isset($columns[$orderIdx])) {
                $query->orderByRaw($grammar->wrap($columns[$orderIdx]) . ' ' . $orderDir);
            }

            // Paging
            $length = (int) $request->input('length', 20);
            $start  = (int) $request->input('start', 0);
            if ($length > 0) {
                $query->offset($start)->limit($length);
            }

            // cast ជា TEXT ដើម្បីកុំឲ្យ json/bytea/numeric បំបែក JSON
            $selects = array_map(function ($col) use ($grammar) {
                return DB::raw('CAST(' . $grammar->wrap($col) . ' AS TEXT) AS ' . $grammar->wrap($col));
            }, $columns);

            $rows = $query->select($selects)->get()->map(function ($row) {
                foreach ($row as $k => $v) {
                    if (is_string($v)) {
                        $row->$k = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
                    }
                }
                return $row;
            });

            return response()->json([
                'draw'            => (int) $request->input('draw'),
                'recordsTotal'    => $total,
                'recordsFiltered' => $filtered,
                'data'            => $rows,
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);

        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            return response()->json([
                'draw'            => (int) $request->input('draw'),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('DBTable data error: ' . $e->getMessage());

            return response()->json([
                'draw'            => (int) $request->input('draw'),
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => $e->getMessage(),
            ], 500);
        }
    }

    /* ---------------------------------------------------------
     | 3) Export
     --------------------------------------------------------- */
    public function export(Request $request)
    {
        try {

            ini_set('memory_limit', '2048M');
            set_time_limit(0);

            $name  = $this->checkTable($request);
            $cols  = $this->selectedColumns($request, $name);
            $query = $this->baseQuery($request, $name);

            $total = (clone $query)->count();

            // លើស limit: ឆ្លើយសារទៅ client
            if ($total > $this->exportLimit) {
                return response()->json([
                    'status'  => 'too_large',
                    'message' => 'ទិន្នន័យមាន ' . number_format($total) . ' rows លើសការកំណត់ ' .
                                 number_format($this->exportLimit) . ' rows។ សូមបន្ថយចន្លោះថ្ងៃ ឬបន្ថែម filter ហើយព្យាយាមម្តងទៀត។',
                ]);
            }

            $grammar = $this->conn()->getQueryGrammar();
            $selects = array_map(function ($col) use ($grammar) {
                return DB::raw('CAST(' . $grammar->wrap($col) . ' AS TEXT) AS ' . $grammar->wrap($col));
            }, $cols);

            $rows = $query->select($selects)->get()->map(function ($row) {
                foreach ($row as $k => $v) {
                    if (is_string($v)) {
                        $row->$k = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
                    }
                }
                return $row;
            });

            return response()->json([
                'status' => 'ok',
                'table'  => $name,
                'rows'   => $rows,
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);

        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('DBTable export error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}