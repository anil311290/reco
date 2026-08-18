# Reco Project Context

Read this first. Inspect only the module files named here before widening a search.

## Purpose and stack

- Reco is a multi-company, offline-sync-ready accounting SaaS with an admin website and mobile REST API. Do not modify `RecoFlutter/`.
- Laravel 13.8, PHP 8.3+, MySQL, Sanctum, Bootstrap 5, jQuery, Yajra DataTables, DomPDF, and Laravel Excel.
- Admin and public website routes: `routes/web.php`. Mobile API routes: `routes/api.php` under `/api/v1`.
- Leave `.env`, `vendor/`, unrelated modules, and existing user changes untouched. Ask before migrations or other database-changing commands.

## Architecture

- Controllers validate, call a service, and return a response only.
- Business workflows belong in `app/Services`; persistence/query logic belongs in `app/Repositories`; bind interfaces in `app/Providers/AppServiceProvider.php`.
- Use Form Requests, API Resources, policies/permissions, transactions for multi-record accounting writes, and existing common UI/AJAX helpers.
- Business records are company-scoped. Preserve `company_id`, `uuid`, `version`, audit IP/user fields, soft deletes, and restore behavior where the module supports them.
- API responses must be JSON with suitable status codes; protected endpoints use `auth:sanctum`.

## Accounting invariants

- The ledger is the accounting source of truth: reports must be derived from ledger entries, not invoice totals.
- Posted vouchers must balance: total debit equals total credit. Respect financial-year/period locks.
- Sales invoices post revenue, tax, inventory/COGS as applicable, and receivables; purchase invoices post expenses/assets, tax, inventory as applicable, and payables.
- Receipts/payments and invoice settlements use `PaymentInvoiceMapping`; cancellations reverse related postings/mappings rather than deleting accounting history.
- Maintain company and financial-year scope in every lookup, mutation, report, and export.

## Module map

| Area | Primary implementation | Focused tests |
| --- | --- | --- |
| Accounts, parties, vouchers | `AccountService`, `PartyService`, `VoucherService`; matching repositories/controllers | `VoucherLedgerIntegrationTest`, `TallyAccountingFlowTest` |
| Invoices and posting | `SalesInvoiceService`, `PurchaseInvoiceService`, `InvoiceAccountingService` | `InvoiceAccountingPostingTest`, `SalesInvoiceJournalPostingTest` |
| Ledger and reports | `LedgerService`, `ReportService`, `ExportService` | `TrialBalanceAndReceiptPaymentTest`, `ReceiptPaymentReportTest` |
| Invoice-payment mapping | `PaymentInvoiceMappingService` and repository/model | `PaymentInvoiceMappingTest`, `InvoiceSettlementAndPeriodLockTest` |
| Financial periods | `FinancialYearService`, `PeriodLockService` | `OpeningBalanceAndFyCarryForwardTest` |
| Auth, company, security | `AuthService`, `CompanyRoleService`, `LoginHistoryService` | `AuthProfileApiTest`, `CompanyScopedCodesApiTest` |
| Masters | `ItemService`, `ItemCategoryService`, `TaxRateService` | `ServiceItemUnitTest` |
| SaaS, billing, website | `SubscriptionService`, `RazorpayService`, `WebsiteService` | `RegistrationFlowTest` |
| Sync and devices | `SyncService`, `NotificationService`, `UserDevice` | Inspect the matching API controller first |

Web controllers are in `app/Http/Controllers/Admin`; API controllers are in `app/Http/Controllers/Api`; requests/resources follow `app/Http/Requests` and `app/Http/Resources`. Views are in `resources/views`; shared AJAX code is `public/assets/js/common.js`.

## Current implementation note

The payment-to-invoice mapping backend and settlement reports are implemented. Remaining likely work is the multi-invoice payment UI, invoice/voucher settlement display, API validation/tests, and regression coverage. See `IMPLEMENTATION_PROGRESS.md` and `docs/PAYMENT_MAPPING_INDEX.md` only when the task concerns settlement mapping.

## Efficient workflow

1. Read the owning controller, service, repository/model, request, route, and nearest focused test.
2. Make the smallest consistent change; do not perform broad refactors.
3. For admin CRUD, retain Bootstrap/jQuery AJAX, Toastr/SweetAlert2, and DataTables patterns. No full-page form submission or page refresh.
4. Run the closest test first: `/usr/local/bin/php artisan test --filter=<TestClass>`. Then run `git diff --check` and inspect `git diff`.
5. Use `/usr/local/bin/php` explicitly if `php` is absent from the current shell PATH.

## Useful commands

```sh
/usr/local/bin/php artisan test --filter=VoucherLedgerIntegrationTest
/usr/local/bin/php artisan test --filter=PaymentInvoiceMappingTest
/usr/local/bin/php artisan test --filter=InvoiceSettlementAndPeriodLockTest
/usr/local/bin/php artisan route:list --path=api/v1
git diff --check
```
