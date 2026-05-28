<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\HasRolePermission;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LoanDisbursementController extends Controller
{
    use HasRolePermission;
    public function __construct()
    {
        $this->applyRolePermissions('Permission');
    }
    public static function getBranchs(){
        $branchs = DB::connection('pgsql')->table('MKT_BRANCH')->get();
        return $branchs;
    }

    public static function getDatas($request)
    {
        $search = $request->input('search.value');
        $fromDate = !empty($request->from_date) 
                    ? Carbon::createFromFormat('d-m-Y', $request->from_date)->format('Y-m-d') 
                    : null;
        $toDate = !empty($request->to_date) 
                    ? Carbon::createFromFormat('d-m-Y', $request->to_date)->format('Y-m-d') 
                    : null;

        $query = DB::connection('pgsql')
            ->table('MKT_ACC_ENTRY')
            ->leftJoin('MKT_LOAN_CONTRACT as LC', 'LC.ID', '=', 'MKT_ACC_ENTRY.Reference')
            ->select([
                'MKT_ACC_ENTRY.*',
                'LC.LoanType'
            ])
            ->where('MKT_ACC_ENTRY.Branch', '<>', '') 
            ->where('MKT_ACC_ENTRY.Currency', '<>', '') 
            ->where('MKT_ACC_ENTRY.Transaction', '=', '1')
            ->where('MKT_ACC_ENTRY.Reference', 'like', 'LC%');
        $query->when(!empty($request->branch_id) && $request->branch_id != 'all', function ($q) use ($request) {
            return $q->where('MKT_ACC_ENTRY.Branch', $request->branch_id);
        });
        $query->when(!empty($fromDate), function ($q) use ($fromDate) {
            return $q->where('MKT_ACC_ENTRY.TransactionDate', '>=', $fromDate);
        });
        $query->when(!empty($toDate), function ($q) use ($toDate) {
            return $q->where('MKT_ACC_ENTRY.TransactionDate', '<=', $toDate);
        });
        $query->when(!empty($request->currency) && $request->currency != 'all', function ($q) use ($request) {
            return $q->where('MKT_ACC_ENTRY.Currency', $request->currency);
        });

        // === ប្រព័ន្ធស្វែងរកសកល (Global Search) ===
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('MKT_ACC_ENTRY.Branch', 'like', "%{$search}%")
                ->orWhere('MKT_ACC_ENTRY.TransactionDate', 'like', "%{$search}%")
                ->orWhere('MKT_ACC_ENTRY.Transaction', 'ilike', "%{$search}%")
                ->orWhere('MKT_ACC_ENTRY.Currency', 'ilike', "%{$search}%")
                ->orWhere('MKT_ACC_ENTRY.Reference', 'ilike', "%{$search}%")
                ->orWhere('LC.LoanType', 'ilike', "%{$search}%");
            });
        }

        return $query;
    }
    public function index(Request $request) {
        if (!$this->denyPermission('Loan Disbursement View')) {
            return view('page.access_page');
        }
        if ($request->ajax()) {
            $query = self::getDatas($request);
            $recordsFiltered = $query->count();
            $hasFilter = $request->filled('from_date') || 
                     $request->filled('to_date') || 
                     ($request->filled('branch_id') && $request->branch_id !== 'all') ||
                     ($request->filled('currency') && $request->currency !== 'all') ||
                     $request->input('search.value');

            if (!$hasFilter) {
                $totalRequest = new Request(); 
                $recordsTotal = self::getDatas($totalRequest)->count();
                session(['loan_records_total' => $recordsTotal]);
            } else {
                $recordsTotal = session('loan_records_total', $recordsFiltered);
            }

            $start = intval($request->input('start', 0));
            $limit = intval($request->input('length', 20));

            $data = $query->orderBy('MKT_ACC_ENTRY.TransactionDate', 'asc')
                        ->offset($start)
                        ->limit($limit)
                        ->get();
            
            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered, 
                'data'            => $data,
            ]);
        }
        $branchs = self::getBranchs();
        return view('mkt-reports.loans.loan_disbursement',compact('branchs'));
    }
}
