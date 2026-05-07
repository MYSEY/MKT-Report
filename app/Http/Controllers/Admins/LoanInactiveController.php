<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\HasRolePermission;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Exports\ExportLoanInactive;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class LoanInactiveController extends Controller
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
        $fromDate = $request->from_closedDate;
        $toDate = $request->to_closedDate;
        // ១. Query សម្រាប់ Table Closed
        $closedQuery = DB::connection('pgsql')->table('MKT_CLOSED_LOAN')
            ->select([
                'Branch', 'ID', 'ContractCustomerID', 'Account', 'Currency', 
                'ValueDate', 'ClosedDate', 'Disbursed', 
                'InterestRate', 'Term', 'MaturityDate', 'LoanProduct', 
                'Sector', 'Category', 'ContractOfficerID', 
                'LoanStatus'
            ]);
        if (!empty($request->branch_id)) {
            $closedQuery->where('Branch', $request->branch_id);
        }
        if (!empty($fromDate)) {
            $closedQuery->whereDate('ClosedDate', '>=', $fromDate);
        }
        if (!empty($toDate)) {
            $closedQuery->whereDate('ClosedDate', '<=', $toDate);
        }
        // ២. Query សម្រាប់ Table Active
        $activeLoans = DB::connection('pgsql')->table('MKT_LOAN_CONTRACT as ACTIVE')
            ->select([
                'Branch', 'ID', 'ContractCustomerID', 'Account', 'Currency', 
                'ValueDate', DB::raw('NULL as "ClosedDate"'), 'Disbursed', 
                'InterestRate', 'Term', 'MaturityDate', 'LoanProduct', 
                'Sector', 'Category', 'ContractOfficerID', 
                DB::raw("'Active' as \"LoanStatus\"")
            ]);

        // *** បន្ថែមត្រង់នេះ៖ Filter Branch លើ Table Active ផ្ទាល់ ***
        if (!empty($request->branch_id)) {
            $activeLoans->where('ACTIVE.Branch', $request->branch_id);
        }

        $activeLoans->whereExists(function ($query) use ($request, $fromDate, $toDate) {
            $query->select(DB::raw(1))
                ->from('MKT_CLOSED_LOAN as c')
                ->whereColumn('c.ContractCustomerID', 'ACTIVE.ContractCustomerID');
            
            if (!empty($request->branch_id)) {
                $query->where('c.Branch', $request->branch_id);
            }
            if (!empty($fromDate)) { 
                $query->whereDate('c.ClosedDate', '>=', $fromDate); 
            }
            if (!empty($toDate)) { 
                $query->whereDate('c.ClosedDate', '<=', $toDate); 
            }
        });

        // ៣. បញ្ចូលគ្នាដោយប្រើ unionAll
        $combinedQuery = $closedQuery->unionAll($activeLoans);

        // ៤. បង្កើត Base Query ថ្មីចេញពី Union
        // ចំណុចសំខាន់៖ ប្រើ mergeBindings ឱ្យត្រូវតាមលំដាប់
        $query = DB::connection('pgsql')->table(DB::raw("({$combinedQuery->toSql()}) as combined"))
            ->setBindings($combinedQuery->getBindings()) // ប្រើ setBindings ជំនួស mergeBindings ដើម្បីភាពច្បាស់លាស់
            ->leftJoin('MKT_CUSTOMER as CUST', 'CUST.ID', '=', 'combined.ContractCustomerID')
            ->leftJoin('MKT_LOAN_PRODUCT as prod', 'prod.ID', '=', 'combined.LoanProduct')
            ->select([
                'combined.*',
                DB::raw('TRIM("CUST"."LastNameEn") || \' \' || TRIM("CUST"."FirstNameEn") as "EnName"'),
                DB::raw('TRIM("prod"."ID") || \' \' || TRIM("prod"."Description") as "ProdName"')
            ]);

        // ៥. បន្ថែម Search បន្ទាប់ពី Join រួច (bindings នឹងត្រូវបន្ថែមតាមក្រោយ)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw('combined."ContractCustomerID"::text'), 'like', "%{$search}%")
                ->orWhere(DB::raw('combined."ID"::text'), 'like', "%{$search}%")
                ->orWhere(DB::raw('TRIM("CUST"."LastNameEn") || \' \' || TRIM("CUST"."FirstNameEn")'), 'ilike', "%{$search}%");
            });
        }

        return $query;
    }
    public function index(Request $request) {
        if (!$this->denyPermission('Loan Inactive View')) {
            return view('page.access_page');
        }
        if ($request->ajax()) {
            $query = self::getDatas($request);
            $recordsFiltered = $query->count();
            $hasFilter = $request->filled('from_closedDate') || 
                     $request->filled('to_closedDate') || 
                     ($request->filled('branch_id') && $request->branch_id !== 'all') ||
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

            $data = $query->orderBy('combined.ContractCustomerID', 'asc')
                        ->orderBy('combined.ValueDate', 'asc')
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
        return view('mkt-reports.loans.loan_inactive',compact('branchs'));
    }
    public function export(Request $request) {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);
        $query = self::getDatas($request)
            ->orderBy('combined.ContractCustomerID', 'asc')
            ->orderBy('combined.ValueDate', 'asc');
        $date = date('d-m-Y');
        $dataGenerator = function () use ($query) {
            $no = 1;
            foreach ($query->cursor() as $row) {
                yield [
                    '#'                 =>$no++,
                    'LoanStatus'        =>$row->LoanStatus,
                    'Branch'            =>$row->Branch,
                    'ID'                =>$row->ID,
                    'ContractCustomerID'=>$row->ContractCustomerID,
                    'CustomerName'      =>$row->EnName,
                    // 'Account'           =>$row->Account,
                    'Currency'          =>$row->Currency,
                    'DisburseDate'      =>$row->ValueDate,
                    'ClosedDate'        =>$row->ClosedDate,
                    'Disbursed'         =>$row->Disbursed,
                    'InterestRate'      =>$row->InterestRate,
                    'Term'              =>$row->Term,
                    'MaturityDate'      =>$row->MaturityDate,
                    'LoanProduct'       =>$row->ProdName,
                    'Sector'            =>$row->Sector,
                    'Category'          =>$row->Category,
                    'ContractOfficerID' =>$row->ContractOfficerID,
                ];
            }
        };
       return (new FastExcel($dataGenerator()))->download('LoanInactiveMonitoring_'.$date.'.xlsx');
    }
    public function exportInactive(Request $request){
        //*** (យ៉ាងហោច RAM 8GB ឡើងទៅ) **/
        ini_set('memory_limit', '-1'); 
        set_time_limit(0);

        $data = self::getDatas($request)->orderBy('combined.ContractCustomerID', 'asc')
            ->orderBy('combined.ValueDate', 'asc')->get();
        $date = $request->get('date') ?? date('Y-m');
        $name_file = "LoanInactiveMonitoring_";
        return Excel::download(new ExportLoanInactive($data), $name_file.$date.'.xlsx');
    }
}
