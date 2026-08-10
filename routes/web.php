<?php

use App\Http\Controllers\WebsiteController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\FinancialYearController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\PartyController;
use App\Http\Controllers\Admin\VoucherController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\LedgerHistoryController;
use App\Http\Controllers\Admin\TaxRateController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\ItemCategoryController;
use App\Http\Controllers\Admin\SalesInvoiceController;
use App\Http\Controllers\Admin\PurchaseInvoiceController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\SubscriptionPlanController;
use App\Http\Controllers\Admin\CompanyApprovalController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\CmsPageController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\TestimonialController;
use App\Http\Controllers\Admin\ContactSubmissionController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Admin\DatabaseBackupController;
use App\Http\Controllers\BackupLinkController;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;

/*
|
|--------------------------------------------------------------------------
| Public Website Routes
|
|--------------------------------------------------------------------------
*/
Route::get('/', [WebsiteController::class, 'home'])->name('website.home');
Route::get('/features', [WebsiteController::class, 'features'])->name('website.features');
Route::get('/pricing', [WebsiteController::class, 'pricing'])->name('website.pricing');
Route::get('/faq', [WebsiteController::class, 'faq'])->name('website.faq');
Route::get('/about', [WebsiteController::class, 'about'])->name('website.about');
Route::get('/contact', [WebsiteController::class, 'contact'])->name('website.contact');
Route::post('/contact', [WebsiteController::class, 'submitContact'])->name('website.contact.submit');
Route::get('/privacy-policy', [WebsiteController::class, 'privacy'])->name('website.privacy');
Route::get('/terms', [WebsiteController::class, 'terms'])->name('website.terms');

// User guide (HTML file lives in project root, not public/)
Route::get('/user-guide', function () {
    $path = base_path('USER_GUIDE.html');

    abort_unless(is_file($path), 404);

    return response()->file($path, ['Content-Type' => 'text/html; charset=UTF-8']);
})->name('user-guide');

Route::redirect('/user_guide', '/user-guide');
Route::get('/USER_GUIDE.html', fn () => redirect()->route('user-guide'));

// Public webhook endpoints (Razorpay)
Route::post('/webhooks/razorpay', [WebhookController::class, 'razorpay'])->name('webhooks.razorpay');

Route::get('/backup/download/{company}', [BackupLinkController::class, 'download'])
    ->middleware(['signed', 'throttle:10,1'])
    ->name('backup.signed-download');

// Registration
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');

// Location AJAX routes (for cascading dropdowns)
Route::get('/api/locations/countries', [LocationController::class, 'countries'])->name('api.locations.countries');
Route::get('/api/locations/{countryId}/states', [LocationController::class, 'states'])->name('api.locations.states');
Route::get('/api/locations/{stateId}/cities', [LocationController::class, 'cities'])->name('api.locations.cities');

