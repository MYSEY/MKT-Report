<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Carbon\Carbon; // នៅលើកំពូលឯកសារ
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\PDDateExport;
use Maatwebsite\Excel\Facades\Excel;

class PDDateController extends Controller
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
                    $q->whereRaw('CAST("PD"."ID" AS TEXT) ILIKE ?', ["%{$searchValue}%"])
                        ->orWhere('PD.Branch', 'ILIKE', "%{$searchValue}%")
                        ->orWhere('PD.LoanID', 'ILIKE', "%{$searchValue}%");
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
            $data = (clone $filteredQuery)->offset($start)->limit($limit)->orderBy('PD.ID')->get();
            return response()->json([
                'draw'            => (int) $request->input('draw'),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data,
            ]);
        }
        $branch = DB::connection('pgsql')->table('MKT_BRANCH')->select('ID', 'Description', 'LocalDescription')->get();
        return view('pd-date.index', compact('branch'));
    }

    public static function getDatas($request)
    {
        $from_date = $request->filled('from_date') ? Carbon::parse($request->input('from_date'))->toDateString() : now()->startOfMonth()->toDateString();
        $to_date_exclusive = $request->filled('to_date') ? Carbon::parse($request->input('to_date'))->addDay()->toDateString()  : now()->endOfMonth()->addDay()->toDateString();
        $query = DB::connection('pgsql')
        ->table('MKT_PD_DATE as PD')
        ->select([
            'PD.Branch',
            'PD.Status',
            'PD.Curr',
            'PD.Inputter',
            'PD.Createdon',
            'PD.Authorizer',
            'PD.Authorizeon',
            'PD.ID',
            'PD.PDID',
            'PD.DueDate',
            'PD.NumDayDue',
            'PD.ODStatus',
            'PD.TotODAmount',
            'PD.OutAmount',
            'PD.PrincipalDue',
            'PD.OutPriAmount',
            'PD.InterestDue',
            'PD.OutIntAmount',
            'PD.PenaltyDue',
            'PD.OutPenAmount',
            'PD.ChargeDue',
            'PD.OutChgAmount',
            'PD.NextRunDate',
            'PD.OutAmountAS',
            'PD.PrincipalDueAS',
            'PD.OutPriAmountAS',
            'PD.IntDueAmountAS',
            'PD.OutIntAmountAS',
            'PD.ChargeDueAS',
            'PD.OutChgAmountAS',
            'PD.PenaltyDueAS',
            'PD.OutPenAmountAS',
            'PD.VATDue',
            'PD.OutVATAmount',
            'PD.VATDueAS',
            'PD.OutVATAmountAS',
            'PD.WFStatus',
        ])
        ->where('PD.DueDate', '>=', $from_date)
        ->where('PD.DueDate', '<', $to_date_exclusive);

        // Branch filter
        if ($request->filled('branch_id') && $request->branch_id !== 'ALL') {
            $query->where('PD.Branch', $request->branch_id);
        }
        return $query;
    }
    public function download(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $query = self::getDatas($request);
        $filename = 'pd_date_report_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new PDDateExport($query), $filename);
    }
}