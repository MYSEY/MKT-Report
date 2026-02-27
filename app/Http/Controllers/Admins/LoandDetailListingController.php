<?php

namespace App\Http\Controllers\Admins;

use App\Exports\ExportLoanDetailListing;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class LoandDetailListingController extends Controller
{
    public function loanDetailListing(Request $request){
        // $subQueryPD = DB::connection('pgsql')
        //     ->table('MKT_PD_DATE')
        //     ->where(function ($q) {
        //         $q->where('OutIntAmountAS', '>', 0)
        //         ->orWhere('OutPriAmountAS', '>', 0);
        //     })
        //     ->select(
        //         'ID',
        //         DB::raw('MAX(CAST("NumDayDue" AS INTEGER)) AS "DueDay"'),
        //         DB::raw('MAX("DueDate") AS "DueDate"'),
        //         DB::raw('MAX("OutIntAmountAS") AS "OutIntAmountAS"'),
        //         DB::raw('MAX("OutPriAmountAS") AS "OutPriAmountAS"')
        //     )
        // ->groupBy('ID');
        // $query = DB::connection('pgsql')
        // ->table('MKT_LOAN_CONTRACT as LC')
        // ->leftJoinSub($subQueryPD, 'PDD', function ($join) {
        //     $join->whereRaw('"PDD"."ID" = \'PD\' || "LC"."ID"');
        // })
        // ->select('LC.*', 'PDD.*')->get();
        // dd($query);


        if (request()->ajax()) {
            $subQueryPD = DB::connection('pgsql')
            ->table('MKT_PD_DATE')
            ->where(function ($q) {
                $q->where('OutIntAmountAS', '>', 0);
                // ->orWhere('OutPriAmountAS', '>', 0);
            })
            ->select(
                'ID',
                DB::raw('MAX(CAST("NumDayDue" AS INTEGER)) AS "DueDay"'),
                DB::raw('MAX("DueDate") AS "DueDate"'),
                DB::raw('MAX("OutIntAmountAS") AS "OutIntAmountAS"'),
                DB::raw('MAX("OutPriAmountAS") AS "OutPriAmountAS"')
            )->groupBy('ID');

           $subQueryACCENTR = DB::connection('pgsql')
            ->table('MKT_ACC_ENTRY')
            ->select(
                'Reference',
                DB::raw('MAX("TransactionDate") AS "LastPaymentDate"')
            )
            ->groupBy('Reference');
            
            // $query = DB::connection('pgsql')->table('MKT_CUSTOMER');
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
                    'ACC.Reference',
                    'ACC.LastPaymentDate',
                ])
                ->leftJoin('MKT_CUSTOMER as CUST', 'LC.ContractCustomerID', '=', 'CUST.ID')
                ->leftJoin('MKT_SCHED_DEFINE as SD', 'LC.ID', '=', 'SD.ID')
                ->leftJoin('MKT_POSITION as POS', 'POS.ID', '=', 'CUST.Position')
                ->leftJoin('MKT_VILLAGE as VL', 'CUST.Village', '=', 'VL.ID')
                ->leftJoin('MKT_COMMUNE as CM', 'CUST.Commune', '=', 'CM.ID')
                ->leftJoin('MKT_DISTRICT as DS', 'CUST.District', '=', 'DS.ID')
                ->leftJoin('MKT_PROVINCE as PR', 'CUST.Province', '=', 'PR.ID')
                ->leftJoin('MKT_SECTOR as Sct', 'LC.Sector', '=', 'Sct.ID')
                ->leftJoin('MKT_LOAN_COLLATERAL as LCol', 'LC.ID', '=', 'LCol.ID')
                ->leftJoin('MKT_LOAN_PRODUCT as LPr', 'LC.LoanProduct', '=', 'LPr.ID')
                ->leftJoinSub($subQueryPD, 'PD', function ($join) {
                    $join->whereRaw('"PD"."ID" = \'PD\' || "LC"."ID"');
                })

                ->leftJoinSub($subQueryACCENTR, 'ACC', function ($join) {
                    $join->on('ACC.Reference', '=', 'LC.ID');
                })
                
                ->leftJoin('MKT_LOAN_CHARGE as LCh1', function($q){
                    $q->on('LC.ID', '=', 'LCh1.ID')
                    ->where('LCh1.ChargeKey', '=', 101);
                })
                ->leftJoin('MKT_LOAN_CHARGE as LCh2', function($q){
                    $q->on('LC.ID', '=', 'LCh2.ID')
                    ->where('LCh2.ChargeKey', '=', 102);
                });
                // ->where('LC.OutstandingAmountAS', '>', 0)
                // ->where('LC.Branch', '<>', '""');

                $query->when($request->branch_id, fn($q,$branch_id) =>
                    $q->where('LC.Branch', $branch_id)
                );
                $query->when($request->LCID, fn($q,$LCID) =>
                    $q->where('LC.ID', 'ilike', "%{$LCID}%")
                );
                
                $searchValue = request()->input('search.value');
                if (!empty($searchValue)) {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('LC.Account', 'like', "%{$searchValue}%")
                        ->orWhere('LC.ID', 'like', "%{$searchValue}%")
                        ->orWhere('CUST.FirstNameEn', 'like', "%{$searchValue}%")
                        ->orWhere('CUST.LastNameEn', 'like', "%{$searchValue}%")
                        ->orWhere('LC.ContractCustomerID', 'like', "%{$searchValue}%")
                        ->orWhere('LC.ContractOfficerID', 'like', "%{$searchValue}%")
                        ->orWhere('PR.LocalDescription', 'like', "%{$searchValue}%")
                        ->orWhere('DS.LocalDescription', 'like', "%{$searchValue}%")
                        ->orWhere('CM.LocalDescription', 'like', "%{$searchValue}%")
                        ->orWhere('VL.LocalDescription', 'like', "%{$searchValue}%")
                        ->orWhere('Sct.Description', 'like', "%{$searchValue}%")
                        ->orWhere('LPr.Description', 'like', "%{$searchValue}%");
                    });
                }


            // Total rows (no search)
            $recordsTotal = DB::connection('pgsql')
            ->table('MKT_LOAN_CONTRACT as LC')
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->where('LC.Branch', $request->branch_id);
            })->count();

            // Total rows (with search)
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
        // Convert to Carbon
        $date = Carbon::parse($data->SystemDate);
        $currentTime = now()->setTimezone('Asia/Phnom_Penh');
        // Add current time
        $dateTime = $date->format('Y-m-d') . '-' . $currentTime->format('H-i');
        // File name
        $fileName = "Loan Detail Listing {$dateTime}.xlsx";
        return Excel::download(new ExportLoanDetailListing($request), $fileName);
    }
}
