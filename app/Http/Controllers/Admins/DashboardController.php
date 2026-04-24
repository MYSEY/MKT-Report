<?php

namespace App\Http\Controllers\Admins;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(){
        $data = DB::connection('pgsql')
        ->table('MKT_LOAN_CONTRACT')
            ->selectRaw("
                SUM(CASE WHEN \"Currency\" = 'KHR' THEN \"OutstandingAmountAS\" ELSE 0 END) as khr,
                SUM(CASE WHEN \"Currency\" = 'USD' THEN \"OutstandingAmountAS\" ELSE 0 END) as usd
            ")
        ->where('OutstandingAmountAS', '>', 0)->first();
        $customer = DB::connection('pgsql')->table('MKT_CUSTOMER')->count();
        $loan = DB::connection('pgsql')->table('MKT_LOAN_CONTRACT')->where('OutstandingAmountAS', '>', 0)->count();
        return view('dashboads.admin',compact('customer','data','loan'));
    }
}