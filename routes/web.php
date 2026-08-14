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
use App\Http\Controllers\Admins\GLBalanceController;
use App\Http\Controllers\Admins\LoandDetailListingController;
use App\Http\Controllers\Admins\NetworkEmployeeController;
use App\Http\Controllers\Admins\SaleRecordController;
use App\Http\Controllers\Admins\TMGController;
use App\Http\Controllers\Admins\BranchCodeController;
use App\Http\Controllers\Admins\InterestIncomeController;
use App\Http\Controllers\Admins\PositionController;
use App\Http\Controllers\Admins\LoanInactiveController;
use App\Http\Controllers\Admins\LoanDisbursementController;
use App\Http\Controllers\Admins\VeryfyRepaymentAgentController;
use App\Http\Controllers\Admins\PdfToExcelController;

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
    Route::prefix('configuration')->group(function () {
        Route::resource('interest-income', InterestIncomeController::class);
        Route::resource('branch-code', BranchCodeController::class);
        Route::post('interest-income/import', [InterestIncomeController::class, 'import']);
        Route::resource('position', PositionController::class);
    });
    Route::prefix('mkt-report')->group(function () {
        Route::get('loan/detail',[LoandDetailListingController::class,'loanDetailListing']);
        Route::get('loan/detail/download',[LoandDetailListingController::class,'download'])->name('loan.detail.download');
        Route::get('co-performance',[COPerformanceController::class,'coPerformance']);
        Route::get('co-performance/download',[COPerformanceController::class,'coPerformanceDownload']);
        Route::get('sale-record',[SaleRecordController::class,'index']);
        Route::get('sale-record/download',[SaleRecordController::class,'exportExcel']);
        Route::get('sale-record/downloads',[SaleRecordController::class,'exportExcelAll']);

        Route::get('sale-record-exemption',[SaleRecordController::class,'indexExemption']);
        Route::get('sale-record-console',[SaleRecordController::class,'indexConsole']);
        Route::get('sale-record-excs/download',[SaleRecordController::class,'exportExCsExcel']);

        Route::get('trial-balance',[SaleRecordController::class,'indexTrialBalance']);

        Route::get('gl-balance', [GLBalanceController::class, 'index'])->name('mkt.gl.balance');
        Route::get('gl-detail/{id}', [GLBalanceController::class, 'detail'])->name('mkt.gl.detail');
        
        Route::get('loan-inactive',[LoanInactiveController::class,'index']);
        // Route::get('loan-inactive/download',[LoanInactiveController::class,'export']);
        Route::get('loan-inactive/download',[LoanInactiveController::class,'exportInactive']);

        Route::get('loan-disbursement',[LoanDisbursementController::class,'index']);

        
        Route::get('veryfy/repayment/agent',[VeryfyRepaymentAgentController::class,'index']);
        Route::get('veryfy/repayment/agent/monthly',[VeryfyRepaymentAgentController::class,'verifyRepaymentAgentMonthly']);
        Route::get('veryfy/repayment/agent/detail/{id}',[VeryfyRepaymentAgentController::class,'verifyRepaymentDetail']);
        Route::post('veryfy/repayment/agent/import',[VeryfyRepaymentAgentController::class,'importVeryfyRepaymentAgent']);
        Route::get('veryfy/repayment/agent/download/morakot/{id}',[VeryfyRepaymentAgentController::class,'downloadToMorakot']);
        Route::get('veryfy/repayment/agent/download/branch/{id}',[VeryfyRepaymentAgentController::class,'downloadToBranch']);
    });
    Route::prefix('hr-report')->group(function () {
        Route::get('network-employee',[NetworkEmployeeController::class,'index']);
        Route::get('network-employee/download',[NetworkEmployeeController::class,'exportExcel'])->name('hr-reports.network_employee_export');

        Route::get('tmg',[TMGController::class,'index']);
        Route::get('tmg/download',[TMGController::class,'exportExcel'])->name('hr-reports.TMG_report');
    });

    Route::get('/pdf-to-excel', [PdfToExcelController::class, 'index'])->name('pdf-to-excel.index');
    Route::post('/pdf-to-excel/convert', [PdfToExcelController::class, 'convert'])->name('pdf-to-excel.convert');
});