/*
|--------------------------------------------------------------------------
| Admin Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    // Authenticated routes
    Route::middleware('auth')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard')
            ->middleware(CheckPermission::class . ':dashboard.view');

        /*
        |--------------------------------------------------------------------------
        | Roles & Permissions Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':roles.view')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
            Route::get('roles/create', [RoleController::class, 'create'])
                ->name('roles.create')
                ->middleware(CheckPermission::class . ':roles.create');
            Route::post('roles', [RoleController::class, 'store'])
                ->name('roles.store')
                ->middleware(CheckPermission::class . ':roles.create');
            Route::get('roles/{role}/edit', [RoleController::class, 'edit'])
                ->name('roles.edit')
                ->middleware(CheckPermission::class . ':roles.edit');
            Route::put('roles/{role}', [RoleController::class, 'update'])
                ->name('roles.update')
                ->middleware(CheckPermission::class . ':roles.edit');
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])
                ->name('roles.destroy')
                ->middleware(CheckPermission::class . ':roles.delete');
            Route::patch('roles/{role}/status', [RoleController::class, 'changeStatus'])
                ->name('roles.status')
                ->middleware(CheckPermission::class . ':roles.edit');
        });

        /*
        |--------------------------------------------------------------------------
        | Settings Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':settings.view')->group(function () {
            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::put('settings/company', [SettingsController::class, 'updateCompany'])
                ->name('settings.company')
                ->middleware(CheckPermission::class . ':settings.edit');
            Route::put('settings/theme', [SettingsController::class, 'updateTheme'])
                ->name('settings.theme')
                ->middleware(CheckPermission::class . ':settings.edit');
            Route::put('settings/accounting', [SettingsController::class, 'updateAccounting'])
                ->name('settings.accounting')
                ->middleware(CheckPermission::class . ':settings.edit');
            Route::get('settings/theme-css', [SettingsController::class, 'getThemeCss'])
                ->name('settings.theme-css');
            Route::get('settings/backup/download', [DatabaseBackupController::class, 'download'])
                ->name('settings.backup.download')
                ->middleware(CheckPermission::class . ':settings.edit');
            Route::post('settings/backup/restore', [DatabaseBackupController::class, 'restore'])
                ->name('settings.backup.restore')
                ->middleware(CheckPermission::class . ':settings.edit');
            Route::put('settings/backup/automation', [DatabaseBackupController::class, 'updateAutomation'])
                ->name('settings.backup.automation')
                ->middleware(CheckPermission::class . ':settings.edit');
        });

        // User Profile
        Route::get('profile', [SettingsController::class, 'profile'])->name('profile');
        Route::put('profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        Route::put('profile/password', [SettingsController::class, 'changePassword'])->name('profile.password');

        /*
        |--------------------------------------------------------------------------
        | Financial Years Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':financial-years.view')->group(function () {
            Route::get('financial-years', [FinancialYearController::class, 'index'])->name('financial-years.index');
            Route::post('financial-years', [FinancialYearController::class, 'store'])
                ->name('financial-years.store')
                ->middleware(CheckPermission::class . ':financial-years.create');
            Route::patch('financial-years/{id}/set-current', [FinancialYearController::class, 'setAsCurrent'])
                ->name('financial-years.set-current');
            Route::patch('financial-years/{id}/close', [FinancialYearController::class, 'close'])
                ->name('financial-years.close')
                ->middleware(CheckPermission::class . ':financial-years.close');
            Route::patch('financial-years/{id}/restore', [FinancialYearController::class, 'restore'])
                ->name('financial-years.restore')
                ->middleware(CheckPermission::class . ':financial-years.close');
            Route::delete('financial-years/{id}', [FinancialYearController::class, 'destroy'])
                ->name('financial-years.destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Accounts Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':accounts.view')->group(function () {
            Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
            Route::get('accounts/create', [AccountController::class, 'create'])
                ->name('accounts.create')
                ->middleware(CheckPermission::class . ':accounts.create');
            Route::post('accounts', [AccountController::class, 'store'])
                ->name('accounts.store')
                ->middleware(CheckPermission::class . ':accounts.create');
            Route::get('accounts/{account}/edit', [AccountController::class, 'edit'])
                ->name('accounts.edit')
                ->middleware(CheckPermission::class . ':accounts.edit');
            Route::put('accounts/{account}', [AccountController::class, 'update'])
                ->name('accounts.update')
                ->middleware(CheckPermission::class . ':accounts.edit');
            Route::delete('accounts/{account}', [AccountController::class, 'destroy'])
                ->name('accounts.destroy')
                ->middleware(CheckPermission::class . ':accounts.delete');
            Route::patch('accounts/{account}/status', [AccountController::class, 'changeStatus'])
                ->name('accounts.status')
                ->middleware(CheckPermission::class . ':accounts.edit');
            Route::get('accounts/by-type', [AccountController::class, 'getByType'])->name('accounts.by-type');
            Route::get('accounts/tree', [AccountController::class, 'tree'])->name('accounts.tree');
            Route::get('accounts/export/excel', [AccountController::class, 'exportExcel'])
                ->name('accounts.export-excel')
                ->middleware(CheckPermission::class . ':accounts.export');
            Route::get('accounts/export/pdf', [AccountController::class, 'exportPdf'])
                ->name('accounts.export-pdf')
                ->middleware(CheckPermission::class . ':accounts.export');
        });

        /*
        |--------------------------------------------------------------------------
        | Parties Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':parties.view')->group(function () {
            Route::get('parties', [PartyController::class, 'index'])->name('parties.index');
            Route::get('parties/create', [PartyController::class, 'create'])
                ->name('parties.create')
                ->middleware(CheckPermission::class . ':parties.create');
            Route::post('parties', [PartyController::class, 'store'])
                ->name('parties.store')
                ->middleware(CheckPermission::class . ':parties.create');
            Route::get('parties/{party}/edit', [PartyController::class, 'edit'])
                ->name('parties.edit')
                ->middleware(CheckPermission::class . ':parties.edit');
            Route::put('parties/{party}', [PartyController::class, 'update'])
                ->name('parties.update')
                ->middleware(CheckPermission::class . ':parties.edit');
            Route::delete('parties/{party}', [PartyController::class, 'destroy'])
                ->name('parties.destroy')
                ->middleware(CheckPermission::class . ':parties.delete');
            Route::patch('parties/{party}/status', [PartyController::class, 'changeStatus'])
                ->name('parties.status')
                ->middleware(CheckPermission::class . ':parties.edit');
            Route::get('parties/by-type', [PartyController::class, 'getByType'])->name('parties.by-type');
            Route::get('parties/{party}/export/excel', [PartyController::class, 'exportExcel'])
                ->name('parties.export-excel')
                ->whereNumber('party');
            Route::get('parties/{party}/export/pdf', [PartyController::class, 'exportPdf'])
                ->name('parties.export-pdf')
                ->whereNumber('party');
            Route::get('parties/{party}', [PartyController::class, 'show'])
                ->name('parties.show')
                ->whereNumber('party');
        });

        /*
        |--------------------------------------------------------------------------
        | Vouchers Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':vouchers.view')->group(function () {
            Route::get('vouchers', [VoucherController::class, 'index'])->name('vouchers.index');
            Route::get('vouchers/type/{type}', [VoucherController::class, 'index'])
                ->name('vouchers.type')
                ->whereIn('type', ['income', 'expense', 'payment', 'receipt', 'journal']);
            Route::get('vouchers/create/{type}', [VoucherController::class, 'create'])
                ->name('vouchers.create')
                ->whereIn('type', ['income', 'expense', 'payment', 'receipt', 'journal'])
                ->middleware(CheckPermission::class . ':vouchers.create');
            Route::post('vouchers', [VoucherController::class, 'store'])
                ->name('vouchers.store')
                ->middleware(CheckPermission::class . ':vouchers.create');
            Route::get('vouchers/{voucher}', [VoucherController::class, 'show'])->name('vouchers.show');
            Route::get('vouchers/{voucher}/edit', [VoucherController::class, 'edit'])
                ->name('vouchers.edit')
                ->middleware(CheckPermission::class . ':vouchers.edit');
            Route::put('vouchers/{voucher}', [VoucherController::class, 'update'])
                ->name('vouchers.update')
                ->middleware(CheckPermission::class . ':vouchers.edit');
            Route::delete('vouchers/{voucher}', [VoucherController::class, 'destroy'])
                ->name('vouchers.destroy')
                ->middleware(CheckPermission::class . ':vouchers.delete');
            Route::patch('vouchers/{voucher}/post', [VoucherController::class, 'post'])
                ->name('vouchers.post')
                ->middleware(CheckPermission::class . ':vouchers.approve');
            Route::patch('vouchers/{voucher}/cancel', [VoucherController::class, 'cancel'])
                ->name('vouchers.cancel')
                ->middleware(CheckPermission::class . ':vouchers.approve');
        });

        /*
        |--------------------------------------------------------------------------
        | Reports Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':reports.view')->group(function () {
            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/day-book', [ReportController::class, 'dayBook'])->name('reports.day-book');
            Route::get('reports/ledger', [ReportController::class, 'ledger'])->name('reports.ledger');
            Route::get('reports/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial-balance');
            Route::get('reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
            Route::get('reports/receipt-payment', [ReportController::class, 'receiptPayment'])->name('reports.receipt-payment');
            Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
            Route::get('reports/debtors-outstanding', [ReportController::class, 'debtorsOutstanding'])->name('reports.debtors-outstanding');
            Route::get('reports/creditors-outstanding', [ReportController::class, 'creditorsOutstanding'])->name('reports.creditors-outstanding');
            Route::get('reports/aging-summary', [ReportController::class, 'agingSummary'])->name('reports.aging-summary');
            // Legacy: thin Cash Flow removed — Receipt & Payment replaces it
            Route::redirect('reports/cash-flow', '/admin/reports/receipt-payment')->name('reports.cash-flow');
            Route::get('ledgers/{ledger}/history', [LedgerHistoryController::class, 'show'])
                ->name('ledgers.history');
        });

        /*
        |--------------------------------------------------------------------------
        | Audit Logs Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':audit-logs.view')->group(function () {
            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::get('audit-logs/{id}', [AuditLogController::class, 'show'])->name('audit-logs.show');
        });

        /*
        |--------------------------------------------------------------------------
        | Notifications & Support
        |--------------------------------------------------------------------------
        */
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

        Route::get('support-tickets', [SupportTicketController::class, 'index'])->name('support-tickets.index');
        Route::get('support-tickets/create', [SupportTicketController::class, 'create'])->name('support-tickets.create');
        Route::post('support-tickets', [SupportTicketController::class, 'store'])->name('support-tickets.store');
        Route::get('support-tickets/{id}', [SupportTicketController::class, 'show'])->name('support-tickets.show');
        Route::post('support-tickets/{id}/reply', [SupportTicketController::class, 'reply'])->name('support-tickets.reply');
        Route::patch('support-tickets/{id}/status', [SupportTicketController::class, 'updateStatus'])->name('support-tickets.status');

        /*
        |--------------------------------------------------------------------------
        | Export Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':reports.export')->group(function () {
            Route::get('export/profit-loss/pdf', [ExportController::class, 'profitLossPdf'])->name('export.profit-loss.pdf');
            Route::get('export/balance-sheet/pdf', [ExportController::class, 'balanceSheetPdf'])->name('export.balance-sheet.pdf');
            Route::get('export/trial-balance/pdf', [ExportController::class, 'trialBalancePdf'])->name('export.trial-balance.pdf');
            Route::get('export/receipt-payment/pdf', [ExportController::class, 'receiptPaymentPdf'])->name('export.receipt-payment.pdf');
            // Legacy cash-flow PDF → Receipt & Payment
            Route::redirect('export/cash-flow/pdf', '/admin/reports/receipt-payment')->name('export.cash-flow.pdf');
            Route::get('export/ledger/pdf', [ExportController::class, 'ledgerPdf'])->name('export.ledger.pdf');
            Route::get('export/day-book/pdf', [ExportController::class, 'dayBookPdf'])->name('export.day-book.pdf');
            Route::get('export/debtors-outstanding/pdf', [ExportController::class, 'debtorsOutstandingPdf'])->name('export.debtors-outstanding.pdf');
            Route::get('export/creditors-outstanding/pdf', [ExportController::class, 'creditorsOutstandingPdf'])->name('export.creditors-outstanding.pdf');
            Route::get('export/aging-summary/pdf', [ExportController::class, 'agingSummaryPdf'])->name('export.aging-summary.pdf');
            Route::get('export/voucher/{id}/pdf', [ExportController::class, 'voucherPdf'])->name('export.voucher.pdf');
            Route::get('export/{type}/excel', [ExportController::class, 'excel'])->name('export.excel');
            Route::get('export/{type}/csv', [ExportController::class, 'csv'])->name('export.csv');
        });

        /*
        |--------------------------------------------------------------------------
        | Tax Rate Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':tax-rates.view')->group(function () {
            Route::get('tax-rates', [TaxRateController::class, 'index'])->name('tax-rates.index');
            Route::get('tax-rates/create', [TaxRateController::class, 'create'])
                ->name('tax-rates.create')
                ->middleware(CheckPermission::class . ':tax-rates.create');
            Route::post('tax-rates', [TaxRateController::class, 'store'])
                ->name('tax-rates.store')
                ->middleware(CheckPermission::class . ':tax-rates.create');
            Route::get('tax-rates/{id}/edit', [TaxRateController::class, 'edit'])
                ->name('tax-rates.edit')
                ->middleware(CheckPermission::class . ':tax-rates.edit');
            Route::put('tax-rates/{id}', [TaxRateController::class, 'update'])
                ->name('tax-rates.update')
                ->middleware(CheckPermission::class . ':tax-rates.edit');
            Route::delete('tax-rates/{id}', [TaxRateController::class, 'destroy'])
                ->name('tax-rates.destroy')
                ->middleware(CheckPermission::class . ':tax-rates.delete');
            Route::patch('tax-rates/{id}/status', [TaxRateController::class, 'status'])
                ->name('tax-rates.status')
                ->middleware(CheckPermission::class . ':tax-rates.edit');
            Route::get('tax-rates/dropdown', [TaxRateController::class, 'dropdown'])->name('tax-rates.dropdown');
        });

        /*
        |--------------------------------------------------------------------------
        | Item Category Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':accounts.view')->group(function () {
            Route::get('item-categories', [ItemCategoryController::class, 'index'])->name('item-categories.index');
            Route::get('item-categories/create', [ItemCategoryController::class, 'create'])
                ->name('item-categories.create')
                ->middleware(CheckPermission::class . ':accounts.create');
            Route::post('item-categories', [ItemCategoryController::class, 'store'])
                ->name('item-categories.store')
                ->middleware(CheckPermission::class . ':accounts.create');
            Route::get('item-categories/{id}/edit', [ItemCategoryController::class, 'edit'])
                ->name('item-categories.edit')
                ->middleware(CheckPermission::class . ':accounts.edit');
            Route::put('item-categories/{id}', [ItemCategoryController::class, 'update'])
                ->name('item-categories.update')
                ->middleware(CheckPermission::class . ':accounts.edit');
            Route::delete('item-categories/{id}', [ItemCategoryController::class, 'destroy'])
                ->name('item-categories.destroy')
                ->middleware(CheckPermission::class . ':accounts.delete');
            Route::patch('item-categories/{id}/status', [ItemCategoryController::class, 'status'])
                ->name('item-categories.status')
                ->middleware(CheckPermission::class . ':accounts.edit');
            Route::get('item-categories/dropdown', [ItemCategoryController::class, 'dropdown'])->name('item-categories.dropdown');
        });

        /*
        |--------------------------------------------------------------------------
        | Item Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':accounts.view')->group(function () {
            Route::get('items', [ItemController::class, 'index'])->name('items.index');
            Route::get('items/create', [ItemController::class, 'create'])
                ->name('items.create')
                ->middleware(CheckPermission::class . ':accounts.create');
            Route::post('items', [ItemController::class, 'store'])
                ->name('items.store')
                ->middleware(CheckPermission::class . ':accounts.create');
            Route::get('items/{id}/edit', [ItemController::class, 'edit'])
                ->name('items.edit')
                ->middleware(CheckPermission::class . ':accounts.edit');
            Route::put('items/{id}', [ItemController::class, 'update'])
                ->name('items.update')
                ->middleware(CheckPermission::class . ':accounts.edit');
            Route::delete('items/{id}', [ItemController::class, 'destroy'])
                ->name('items.destroy')
                ->middleware(CheckPermission::class . ':accounts.delete');
            Route::patch('items/{id}/status', [ItemController::class, 'status'])
                ->name('items.status')
                ->middleware(CheckPermission::class . ':accounts.edit');
            Route::get('items/dropdown', [ItemController::class, 'dropdown'])->name('items.dropdown');
            Route::get('items/low-stock', [ItemController::class, 'lowStock'])->name('items.low-stock');
            Route::get('items/{id}/export/excel', [ItemController::class, 'exportExcel'])
                ->name('items.export-excel')
                ->whereNumber('id');
            Route::get('items/{id}/export/pdf', [ItemController::class, 'exportPdf'])
                ->name('items.export-pdf')
                ->whereNumber('id');
            Route::get('items/{id}', [ItemController::class, 'show'])
                ->name('items.show')
                ->whereNumber('id');
        });

        /*
        |--------------------------------------------------------------------------
        | Sales Invoice Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':vouchers.view')->group(function () {
            Route::get('sales-invoices', [SalesInvoiceController::class, 'index'])->name('sales-invoices.index');
            Route::get('sales-invoices/create', [SalesInvoiceController::class, 'create'])->name('sales-invoices.create');
            Route::post('sales-invoices/quick-add-item', [SalesInvoiceController::class, 'quickAddItem'])->name('sales-invoices.quick-add-item');
            Route::post('sales-invoices', [SalesInvoiceController::class, 'store'])->name('sales-invoices.store');
            Route::get('sales-invoices/{id}', [SalesInvoiceController::class, 'show'])->name('sales-invoices.show');
            Route::get('sales-invoices/{id}/pdf', [SalesInvoiceController::class, 'exportPdf'])->name('sales-invoices.pdf');
            Route::get('sales-invoices/{id}/edit', [SalesInvoiceController::class, 'edit'])->name('sales-invoices.edit');
            Route::put('sales-invoices/{id}', [SalesInvoiceController::class, 'update'])->name('sales-invoices.update');
            Route::delete('sales-invoices/{id}', [SalesInvoiceController::class, 'destroy'])->name('sales-invoices.destroy');
            Route::post('sales-invoices/{id}/cancel', [SalesInvoiceController::class, 'cancel'])->name('sales-invoices.cancel');
            Route::post('sales-invoices/{id}/payment', [SalesInvoiceController::class, 'payment'])->name('sales-invoices.payment');
            Route::get('sales-invoices/overdue', [SalesInvoiceController::class, 'overdue'])->name('sales-invoices.overdue');
        });

        /*
        |--------------------------------------------------------------------------
        | Purchase Invoice Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckPermission::class . ':vouchers.view')->group(function () {
            Route::get('purchase-invoices', [PurchaseInvoiceController::class, 'index'])->name('purchase-invoices.index');
            Route::get('purchase-invoices/create', [PurchaseInvoiceController::class, 'create'])->name('purchase-invoices.create');
            Route::post('purchase-invoices/quick-add-item', [PurchaseInvoiceController::class, 'quickAddItem'])->name('purchase-invoices.quick-add-item');
            Route::post('purchase-invoices', [PurchaseInvoiceController::class, 'store'])->name('purchase-invoices.store');
            Route::get('purchase-invoices/{id}', [PurchaseInvoiceController::class, 'show'])->name('purchase-invoices.show');
            Route::get('purchase-invoices/{id}/edit', [PurchaseInvoiceController::class, 'edit'])->name('purchase-invoices.edit');
            Route::put('purchase-invoices/{id}', [PurchaseInvoiceController::class, 'update'])->name('purchase-invoices.update');
            Route::delete('purchase-invoices/{id}', [PurchaseInvoiceController::class, 'destroy'])->name('purchase-invoices.destroy');
            Route::post('purchase-invoices/{id}/cancel', [PurchaseInvoiceController::class, 'cancel'])->name('purchase-invoices.cancel');
            Route::post('purchase-invoices/{id}/payment', [PurchaseInvoiceController::class, 'payment'])->name('purchase-invoices.payment');
        });

        /*
        |--------------------------------------------------------------------------
        | Subscription Routes
        |--------------------------------------------------------------------------
        */
        Route::get('subscriptions/plans', [SubscriptionController::class, 'plans'])->name('subscriptions.plans');
        Route::get('subscriptions/current', [SubscriptionController::class, 'current'])->name('subscriptions.current');
        Route::post('subscriptions/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
        Route::post('subscriptions/verify-payment', [SubscriptionController::class, 'verifyPayment'])->name('subscriptions.verify-payment');
        Route::post('subscriptions/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
        Route::post('subscriptions/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::get('subscriptions/invoices', [SubscriptionController::class, 'invoices'])->name('subscriptions.invoices');
        Route::get('subscriptions/payments', [SubscriptionController::class, 'payments'])->name('subscriptions.payments');

        // Subscription Plans CRUD (Super Admin)
        Route::middleware(CheckRole::class . ':superadmin')->group(function () {
            Route::get('subscription-plans', [SubscriptionPlanController::class, 'index'])->name('subscription-plans.index');
            Route::post('subscription-plans', [SubscriptionPlanController::class, 'store'])->name('subscription-plans.store');
            Route::get('subscription-plans/{id}', [SubscriptionPlanController::class, 'show'])->name('subscription-plans.show');
            Route::put('subscription-plans/{id}', [SubscriptionPlanController::class, 'update'])->name('subscription-plans.update');
            Route::delete('subscription-plans/{id}', [SubscriptionPlanController::class, 'destroy'])->name('subscription-plans.destroy');
            Route::patch('subscription-plans/{id}/status', [SubscriptionPlanController::class, 'toggleStatus'])->name('subscription-plans.status');
        });

        // Company Approval (Super Admin)
        Route::middleware(CheckRole::class . ':superadmin')->group(function () {
            Route::get('companies/approval', [CompanyApprovalController::class, 'index'])->name('companies.approval');
            Route::patch('companies/{id}/approve', [CompanyApprovalController::class, 'approve'])->name('companies.approve');
            Route::patch('companies/{id}/reject', [CompanyApprovalController::class, 'reject'])->name('companies.reject');
        });

        // Full Company Management (Super Admin)
        Route::middleware(CheckRole::class . ':superadmin')->group(function () {
            Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
            Route::get('companies/{id}', [CompanyController::class, 'show'])->name('companies.show');
            Route::get('companies/{id}/edit', [CompanyController::class, 'edit'])->name('companies.edit');
            Route::put('companies/{id}', [CompanyController::class, 'update'])->name('companies.update');
            Route::delete('companies/{id}', [CompanyController::class, 'destroy'])->name('companies.destroy');
        });

        Route::middleware(CheckRole::class . ':superadmin')->group(function () {
            Route::get('platform/subscriptions', [SubscriptionController::class, 'platformIndex'])->name('platform-subscriptions.index');
            Route::get('platform/payments', [SubscriptionController::class, 'platformPayments'])->name('platform-subscriptions.payments');
        });

        /*
        |--------------------------------------------------------------------------
        | Theme Routes
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckRole::class . ':superadmin')->group(function () {
            Route::get('themes', [ThemeController::class, 'index'])->name('themes.index');
            Route::put('themes', [ThemeController::class, 'update'])->name('themes.update');
            Route::post('themes/apply', [ThemeController::class, 'apply'])->name('themes.apply');
            Route::post('themes/toggle-dark-mode', [ThemeController::class, 'toggleDarkMode'])->name('themes.toggle-dark-mode');
            Route::get('themes/current', [ThemeController::class, 'current'])->name('themes.current');
        });

        /*
        |--------------------------------------------------------------------------
        | CMS Routes (Website Content Management)
        |--------------------------------------------------------------------------
        */
        Route::middleware(CheckRole::class . ':superadmin')->prefix('cms')->name('cms.')->group(function () {
            // CMS Pages
            Route::get('pages', [CmsPageController::class, 'index'])->name('pages.index');
            Route::get('pages/{page}/edit', [CmsPageController::class, 'edit'])->name('pages.edit');
            Route::put('pages/{page}', [CmsPageController::class, 'update'])->name('pages.update');
            Route::patch('pages/{page}/toggle-nav', [CmsPageController::class, 'toggleNav'])->name('pages.toggle-nav');

            // FAQs
            Route::get('faqs', [FaqController::class, 'index'])->name('faqs.index');
            Route::post('faqs', [FaqController::class, 'store'])->name('faqs.store');
            Route::put('faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
            Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');
            Route::patch('faqs/{faq}/toggle', [FaqController::class, 'toggleStatus'])->name('faqs.toggle');

            // Testimonials
            Route::get('testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');
            Route::post('testimonials', [TestimonialController::class, 'store'])->name('testimonials.store');
            Route::put('testimonials/{testimonial}', [TestimonialController::class, 'update'])->name('testimonials.update');
            Route::delete('testimonials/{testimonial}', [TestimonialController::class, 'destroy'])->name('testimonials.destroy');
            Route::patch('testimonials/{testimonial}/toggle', [TestimonialController::class, 'toggleStatus'])->name('testimonials.toggle');
        });

        // Contact Submissions
        Route::middleware(CheckRole::class . ':superadmin')->group(function () {
            Route::get('contacts', [ContactSubmissionController::class, 'index'])->name('contacts.index');
            Route::get('contacts/{contact}', [ContactSubmissionController::class, 'show'])->name('contacts.show');
            Route::put('contacts/{contact}', [ContactSubmissionController::class, 'update'])->name('contacts.update');
            Route::delete('contacts/{contact}', [ContactSubmissionController::class, 'destroy'])->name('contacts.destroy');
            Route::get('contacts/counts', [ContactSubmissionController::class, 'counts'])->name('contacts.counts');
        });
    });
});

/*
|--------------------------------------------------------------------------
| Admin Auth Routes (via laravel/ui)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    // Guest routes (not logged in)
    Route::middleware('guest')->group(function () {
        // Login
        Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

        // Register
        Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
        Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');

        // Forgot Password
        Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
        Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
        Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
    });

    // Authenticated routes
    Route::middleware('auth')->group(function () {
        // Logout
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

        // Email Verification
        Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
        Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify');
        Route::post('/email/resend', [VerificationController::class, 'resend'])->name('verification.resend');

        // Confirm Password
        Route::get('/password/confirm', [ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
        Route::post('/password/confirm', [ConfirmPasswordController::class, 'confirm']);
    });
});
