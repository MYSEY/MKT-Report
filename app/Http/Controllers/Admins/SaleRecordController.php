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
        $gl_code = $request->get('gl_code');
        $lc_group = $request->get('lc');
        $reference = $request->get('reference');

        // ទាញយកអត្រាប្ដូរប្រាក់ពី pgsql
        $currencyRate = DB::connection('pgsql')->table('MKT_CURRENCY_HIST as ch')
            ->where('ch.Authorizeon', 'like', $dateInput.'%')
            ->where('ch.ID', 'like', 'USD%')
            ->orderBy('ch.Curr', 'desc')
            ->first();
        $rate = $currencyRate ? floatval($currencyRate->OtherRate1) : 4000;

        // 💡 ជំហានទី ១៖ ឆែកមើលក្នុងតារាង journal_backups នៅលើ Database Default (ដក connection('pgsql') ចេញ)
        $backupExistsQuery = DB::table('journal_backups')
            ->where('TransactionMonth', $dateInput);
            
        if (!empty($gl_code)) {
            $backupExistsQuery->where('GLAcc', 'like', $gl_code . '%');
        }
        if (!empty($reference)) {
            $backupExistsQuery->where('Reference' , 'like', $reference . '%');
        }
        
        if ($backupExistsQuery->clone()->exists()) {
            if (!empty($lc_group)) {
                $backupExistsQuery->select([
                    'Reference',
                    'TransactionMonth',
                    
                    // 💡 ធ្វើការបូកសរុប (SUM) ទៅលើ Column ទឹកប្រាក់នីមួយៗ
                    DB::raw('SUM(Amount_KHR) as Amount_KHR'),
                    DB::raw('SUM(Amount_USD) as Amount_USD'),
                    DB::raw('SUM(Total_Amount_KHR) as Total_Amount_KHR'),
                    DB::raw('SUM(Income_Tax) as Income_Tax'),
                    
                    // 💡 សម្រាប់ Column ជាអក្សរផ្សេងៗ ត្រូវប្រើ MAX() ដើម្បីកុំឱ្យវាទាស់ជាមួយ Group By
                    DB::raw('MAX(GLAcc) as GLAcc'),
                    DB::raw('MAX(JN_Day) as JN_Day'),
                    DB::raw('MAX(Currency) as Currency'),
                    DB::raw('MAX(KhName) as KhName'),
                    DB::raw('MAX(EnName) as EnName'),
                    DB::raw('MAX(Transaction) as Transaction'),
                    DB::raw('MAX(UserReference) as UserReference'),
                    DB::raw('MAX(Module) as Module')
                ])
                ->groupBy('Reference', 'TransactionMonth');
            }
            return [
                "query" => $backupExistsQuery, 
                "combinedSubQuery" => DB::table('journal_backups')->where('TransactionMonth', $dateInput), 
                "currencyRate" => $rate, 
                "is_from_backup" => true
            ];
        }

        // ១. Query សម្រាប់តារាង MKT_JOURNAL (រក្សាទុក connection('pgsql') ព្រោះជាតារាងផ្សាយផ្ទាល់)
        $queryJN = DB::connection('pgsql')->table('MKT_JOURNAL as jn')
            ->join('MKT_GL_MAPPING_DE as glmd', 'glmd.ConsolKey', '=', 'jn.GL_KEYS')
            ->leftJoin('MKT_TRANSACTION as t', 't.ID', '=', 'jn.Transaction')
            ->select([
                DB::raw("'{$dateInput}' as \"TransactionMonth\""),
                'jn.Reference', 
                DB::raw('SUM(CASE WHEN jn."DebitCredit" = \'Cr\' THEN jn."Amount" ELSE -jn."Amount" END) as "NetAmount"'),
                'glmd.ID as GLAcc',
                'jn.Currency',
                DB::raw('MAX(jn."Transaction" || \' - \' || t."Description") as "Transaction"'),
                DB::raw('MAX(jn."UserReference") as "UserReference"'),
                DB::raw('MAX(jn."LCYAmount") as "LCYAmount"'),
                DB::raw('MAX(jn."LCYPrevBalance") as "LCYPrevBalance"'),
                DB::raw('MAX(jn."Module") as "Module"')
            ])
            ->whereBetween('jn.TransactionDate', [$from_date, $to_date])
            ->where('jn.Reference', 'like', 'LC%');

        if (!empty($gl_code)) {
            $queryJN->where('glmd.ID', $gl_code);
        }
        if (!empty($reference)) {
            $queryJN->where('jn.Reference', $reference);
        }
        $queryJN->groupBy('jn.Reference', 'glmd.ID', 'jn.Currency');

        // ២. Query សម្រាប់តារាង MKT_AIR_JOURNAL (រក្សាទុក connection('pgsql'))
        $queryAIR = DB::connection('pgsql')->table('MKT_AIR_JOURNAL as air')
            ->join('MKT_GL_MAPPING_DE as glmd', 'glmd.ConsolKey', '=', 'air.GL_KEYS')
            ->leftJoin('MKT_TRANSACTION as t', 't.ID', '=', 'air.Transaction')
            ->select([
                DB::raw("'{$dateInput}' as \"TransactionMonth\""),
                'air.Reference', 
                DB::raw('SUM(CASE WHEN air."DebitCredit" = \'Cr\' THEN air."Amount" ELSE -air."Amount" END) as "NetAmount"'),
                'glmd.ID as GLAcc',
                'air.Currency',
                DB::raw('MAX(air."Transaction" || \' - \' || t."Description") as "Transaction"'),
                DB::raw('MAX(air."UserReference") as "UserReference"'),
                DB::raw('MAX(air."LCYAmount") as "LCYAmount"'),
                DB::raw('MAX(air."LCYPrevBalance") as "LCYPrevBalance"'),
                DB::raw('MAX(air."Module") as "Module"')
            ])
            ->whereBetween('air.TransactionDate', [$from_date, $to_date])
            ->where('air.Reference', 'like', 'LC%');

        if (!empty($gl_code)) {
            $queryAIR->where('glmd.ID', $gl_code);
        }
        if (!empty($reference)) {
            $queryAIR->where('air.Reference', $reference);
        }
        $queryAIR->groupBy('air.Reference', 'glmd.ID', 'air.Currency');
            
        // ៣. រួមបញ្ចូលតារាងទាំងពីរ
        $combinedSubQuery = $queryJN->unionAll($queryAIR);

        // ៤. Main Query ស្រោបពីលើ Subquery ដើម
        $finalResult = DB::connection('pgsql')
            ->table(DB::raw("({$combinedSubQuery->toSql()}) as combined"))
            ->select([
                'combined.Reference',
                DB::raw('SUM(combined."NetAmount") as "TotalNetAmount"'),
                DB::raw('SUM(CASE WHEN combined."Currency" = \'KHR\' THEN combined."NetAmount" ELSE 0 END) as "Amount_KHR"'),
                DB::raw('SUM(CASE WHEN combined."Currency" = \'USD\' THEN combined."NetAmount" ELSE 0 END) as "Amount_USD"'),
                DB::raw("SUM(CASE WHEN combined.\"Currency\" = 'USD' THEN combined.\"NetAmount\" * {$rate} ELSE combined.\"NetAmount\" END) as \"Total_Amount_KHR\""),
                DB::raw("SUM(CASE WHEN combined.\"Currency\" = 'USD' THEN combined.\"NetAmount\" * {$rate} ELSE combined.\"NetAmount\" END) * 0.01 as \"Income_Tax\""),
                
                'combined.TransactionMonth',
                'combined.GLAcc',
                'combined.Currency',
                DB::raw('MAX(combined."Transaction") as "Transaction"'),
                DB::raw('MAX(combined."UserReference") as "UserReference"'),
                DB::raw('SUM(combined."LCYAmount") as "TotalLCYAmount"'),          
                DB::raw('SUM(combined."LCYPrevBalance") as "TotalLCYPrevBalance"'),  
                DB::raw('MAX(combined."Module") as "Module"'),
            ])
            ->groupBy('combined.Reference', 'combined.TransactionMonth', 'combined.GLAcc', 'combined.Currency');

        $finalResult->mergeBindings($combinedSubQuery);

        // 💡 ជំហានទី ៣៖ ទាញទិន្នន័យពី pgsql មកផ្គុំឈ្មោះអតិថិជន រួចយកទៅរក្សាទុកក្នុង Database Default
        $liveData = $finalResult->get();

        if ($liveData->isNotEmpty()) {
            $references = $liveData->pluck('Reference')->unique()->toArray();

            // ទាញយក Customer ID ពី pgsql
            $activeLoans = DB::connection('pgsql')->table('MKT_LOAN_CONTRACT')->whereIn('ID', $references)->pluck('ContractCustomerID', 'ID');
            $closedLoans = DB::connection('pgsql')->table('MKT_CLOSED_LOAN')->whereIn('ID', $references)->pluck('ContractCustomerID', 'ID');
            $customerIds = $activeLoans->merge($closedLoans)->filter()->unique()->toArray();

            // ស្វែងរកឈ្មោះពីតារាង Customer របស់ pgsql
            $customers = DB::connection('pgsql')->table('MKT_CUSTOMER')->whereIn('ID', $customerIds)
                ->select('ID', 'LastNameKh', 'FirstNameKh', 'LastNameEn', 'FirstNameEn')->get()->keyBy('ID');
            // 💡 ១. ទៅទាញយក Record ដែលមានស្រាប់នៅក្នុងខែនេះមកសិន ដើម្បីយកមកធ្វើជាបញ្ជីស្កេនឆែក (Pluck Unique Keys)
            $existingRecords = DB::table('journal_backups')
                ->where('TransactionMonth', $dateInput) // $dateInput មកពីផ្នែកខាងលើនៃ getDatas()
                ->select('TransactionMonth', 'Reference', 'GLAcc')
                ->get()
                ->map(function ($item) {
                    // បង្កើតកូនសោររួមគ្នា (Unique Key Combo) សម្រាប់សម្គាល់ Record ម្នាក់ៗ
                    return "{$item->TransactionMonth}_{$item->Reference}_{$item->GLAcc}";
                })
                ->toArray();

            $backupData = [];
            $now = now();

            foreach ($liveData as $row) {
                // Unique Key សម្រាប់ Row នីមួយៗដែលទាញបានពី Live Data
                $currentKey = "{$row->TransactionMonth}_{$row->Reference}_{$row->GLAcc}";
                if (in_array($currentKey, $existingRecords)) {
                    continue; 
                }

                $custId = $activeLoans->get($row->Reference) ?? $closedLoans->get($row->Reference);
                $cust = $customers->get($custId);

                $khName = $cust ? trim($cust->LastNameKh) . ' ' . trim($cust->FirstNameKh) : 'N/A';
                $enName = $cust ? trim($cust->LastNameEn) . ' ' . trim($cust->FirstNameEn) : 'N/A';
                $lastDay = Carbon::parse($row->TransactionMonth)->endOfMonth()->format('d');
                $backupData[] = [
                    'TransactionMonth'    => $row->TransactionMonth,
                    'JN_Day'              => $lastDay,
                    'GLAcc'               => $row->GLAcc,
                    'Currency'            => $row->Currency,
                    'Reference'           => $row->Reference,
                    'KhName'              => $khName,
                    'EnName'              => $enName,
                    'Amount_KHR'          => $row->Amount_KHR,
                    'Amount_USD'          => $row->Amount_USD,
                    'Total_Amount_KHR'    => $row->Total_Amount_KHR,
                    'Income_Tax'          => $row->Income_Tax,
                    'CategoryID'          => $row->CategoryID ?? null,
                    'Module'              => $row->Module ?? null,
                    'Transaction'         => $row->Transaction ?? null,
                    'UserReference'       => $row->UserReference ?? '',
                    'TotalLCYAmount'      => $row->TotalLCYAmount ?? 0,      
                    'TotalLCYPrevBalance' => $row->TotalLCYPrevBalance ?? 0, 
                    'created_at'          => $now,
                    'updated_at'          => $now
                ];
            }

            // 💡 ៤. បញ្ជូនតែទិន្នន័យណាដែលថ្មីស្រឡាង (មិនទាន់មានក្នុង DB) ទៅ insert តែប៉ុណ្ណោះ
            if (!empty($backupData)) {
                foreach (array_chunk($backupData, 100) as $chunk) {
                    DB::table('journal_backups')->insert($chunk);
                }
            }
        }

        // 💡 ជំហានកែសម្រួល៖ បង្កើត Base Query មួយរួមគ្នាសម្រាប់យកទៅប្រើប្រាស់ទាំង Rows និង Footer
        $baseBackupFinalQuery = DB::table('journal_backups')->where('TransactionMonth', $dateInput);

        if (!empty($gl_code)) {
            $baseBackupFinalQuery->where('GLAcc', 'like', $gl_code . '%');
        }
        if (!empty($reference)) {
            $baseBackupFinalQuery->where('Reference', 'like', $reference . '%');
        }
        // 💡 បន្ថែមការ Filter តាម Search Text របស់ DataTables ដើម្បីឱ្យ Footer រត់ត្រូវលេខជានិច្ច
        // if (!empty($search_text)) {
        //     $baseBackupFinalQuery->where(function ($q) use ($search_text) {
        //         $q->where('Reference', 'like', '%' . $search_text . '%')
        //           ->orWhere('KhName', 'like', '%' . $search_text . '%')
        //           ->orWhere('EnName', 'like', '%' . $search_text . '%');
        //     });
        // }

        // 💡 ប្រើប្រាស់ ->clone() បំបែកចេញជា ២ Queries ដាច់ដោយឡែកពីគ្នា
        $journalBackups = $baseBackupFinalQuery->clone();
        $combinedTotal  = $baseBackupFinalQuery->clone(); // សម្រាប់បូកសរុប Footer (មាន Filter ពេញលេញ)

        // 💡 ឆែកលក្ខខណ្ឌ Group By ទៅលើ Rows (លក្ខខណ្ឌ Filters ទាំងអស់នៅតែរក្សាទុកដដែល ១០០%)
        if (!empty($lc_group)) {
            $journalBackups->select([
                'Reference',
                'TransactionMonth',
                
                // 💡 ធ្វើការបូកសរុប (SUM) ទៅលើ Column ទឹកប្រាក់នីមួយៗ
                DB::raw('SUM(Amount_KHR) as Amount_KHR'),
                DB::raw('SUM(Amount_USD) as Amount_USD'),
                DB::raw('SUM(Total_Amount_KHR) as Total_Amount_KHR'),
                DB::raw('SUM(Income_Tax) as Income_Tax'),
                
                // 💡 សម្រាប់ Column ជាអក្សរផ្សេងៗ ត្រូវប្រើ MAX() ដើម្បីកុំឱ្យវាទាស់ជាមួយ Group By
                DB::raw('MAX(GLAcc) as GLAcc'),
                DB::raw('MAX(JN_Day) as JN_Day'),
                DB::raw('MAX(Currency) as Currency'),
                DB::raw('MAX(KhName) as KhName'),
                DB::raw('MAX(EnName) as EnName'),
                DB::raw('MAX(Transaction) as Transaction'),
                DB::raw('MAX(UserReference) as UserReference'),
                DB::raw('MAX(Module) as Module')
            ])
            ->groupBy('Reference', 'TransactionMonth');
        }

        return [
            "query" => $journalBackups,
            "combinedSubQuery" => $combinedTotal,
            "currencyRate" => $rate,
            "is_from_backup" => true
        ];
    }
    public function index(Request $request) {
        if (!$this->denyPermission('Sale Record View')) {
            return view('page.access_page');
        }

        if (request()->ajax()) {
            $get = self::getDatas($request);
            $baseQuery = $get['query'];
            $lc_group = $request->get('lc'); // 💡 ចាប់យក lc មកឆែកលក្ខគណ្ឌ Count

            // 💡 កែសម្រួល៖ គណនា Count ឱ្យត្រឹមត្រូវ ទោះជាមានការប្រើប្រាស់ Group By ក៏ដោយ
            if (!empty($lc_group)) {
                $recordsTotal = $baseQuery->clone()->get()->count();
            } else {
                $recordsTotal = $baseQuery->clone()->count();
            }
            $recordsFiltered = $recordsTotal;

            $start = intval(request()->input('start', 0));
            $limit = intval(request()->input('length', 20));
            
            // 💡 កែសម្រួល៖ ឆែកលក្ខខណ្ឌ ប្រសិនបើមិនមែនជា "All" (-1) ទើបដាក់ Offset និង Limit
            if ($limit != -1) {
                $baseQuery->offset($start)->limit($limit);
            }

            // ទាញយកទិន្នន័យដែលមានទាំងឈ្មោះ KhName និង EnName រួចជាស្រេចនៅក្នុង Backup Table
            $data = $baseQuery->orderBy('GLAcc', 'desc')->get();

            return response()->json([
                'draw'            => intval(request()->input('draw')),
                'recordsTotal'    => $recordsTotal,
                'recordsFiltered' => $recordsFiltered, 
                'data'            => $data,
                'currency'        => $get["currencyRate"]
            ]);
        }

        return view('mkt-reports.sale-records.sale-record');
    }

    public function exportExcel(Request $request) {
        ini_set('memory_limit', '-1'); 
        set_time_limit(0);

        $get = self::getDatas($request);
        $queryBuilder = $get["query"];
        $currency = $get["currencyRate"];
        $date = $request->get('date') ?? date('Y-m');
        
        $start = $request->get('start', 0);
        $length = $request->get('length', 20);
        $lc_group = $request->get('lc');

        // 💡 កែសម្រួល៖ ឆែកលក្ខខណ្ឌ Pagination សម្រាប់ Export ការពារ Error "Division by zero" ពេលរើស "All"
        if ($length != -1) {
            $queryBuilder->offset($start)->limit($length);
            $currentPage = ($start / $length) + 1;
            $pageStr = '_Page_' . $currentPage;
        } else {
            $pageStr = '_All_Records';
        }

        $paginatedData = $queryBuilder->get();

        $templateName = !empty($lc_group) ? 'Group_By_LC' : 'Group_By_GL';
        $fileName = 'Sale_Record_' . $templateName . $pageStr . '_' . $date . '.xlsx';

        return Excel::download(new ExportSaleRecord($paginatedData, $date, $currency, $lc_group), $fileName);
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
