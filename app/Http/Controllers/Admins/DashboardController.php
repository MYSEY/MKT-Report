<?php

namespace App\Http\Controllers\Admins;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(){
        return view('dashboads.admin');
    }
}