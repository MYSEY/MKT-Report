<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class JournalDeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $db = DB::connection('pgsql');
            // Base query with filters
            $query = self::getData($request);
            // Total records (cached for 60 seconds)
            $recordsTotal = Cache::remember(
                'mkt_journal_total_records',
                60,
                function () use ($db) {
                    return $db->table('MKT_JOURNAL')->count();
                }
            );

            // Filtered records
            $recordsFiltered = (clone $query)->count();
            // DataTables pagination
            $start = max(0, (int) $request->input('start', 0));
            $limit = (int) $request->input('length', 10);
            if ($limit <= 0) {
                $limit = 10;
            }

            $limit = min($limit, 100);
            // Get only current page
            $data = (clone $query)->orderByDesc('ID')->offset($start)->limit($limit)->get();
            return response()->json([
                'draw'            => (int) $request->input('draw'),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data,
            ]);
        }
        $branch = DB::connection('pgsql')->table('MKT_BRANCH')->select('ID', 'Description', 'LocalDescription')->get();
        return view('journal-de.index', compact('branch'));
    }

    public static function getData($request)
    {
        // Default date range
        $fromDate = $request->filled('from_date') ? Carbon::createFromFormat('d-m-Y', $request->from_date)->toDateString() : now()->startOfMonth()->toDateString();
        $toDate = $request->filled('to_date') ? Carbon::createFromFormat('d-m-Y', $request->to_date)->addDay()->toDateString() : now()->endOfMonth()->addDay()->toDateString();
        $query = DB::connection('pgsql')->table('MKT_JOURNAL');
        // Branch filter
        if ($request->filled('branch_id')) {
            $query->where('Branch', $request->branch_id);
        }
        // Timestamp range filter
        $query->where('TransactionDate', '>=', $fromDate)->where('TransactionDate', '<=', $toDate);
        return $query;
    }
    public function download(Request $request)
    {
        $data = self::getData($request)->orderBy('ID', 'desc')->get();
        $filename = 'journal_de_report_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        $columns = [
            'Branch', 
            'TransactionDate', 
            'ID', 
            'Description', 
            'CategoryID',
            'GL_KEYS',
            'Currency',
            'DebitCredit',
            'Amount',
            'PrevBalance',
            'Module',
            'Transaction',
            'Reference',
            'LCYAmount',
            'LCYPrevBalance',
            'UserReference',
            'Account',
            'CustomerID',
            'Status',
            'Inputter',
            'Createdon',
            'Authorizer',
            'Authorizeon',
        ];
        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($data as $row) {
                fputcsv($file, [
                    $row->Branch,
                    $row->TransactionDate,
                    $row->ID,
                    $row->Description,
                    $row->CategoryID,
                    $row->GL_KEYS,
                    $row->Currency,
                    $row->DebitCredit,
                    $row->Amount,
                    $row->PrevBalance,
                    $row->Module,
                    $row->Transaction,
                    $row->Reference,
                    $row->LCYAmount,
                    $row->LCYPrevBalance,
                    $row->UserReference,
                    $row->Account,
                    $row->CustomerID,
                    $row->Status,
                    $row->Inputter,
                    $row->Createdon,
                    $row->Authorizer,
                    $row->Authorizeon,
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}
