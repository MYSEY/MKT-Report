<?php

namespace App\Http\Controllers\Admins;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(){
        $subQueryPD = DB::connection('pgsql')
            ->table('MKT_PD_DATE')
            ->select(
                'ID',
                DB::raw('SUM("OutPriAmountAS") AS "PDPrincipal"'),
                DB::raw('SUM("OutIntAmountAS") AS "PDInterest"'),
                DB::raw('SUM("OutPenAmountAS") AS "PDPenalty"'),
                DB::raw('MAX(CAST("NumDayDue" AS INTEGER)) AS "DueDay"'),
                DB::raw('MAX("DueDate") AS "DueDate"')
            )
        ->groupBy('ID');
        $query = DB::connection('pgsql')
        ->table('MKT_LOAN_CONTRACT as LC')
        ->select([
            'LC.ContractOfficerID',
            'LC.Currency',
            DB::raw('
                COUNT(
                    DISTINCT CASE
                        WHEN "PD"."DueDay" >= 30
                        THEN "LC"."ID"
                    END
                ) AS "Pars30"
            '),
            DB::raw('
                SUM(
                    CASE
                        WHEN "PD"."DueDay" >= 30
                        THEN "LC"."OutstandingAmountAS"
                        ELSE 0
                    END
                ) AS "Par30Outstanding"
            '),
            DB::raw('
                ROUND(
                    (
                        SUM(
                            CASE
                                WHEN "PD"."DueDay" >= 30
                                THEN "LC"."OutstandingAmountAS"
                                ELSE 0
                            END
                        )
                        /
                        NULLIF(SUM("LC"."OutstandingAmountAS"),0)
                    ) * 100,
                    2
                ) AS "Par30Rate"
            ')
        ])
        ->leftJoinSub($subQueryPD, 'PD', function ($join) {
            $join->whereRaw('"PD"."ID" = \'PD\' || "LC"."ID"');
        })
        ->where('LC.OutstandingAmountAS', '>', 0)
        ->groupBy(
            'LC.ContractOfficerID',
            'LC.Currency'
        );
        $data = $query->get();
        // dd($data);
        
        $data = DB::connection('pgsql')
        ->table('MKT_LOAN_CONTRACT as LC') // ✅ add alias here
        ->selectRaw("
            SUM(CASE WHEN \"Currency\" = 'KHR' THEN \"OutstandingAmount\" ELSE 0 END) as khr,
            SUM(CASE WHEN \"Currency\" = 'USD' THEN \"OutstandingAmount\" ELSE 0 END) as usd
        ")->where('OutstandingAmountAS', '>', 0)->first();
        $customer = DB::connection('pgsql')->table('MKT_CUSTOMER')->count();
        $loan = DB::connection('pgsql')->table('MKT_LOAN_CONTRACT')->where('OutstandingAmountAS', '>', 0)->count();
        return view('dashboads.admin',compact('customer','data','loan'));
    }
}