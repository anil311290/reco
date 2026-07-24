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
use App\Http\Controllers\Api\FinancialYearApiController;
use App\Http\Controllers\Api\ExportApiController;
use App\Http\Controllers\Api\ItemApiController;
use App\Http\Controllers\Api\ItemCategoryApiController;
use App\Http\Controllers\Api\SalesInvoiceApiController;
use App\Http\Controllers\Api\PurchaseInvoiceApiController;
use App\Http\Controllers\Api\SubscriptionApiController;
use App\Http\Controllers\Api\ThemeApiController;
use App\Http\Controllers\Api\TaxRateApiController;
use App\Http\Controllers\Api\LocationApiController;
use App\Http\Controllers\Api\StatesCitiesApiController;
use App\Http\Controllers\Api\SyncApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\AuditLogApiController;
use App\Http\Controllers\Api\SupportTicketApiController;
use App\Http\Controllers\Api\DeviceApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/states', [StatesCitiesApiController::class, 'states']);
    Route::get('/states/{stateId}/cities', [StatesCitiesApiController::class, 'cities']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Authentication Required)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);

    // Security (mobile app lock / PIN)
    Route::post('/pin/login', [SecurityApiController::class, 'pinLogin']);
    Route::post('/pin/set', [SecurityApiController::class, 'setPin']);
    Route::post('/pin/verify', [SecurityApiController::class, 'verifyPin']);
    Route::put('/security/app-lock', [SecurityApiController::class, 'toggleAppLock']);
    Route::get('/security/settings', [SecurityApiController::class, 'getSecuritySettings']);
    Route::put('/security/settings', [SecurityApiController::class, 'updateSecuritySettings']);

    // Dashboard
    Route::get('/dashboard', [DashboardApiController::class, 'index']);
    Route::get('/dashboard/monthly-data', [DashboardApiController::class, 'monthlyData']);
    Route::get('/dashboard/receivables-trend', [DashboardApiController::class, 'receivablesTrend']);
    Route::get('/dashboard/payables-trend', [DashboardApiController::class, 'payablesTrend']);

    // Locations
    Route::get('/locations/countries', [LocationApiController::class, 'countries']);
    Route::get('/locations/{countryId}/states', [LocationApiController::class, 'states']);
    Route::get('/locations/{stateId}/cities', [LocationApiController::class, 'cities']);

    // Accounts (static routes before {id})
    Route::get('/accounts', [AccountApiController::class, 'index']);
    Route::post('/accounts', [AccountApiController::class, 'store']);
    Route::get('/accounts/by-type', [AccountApiController::class, 'getByType']);
    Route::get('/accounts/tree', [AccountApiController::class, 'tree']);
    Route::get('/accounts/cash-bank', [AccountApiController::class, 'cashBank']);
    Route::get('/accounts/payment-particulars', [AccountApiController::class, 'paymentParticulars']);
    Route::get('/accounts/adjustment-particulars', [AccountApiController::class, 'adjustmentParticulars']);
    Route::get('/accounts/{id}', [AccountApiController::class, 'show']);
    Route::put('/accounts/{id}', [AccountApiController::class, 'update']);
    Route::delete('/accounts/{id}', [AccountApiController::class, 'destroy']);
    Route::patch('/accounts/{id}/status', [AccountApiController::class, 'changeStatus']);

    // Parties
    Route::get('/parties', [PartyApiController::class, 'index']);
    Route::post('/parties', [PartyApiController::class, 'store']);
    Route::get('/parties/by-type', [PartyApiController::class, 'getByType']);
    Route::get('/parties/{id}/history', [PartyApiController::class, 'history']);
    Route::get('/parties/{id}', [PartyApiController::class, 'show']);
    Route::put('/parties/{id}', [PartyApiController::class, 'update']);
    Route::delete('/parties/{id}', [PartyApiController::class, 'destroy']);
    Route::patch('/parties/{id}/status', [PartyApiController::class, 'changeStatus']);

    // Vouchers
    Route::get('/vouchers', [VoucherApiController::class, 'index']);
    Route::post('/vouchers', [VoucherApiController::class, 'store']);
    Route::get('/vouchers/{id}', [VoucherApiController::class, 'show']);
    Route::put('/vouchers/{id}', [VoucherApiController::class, 'update']);
    Route::delete('/vouchers/{id}', [VoucherApiController::class, 'destroy']);
    Route::patch('/vouchers/{id}/post', [VoucherApiController::class, 'post']);
    Route::patch('/vouchers/{id}/cancel', [VoucherApiController::class, 'cancel']);

    // Payment vouchers (money out — mirrors admin Payments module)
    Route::get('/payments', [VoucherApiController::class, 'indexPayments']);
    Route::post('/payments', [VoucherApiController::class, 'storePayment']);
    Route::get('/payments/{id}', [VoucherApiController::class, 'showPayment']);
    Route::put('/payments/{id}', [VoucherApiController::class, 'updatePayment']);
    Route::delete('/payments/{id}', [VoucherApiController::class, 'destroyPayment']);
    Route::patch('/payments/{id}/cancel', [VoucherApiController::class, 'cancelPayment']);

    // Receipt vouchers (money in — mirrors admin Receipts module)
    Route::get('/receipts', [VoucherApiController::class, 'indexReceipts']);
    Route::post('/receipts', [VoucherApiController::class, 'storeReceipt']);
    Route::get('/receipts/{id}', [VoucherApiController::class, 'showReceipt']);
    Route::put('/receipts/{id}', [VoucherApiController::class, 'updateReceipt']);
    Route::delete('/receipts/{id}', [VoucherApiController::class, 'destroyReceipt']);
    Route::patch('/receipts/{id}/cancel', [VoucherApiController::class, 'cancelReceipt']);

    // Adjustment vouchers (journal entries — mirrors admin Adjustments module)
    Route::get('/adjustments', [VoucherApiController::class, 'indexAdjustments']);
    Route::post('/adjustments', [VoucherApiController::class, 'storeAdjustment']);
    Route::get('/adjustments/{id}', [VoucherApiController::class, 'showAdjustment']);
    Route::put('/adjustments/{id}', [VoucherApiController::class, 'updateAdjustment']);
    Route::delete('/adjustments/{id}', [VoucherApiController::class, 'destroyAdjustment']);
    Route::patch('/adjustments/{id}/cancel', [VoucherApiController::class, 'cancelAdjustment']);

    // Ledgers
    Route::get('/ledgers', [LedgerApiController::class, 'index']);
    Route::get('/ledgers/{id}', [LedgerApiController::class, 'show']);
    Route::get('/ledgers/{id}/entries', [LedgerApiController::class, 'entries']);
    Route::get('/ledgers/{id}/history', [LedgerHistoryApiController::class, 'index']);

    // Reports
    Route::get('/reports/day-book', [ReportApiController::class, 'dayBook']);
    Route::get('/reports/cash-book', [ReportApiController::class, 'cashBook']);
    Route::get('/reports/bank-book', [ReportApiController::class, 'bankBook']);
    Route::get('/reports/ledger', [ReportApiController::class, 'ledger']);
    Route::get('/reports/trial-balance', [ReportApiController::class, 'trialBalance']);
    Route::get('/reports/profit-loss', [ReportApiController::class, 'profitLoss']);
    Route::get('/reports/balance-sheet', [ReportApiController::class, 'balanceSheet']);
    Route::get('/reports/debtors-outstanding', [ReportApiController::class, 'debtorsOutstanding']);
    Route::get('/reports/creditors-outstanding', [ReportApiController::class, 'creditorsOutstanding']);

    // Settings
    Route::get('/settings', [SettingsApiController::class, 'index']);
    Route::get('/settings/company', [SettingsApiController::class, 'getCompanySettings']);
    Route::put('/settings/company', [SettingsApiController::class, 'updateCompany']);
    Route::get('/settings/theme', [SettingsApiController::class, 'getThemeSettings']);
    Route::put('/settings/accounting', [SettingsApiController::class, 'updateAccounting']);

    // Financial years
    Route::get('/financial-years', [FinancialYearApiController::class, 'index']);
    Route::post('/financial-years', [FinancialYearApiController::class, 'store']);
    Route::get('/financial-years/current', [FinancialYearApiController::class, 'current']);
    Route::patch('/financial-years/{id}/set-current', [FinancialYearApiController::class, 'setAsCurrent']);
    Route::patch('/financial-years/{id}/close', [FinancialYearApiController::class, 'close']);
    Route::delete('/financial-years/{id}', [FinancialYearApiController::class, 'destroy']);

    // Legacy settings financial year routes (read-only aliases)
    Route::get('/settings/financial-years', [SettingsApiController::class, 'getFinancialYears']);
    Route::get('/settings/financial-year/current', [SettingsApiController::class, 'getCurrentFinancialYear']);

    // Exports
    Route::get('/export/profit-loss/pdf', [ExportApiController::class, 'profitLossPdf']);
    Route::get('/export/balance-sheet/pdf', [ExportApiController::class, 'balanceSheetPdf']);
    Route::get('/export/trial-balance/pdf', [ExportApiController::class, 'trialBalancePdf']);
    Route::get('/export/day-book/pdf', [ExportApiController::class, 'dayBookPdf']);
    Route::get('/export/ledger/pdf', [ExportApiController::class, 'ledgerPdf']);
    Route::get('/export/debtors-outstanding/pdf', [ExportApiController::class, 'debtorsOutstandingPdf']);
    Route::get('/export/creditors-outstanding/pdf', [ExportApiController::class, 'creditorsOutstandingPdf']);
    Route::get('/export/voucher/{id}/pdf', [ExportApiController::class, 'voucherPdf']);
    Route::get('/export/sales-invoice/{id}/pdf', [ExportApiController::class, 'salesInvoicePdf']);
    Route::get('/export/masters/{type}/excel', [ExportApiController::class, 'masterExcel']);
    Route::get('/export/masters/{type}/pdf', [ExportApiController::class, 'masterPdf']);

    // Tax rates
    Route::get('/tax-rates', [TaxRateApiController::class, 'index']);
    Route::post('/tax-rates', [TaxRateApiController::class, 'store']);
    Route::get('/tax-rates/dropdown', [TaxRateApiController::class, 'dropdown']);
    Route::get('/tax-rates/{id}', [TaxRateApiController::class, 'show']);
    Route::put('/tax-rates/{id}', [TaxRateApiController::class, 'update']);
    Route::delete('/tax-rates/{id}', [TaxRateApiController::class, 'destroy']);
    Route::patch('/tax-rates/{id}/status', [TaxRateApiController::class, 'status']);

    // Item categories
    Route::get('/item-categories', [ItemCategoryApiController::class, 'index']);
    Route::post('/item-categories', [ItemCategoryApiController::class, 'store']);
    Route::get('/item-categories/dropdown', [ItemCategoryApiController::class, 'dropdown']);
    Route::get('/item-categories/{id}', [ItemCategoryApiController::class, 'show']);
    Route::put('/item-categories/{id}', [ItemCategoryApiController::class, 'update']);
    Route::delete('/item-categories/{id}', [ItemCategoryApiController::class, 'destroy']);
    Route::patch('/item-categories/{id}/status', [ItemCategoryApiController::class, 'status']);

    // Items
    Route::get('/items', [ItemApiController::class, 'index']);
    Route::post('/items', [ItemApiController::class, 'store']);
    Route::get('/items/low-stock', [ItemApiController::class, 'lowStock']);
    Route::get('/items/dropdown', [ItemApiController::class, 'dropdown']);
    Route::get('/items/{id}', [ItemApiController::class, 'show']);
    Route::put('/items/{id}', [ItemApiController::class, 'update']);
    Route::delete('/items/{id}', [ItemApiController::class, 'destroy']);
    Route::patch('/items/{id}/status', [ItemApiController::class, 'status']);

    // Sales invoices
    Route::get('/sales-invoices', [SalesInvoiceApiController::class, 'index']);
    Route::post('/sales-invoices', [SalesInvoiceApiController::class, 'store']);
    Route::get('/sales-invoices/overdue', [SalesInvoiceApiController::class, 'overdue']);
    Route::get('/sales-invoices/{id}', [SalesInvoiceApiController::class, 'show']);
    Route::put('/sales-invoices/{id}', [SalesInvoiceApiController::class, 'update']);
    Route::delete('/sales-invoices/{id}', [SalesInvoiceApiController::class, 'destroy']);
    Route::post('/sales-invoices/{id}/payment', [SalesInvoiceApiController::class, 'payment']);
    Route::get('/sales-invoices/{id}/pdf', [SalesInvoiceApiController::class, 'exportPdf']);

    // Service sales invoices (same resource as sales with invoice_type=service)
    Route::get('/service-sales-invoices', [SalesInvoiceApiController::class, 'indexService']);
    Route::post('/service-sales-invoices', [SalesInvoiceApiController::class, 'storeService']);
    Route::get('/service-sales-invoices/{id}', [SalesInvoiceApiController::class, 'showService']);
    Route::put('/service-sales-invoices/{id}', [SalesInvoiceApiController::class, 'updateService']);
    Route::delete('/service-sales-invoices/{id}', [SalesInvoiceApiController::class, 'destroyService']);
    Route::post('/service-sales-invoices/{id}/payment', [SalesInvoiceApiController::class, 'paymentService']);

    // Purchase invoices
    Route::get('/purchase-invoices', [PurchaseInvoiceApiController::class, 'index']);
    Route::post('/purchase-invoices', [PurchaseInvoiceApiController::class, 'store']);
    Route::get('/purchase-invoices/{id}', [PurchaseInvoiceApiController::class, 'show']);
    Route::put('/purchase-invoices/{id}', [PurchaseInvoiceApiController::class, 'update']);
    Route::delete('/purchase-invoices/{id}', [PurchaseInvoiceApiController::class, 'destroy']);
    Route::post('/purchase-invoices/{id}/payment', [PurchaseInvoiceApiController::class, 'payment']);

    // Subscriptions
    Route::get('/subscriptions/plans', [SubscriptionApiController::class, 'plans']);
    Route::get('/subscriptions/current', [SubscriptionApiController::class, 'current']);
    Route::post('/subscriptions/subscribe', [SubscriptionApiController::class, 'subscribe']);
    Route::post('/subscriptions/verify-payment', [SubscriptionApiController::class, 'verifyPayment']);
    Route::post('/subscriptions/change-plan', [SubscriptionApiController::class, 'changePlan']);
    Route::post('/subscriptions/cancel', [SubscriptionApiController::class, 'cancel']);
    Route::get('/subscriptions/invoices', [SubscriptionApiController::class, 'invoices']);
    Route::get('/subscriptions/payments', [SubscriptionApiController::class, 'payments']);

    // Themes
    Route::get('/themes/current', [ThemeApiController::class, 'current']);
    Route::get('/themes', [ThemeApiController::class, 'themes']);
    Route::put('/themes', [ThemeApiController::class, 'update']);
    Route::post('/themes/apply', [ThemeApiController::class, 'apply']);
    Route::post('/themes/toggle-dark-mode', [ThemeApiController::class, 'toggleDarkMode']);

    // Offline sync (mobile)
    Route::post('/sync/upload', [SyncApiController::class, 'upload']);
    Route::post('/sync/run', [SyncApiController::class, 'run']);
    Route::get('/sync/download', [SyncApiController::class, 'download']);
    Route::get('/sync/bootstrap', [SyncApiController::class, 'bootstrap']);
    Route::get('/sync/status', [SyncApiController::class, 'status']);

    // Notifications
    Route::get('/notifications', [NotificationApiController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationApiController::class, 'unreadCount']);
    Route::patch('/notifications/{id}/read', [NotificationApiController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationApiController::class, 'markAllAsRead']);

    // Audit logs
    Route::get('/audit-logs', [AuditLogApiController::class, 'index']);
    Route::get('/audit-logs/{id}', [AuditLogApiController::class, 'show']);

    // Support tickets
    Route::get('/support-tickets', [SupportTicketApiController::class, 'index']);
    Route::post('/support-tickets', [SupportTicketApiController::class, 'store']);
    Route::get('/support-tickets/{id}', [SupportTicketApiController::class, 'show']);
    Route::post('/support-tickets/{id}/reply', [SupportTicketApiController::class, 'reply']);
    Route::patch('/support-tickets/{id}/status', [SupportTicketApiController::class, 'updateStatus']);

    // Device registration (push + sync tracking)
    Route::post('/devices/register', [DeviceApiController::class, 'register']);
});
