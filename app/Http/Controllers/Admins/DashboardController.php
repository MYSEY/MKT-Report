<?php

namespace App\Http\Controllers\Admins;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(){
        $Profitble = DB::connection('mysqlhrconnection')
        ->table('users')
        ->leftJoin('positions', 'users.position_id', '=', 'positions.id')
        ->whereIn('positions.name_english', [
            'Junior Credit Officer',
            'Credit Officer',
            'Senior Credit Officer',
            'Junior Relationship Officer, Digital Lending',
            'Relationship Officer, Digital Lending'
        ])->count();
        // $subQueryPD = DB::connection('pgsql')
        //     ->table('MKT_PD_DATE')
        //     ->select(
        //         'ID',
        //         DB::raw('MAX(CAST("NumDayDue" AS INTEGER)) AS "DueDay"'),
        //         DB::raw('MAX("DueDate") AS "DueDate"')
        //     )
        // ->groupBy('ID');
        // $query = DB::connection('pgsql')
        // ->table('MKT_LOAN_CONTRACT as LC')
        // ->select([
        //     DB::raw('
        //         COUNT(
        //             DISTINCT CASE
        //                 WHEN "PD"."DueDay" >= 30
        //                 THEN "LC"."ID"
        //             END
        //         ) AS "Pars30"
        //     '),
        //     DB::raw('
        //         SUM(
        //             CASE
        //                 WHEN "PD"."DueDay" >= 30
        //                 THEN "LC"."OutstandingAmountAS"
        //                 ELSE 0
        //             END
        //         ) AS "Par30Outstanding"
        //     '),
        // ])
        // ->leftJoinSub($subQueryPD, 'PD', function ($join) {
        //     $join->whereRaw('"PD"."ID" = \'PD\' || "LC"."ID"');
        // });
        // $data = $query->get();
        // $totalPars30 = $data->sum('Pars30');
        // $totalParAmount = $data->sum('Par30Outstanding');
        // $TotalOutstanding = DB::connection('pgsql')->table('MKT_LOAN_CONTRACT')->where('OutstandingAmountAS', '>', 0)->sum('OutstandingAmountAS');
        // $finalParRate = $TotalOutstanding > 0 ? round(($totalParAmount / $TotalOutstanding) * 100, 2): 0;
        // dd([
        //     'Pars30' => $totalPars30,
        //     'ParAmount30' => $totalParAmount,
        //     'TotalOutstanding' => $TotalOutstanding,
        //     'Par30Rate' => $finalParRate,
        // ]);

        $subQueryPD = DB::connection('pgsql')
        ->table('MKT_PD_DATE')
        ->select(
            'ID',
            DB::raw('MAX(CAST("NumDayDue" AS INTEGER)) AS "DueDay"')
        )->groupBy('ID');

        $result = DB::connection('pgsql')
            ->table('MKT_LOAN_CONTRACT as LC')
            ->leftJoinSub($subQueryPD, 'PD', function ($join) {
                $join->whereRaw('"PD"."ID" = \'PD\' || "LC"."ID"');
            })
            ->where('LC.OutstandingAmountAS', '>', 0)
            ->selectRaw('
                COUNT(
                    DISTINCT CASE
                        WHEN "PD"."DueDay" >= 30
                        THEN "LC"."ID"
                    END
                ) AS "Pars30",

                SUM(
                    CASE
                        WHEN "PD"."DueDay" >= 30
                        THEN "LC"."OutstandingAmountAS"
                        ELSE 0
                    END
                ) AS "Par30Outstanding"
        ')->first();
        $totalPars30 = (int) ($result->Pars30 ?? 0);
        $totalParAmount = (float) ($result->Par30Outstanding ?? 0);

        $totalOutstanding = DB::connection('pgsql')->table('MKT_LOAN_CONTRACT')->where('OutstandingAmountAS', '>', 0)->sum('OutstandingAmountAS');
        $finalParRate = $totalOutstanding > 0 ? round(($totalParAmount / $totalOutstanding) * 100, 2) : 0;
        $totalAssetClass = DB::connection('pgsql')->table('MKT_LOAN_CONTRACT')->where('AssetClass', '>', 20)->count();
        // dd([
        //     'Pars30' => $totalPars30,
        //     'ParAmount30' => $totalParAmount,
        //     'TotalOutstanding' => $totalOutstanding,
        //     'Par30Rate' => $finalParRate,
        // ]);
        
        
        $data = DB::connection('pgsql')
        ->table('MKT_LOAN_CONTRACT as LC') // ✅ add alias here
        ->selectRaw("
            SUM(CASE WHEN \"Currency\" = 'KHR' THEN \"OutstandingAmount\" ELSE 0 END) as khr,
            SUM(CASE WHEN \"Currency\" = 'USD' THEN \"OutstandingAmount\" ELSE 0 END) as usd
        ")->where('OutstandingAmountAS', '>', 0)->first();
        $customer = DB::connection('pgsql')->table('MKT_CUSTOMER')->count();
        $loan = DB::connection('pgsql')->table('MKT_LOAN_CONTRACT')->where('OutstandingAmountAS', '>', 0)->count();
        return view('dashboads.admin',compact('customer','data','loan','finalParRate','totalAssetClass','Profitble'));
    }
}