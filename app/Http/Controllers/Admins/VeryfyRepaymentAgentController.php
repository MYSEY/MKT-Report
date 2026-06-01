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
            return $this->processWing();
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
    private function processWing()
    {
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
            ]);
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