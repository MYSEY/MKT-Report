<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\HasRolePermission;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
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

        // ១. ទាញទិន្នន័យពី Table Active
        $activeLoans = DB::connection('pgsql')->table('MKT_LOAN_CONTRACT')
            ->select([
                'Branch', 'ID', 'ContractCustomerID', 'Account', 'Currency', 
                'ValueDate', DB::raw('NULL as "ClosedDate"'), 'Disbursed', 
                'InterestRate', 'Term', 'MaturityDate', 'LoanProduct', 
                'Sector', 'Category', 'ContractOfficerID', 'LoanStatus' // យកតម្លៃតាម DB
            ]);

        // ២. ទាញទិន្នន័យពី Table Closed រួច Union
        $combinedQuery = DB::connection('pgsql')->table('MKT_CLOSED_LOAN')
            ->select([
                'Branch', 'ID', 'ContractCustomerID', 'Account', 'Currency', 
                'ValueDate', 'ClosedDate', 'Disbursed', 
                'InterestRate', 'Term', 'MaturityDate', 'LoanProduct', 
                'Sector', 'Category', 'ContractOfficerID', 'LoanStatus' // យកតម្លៃតាម DB
            ])
            ->unionAll($activeLoans);

        // ៣. Join ជាមួយ Customer ដើម្បីយកឈ្មោះ
        $query = DB::connection('pgsql')->table(DB::raw("({$combinedQuery->toSql()}) as combined"))
            ->mergeBindings($combinedQuery)
            ->leftJoin('MKT_CUSTOMER as CUST', 'CUST.ID', '=', 'combined.ContractCustomerID')
            ->select([
                'combined.*',
                DB::raw('TRIM("CUST"."LastNameEn") || \' \' || TRIM("CUST"."FirstNameEn") as "EnName"')
            ]);

        // filter
        $query->when($request->from_closedDate, function ($q, $from_date) {
            $formattedDate = Carbon::parse($from_date)->format('Y-m-d');
            return $q->whereDate('combined.ClosedDate', '>=', $formattedDate);
        });

        $query->when($request->to_closedDate, function ($q, $to_date) {
            $formattedDate = Carbon::parse($to_date)->format('Y-m-d');
            return $q->whereDate('combined.ClosedDate', '<=', $formattedDate);
        });
        $query->when($request->branch_id, function ($q, $branch_id) {
            return $q->where('combined.Branch', $branch_id);
        });
        // ៤. Search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('combined.ContractCustomerID', 'ilike', "%{$search}%")
                ->orWhere('combined.Account', 'ilike', "%{$search}%")
                
                // Search ឈ្មោះពេញជាភាសាអង់គ្លេស (LastNameEn + FirstNameEn)
                ->orWhere(DB::raw('TRIM("CUST"."LastNameEn") || \' \' || TRIM("CUST"."FirstNameEn")'), 'ilike', "%{$search}%")
                
                // Search ឈ្មោះពេញជាភាសាខ្មែរ (LastNameKh + FirstNameKh)
                ->orWhere(DB::raw('TRIM("CUST"."LastNameKh") || \' \' || TRIM("CUST"."FirstNameKh")'), 'ilike', "%{$search}%")
                
                // Search ឈ្មោះដាច់ដោយឡែកក៏បាន
                ->orWhere('CUST.LastNameEn', 'ilike', "%{$search}%")
                ->orWhere('CUST.FirstNameEn', 'ilike', "%{$search}%")
                ->orWhere('CUST.LastNameKh', 'ilike', "%{$search}%")
                ->orWhere('CUST.FirstNameKh', 'ilike', "%{$search}%");
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

            $totalActive = DB::connection('pgsql')->table('MKT_LOAN_CONTRACT')->count();
            $totalClosed = DB::connection('pgsql')->table('MKT_CLOSED_LOAN')->count();
            $recordsTotal = $totalActive + $totalClosed;

            $recordsFiltered = $query->count(); 

            $start = intval($request->input('start', 0));
            $limit = intval($request->input('length', 20));

            // តម្រៀបតាម Customer ID ដើម្បីឱ្យនៅជុំគ្នា
            $data = $query->orderBy('combined.ContractCustomerID', 'asc')
                        ->orderBy('combined.ValueDate', 'desc')
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
            ->orderBy('combined.ValueDate', 'desc');
        $date = date('d-m-Y');
        $dataGenerator = function () use ($query) {
            $no = 1;
            foreach ($query->cursor() as $row) {
                yield [
                    '#'                 =>$no++,
                    'Branch'            =>$row->Branch,
                    'ID'                =>$row->ID,
                    'ContractCustomerID'=>$row->ContractCustomerID,
                    'CustomerName'      =>$row->EnName,
                    'Account'           =>$row->Account,
                    'Currency'          =>$row->Currency,
                    'DisburseDate'      =>$row->ValueDate,
                    'ClosedDate'        =>$row->ClosedDate,
                    'Disbursed'         =>$row->Disbursed,
                    'InterestRate'      =>$row->InterestRate,
                    'Term'              =>$row->Term,
                    'MaturityDate'      =>$row->MaturityDate,
                    'LoanProduct'       =>$row->LoanProduct,
                    'Sector'            =>$row->Sector,
                    'Category'          =>$row->Category,
                    'ContractOfficerID' =>$row->ContractOfficerID,
                    'LoanStatus'        =>$row->LoanStatus,
                ];
            }
        };
       return (new FastExcel($dataGenerator()))->download('LoanInactiveMonitoring_'.$date.'.xlsx');
    }
}
