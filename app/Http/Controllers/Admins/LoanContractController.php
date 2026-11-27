<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon; // នៅលើកំពូលឯកសារ
use Illuminate\Support\Facades\DB;
use App\Exports\LoanContractExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\HasRolePermission;
class LoanContractController extends Controller
{
    use HasRolePermission;

    public function __construct()
    {
        $this->applyRolePermissions('Loan Contract');
    }
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
                    $q->whereRaw('CAST("ID" AS TEXT) ILIKE ?', ["%{$searchValue}%"])
                        ->orWhere('Branch', 'ILIKE', "%{$searchValue}%")
                        ->where('LoanProduct', 'ILIKE', "%{$searchValue}%")
                        ->where('LoanAmount', 'ILIKE', "%{$searchValue}%")
                        ->where('LoanID', 'ILIKE', "%{$searchValue}%");
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
            $data = (clone $filteredQuery)->offset($start)->limit($limit)->orderBy('ID')->get();
            return response()->json([
                'draw'            => (int) $request->input('draw'),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data'            => $data,
            ]);
        }
        $branch = DB::connection('pgsql')->table('MKT_BRANCH')->select('ID', 'Description', 'LocalDescription')->get();
        return view('loan-contract.index', compact('branch'));
    }

    public static function getDatas($request)
    {
        $from_date = $request->filled('from_date') ? Carbon::parse($request->input('from_date'))->toDateString() : now()->startOfMonth()->toDateString();
        $to_date_exclusive = $request->filled('to_date') ? Carbon::parse($request->input('to_date'))->addDay()->toDateString()  : now()->endOfMonth()->addDay()->toDateString();
        $query = DB::connection('pgsql')
        ->table('MKT_LOAN_CONTRACT')
        ->where('ValueDate', '>=', $from_date)
        ->where('ValueDate', '<', $to_date_exclusive);

        // Branch filter
        if ($request->filled('branch_id') && $request->branch_id !== 'ALL') {
            $query->where('Branch', $request->branch_id);
        }
        return $query;
    }
    public function download(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $query = self::getDatas($request);
        $filename = 'loan_contract_report_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new LoanContractExport($query), $filename);
    }
}
