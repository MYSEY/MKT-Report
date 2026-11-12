<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use App\Models\MktTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Traits\HasRolePermission;
use Carbon\Carbon;

class DBTableController extends Controller
{
    use HasRolePermission;

    protected $exportLimit = 500000;   // ចំនួន row អតិបរមាសម្រាប់ export
    protected $schemaTtl   = 3600;     // cache ព័ត៌មាន column (វិនាទី)
    protected $countTtl    = 60;       // cache COUNT (វិនាទី)

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

        $tables = MktTable::active()->get();
        return view('mkt-reports.db-tables.index', compact('tables'));
    }

    /* ---------------------------------------------------------
     | Helpers
     --------------------------------------------------------- */
    // អនុញ្ញាតតែ table ដែលមានក្នុង mkt-table ហើយ Active
    protected function checkTable(Request $request): string
    {
        $name = $request->input('table_name');

        abort_unless(
            is_string($name)
            && MktTable::where('table_name', $name)->where('is_active', true)->exists(),
            422,
            'Invalid table'
        );

        return $name;
    }

    protected function checkExportPermission(): void
    {
        // abort_unless(auth()->user()->can('MKT DB Table Export'), 403, 'No permission to export');
    }

    // ឈ្មោះ table ពិតក្នុង Postgres (cache ក្នុង Laravel មិនប៉ះ DB)
    protected function realTableName(string $name): string
    {
        $real = Cache::remember('dbtable_real_' . $name, $this->schemaTtl, function () use ($name) {
            return $this->conn()
                ->table('information_schema.tables')
                ->whereRaw('LOWER(table_name) = ?', [strtolower($name)])
                ->whereNotIn('table_schema', ['pg_catalog', 'information_schema'])
                ->value('table_name');
        });

        abort_unless($real, 404, 'Table not found in database');
        return $real;
    }

    // [ {column_name, data_type}, ... ] តាមលំដាប់ដើម (cache)
    protected function columnInfo(string $name)
    {
        return Cache::remember('dbtable_cols_' . $name, $this->schemaTtl, function () use ($name) {
            return $this->conn()
                ->table('information_schema.columns')
                ->select('column_name', 'data_type')
                ->where('table_name', $this->realTableName($name))
                ->whereNotIn('table_schema', ['pg_catalog', 'information_schema'])
                ->orderBy('ordinal_position')
                ->get();
        });
    }

    protected function columnList(string $name): array
    {
        return $this->columnInfo($name)->pluck('column_name')->all();
    }

    // បម្លែង "1,2,5-8" ទៅជាឈ្មោះ column (index ចាប់ពី 1)
    protected function columnsByIndex(string $name, ?string $expr): array
    {
        $all = $this->columnList($name);
        $max = count($all);
        $idx = [];

        foreach (explode(',', (string) $expr) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $part, $m)) {
                $a = min((int) $m[1], (int) $m[2]);
                $b = max((int) $m[1], (int) $m[2]);
                for ($i = $a; $i <= min($b, $max); $i++) {
                    $idx[] = $i;
                }
            } elseif (ctype_digit($part)) {
                $idx[] = (int) $part;
            }
        }

        $cols = [];
        foreach (array_unique($idx) as $i) {
            if ($i >= 1 && $i <= $max) {
                $cols[] = $all[$i - 1];
            }
        }

        return $cols;
    }

    // column ដែលត្រូវបង្ហាញ: តាម index (បើមាន) ឬតាម checkbox
    protected function selectedColumns(Request $request, string $name): array
    {
        $all = $this->columnList($name);

        $expr = $request->input('col_index');
        if (is_string($expr) && trim($expr) !== '') {
            $byIdx = $this->columnsByIndex($name, $expr);
            if ($byIdx) {
                return $byIdx;
            }
        }

        $sel = array_values(array_intersect($all, (array) $request->input('select_cols', [])));
        return $sel ?: $all;
    }

    // [CAST(col AS TEXT) AS col, ...] ការពារ json/bytea/numeric បំបែក output
    protected function castSelects(array $cols): array
    {
        $grammar = $this->conn()->getQueryGrammar();

        return array_map(function ($col) use ($grammar) {
            return DB::raw('CAST(' . $grammar->wrap($col) . ' AS TEXT) AS ' . $grammar->wrap($col));
        }, $cols);
    }

    // column សម្រាប់ filter ថ្ងៃ ដែលអ្នកប្រើជ្រើស
    protected function dateColumn(Request $request, string $name): ?array
    {
        $wanted = $request->input('date_col');
        if (!is_string($wanted) || $wanted === '') {
            return null;
        }

        $found = $this->columnInfo($name)->first(fn ($c) => $c->column_name === $wanted);

        return $found ? [$found->column_name, $found->data_type] : null;
    }

    // Query មូលដ្ឋាន = filter ថ្ងៃ + filter តម្លៃ
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
        $sample  = $sample !== null ? trim((string) $sample) : null;
        $wrapped = $this->conn()->getQueryGrammar()->wrap($col);

        if ($sample !== null && preg_match('/^\d{4}-\d{2}-\d{2}/', $sample)) {
            $query->whereBetween($col, [$from->format('Y-m-d'), $to->format('Y-m-d') . ' 23:59:59']);
        } elseif ($sample !== null && preg_match('/^\d{8}/', $sample)) {
            $query->whereBetween($col, [$from->format('Ymd'), $to->format('Ymd') . '235959']);
        } elseif ($sample !== null && preg_match('/^\d{2}-\d{2}-\d{4}/', $sample)) {
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

    // COUNT ដែល cache រយៈពេលខ្លី (ប្តូរទំព័រ/sort មិនបាច់រាប់ម្តងទៀត)
    protected function cachedCount(Request $request, string $name, $query): int
    {
        $key = 'dbtable_count_' . md5(json_encode([
            $name,
            $request->input('date_col'),
            $request->input('from_date'),
            $request->input('to_date'),
            $request->input('col_filters'),
        ]));

        return (int) Cache::remember($key, $this->countTtl, fn () => (clone $query)->count());
    }

    // បម្លែង query ទៅជា SQL ពេញ (bindings ត្រូវ quote ដោយ PDO) សម្រាប់ DECLARE CURSOR
    protected function rawSql($query): string
    {
        $pdo = $this->conn()->getPdo();

        $bindings = array_map(function ($b) use ($pdo) {
            if ($b instanceof \DateTimeInterface) {
                $b = $b->format('Y-m-d H:i:s');
            }
            if (is_null($b)) {
                return 'NULL';
            }
            if (is_bool($b)) {
                return $b ? 'TRUE' : 'FALSE';
            }
            if (is_int($b) || is_float($b)) {
                return (string) $b;
            }
            return $pdo->quote((string) $b);
        }, $query->getBindings());

        return Str::replaceArray('?', $bindings, $query->toSql());
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
     | 2) DataTables server-side data (គ្មាន global search)
     --------------------------------------------------------- */
    public function data(Request $request)
    {
        try {
            $name    = $this->checkTable($request);
            $columns = $this->selectedColumns($request, $name);
            $grammar = $this->conn()->getQueryGrammar();

            $query = $this->baseQuery($request, $name);
            $total = $this->cachedCount($request, $name, $query);

            // Ordering: តែពេលអ្នកប្រើ click sort
            $orderIdx = $request->input('order.0.column');
            if ($orderIdx !== null && isset($columns[(int) $orderIdx])) {
                $dir = $request->input('order.0.dir') === 'desc' ? 'desc' : 'asc';
                $query->orderByRaw($grammar->wrap($columns[(int) $orderIdx]) . ' ' . $dir);
            }

            // Paging
            $length = (int) $request->input('length', 20);
            $start  = (int) $request->input('start', 0);
            if ($length > 0) {
                $query->offset($start)->limit($length);
            }

            $rows = $query->select($this->castSelects($columns))->get()->map(function ($row) {
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
                'recordsFiltered' => $total,
                'data'            => $rows,
            ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);

        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            return response()->json([
                'draw' => (int) $request->input('draw'), 'recordsTotal' => 0,
                'recordsFiltered' => 0, 'data' => [], 'error' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('DBTable data error: ' . $e->getMessage());

            return response()->json([
                'draw' => (int) $request->input('draw'), 'recordsTotal' => 0,
                'recordsFiltered' => 0, 'data' => [], 'error' => $e->getMessage(),
            ], 500);
        }
    }

    /* ---------------------------------------------------------
     | 3) Export step 1: ឆែកចំនួន row មុន download
     --------------------------------------------------------- */
    public function exportCheck(Request $request)
    {
        try {
            $this->checkExportPermission();

            $name  = $this->checkTable($request);
            $total = (int) $this->baseQuery($request, $name)->count();

            if ($total === 0) {
                return response()->json(['status' => 'empty', 'total' => 0]);
            }

            if ($total > $this->exportLimit) {
                return response()->json([
                    'status'  => 'too_large',
                    'total'   => $total,
                    'message' => 'ទិន្នន័យមាន ' . number_format($total) . ' rows លើសការកំណត់ ' .
                                 number_format($this->exportLimit) . ' rows។ សូមបន្ថយចន្លោះថ្ងៃ ឬបន្ថែម filter ហើយព្យាយាមម្តងទៀត។',
                ]);
            }

            return response()->json(['status' => 'ok', 'total' => $total]);

        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('DBTable exportCheck error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /* ---------------------------------------------------------
     | 4) Export step 2: stream CSV ដោយ server-side cursor
     |    (memory ថេរ មិនទាញទាំងអស់ចូល PHP)
     --------------------------------------------------------- */
    public function export(Request $request)
    {
        $this->checkExportPermission();
        set_time_limit(0);

        $name   = $this->checkTable($request);
        $cols   = $this->selectedColumns($request, $name);
        $query  = $this->baseQuery($request, $name);
        $format = $request->input('format') === 'xlsx' ? 'xlsx' : 'csv';

        $total = (int) (clone $query)->count();
        abort_if($total > $this->exportLimit, 422, 'Too many rows, please narrow the filters');

        $sql      = $this->rawSql($query->select($this->castSelects($cols)));
        $filename = $name . '_' . now()->format('Y-m-d_His') . '.' . $format;
        $pdo      = $this->conn()->getPdo();

        // អានទិន្នន័យម្តង 5000 rows ហើយ ហៅ $emit($row) សម្រាប់ម្នាក់ៗ
        $stream = function (callable $emit) use ($pdo, $sql) {
            try {
                $pdo->beginTransaction();
                $pdo->exec('DECLARE export_cur NO SCROLL CURSOR FOR ' . $sql);

                while (true) {
                    $rows = $pdo->query('FETCH 5000 FROM export_cur')->fetchAll(\PDO::FETCH_NUM);
                    if (!$rows) {
                        break;
                    }
                    foreach ($rows as $r) {
                        $emit($r);
                    }
                }

                $pdo->exec('CLOSE export_cur');
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                Log::error('DBTable export error: ' . $e->getMessage());
            }
        };

        if ($format === 'xlsx') {
            return response()->streamDownload(function () use ($stream, $cols) {
                $writer = new \OpenSpout\Writer\XLSX\Writer();
                $writer->openToFile('php://output');
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($cols));

                $stream(function ($r) use ($writer) {
                    // បង្ខំជា string ដើម្បីកុំឲ្យលេខវែងក្លាយជា 1.23E+15
                    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(array_map('strval', $r)));
                });

                $writer->close();
            }, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        return response()->streamDownload(function () use ($stream, $cols) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $cols);

            $stream(function ($r) use ($out) {
                fputcsv($out, $r);
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}