<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admins\RoleController;
use App\Http\Controllers\Admins\UserController;
use App\Http\Controllers\Admins\CategoryController;
use App\Http\Controllers\Admins\DashboardController;
use App\Http\Controllers\Admins\PermissionController;
use App\Http\Controllers\Admins\COPerformanceController;
use App\Http\Controllers\Admins\LoandDetailListingController;
use App\Http\Controllers\Admins\NetworkEmployeeController;
use App\Http\Controllers\Admins\SaleRecordController;
use App\Http\Controllers\Admins\TMGController;

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
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::prefix('setting')->group(function () {
        Route::resource('category', CategoryController::class);
        Route::resource('permission', PermissionController::class);
        Route::resource('role', RoleController::class);
        Route::get('user', [UserController::class, 'index']);
    });
    Route::prefix('mkt-report')->group(function () {
        Route::get('loan/detail',[LoandDetailListingController::class,'loanDetailListing']);
        Route::get('loan/detail/download',[LoandDetailListingController::class,'download'])->name('loan.detail.download');
        Route::get('co-performance',[COPerformanceController::class,'coPerformance']);
        Route::get('co-performance/download',[COPerformanceController::class,'coPerformanceDownload']);
    });
    Route::prefix('hr-report')->group(function () {
        Route::get('network-employee',[NetworkEmployeeController::class,'index']);
        Route::get('network-employee/download',[NetworkEmployeeController::class,'exportExcel'])->name('hr-reports.network_employee_export');

        Route::get('tmg',[TMGController::class,'index']);
        Route::get('tmg/download',[TMGController::class,'exportExcel'])->name('hr-reports.TMG_report');

    });
});
