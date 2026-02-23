<?php

use App\Http\Controllers\Admins\COPerformanceController;
use App\Http\Controllers\Admins\DashboardController;
use App\Http\Controllers\Admins\LoandDetailListingController;
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

Auth::routes();
Route::group(['middleware'=>['auth:sanctum'], 'prefix'=>'admin'],function(){
    Route::get('/dashboard', [DashboardController::class, 'index']);
    // Route::get('loan/detail/listing',[LoandDetailListingController::class,'loanDetailListing']);
    // Route::get('loan/detail/listing/download',[LoandDetailListingController::class,'download']);


    Route::prefix('report')->group(function () {
        Route::get('loan/detail',[LoandDetailListingController::class,'loanDetailListing']);
        Route::get('loan/detail/download',[LoandDetailListingController::class,'download'])->name('loan.detail.download');
        Route::get('co-performance',[COPerformanceController::class,'coPerformance']);
        Route::get('co-performance/download',[COPerformanceController::class,'coPerformanceDownload']);
    });

    // users
    Route::get('/user', [UserController::class, 'index']);
});