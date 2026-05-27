<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HasRolePermission;
use App\Exports\ExportSaleRecord;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use App\Models\InterestIncome;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SaleRecordController extends Controller
{
    use HasRolePermission;
    public static function getBranchs(){
        $branchs = DB::connection('pgsql')->table('MKT_BRANCH')->get();
        return $branchs;
    }
    public static function getDatas($request)
    {
        $dateInput = $request->get('date') ?? date('Y-m');
        $time = strtotime($dateInput);
        $from_date = date('Y-m-01', $time);
        $to_date = date('Y-m-t', $time);

        $currencyRate = DB::connection('pgsql')->table('MKT_CURRENCY_HIST as ch')
            ->where('ch.Authorizeon', 'like', $dateInput.'%')
            ->where('ch.ID', 'like', 'USD%')
            ->orderBy('ch.Curr', 'desc')
            ->first();
        $rate = $currencyRate ? $currencyRate->OtherRate1 : 4000;

        // ១. Sub-query: បូកសរុបបំបែកតាម Currency និង Reference
        $subQuery = DB::connection('pgsql')->table('MKT_AIR_JOURNAL')
        ->select([
            'Reference',
            'Currency',
            DB::raw('MAX("TransactionDate") as "TransactionDate"'),
            // គណនា Net Amount (Cr - Dr)
            DB::raw('SUM(CASE WHEN "DebitCredit" = \'Cr\' THEN "Amount" ELSE -"Amount" END) as "NetAmount"')
        ])
        ->whereBetween('TransactionDate', [$from_date, $to_date])
        ->where('GL_KEYS', 'like', '5%')
        ->groupBy('Reference', 'Currency');

        // ២. Main Query: រៀបចំ Column តាមរូបភាព Table របស់អ្នក
        $query = DB::connection('pgsql')->table(DB::raw("({$subQuery->toSql()}) as j"))
        ->mergeBindings($subQuery) 
        ->select([
            'j.Reference',
            'j.TransactionDate',
            'j.Currency',
            'j.NetAmount',
            
            // ១. Amount KHR: បើជា KHR យក NetAmount បើមិនមែនយក 0
            DB::raw('CASE WHEN j."Currency" = \'KHR\' THEN j."NetAmount" ELSE 0 END as "Amount_KHR"'),

            // ២. Amount USD: បើជា USD យក NetAmount បើមិនមែនយក 0
            DB::raw('CASE WHEN j."Currency" = \'USD\' THEN j."NetAmount" ELSE 0 END as "Amount_USD"'),

            // ៣. Total Amount KHR: (Amount_USD * rate) + Amount_KHR
            DB::raw("CASE 
                WHEN j.\"Currency\" = 'USD' THEN j.\"NetAmount\" * $rate 
                ELSE j.\"NetAmount\" 
            END as \"Total_Amount_KHR\""),

            // ៤. Income Tax Rate 1%: Total_Amount_KHR * 0.01
            DB::raw("(CASE 
                WHEN j.\"Currency\" = 'USD' THEN j.\"NetAmount\" * $rate 
                ELSE j.\"NetAmount\" 
            END) * 0.01 as \"Income_Tax\""),

            DB::raw('TRIM("CUST"."LastNameKh") || \' \' || TRIM("CUST"."FirstNameKh") as "KhName"'),
            DB::raw('TRIM("CUST"."LastNameEn") || \' \' || TRIM("CUST"."FirstNameEn") as "EnName"')
        ])
        ->leftJoin('MKT_LOAN_CONTRACT as LC', 'LC.ID', '=', 'j.Reference')
        ->leftJoin('MKT_CLOSED_LOAN as CL', 'CL.ID', '=', 'j.Reference')
        ->leftJoin('MKT_CUSTOMER as CUST', 'CUST.ID', '=', DB::raw('COALESCE("LC"."ContractCustomerID", "CL"."ContractCustomerID")'));
        // Search logic
        $search = request()->input('search.value');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('j.Reference', 'ilike', "%{$search}%")
                ->orWhere('CUST.LastNameEn', 'ilike', "%{$search}%")
                ->orWhere('CUST.FirstNameEn', 'ilike', "%{$search}%");
            });
        }

        return ["query" => $query, "currencyRate" => $rate];
    }
    public static function getDataDetails($request){
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
    public static function getDataTB($request) {
        $AccessBranch = Auth::user()->AccessBranch;
        $branches = preg_split('/\s+/', trim($AccessBranch));
        $dateInput = $request->get('date') ?? date('Y-m');
        $previousDate = Carbon::parse($dateInput)->subMonth()->format('Y-m');
        $previousYear = Carbon::parse($dateInput)->format('Y');
        $previousMonth = Carbon::parse($dateInput)->subMonth()->format('m');

        if($request->period !="current_month" && !$request->date){
            if($request->period == "current_previous_month"){
                $dateInput      = Carbon::parse()->format('Y-m');
                $previousDate   = Carbon::parse()->subMonth()->format('Y-m');
                $previousYear   = Carbon::parse()->subMonth()->format('Y');
                $previousMonth  = Carbon::parse()->subMonth()->format('m');
            };
            if($request->period == "current_previous_year"){
                $dateInput      = Carbon::parse()->subYear(1)->format('Y');
                $previousDate   = Carbon::parse()->subYear(2)->format('Y');
                $previousYear   = Carbon::parse()->subYear(1)->format('Y');
                $previousMonth  = null;
            };
            if($request->period == "current_year"){
                $dateInput      = Carbon::parse()->format('Y');
                $previousDate   = Carbon::parse()->subYear()->format('Y');
                $previousYear   = Carbon::parse()->format('Y');
                $previousMonth  = null;
            };
        }
        // ទាញយក Exchange Rate នៃខែមុននោះពី Database
        $previousCurrencyRate = DB::connection('pgsql')->table('MKT_CURRENCY_HIST as ch')
            ->where('ch.Authorizeon', 'like', $previousDate . '%') // ប្រើខែដែលបានដករួច
            ->where('ch.ID', 'like', 'USD%')
            ->orderBy('ch.Curr', 'desc')
            ->first();
        $previousRate = $previousCurrencyRate ? $previousCurrencyRate->OtherRate1 : 4000;
        $previousRate = $previousRate > 0 ? $previousRate : 4000;

        $isCurrent = ($request->period == "current_month");
        $name_table_currency = $isCurrent ? "MKT_CURRENCY" : "MKT_CURRENCY_HIST";
        $currencyRate = DB::connection('pgsql')->table($name_table_currency.' as ch')
            ->where('ch.Authorizeon', 'like', $dateInput.'%')
            ->where('ch.ID', 'like', 'USD%')
            ->orderBy('ch.Curr', 'desc')
            ->first();
        $currentExcahnge = $currencyRate ? $currencyRate->OtherRate1 : 4000;
        $currentExcahnge = $currentExcahnge > 0 ? $currentExcahnge : 4000;
        $Symbol = '/'; 
        $Currency = "KHR";
        if ($request->filled('currency') && $request->currency == "KHR") {
            $Symbol = '*';
            $Currency = "USD";
        }
        $name_table = $isCurrent ? "MKT_GL_BALANCE" : "MKT_GL_BALANCE_BACKUP";
        $query = DB::connection('pgsql')->table("MKT_GL_MAPPING as mp")
            ->leftJoin($name_table . ' as bl', 'mp.ID', '=', 'bl.ID')
            ->select(
                'mp.ID',
                'mp.Description',
                'mp.BalanceType',
                DB::raw("COALESCE(SUM(CASE 
                    WHEN bl.\"Currency\" = '" . $Currency . "' THEN ROUND(CAST(bl.\"PrevMonthBal\" AS NUMERIC) " . $Symbol . " " . $previousRate . ", 2)
                    ELSE ROUND(CAST(bl.\"PrevMonthBal\" AS NUMERIC), 2)
                END), 0) as \"BeginningBalance\""),

                  // --- EndingBalance ---
                DB::raw("COALESCE(SUM(CASE 
                    WHEN bl.\"Currency\" = '" . $Currency . "' THEN ROUND(CAST(bl.\"CurrentMonthBal\" AS NUMERIC) " . $Symbol . " " . $currentExcahnge . ", 2)
                    ELSE ROUND(CAST(bl.\"CurrentMonthBal\" AS NUMERIC), 2)
                END), 0) as \"movementBalance\""),

                DB::raw('SUM(bl."LCYBalance") as "LCYBalance"'),
                DB::raw('SUM(bl."LCYPrevMonthBal") as "LCYPrevMonthBal"'),
                DB::raw('SUM(bl."CurrentMonthBal") as "CurrentMonthBal"'),
                DB::raw('SUM(bl."LCYCurrentMonthBal") as "LCYCurrentMonthBal"'),
                DB::raw('SUM(bl."PrevYearBal") as "PrevYearBal"'),
                DB::raw('SUM(bl."LCYPrevYearBal") as "LCYPrevYearBal"'),
                DB::raw('SUM(bl."YTDBal") as "YTDBal"'),
                DB::raw('SUM(bl."LCYYTDBal") as "LCYYTDBal"'),
                // --- EndingBalance ---
                DB::raw("COALESCE(SUM(CASE 
                    WHEN bl.\"Currency\" = '" . $Currency . "' THEN ROUND(CAST(bl.\"Balance\" AS NUMERIC) " . $Symbol . " " . $currentExcahnge . ", 2)
                    ELSE ROUND(CAST(bl.\"Balance\" AS NUMERIC), 2)
                END), 0) as \"EndingBalance\""),
                DB::raw('SUM(bl."LCYBalance") as "LCYBalance"')
            )
            ->whereRaw('LENGTH(bl."ID"::text) = 8') // Cast ទៅជា text ករណី ID ជាប្រភេទលេខ
            ->where('bl.Branch', '<>', '');
        
        // --- Filters ---
        if($branches[0] == "HQ"){
            if ($request->branch_id && $request->branch_id != 'ALL') {
                $query->where('bl.Branch', $request->branch_id);
            }
        }else{
            $query->where('bl.Branch', $branches[0]);
        }
        if(!$isCurrent){
            if ($previousYear) {
                $query->where('bl.GLYear', $previousYear);
            }
            if ($previousMonth) {
                $query->where('bl.GLMonth', $previousMonth);
            }
        }
        
        $search = request()->input('search.value');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where(DB::raw('bl."ID"::text'), 'like', "%{$search}%")
                ->orWhere(DB::raw('mp."ID"::text'), 'like', "%{$search}%")
                ->orWhere(DB::raw('mp."Description"::text'), 'like', "%{$search}%");
            });
        }
        $query->groupBy('mp.ID');
        return $query;
    }

    public function indexTrialBalance(Request $request) {
        if (!$this->denyPermission('Trial Balance View')) {
            return view('page.access_page');
        }
        
        if (request()->ajax()) {
            // ១. ទាញ Query ពី getDataTB (ដែលមិនទាន់មាន GroupBy)
            $baseQuery = self::getDataTB($request);
            
            $groupedQuery = clone $baseQuery;
            $groupedQuery->groupBy('mp.ID', 'mp.Description', 'mp.BalanceType');

            // ៣. រាប់ចំនួន RecordsFiltered (ប្រើ Sub-query)
            $recordsTotal = DB::connection('pgsql')
                ->table(DB::raw("({$groupedQuery->toSql()}) as sub"))
                ->mergeBindings($groupedQuery)
                ->count();

            // ៤. គណនា Totals សម្រាប់ Footer
            $totals = DB::connection('pgsql')
                ->table(DB::raw("({$groupedQuery->toSql()}) as sub"))
                ->mergeBindings($groupedQuery)
                ->select(
                    DB::raw("SUM(\"BeginningBalance\") as sum_beg"),
                    DB::raw("SUM(\"movementBalance\") as sum_mov"),
                    DB::raw("SUM(\"EndingBalance\") as sum_end"),
                )
                ->first();

            // ៥. ទាញទិន្នន័យសម្រាប់បង្ហាញតាមទំព័រ
            $start = intval($request->input('start', 0));
            $limit = intval($request->input('length', 20));
            
            if ($limit == -1) {
                $data = $groupedQuery->get();
            } else {
                $data = $groupedQuery->offset($start)->limit($limit)->get();
            }

            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsTotal, 
                'data' => $data,
                'totals' => [
                    'sum_beg' => number_format($totals->sum_beg ?? 0, 2),
                    'sum_mov' => number_format($totals->sum_mov ?? 0, 2),
                    'sum_end' => number_format($totals->sum_end ?? 0, 2),
                ]
            ]);
        }
        
        $branchs = self::getBranchs();
        return view('mkt-reports.trial-balances.index', compact('branchs'));
    }
    public function index(Request $request) {
        if (!$this->denyPermission('Sale Record View')) {
            return view('page.access_page');
        }
        if (request()->ajax()) {
            $get = self::getDatas($request);
            
            // ✅ វិធីរាប់ចំនួន Record សរុបឱ្យត្រឹមត្រូវសម្រាប់ Group By (PostgreSQL)
            $totalQuery = DB::connection('pgsql')->table(DB::raw("({$get['query']->toSql()}) as sub"))
                            ->mergeBindings($get['query']); 
            $recordsTotal = $totalQuery->count();
            $recordsFiltered = $recordsTotal; 

            $start = intval(request()->input('start', 0));
            $limit = intval(request()->input('length', 20));

            // ✅ ទាញយកទិន្នន័យតាមកម្រិត (Limit/Offset)
            $data = $get["query"]
                    ->orderBy('j.Reference', 'desc')
                    ->offset($start)
                    ->limit($limit)
                    ->get();
            
            return response()->json([
                'draw' => intval(request()->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered, 
                'data' => $data,
                'currency' => $get["currencyRate"]
            ]);
        }
        return view('mkt-reports.sale-records.sale-record');
    }
    public function exportExcel(Request $request) {
        //*** (យ៉ាងហោច RAM 8GB ឡើងទៅ) **/
        ini_set('memory_limit', '-1'); 
        set_time_limit(0);

        $get = self::getDatas($request);
        $data = $get["query"]->get();
        $currency = $get["currencyRate"];
        $date = $request->get('date') ?? date('Y-m');
        return Excel::download(new ExportSaleRecord($data, $date, $currency,null), 'Sale_Record_'.$date.'.xlsx');
    }
    public function exportExcelAll(Request $request) 
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(0);

        $get = self::getDataDetails($request);
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
    public static function getDataExSl($request, $type)
    {
        $dateInput = $request->get('date') ?? date('Y-m');
        $time = strtotime($dateInput);
        $year = date('Y', $time);
        $month = date('m', $time);

        // ១. ទាញយកបញ្ជីលេខគណនី
        $accountNumbers = InterestIncome::where("type", $type)
                            ->pluck('account_number')
                            ->toArray();

        // ២. ទាញយកអត្រាប្តូរប្រាក់
        $currencyRate = DB::connection('pgsql')->table('MKT_CURRENCY_HIST as ch')
            ->where('ch.Authorizeon', 'like', $dateInput.'%')
            ->where('ch.ID', 'like', 'USD%')
            ->orderBy('ch.Curr', 'desc')
            ->first();

        $rate = $currencyRate ? $currencyRate->OtherRate1 : 4000;

        // ៣. បង្កើត Raw SQL សម្រាប់បូកសរុប (ជៀសវាងសរសេរដដែលៗ)
        $sumKhr = "SUM(CASE WHEN \"Bl\".\"Currency\" = 'KHR' THEN ROUND(COALESCE(\"Bl\".\"CurrentMonthBal\", 0)::numeric, 2) ELSE 0 END)";
        $sumUsd = "SUM(CASE WHEN \"Bl\".\"Currency\" = 'USD' THEN ROUND(COALESCE(\"Bl\".\"CurrentMonthBal\", 0)::numeric, 2) ELSE 0 END)";
        $sumTotalKhr = "SUM(
            CASE 
                WHEN \"Bl\".\"Currency\" = 'USD' THEN ROUND(COALESCE(\"Bl\".\"CurrentMonthBal\", 0)::numeric, 2) * $rate 
                WHEN \"Bl\".\"Currency\" = 'KHR' THEN ROUND(COALESCE(\"Bl\".\"CurrentMonthBal\", 0)::numeric, 2) 
                ELSE 0 
            END
        )";

        // ៤. កំណត់ Select និង GroupBy តាមលក្ខខណ្ឌ Branch
        if ($request->branch_id) {
            $selectColumns = [
                DB::raw('MAX("Bl"."ID") as "ID"'), // ប្រើ MAX ដើម្បីកុំឱ្យ Error GroupBy
                'Bl.Branch',
                'Bl.GLYear', 
                'Bl.GLMonth',
                DB::raw('MAX("Bl"."GLDay") as "GLDay"'),
                DB::raw('MAX("Mp"."ID") as "MpID"'),
                DB::raw("'All Accounts In Branch' as \"Description\""), // បង្ហាញអក្សរជួសឱ្យ Description លម្អិត
                'Bl.Currency',
            ];
            $groupByColumns = ['Bl.Branch', 'Bl.GLYear', 'Bl.GLMonth', 'Bl.Currency'];
        } else {
            $selectColumns = [
                'Bl.ID',
                'Bl.GLYear', 
                'Bl.GLMonth',
                DB::raw('MAX("Bl"."GLDay") as "GLDay"'), 
                'Mp.ID as MpID',
                'Mp.Description',
                'Bl.Currency',
            ];
            $groupByColumns = ['Bl.ID', 'Bl.GLYear', 'Bl.GLMonth', 'Mp.ID', 'Mp.Description', 'Bl.Currency'];
        }

        // ៥. បន្ថែម Column បូកសរុបចូលក្នុង Select
        $selectColumns[] = DB::raw("$sumKhr as \"AmountKHR\"");
        $selectColumns[] = DB::raw("$sumUsd as \"AmountUSD\"");
        $selectColumns[] = DB::raw("$sumTotalKhr as \"TotalAmountKHR\"");
        $selectColumns[] = DB::raw("ROUND(($sumTotalKhr * 0.01)::numeric, 2) as \"Exemption1Percent\"");
        $selectColumns[] = DB::raw('SUM(ROUND(COALESCE("Bl"."PrevMonthBal", 0)::numeric, 2)) as "PrevMonthBal"');
        $selectColumns[] = DB::raw('SUM(ROUND(COALESCE("Bl"."CurrentMonthBal", 0)::numeric, 2)) as "CurrentMonthBal"');

        // ៦. ចាប់ផ្តើម Query
        $query = DB::connection('pgsql')->table('MKT_GL_BALANCE_BACKUP as Bl')
            ->leftJoin('MKT_GL_MAPPING as Mp', 'Bl.ID', '=', 'Mp.ID')
            ->when($request->branch_id, fn($q, $branch_id) => $q->where('Bl.Branch', $branch_id))
            ->select($selectColumns)
            ->whereIn("Bl.ID", $accountNumbers)
            ->where('Bl.Audit', '=', '1')
            ->where('Bl.GLYear', '=', $year)
            ->where('Bl.GLMonth', '=', $month)
            ->groupBy($groupByColumns)
            ->orderBy($request->branch_id ? 'Bl.Branch' : 'Bl.ID', 'asc');

        // ៧. ផ្នែក Search
        $search = request()->input('search.value');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('Bl.ID', 'ilike', "%{$search}%")
                ->orWhere('Mp.Description', 'ilike', "%{$search}%");
            });
        }

        return [
            "query" => $query,
            "currencyRate" => $rate
        ];
    }
    public function indexExemption(Request $request) {
        if (!$this->denyPermission('Sale Record Exemption View')) {
            return view('page.access_page');
        }
        if (request()->ajax()) {
            $get = self::getDataExSl($request,"2");
            
            $totalQuery = DB::connection('pgsql')->table(DB::raw("({$get['query']->toSql()}) as sub"))
                            ->mergeBindings($get['query']);
            $recordsTotal = $totalQuery->count();

            $start = intval($request->input('start', 0));
            $limit = intval($request->input('length', 20));

            $data = $get["query"]
                    ->reorder() // លុប order ចាស់ដែលមកពី getDataExemptions (បើមាន)
                    // ->orderBy('Bl.ID', 'asc') 
                    ->offset($start)
                    ->limit($limit)
                    ->get();
            
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsTotal, 
                'data' => $data,
                'currency' => $get["currencyRate"]
            ]);
        }
        $branchs = self::getBranchs();
        return view('mkt-reports.sale-records.sale-record-exemption',compact('branchs'));
    }
    public function indexConsole(Request $request) {
        if (!$this->denyPermission('Sale Record Console View')) {
            return view('page.access_page');
        }
        if (request()->ajax()) {
            $get = self::getDataExSl($request,"1");
            
            $totalQuery = DB::connection('pgsql')->table(DB::raw("({$get['query']->toSql()}) as sub"))
                            ->mergeBindings($get['query']);
            $recordsTotal = $totalQuery->count();

            $start = intval($request->input('start', 0));
            $limit = intval($request->input('length', 20));

            // ✅ ដំណោះស្រាយ៖ ប្ដូរពី MKT_GL_BALANCE_BACKUP.ID មកជា Bl.ID
            $data = $get["query"]
                    ->reorder() // លុប order ចាស់ដែលមកពី getDataExemptions (បើមាន)
                    // ->orderBy('Bl.ID', 'asc') 
                    ->offset($start)
                    ->limit($limit)
                    ->get();
            
            return response()->json([
                'draw' => intval($request->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsTotal, 
                'data' => $data,
                'currency' => $get["currencyRate"]
            ]);
        }
        $branchs = self::getBranchs();
        return view('mkt-reports.sale-records.sale-record-console', compact('branchs'));
    }
    public function exportExCsExcel(Request $request){
        //*** (យ៉ាងហោច RAM 8GB ឡើងទៅ) **/
        ini_set('memory_limit', '-1'); 
        set_time_limit(0);

        $get = self::getDataExSl($request, $request->type);
        $data = $get["query"]->get();
        $currency = $get["currencyRate"];
        $date = $request->get('date') ?? date('Y-m');
        $name_file = $request->type == "2" ? "Sale_record_exemption_" : "Sale_record_console_";
        return Excel::download(new ExportSaleRecord($data, $date, $currency,$request->type), $name_file.$date.'.xlsx');
    }
}
