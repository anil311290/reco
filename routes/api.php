<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AccountApiController;
use App\Http\Controllers\Api\PartyApiController;
use App\Http\Controllers\Api\VoucherApiController;
use App\Http\Controllers\Api\DashboardApiController;
use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\LedgerApiController;
use App\Http\Controllers\Api\LedgerHistoryApiController;
use App\Http\Controllers\Api\SecurityApiController;
use App\Http\Controllers\Api\SettingsApiController;
use App\Http\Controllers\Api\ExportApiController;
use App\Http\Controllers\Api\ItemApiController;
use App\Http\Controllers\Api\SalesInvoiceApiController;
use App\Http\Controllers\Api\PurchaseInvoiceApiController;
use App\Http\Controllers\Api\BankAccountApiController;
use App\Http\Controllers\Api\SubscriptionApiController;
use App\Http\Controllers\Api\ThemeApiController;
use App\Http\Controllers\Api\TaxRateApiController;
use App\Http\Controllers\Api\LocationApiController;
use App\Http\Controllers\Api\StatesCitiesApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    // Public states and cities routes (used by dropdowns)
    Route::get('/states', [StatesCitiesApiController::class, 'states']);
    Route::get('/states/{stateId}/cities', [StatesCitiesApiController::class, 'cities']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Authentication Required)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);

    // Security routes (PIN, App Lock)
    Route::post('/pin/login', [SecurityApiController::class, 'pinLogin']);
    Route::post('/pin/set', [SecurityApiController::class, 'setPin']);
    Route::post('/pin/verify', [SecurityApiController::class, 'verifyPin']);
    Route::put('/security/app-lock', [SecurityApiController::class, 'toggleAppLock']);
    Route::get('/security/settings', [SecurityApiController::class, 'getSecuritySettings']);
    Route::put('/security/settings', [SecurityApiController::class, 'updateSecuritySettings']);

    // Dashboard routes
    Route::get('/dashboard', [DashboardApiController::class, 'index']);

    // Location routes (for cascading dropdowns)
    Route::get('/locations/countries', [LocationApiController::class, 'countries']);
    Route::get('/locations/{countryId}/states', [LocationApiController::class, 'states']);
    Route::get('/locations/{stateId}/cities', [LocationApiController::class, 'cities']);
    
    Route::get('/dashboard/monthly-data', [DashboardApiController::class, 'monthlyData']);
    Route::get('/dashboard/receivables-trend', [DashboardApiController::class, 'receivablesTrend']);
    Route::get('/dashboard/payables-trend', [DashboardApiController::class, 'payablesTrend']);

    // Account routes
    Route::get('/accounts', [AccountApiController::class, 'index']);
    Route::get('/accounts/{id}', [AccountApiController::class, 'show']);
    Route::get('/accounts/by-type', [AccountApiController::class, 'getByType']);

    // Party routes
    Route::get('/parties', [PartyApiController::class, 'index']);
    Route::get('/parties/{id}', [PartyApiController::class, 'show']);
    Route::get('/parties/by-type', [PartyApiController::class, 'getByType']);

    // Voucher routes
    Route::get('/vouchers', [VoucherApiController::class, 'index']);
    Route::get('/vouchers/{id}', [VoucherApiController::class, 'show']);
    Route::post('/vouchers', [VoucherApiController::class, 'store']);
    Route::patch('/vouchers/{id}/post', [VoucherApiController::class, 'post']);
    Route::patch('/vouchers/{id}/cancel', [VoucherApiController::class, 'cancel']);
    Route::get('/vouchers/statistics', [VoucherApiController::class, 'statistics']);

    // Ledger routes
    Route::get('/ledgers', [LedgerApiController::class, 'index']);
    Route::get('/ledgers/{id}', [LedgerApiController::class, 'show']);
    Route::get('/ledgers/{id}/entries', [LedgerApiController::class, 'entries']);
    Route::get('/ledgers/{id}/history', [LedgerHistoryApiController::class, 'index']);

    // Report routes
    Route::get('/reports/profit-loss', [ReportApiController::class, 'profitLoss']);
    Route::get('/reports/balance-sheet', [ReportApiController::class, 'balanceSheet']);
    Route::get('/reports/trial-balance', [ReportApiController::class, 'trialBalance']);
    Route::get('/reports/day-book', [ReportApiController::class, 'dayBook']);
    Route::get('/reports/ledger', [ReportApiController::class, 'ledger']);
    Route::get('/reports/debtors-outstanding', [ReportApiController::class, 'debtorsOutstanding']);
    Route::get('/reports/creditors-outstanding', [ReportApiController::class, 'creditorsOutstanding']);

    // Settings routes
    Route::get('/settings', [SettingsApiController::class, 'index']);
    Route::get('/settings/company', [SettingsApiController::class, 'getCompanySettings']);
    Route::get('/settings/theme', [SettingsApiController::class, 'getThemeSettings']);
    Route::get('/settings/financial-years', [SettingsApiController::class, 'getFinancialYears']);
    Route::get('/settings/financial-year/current', [SettingsApiController::class, 'getCurrentFinancialYear']);

    // Export routes
    Route::get('/export/types', [ExportApiController::class, 'getExportTypes']);
    Route::get('/export/profit-loss/pdf', [ExportApiController::class, 'profitLossPdf']);
    Route::get('/export/balance-sheet/pdf', [ExportApiController::class, 'balanceSheetPdf']);
    Route::get('/export/ledger/pdf', [ExportApiController::class, 'ledgerPdf']);
    Route::get('/export/voucher/{id}/pdf', [ExportApiController::class, 'voucherPdf']);
    Route::get('/export/history', [ExportApiController::class, 'history']);
    Route::post('/export/share', [ExportApiController::class, 'shareStatement']);

    // Tax Rate routes
    Route::get('/tax-rates', [TaxRateApiController::class, 'index']);
    Route::get('/tax-rates/{id}', [TaxRateApiController::class, 'show']);
    Route::post('/tax-rates', [TaxRateApiController::class, 'store']);
    Route::put('/tax-rates/{id}', [TaxRateApiController::class, 'update']);

    // Item routes
    Route::get('/items', [ItemApiController::class, 'index']);
    Route::get('/items/low-stock', [ItemApiController::class, 'lowStock']);
    Route::get('/items/{id}', [ItemApiController::class, 'show']);
    Route::post('/items', [ItemApiController::class, 'store']);
    Route::put('/items/{id}', [ItemApiController::class, 'update']);

    // Sales Invoice routes
    Route::get('/sales-invoices', [SalesInvoiceApiController::class, 'index']);
    Route::get('/sales-invoices/overdue', [SalesInvoiceApiController::class, 'overdue']);
    Route::get('/sales-invoices/{id}', [SalesInvoiceApiController::class, 'show']);
    Route::post('/sales-invoices', [SalesInvoiceApiController::class, 'store']);
    Route::post('/sales-invoices/{id}/payment', [SalesInvoiceApiController::class, 'payment']);
    Route::post('/sales-invoices/{id}/generate-voucher', [SalesInvoiceApiController::class, 'generateVoucher']);

    // Purchase Invoice routes
    Route::get('/purchase-invoices', [PurchaseInvoiceApiController::class, 'index']);
    Route::get('/purchase-invoices/{id}', [PurchaseInvoiceApiController::class, 'show']);
    Route::post('/purchase-invoices', [PurchaseInvoiceApiController::class, 'store']);
    Route::post('/purchase-invoices/{id}/payment', [PurchaseInvoiceApiController::class, 'payment']);
    Route::post('/purchase-invoices/{id}/generate-voucher', [PurchaseInvoiceApiController::class, 'generateVoucher']);

    // Bank Account routes
    Route::get('/bank-accounts', [BankAccountApiController::class, 'index']);
    Route::get('/bank-accounts/{id}', [BankAccountApiController::class, 'show']);
    Route::post('/bank-accounts', [BankAccountApiController::class, 'store']);
    Route::put('/bank-accounts/{id}', [BankAccountApiController::class, 'update']);
    Route::patch('/bank-accounts/{id}/default', [BankAccountApiController::class, 'setDefault']);

    // Subscription routes
    Route::get('/subscriptions/plans', [SubscriptionApiController::class, 'plans']);
    Route::get('/subscriptions/current', [SubscriptionApiController::class, 'current']);
    Route::post('/subscriptions/subscribe', [SubscriptionApiController::class, 'subscribe']);
    Route::post('/subscriptions/change-plan', [SubscriptionApiController::class, 'changePlan']);
    Route::post('/subscriptions/cancel', [SubscriptionApiController::class, 'cancel']);

    // Theme routes
    Route::get('/themes/current', [ThemeApiController::class, 'current']);
    Route::get('/themes', [ThemeApiController::class, 'themes']);
    Route::put('/themes', [ThemeApiController::class, 'update']);
    Route::post('/themes/apply', [ThemeApiController::class, 'apply']);
    Route::post('/themes/toggle-dark-mode', [ThemeApiController::class, 'toggleDarkMode']);
});
