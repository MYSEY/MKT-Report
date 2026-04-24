<?php

namespace App\Http\Controllers\Admins;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Traits\HasRolePermission;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExportLoanDetailListing;

class LoandDetailListingController extends Controller
{
    use HasRolePermission;

    public function __construct()
    {
        $this->applyRolePermissions('Loan Detail');
    }

    public function loanDetailListing(Request $request){
        if (!$this->denyPermission('Loan Detail View')) {
            return view('page.access_page');
        }
        if (request()->ajax()) {
            $query = self::getDatas($request);
            $recordsTotal = DB::connection('pgsql')
            ->table('MKT_LOAN_CONTRACT as LC')
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->where('LC.Branch', $request->branch_id);
            })->count();
            $recordsFiltered = $query->count();
            // Pagination
            $start = intval(request()->input('start', 0));
            $limit = intval(request()->input('length', 10));
            $data = $query->orderBy('LC.ID', 'desc')->offset($start)->limit($limit)->get();
            return response()->json([
                'draw' => intval(request()->input('draw')),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data
            ]);
        }
        $branch = DB::connection('pgsql')->table('MKT_BRANCH')->select('ID', 'Description', 'LocalDescription')->get();
        $data = DB::connection('pgsql')->table('MKT_DATES')->select('ID', 'SystemDate')->first();
        $currency = DB::connection('pgsql')->table('MKT_CURRENCY')->select('ID')->where('ID', 'USD')->first();
        return view('loans.loan_detail',compact('branch', 'data', 'currency'));
    }
    public function download(Request $request){
        $data = DB::connection('pgsql')->table('MKT_DATES')->select('ID', 'SystemDate')->first();
        $date = Carbon::parse($data->SystemDate);
        $currentTime = now()->setTimezone('Asia/Phnom_Penh');
        $dateTime = $date->format('Y-m-d') . '-' . $currentTime->format('H-i');
        $fileName = "Loan Detail Listing {$dateTime}.xlsx";
        $query = self::getDatas($request);
        $data = $query->get();
        return Excel::download(new ExportLoanDetailListing($data), $fileName);
    }
    public static function getDatas($request)
    {
        // 🔹 SubQuery: PD (latest DueDay per ID)
        $subQueryPD = DB::connection('pgsql')
            ->table(DB::raw('(
                SELECT DISTINCT ON ("ID")
                    "ID",
                    CAST("NumDayDue" AS INTEGER) AS "DueDay",
                    "DueDate"
                FROM "MKT_PD_DATE"
                WHERE "OutIntAmountAS" > 0 OR "OutPriAmountAS" > 0
                ORDER BY "ID", CAST("NumDayDue" AS INTEGER) DESC
            ) as PD'));

        // 🔹 SubQuery: Last Payment (FIXED - no more correlated subquery)
        $subQueryACCENTR = DB::connection('pgsql')
            ->table('MKT_ACC_ENTRY')
            ->select(
                'Account',
                DB::raw('MAX("TransactionDate") AS "LastPaymentDate"')
            )
            ->where('Amount', '>', 0)
            ->whereIn('Transaction', ['6','7','10','11','13','25','27','40','52','53','54'])
            ->groupBy('Account');

        // 🔹 Main Query
        $query = DB::connection('pgsql')
            ->table('MKT_LOAN_CONTRACT as LC')
            ->select([
                'LC.ID',
                'LC.ContractCustomerID',
                'LC.Branch',
                'LC.Account',
                'LC.Currency',
                'LC.Disbursed',
                'LC.LoanBalanceAS',
                'LC.OutstandingAmountAS',
                'LC.InterestRate',
                'LC.AIRAS',
                'LC.AIRCurrentAS',
                'LC.AccrIntPerDay',
                'LC.TotalInterest',
                'LC.ValueDate',
                'LC.MaturityDate',
                'LC.Term',
                'LC.DisbursedStat',
                'LC.AssetClass',
                'LC.MoreThanOneYear',
                'LC.CBCSubSection',
                'LC.LoanPurpose',
                'LC.ContractOfficerID',
                'LC.LoanType',
                'LC.RestructuredCycle',
                'LCol.Collateral as CollateralID',
                'LC.Amount',
                'LC.OutstandingAmount',
                'LC.EIRRate',
                'LC.AccrInterest',
                'LC.IntIncEarned',
                'LC.Sector as MACode',
                'LC.LoanProduct',
                'LC.Cycle',
                'LC.SubAmount',
                'LC.SubLoanPurpose',
                'LC.PartneredWith',
                'LC.RestructureType',
                'CUST.LastNameEn',
                'CUST.FirstNameEn',
                'CUST.Gender',
                'CUST.IDType',
                'CUST.IDNumber',
                'CUST.Mobile1',
                'CUST.Mobile2',
                'CUST.HouseNo',
                'CUST.CBCISSubSection as CBCISSubSectionCuSt',
                'CUST.Village as AddressCode',
                'CUST.Street',
                'VL.LocalDescription as Village',
                'CM.LocalDescription as Commune',
                'DS.LocalDescription as District',
                'PR.LocalDescription as Province',
                'Sct.Description as MADes',
                'LPr.Description as LoanProductDes',
                'PD.DueDay',
                'PD.DueDate as OverdueDate',
                'LCh1.Charge AS LoanCharge101',
                'LCh1.Charge AS LoanCharge',
                'LCh1.ChargeEarned',
                'LCh1.ChargeUnearned',
                'LCh2.Charge AS LoanCharge102',
                'LCh2.Charge as RegularCharge',
                'POS.Description as CustomerOccupation',
                'SD.RepMode as ScheduleType',

                // ✅ FIXED: use joined subquery instead
                'AE.LastPaymentDate'
            ])

            // 🔹 JOIN optimized
            ->leftJoinSub($subQueryPD, 'PD', function ($join) {
                $join->whereRaw('"PD"."ID" = \'PD\' || "LC"."ID"');
            })

            ->leftJoinSub($subQueryACCENTR, 'AE', function ($join) {
                $join->on('AE.Account', '=', 'LC.Account');
            })

            ->leftJoin('MKT_LOAN_CHARGE as LCh1', function ($q) {
                $q->on('LC.ID', '=', 'LCh1.ID')
                ->where('LCh1.ChargeKey', 101);
            })

            ->leftJoin('MKT_LOAN_CHARGE as LCh2', function ($q) {
                $q->on('LC.ID', '=', 'LCh2.ID')
                ->where('LCh2.ChargeKey', 102);
            })

            ->leftJoin('MKT_CUSTOMER as CUST', 'LC.ContractCustomerID', '=', 'CUST.ID')
            ->leftJoin('MKT_SCHED_DEFINE as SD', 'LC.ID', '=', 'SD.ID')
            ->leftJoin('MKT_POSITION as POS', 'POS.ID', '=', 'CUST.Position')
            ->leftJoin('MKT_VILLAGE as VL', 'CUST.Village', '=', 'VL.ID')
            ->leftJoin('MKT_COMMUNE as CM', 'CUST.Commune', '=', 'CM.ID')
            ->leftJoin('MKT_DISTRICT as DS', 'CUST.District', '=', 'DS.ID')
            ->leftJoin('MKT_PROVINCE as PR', 'CUST.Province', '=', 'PR.ID')
            ->leftJoin('MKT_SECTOR as Sct', 'LC.Sector', '=', 'Sct.ID')
            ->leftJoin('MKT_LOAN_COLLATERAL as LCol', 'LC.ID', '=', 'LCol.ID')
            ->leftJoin('MKT_LOAN_PRODUCT as LPr', 'LC.LoanProduct', '=', 'LPr.ID');

        // 🔹 Filters
        $query->when($request->branch_id, fn($q, $branch_id) =>
            $q->where('LC.Branch', $branch_id)
        );

        $query->when($request->LCID, fn($q, $LCID) =>
            $q->where('LC.ID', 'ilike', "%{$LCID}%")
        );

        // 🔹 Search (can be improved with trigram index)
        if ($searchValue = request()->input('search.value')) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('LC.Account', 'ilike', "%{$searchValue}%")
                ->orWhere('LC.ID', 'ilike', "%{$searchValue}%")
                ->orWhere('CUST.FirstNameEn', 'ilike', "%{$searchValue}%")
                ->orWhere('CUST.LastNameEn', 'ilike', "%{$searchValue}%");
            });
        }
        return $query->orderBy('LC.ID');
    }
    // public static function getDatas($request)
    // {
    //     $subQueryPD = DB::connection('pgsql')
    //     ->table(DB::raw('(
    //         SELECT DISTINCT ON ("ID")
    //             "ID",
    //             CAST("NumDayDue" AS INTEGER) AS "DueDay",
    //             "DueDate"
    //         FROM "MKT_PD_DATE"
    //         WHERE "OutIntAmountAS" > 0 OR "OutPriAmountAS" > 0
    //         ORDER BY "ID", CAST("NumDayDue" AS INTEGER) DESC
    //     ) as PD'));
        
    //     $subQueryACCENTR = DB::connection('pgsql')
    //     ->table('MKT_ACC_ENTRY')
    //     ->select(
    //         'Account',
    //         DB::raw('MAX("Reference") AS "Reference"'),
    //         DB::raw('MAX("TransactionDate") AS "LastPaymentDate"'),
    //     )
    //     ->where('Amount', '>', 0)
    //     ->groupBy('Account');
        
    //     $query = DB::connection('pgsql')
    //         ->table('MKT_LOAN_CONTRACT as LC')
    //         ->select([
    //             'LC.ID',
    //             'LC.ContractCustomerID',
    //             'LC.Branch',
    //             'LC.Account',
    //             'LC.Currency',
    //             'LC.Disbursed',
    //             'LC.LoanBalanceAS',
    //             'LC.OutstandingAmountAS',
    //             'LC.InterestRate',
    //             'LC.AIRAS',
    //             'LC.AIRCurrentAS',
    //             'LC.AccrIntPerDay',
    //             'LC.TotalInterest',
    //             'LC.ValueDate',
    //             'LC.MaturityDate',
    //             'LC.Term',
    //             'LC.DisbursedStat',
    //             'LC.AssetClass',
    //             'LC.MoreThanOneYear',
    //             'LC.CBCSubSection',
    //             'LC.LoanPurpose',
    //             'LC.ContractOfficerID',
    //             'LC.LoanType',
    //             'LC.RestructuredCycle',
    //             'LCol.Collateral as CollateralID',
    //             'LC.Amount',
    //             'LC.OutstandingAmount',
    //             'LC.EIRRate',
    //             'LC.AccrInterest',
    //             'LC.IntIncEarned',
    //             'LC.Sector as MACode',
    //             'LC.LoanProduct',
    //             'LC.Cycle',
    //             'LC.SubAmount',
    //             'LC.SubLoanPurpose',
    //             'LC.PartneredWith',
    //             'LC.RestructureType',
    //             'CUST.LastNameEn',
    //             'CUST.FirstNameEn',
    //             'CUST.Gender',
    //             'CUST.IDType',
    //             'CUST.IDNumber',
    //             'CUST.Mobile1',
    //             'CUST.Mobile2',
    //             'CUST.HouseNo',
    //             'CUST.CBCISSubSection as CBCISSubSectionCuSt',
    //             'CUST.Village as AddressCode',
    //             'CUST.Street',
    //             'VL.LocalDescription as Village',
    //             'CM.LocalDescription as Commune',
    //             'DS.LocalDescription as District',
    //             'PR.LocalDescription as Province',
    //             'Sct.Description as MADes',
    //             'LPr.Description as LoanProductDes',
    //             'PD.DueDay',
    //             'PD.DueDate as OverdueDate',
    //             'LCh1.Charge AS LoanCharge101',
    //             'LCh1.Charge AS LoanCharge',
    //             'LCh1.ChargeEarned',
    //             'LCh1.ChargeUnearned',
    //             'LCh2.Charge AS LoanCharge102',
    //             'LCh2.Charge as RegularCharge',
    //             'POS.Description as CustomerOccupation',
    //             'SD.RepMode as ScheduleType',
    //             DB::raw('(
    //                 SELECT MAX(AE."TransactionDate")
    //                 FROM "MKT_ACC_ENTRY" AE
    //                 WHERE AE."Account" = "LC"."Account"
    //                 AND AE."Amount" > 0
    //                 AND AE."Transaction" IN (\'6\',\'7\',\'10\',\'11\',\'13\',\'25\',\'27\',\'40\',\'52\',\'53\',\'54\')
    //                 AND (
    //                     AE."Reference" = "LC"."ID"
    //                     OR AE."Reference" = \'PD\' || "LC"."ID"
    //                 )
    //             ) as "LastPaymentDate"')
    //         ])
    //         ->leftJoinSub($subQueryPD, 'PD', function ($join) {
    //             $join->whereRaw('"PD"."ID" = \'PD\' || "LC"."ID"');
    //         })
    //         ->leftJoin('MKT_LOAN_CHARGE as LCh1', function($q){
    //             $q->on('LC.ID', '=', 'LCh1.ID')
    //             ->where('LCh1.ChargeKey', '=', 101);
    //         })
    //         ->leftJoin('MKT_LOAN_CHARGE as LCh2', function($q){
    //             $q->on('LC.ID', '=', 'LCh2.ID')
    //             ->where('LCh2.ChargeKey', '=', 102);
    //         })
    //         ->leftJoin('MKT_CUSTOMER as CUST', 'LC.ContractCustomerID', '=', 'CUST.ID')
    //         ->leftJoin('MKT_SCHED_DEFINE as SD', 'LC.ID', '=', 'SD.ID')
    //         ->leftJoin('MKT_POSITION as POS', 'POS.ID', '=', 'CUST.Position')
    //         ->leftJoin('MKT_VILLAGE as VL', 'CUST.Village', '=', 'VL.ID')
    //         ->leftJoin('MKT_COMMUNE as CM', 'CUST.Commune', '=', 'CM.ID')
    //         ->leftJoin('MKT_DISTRICT as DS', 'CUST.District', '=', 'DS.ID')
    //         ->leftJoin('MKT_PROVINCE as PR', 'CUST.Province', '=', 'PR.ID')
    //         ->leftJoin('MKT_SECTOR as Sct', 'LC.Sector', '=', 'Sct.ID')
    //         ->leftJoin('MKT_LOAN_COLLATERAL as LCol', 'LC.ID', '=', 'LCol.ID')
    //         ->leftJoin('MKT_LOAN_PRODUCT as LPr', 'LC.LoanProduct', '=', 'LPr.ID');

    //         $query->when($request->branch_id, fn($q,$branch_id) =>
    //             $q->where('LC.Branch', $branch_id)
    //         );
    //         $query->when($request->LCID, fn($q,$LCID) =>
    //             $q->where('LC.ID', 'ilike', "%{$LCID}%")
    //         );
            
    //         $searchValue = request()->input('search.value');
    //         if (!empty($searchValue)) {
    //             $query->where(function ($q) use ($searchValue) {
    //                 $q->where('LC.Account', 'like', "%{$searchValue}%")
    //                 ->orWhere('LC.ID', 'like', "%{$searchValue}%")
    //                 ->orWhere('CUST.FirstNameEn', 'like', "%{$searchValue}%")
    //                 ->orWhere('CUST.LastNameEn', 'like', "%{$searchValue}%")
    //                 ->orWhere('LC.ContractCustomerID', 'like', "%{$searchValue}%")
    //                 ->orWhere('LC.ContractOfficerID', 'like', "%{$searchValue}%")
    //                 ->orWhere('PR.LocalDescription', 'like', "%{$searchValue}%")
    //                 ->orWhere('DS.LocalDescription', 'like', "%{$searchValue}%")
    //                 ->orWhere('CM.LocalDescription', 'like', "%{$searchValue}%")
    //                 ->orWhere('VL.LocalDescription', 'like', "%{$searchValue}%")
    //                 ->orWhere('Sct.Description', 'like', "%{$searchValue}%")
    //                 ->orWhere('LPr.Description', 'like', "%{$searchValue}%");
    //             });
    //         }
    //     return $query->orderBy('LC.ID');
    // }
}