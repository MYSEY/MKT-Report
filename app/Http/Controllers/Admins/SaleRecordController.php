<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasRolePermission;
use App\Exports\ExportSaleRecord;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;

class SaleRecordController extends Controller
{
    use HasRolePermission;
    public function __construct()
    {
        $this->applyRolePermissions('Sale Record');
    }
    public static function getDatas($request)
    {
        $dateInput = $request->get('date') ?? date('Y-m');
        $time = strtotime($dateInput);
        $from_date = date('Y-m-01', $time);
        $to_date = date('Y-m-t', $time);

        // ទាញយកអត្រាប្តូរប្រាក់ (Rate) បើរកមិនឃើញឱ្យ default = 4000
        $currencyRate = DB::connection('pgsql')->table('MKT_CURRENCY_HIST as ch')
            ->where('ch.Authorizeon', 'like', $dateInput.'%')
            ->where('ch.ID', 'like', 'USD%')
            ->orderBy('ch.Curr', 'desc')
            ->first();

        $rate = $currencyRate ? $currencyRate->OtherRate1 : 4000; // ប្រើតម្លៃពី DB បើគ្មានប្រើ 4000
        $query = DB::connection('pgsql')->table('MKT_AIR_JOURNAL')
            ->select([
                'MKT_AIR_JOURNAL.TransactionDate',
                'MKT_AIR_JOURNAL.Branch',
                'MKT_AIR_JOURNAL.Reference',
                DB::raw('CONCAT(TRIM("CUST"."LastNameKh"), \' \', TRIM("CUST"."FirstNameKh")) as "KhName"'),
                DB::raw('CONCAT(TRIM("CUST"."LastNameEn"), \' \', TRIM("CUST"."FirstNameEn")) as "EnName"'),
                'MKT_AIR_JOURNAL.Currency',
                'MKT_AIR_JOURNAL.Amount',
                // ✅ គណនា Total KHR ពី Backend
                DB::raw("CASE 
                    WHEN \"MKT_AIR_JOURNAL\".\"Currency\" = 'USD' THEN \"MKT_AIR_JOURNAL\".\"Amount\" * $rate 
                    ELSE \"MKT_AIR_JOURNAL\".\"Amount\" 
                END as \"TotalKHR\""),
                // ✅ គណនា Tax 1% ពី Backend
                DB::raw("CASE 
                    WHEN \"MKT_AIR_JOURNAL\".\"Currency\" = 'USD' THEN (\"MKT_AIR_JOURNAL\".\"Amount\" * $rate) * 0.01 
                    ELSE \"MKT_AIR_JOURNAL\".\"Amount\" * 0.01 
                END as \"Tax1Percent\""),
                'MKT_AIR_JOURNAL.GL_KEYS',
                'MKT_AIR_JOURNAL.DebitCredit',
                'MKT_AIR_JOURNAL.PrevBalance',
                'MKT_AIR_JOURNAL.Transaction',
                'MKT_AIR_JOURNAL.LCYAmount',
                'MKT_AIR_JOURNAL.LCYPrevBalance',
                'MKT_AIR_JOURNAL.InterestRate'
            ])
            // ... leftJoin និង where ទុកដដែល ...
            ->leftJoin('MKT_LOAN_CONTRACT as LC', 'LC.ID', '=', 'MKT_AIR_JOURNAL.Reference')
            ->leftJoin('MKT_CLOSED_LOAN as CL', 'CL.ID', '=', 'MKT_AIR_JOURNAL.Reference')
            ->leftJoin('MKT_CUSTOMER as CUST', 'CUST.ID', '=', DB::raw('COALESCE("LC"."ContractCustomerID", "CL"."ContractCustomerID")'))
            ->where('MKT_AIR_JOURNAL.TransactionDate', '>=', $from_date)
            ->where('MKT_AIR_JOURNAL.TransactionDate', '<=', $to_date)
            ->where('MKT_AIR_JOURNAL.GL_KEYS', 'like', '5%');

        // ផ្នែក Search
        $search = request()->input('search.value');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                // ១. បង្កើត Raw Expression សម្រាប់បូកបញ្ចូលឈ្មោះ (Khmer & English)
                $fullNameKh = 'TRIM("CUST"."LastNameKh") || \' \' || TRIM("CUST"."FirstNameKh")';
                $fullNameEn = 'TRIM("CUST"."LastNameEn") || \' \' || TRIM("CUST"."FirstNameEn")';

                $q->where(DB::raw($fullNameKh), 'ilike', "%{$search}%") // Search ឈ្មោះខ្មែរពេញ
                ->orWhere(DB::raw($fullNameEn), 'ilike', "%{$search}%") // Search ឈ្មោះអង់គ្លេសពេញ
                ->orWhere('MKT_AIR_JOURNAL.Reference', 'ilike', "%{$search}%")
                ->orWhere('CUST.LastNameEn', 'ilike', "%{$search}%") // បន្ថែមសម្រាប់ករណី search តែត្រកូល
                ->orWhere('CUST.FirstNameEn', 'ilike', "%{$search}%"); // បន្ថែមសម្រាប់ករណី search តែឈ្មោះ
            });
        }
        
        return [
            "query"=>$query,
            "currencyRate"=>$rate
        ];
        
    }
    public function index(Request $request) {
        if (!$this->denyPermission('Sale Record View')) {
            return view('page.access_page');
        }
        if (request()->ajax()) {
            $get = self::getDatas($request);
            $recordsFiltered = $get["query"]->count();
            $start = intval(request()->input('start', 0));
            $limit = intval(request()->input('length', 20));
            $data = $get["query"]->orderBy('MKT_AIR_JOURNAL.ID', 'ASC')->offset($start)->limit($limit)->get();
            
            return response()->json([
                'draw' => intval(request()->input('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => $get["query"]->count(), 
                'data' => $data,
                'currency'=>$get["currencyRate"]
            ]);
        }
        return view('mkt-reports.sale-record');
    }
    public function exportExcel(Request $request) {
        //*** (យ៉ាងហោច RAM 8GB ឡើងទៅ) **/
        ini_set('memory_limit', '-1'); 
        set_time_limit(0);

        $get = self::getDatas($request);
        $data = $get["query"]->get();
        $currency = $get["currencyRate"];
        $date = $request->get('date') ?? date('Y-m');
        return Excel::download(new ExportSaleRecord($data, $date, $currency), 'Sale_Record_'.$date.'.xlsx');
    }

    public function exportExcelAll(Request $request) 
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);

        $get = self::getDatas($request);
        $query = $get["query"];
        $date = $request->get('date') ?? date('Y-m');
        $dataGenerator = function () use ($query) {
            $no = 1;
            foreach ($query->cursor() as $row) {
                yield [
                    'ល.រ' => $no++,
                    'កាលបរិច្ឆេទ' => $row->TransactionDate,
                    'លេខវិក្កយបត្រប្រតិបត្តិការគយ' => '11111',
                    'ប្រភេទ' => 2,
                    'លេខសម្គាល់' => $row->Reference,
                    'ឈ្មោះ (ខ្មែរ)' => $row->KhName,
                    'ឈ្មោះ (ឡាតាំង)' => $row->EnName,
                    'ប្រភេទផ្គត់ផ្គង់' => 3,
                    'តម្លៃ ជាប្រាក់រៀល' => ($row->Currency == 'KHR' ? $row->Amount : 0),
                    'តម្លៃ ជាប្រាក់ដុល្លារ' => ($row->Currency == 'USD' ? $row->Amount : 0),
                    'តម្លៃសរុប ជាប្រាក់រៀល' => $row->TotalKHR,
                    'អត្រាប្រាក់ពន្ធរំដោះលើប្រាក់ចំណូល ១%' => round($row->Tax1Percent),
                    'បរិយាយ' => 'Loan Repayment',
                    'វិធីសាស្ត្រគណនេយ្យ' => 0,
                ];
            }
        };

       return (new FastExcel($dataGenerator()))->download('សៀវភៅទិញ_'.$date.'.xlsx');
    }
}
