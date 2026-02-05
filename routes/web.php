<?php

use App\Http\Controllers\Admins\DashboardController;
use App\Http\Controllers\Admins\TicketController;
use App\Http\Controllers\Admins\UserController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/', [LoginController::class, 'index']);
Route::post('/login', [LoginController::class, 'login']);
// Route::get('/', function () {
//     // return view('layouts.admin');
//     return view('welcome');
// });

Auth::routes();
Route::group(['middleware'=>['auth:sanctum'], 'prefix'=>'admin'],function(){
    Route::get('/dashboad', [DashboardController::class, 'index']);

    // users 
    Route::get('/user', [UserController::class, 'index']);
    
});
