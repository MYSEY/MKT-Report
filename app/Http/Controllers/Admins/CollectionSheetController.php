<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Carbon\Carbon; // នៅលើកំពូលឯកសារ
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\CollectionSheetExport;
use Maatwebsite\Excel\Facades\Excel;

class CollectionSheetController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = self::getDatas($request);
            // Total records after Branch filter, before Search
            $recordsTotal = (clone $query)->count();
            // Apply search separately
            $searchValue = trim((string) $request->input('search.value', ''));
            $filteredQuery = clone $query;
            if ($searchValue !== '') {
                $filteredQuery->where(function ($q) use ($searchValue) {
                    $q->whereRaw('CAST("RS"."ID" AS TEXT) ILIKE ?', ["%{$searchValue}%"])
                        ->orWhere('RS.Branch', 'ILIKE', "%{$searchValue}%")
                        ->orWhere('RS.LoanID', 'ILIKE', "%{$searchValue}%");
                });
            }

            $recordsFiltered = (clone $filteredQuery)->count();
            // Pagination
            $start = max(0, (int) $request->input('start', 0));
            $limit = (int) $request->input('length', 10);

            if ($limit <= 0) {
                $limit = 10;
            }

            $limit = min($limit, 100);
            $data = (clone $filteredQuery)->offset($start)->limit($limit)->orderBy('RS.ID')->get();
            return response()->json([
                'draw'            => (int) $request->input('draw'),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data,
            ]);
        }
        $branch = DB::connection('pgsql')->table('MKT_BRANCH')->select('ID', 'Description', 'LocalDescription')->get();
        return view('collection-sheet.index', compact('branch'));
    }

    public static function getDatas($request)
    {
        $from_date = $request->filled('from_date') ? Carbon::parse($request->input('from_date'))->toDateString() : now()->startOfMonth()->toDateString();
        $to_date_exclusive = $request->filled('to_date') ? Carbon::parse($request->input('to_date'))->addDay()->toDateString()  : now()->endOfMonth()->addDay()->toDateString();
        $query = DB::connection('pgsql')
        ->table('MKT_REP_SCHEDULE as RS')
        ->leftJoin('MKT_LOAN_CONTRACT as LC', 'RS.LoanID', '=', 'LC.ID')
        ->select([
            'RS.ID',
            'RS.Branch',
            'RS.LoanID',
            'RS.CollectionDate',
            'RS.Principal',
            'RS.Interest',
            'RS.Charge',
            'RS.Balance',
            'LC.Currency',
        ])
        ->where('RS.CollectionDate', '>=', $from_date)
        ->where('RS.CollectionDate', '<', $to_date_exclusive);

        // Branch filter
        if ($request->filled('branch_id') && $request->branch_id !== 'ALL') {
            $query->where('RS.Branch', $request->branch_id);
        }
        return $query;
    }

    public function download(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1G');
        $query = self::getDatas($request);
        $filename = 'collection_sheet_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new CollectionSheetExport($query), $filename);
        
        // set_time_limit(0);
        // ini_set('memory_limit', '512M');
        // $query = self::getDatas($request);
        // $filename = 'collection_sheet_' . now()->format('Ymd_His') . '.csv';
        // return response()->streamDownload(function () use ($query) {
        //     $file = fopen('php://output', 'w');
        //     // UTF-8 BOM for Excel
        //     fwrite($file, "\xEF\xBB\xBF");
        //     fputcsv($file, [
        //         'Loan ID',
        //         'Repayment Date',
        //         'Principal',
        //         'Interest',
        //         'Charge',
        //         'Branch',
        //         'Currency',
        //     ]);

        //     $query->reorder();
        //     foreach ($query->lazyById(1000, 'RS.ID', 'ID') as $row) {
        //         fputcsv($file, [
        //             $row->LoanID,
        //             $row->CollectionDate,
        //             number_format((float) $row->Principal, 2, '.', ','),
        //             number_format((float) $row->Interest, 2, '.', ','),
        //             number_format((float) $row->Charge, 2, '.', ','),
        //             $row->Branch,
        //             $row->Currency,
        //         ]);
        //     }
        //     fclose($file);
        // }, $filename, [
        //     'Content-Type'  => 'text/csv; charset=UTF-8',
        //     'Cache-Control' => 'no-cache, no-store',
        // ]);
    }
}
