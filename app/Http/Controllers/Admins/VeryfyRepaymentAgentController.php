<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class VeryfyRepaymentAgentController extends Controller
{
    public function index()
    {
        return view('verify_repayment_agent.index');
    }

    public function importVeryfyRepaymentAgent(Request $request){
        $data = Excel::toArray([], $request->file('file'));
        $rows = $data[0];

        // ==================
        // WING
        // ==================
        if (isset($rows[9][0]) && trim($rows[9][0]) == 'No.') {
            return $this->processWing($request);
        }

        // ==================
        // AC
        // ==================
        elseif (isset($rows[2][0]) && trim($rows[2][0]) == 'TXN_DATE') {
            return $this->processAC();
        }

        // ==================
        // AMK
        // ==================
        elseif (isset($rows[13][0])) {
            return $this->processAMK();
        }

        // ==================
        // TrueMeny
        // ==================
        elseif (isset($rows[0][1]) && trim($rows[0][1]) == 'TXN ID (short_order_id)') {
            return $this->processTrueMoney();
        }else {
            return response()->json([
                'status' => false,
                'message' => 'Unknown file format'
            ]);
        }
        dd([
            'exchange_rate' => $request->exchange_rate,
            'date' => $request->date,
            'data' => $data,
        ]);
    }

    // ==================================================
    // WING
    // ==================================================
    private function processWing(Request $request)
    {
        // $subQueryPD = DB::connection('pgsql')
        //     ->table('MKT_PD_DATE')
        //     ->select(
        //         'ID',
        //         DB::raw('SUM("OutPriAmountAS") AS "PDPrincipal"'),
        //         DB::raw('SUM("OutIntAmountAS") AS "PDInterest"'),
        //         DB::raw('SUM("OutPenAmountAS") AS "PDPenalty"'),
        //         DB::raw('MAX(CAST("NumDayDue" AS INTEGER)) AS "DueDay"'),
        //         DB::raw('MAX("DueDate") AS "DueDate"')
        //     )
        // ->groupBy('ID');

        
        $query = DB::connection('pgsql')
            ->table('MKT_LOAN_CONTRACT as LC')
            ->leftJoin('MKT_CUSTOMER as CUST', 'LC.ContractCustomerID', '=', 'CUST.ID')
            ->leftJoin('MKT_PROVINCE as PR', 'PR.ID', '=', 'CUST.Province')
            ->leftJoin('MKT_DISTRICT as DS', 'DS.ID', '=', 'CUST.District')
            ->leftJoin('MKT_COMMUNE as CM', 'CM.ID', '=', 'CUST.Commune')
            ->leftJoin('MKT_VILLAGE as VL', 'VL.ID', '=', 'CUST.Village')
            ->leftJoin('MKT_LOAN_PRODUCT as LP', 'LC.LoanProduct', '=', 'LP.ID')
            ->leftJoin('MKT_PAST_DUE as PD', 'LC.ID', '=', 'PD.LoanID')
            ->leftJoin('MKT_REP_SCHEDULE as REP', 'PD.LoanID', '=', 'REP.LoanID')
            ->select([
                'LC.ID as LD Number',
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
                'LC.ValueDate as Disburse Date',
                'LC.MaturityDate',
                'CUST.Officer',
                'CUST.LastNameEn',
                'CUST.FirstNameEn',
                'CUST.Gender',
                'CUST.Mobile1',
                'CUST.Mobile2',
                'PD.TotODAmountAS as TotaArr',
                'PD.TotPrincipalDueAS as PricipalArr',
                'PD.TotInterestDueAS as InteresArr',
                'PD.TotPenaltyDueAS as PenaltyArr',
                'PD.TotChargeDueAS as ChargeArr',
                'REP.LoanID',
                'REP.CollectionDate',
                'REP.Principal',
                'REP.Interest',
                'REP.Charge',
                'REP.Balance',
                'REP.Curr',
                'LP.Description as LoanProduct',
                'PR.LocalDescription as PROVINCE',
                'DS.LocalDescription as DISTRICT',
                'CM.LocalDescription as COMMUNE',
                'VL.LocalDescription as VILLAGE',
            ])->where('REP.CollectionDate', $request->date);
        $data = $query->limit(10)->get();
        dd($data);
        return $query->orderBy('LC.ID');
    }

    // ==================================================
    // AC
    // ==================================================
    private function processAC()
    {
        dd('Process AC');
    }

    // ==================================================
    // AMK
    // ==================================================
    private function processAMK()
    {
        dd('Process AMK');
    }

    // ==================================================
    // TRUE MONEY
    // ==================================================
    private function processTrueMoney()
    {
        dd('Process TrueMoney');
    }
}