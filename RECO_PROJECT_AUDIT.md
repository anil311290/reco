# RECO – Offline First Accounting & Receivables Management SaaS
## Complete Project Audit Document

**Document Version:** 1.0
**Audit Date:** June 17, 2026
**Project Codename:** Reco (formerly LedgerPro)
**Prepared For:** Architect Review & Future Development Planning

---

# 1. Project Overview

## 1.1 Project Name
**RECO** (formerly LedgerPro)

## 1.2 Purpose
RECO is an **offline-first Accounting and Receivables Management SaaS platform** designed for Indian businesses. It provides double-entry bookkeeping, invoice management, receivables tracking, GST tax compliance, and WhatsApp-based payment reminders. The application supports both a web admin panel and a mobile-first offline-capable API layer.

## 1.3 Current Development Status
- **Backend (Laravel 12):** ~65% Complete
- **Web Admin Panel:** ~55% Complete
- **API Layer:** ~60% Complete
- **Mobile App:** 0% (API endpoints ready for consumption)
- **Offline Sync Engine:** Schema designed, implementation pending
- **Overall Estimated Completion:** ~50%

## 1.4 Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend Framework | Laravel | 12.x (latest) |
| PHP Version | PHP | 8.3+ |
| Database | MySQL | (configured for port 3306) |
| Authentication (Web) | Laravel UI + Session | Auth scaffolding |
| Authentication (API) | Laravel Sanctum | 4.3 |
| Frontend CSS | Bootstrap | 5.x |
| Frontend JS | jQuery + DataTables + Chart.js | Latest |
| API Documentation | L5-Swagger (OpenAPI) | 11.0 |
| PDF Generation | Barryvdh DomPDF | 3.1 |
| Excel Export | Maatwebsite Excel | 3.1 |
| Server-Side Tables | Yajra DataTables | 13.1 |
| Payment Gateway | Razorpay | (integrated) |
| Build Tool | Vite | (Laravel default) |
| Testing | PHPUnit | 12.5 |

## 1.5 Major Features Implemented

### Fully Implemented
1. Authentication system (Web + API login, register, logout, password reset)
2. Company-based multi-tenancy with registration approval flow
3. Roles and Permissions system (4 default roles, 26 permissions)
4. Account Master (Chart of Accounts with hierarchical tree structure)
5. Party Master (Debtors and Creditors with location support)
6. Voucher Management (Income, Expense, Receipt, Payment, Journal, Adjustment)
7. Double-Entry Ledger Engine with running balances
8. Sales Invoice management with line items, tax calculation
9. Purchase Invoice management with line items, tax calculation
10. Bank Account management with default account support
11. Tax Rate management (GST, IGST, CGST/SGST, VAT, Exempt)
12. Item/Inventory management with stock tracking
13. Financial Year management (create, switch, close)
14. Company Settings (name, GST, PAN, currency, timezone)
15. Theme System (colors, fonts, dark mode, per-company customization)
16. Dashboard with statistics, charts, and trends
17. Report generation (P&L, Balance Sheet, Trial Balance, Day Book, Ledger)
18. PDF/Excel/CSV export engine
19. Audit logging for all CRUD operations
20. Login history tracking with device information
21. OTP verification system (email + SMS ready)
22. PIN-based mobile authentication
23. User device management (trust, deactivation)
24. Subscription management (plans, billing, invoices, payments)
25. Razorpay integration (orders, webhooks, payments)
26. WhatsApp message logging
27. Receivable reminder system (automated + manual)
28. Polymorphic attachment system
29. Notification system (in-app, email, SMS, push channels)
30. Website CMS (pages, FAQs, testimonials, contact submissions)
31. Cascading country/state/city dropdowns
32. OpenAPI/Swagger documentation for all API endpoints

### Partially Implemented
1. Sync queue table structure (no sync processing logic)
2. WhatsApp message sending (log table ready, sending not implemented)
3. OTP sending (email/SMS sending methods are TODO stubs)
4. Subscription plan limit enforcement (schema ready, enforcement logic partial)

### Pending Implementation
1. Offline Sync Engine - No sync processing, conflict resolution, or upload/download flow
2. Mobile App - No React Native/Flutter app built yet
3. Background Job Processing - No Jobs/Events/Listeners implemented
4. Email Notifications - Mail templates not built
5. Push Notifications - FCM integration not implemented
6. WhatsApp Business API Integration - Log structure ready, API integration pending
7. GST Return Filing Reports - GSTR-1, GSTR-3B not implemented
8. Recurring Invoices - is_recurring field exists but no automation
9. Multi-currency Support - Currency field exists but no conversion logic
10. Policy-based Authorization - Uses middleware only, no Laravel Policies

---

# 2. Architecture Overview

## 2.1 High Level Architecture Diagram

```
+-----------------------------------------------------+
|                    CLIENT LAYER                       |
|  +--------------+  +--------------+  +--------------+|
|  |  Web Browser  |  | Mobile App   |  |  Future Apps ||
|  |  (Admin Panel)|  | (React Native|  |  (Flutter,   ||
|  |               |  |  / Flutter)  |  |   PWA)       ||
|  +------+-------+  +------+-------+  +------+-------+|
|         |                  |                  |        |
|         | HTTP/HTTPS       | REST API         |REST   |
+---------+------------------+------------------+-------+
          |                  |                  |
+---------+------------------+------------------+-------+
|         v                  v                  v        |
|  +--------------------------------------------------+|
|  |              LARAVEL 12 API SERVER                ||
|  |                                                    ||
|  |  +----------------------------------------------+ ||
|  |  |           MIDDLEWARE LAYER                    | ||
|  |  |  auth:sanctum | CheckPermission | CheckRole  | ||
|  |  +--------------------+-------------------------+ ||
|  |                       |                           ||
|  |  +--------------------v-------------------------+ ||
|  |  |           CONTROLLER LAYER                   | ||
|  |  |  Admin Controllers  |  API Controllers        | ||
|  |  +--------------------+-------------------------+ ||
|  |                       |                           ||
|  |  +--------------------v-------------------------+ ||
|  |  |           SERVICE LAYER                      | ||
|  |  |  21 Service Classes (Business Logic)         | ||
|  |  +--------------------+-------------------------+ ||
|  |                       |                           ||
|  |  +--------------------v-------------------------+ ||
|  |  |         REPOSITORY LAYER                    | ||
|  |  |  6 Repository Classes (Data Access)          | ||
|  |  +--------------------+-------------------------+ ||
|  |                       |                           ||
|  |  +--------------------v-------------------------+ ||
|  |  |         ELOQUENT ORM / MODELS               | ||
|  |  |  42 Model Classes (Data Schema)              | ||
|  |  +--------------------+-------------------------+ ||
|  +-----------------------+--------------------------+|
|                          |                            |
|  +-----------------------v--------------------------+|
|  |                 DATA LAYER                        ||
|  |  +------------+  +------------+  +-----------+   ||
|  |  |  MySQL DB   |  |  Storage   |  |  Queue    |   ||
|  |  | (52 tables) |  | (Files/PDFs)|  | (database)|   ||
|  |  +------------+  +------------+  +-----------+   ||
|  +--------------------------------------------------+|
|                                                      |
|  +--------------------------------------------------+|
|  |            EXTERNAL SERVICES                      ||
|  |  +----------+  +--------+  +----------------+   ||
|  |  | Razorpay  |  | WhatsApp|  |  Email/SMS     |   ||
|  |  | (Payments)|  | Business|  |  (OTP/Notify)  |   ||
|  |  +----------+  +--------+  +----------------+   ||
|  +--------------------------------------------------+|
+------------------------------------------------------+
```

## 2.2 Backend Architecture
- **Framework:** Laravel 12 with PHP 8.3+
- **Pattern:** Repository Pattern + Service Layer Pattern (SOLID principles)
- **Multi-tenancy:** Company-based isolation via company_id on all business tables
- **Authentication:** Dual-mode - Session-based for web, Token-based (Sanctum) for API
- **Middleware:** CheckPermission and CheckRole for authorization
- **Response Format:** Consistent JSON via ResponseHelper ({success, message, data})
- **Database:** MySQL with 52 tables, soft deletes, UUID primary keys, audit fields
- **Offline Sync:** Table structure designed (version, synced_at fields), processing logic pending

## 2.3 Mobile Architecture
- **Current Status:** API endpoints defined and documented (L5-Swagger), mobile app not yet built
- **Target:** React Native or Flutter offline-first mobile app
- **API Base URL:** http://127.0.0.1:8000/api/v1 (local), https://api.reco.app/v1 (production)
- **Authentication:** Sanctum bearer tokens
- **Offline Support:** PIN-based login, app lock, biometric settings
- **Device Management:** UserDevice model tracks device type, trust status, FCM tokens

## 2.4 Sync Architecture (Designed, Not Implemented)
- **Table:** sync_queue - Central sync queue with operation tracking
- **Conflict Detection:** Version-based (local_version vs server_version)
- **Conflict Resolution Strategies:** server_wins, client_wins, manual
- **Operations:** create, update, delete
- **Status Flow:** pending -> processing -> completed/failed
- **Retry Logic:** retry_count / max_retries fields
- **Device Tracking:** device_id per sync entry

## 2.5 SaaS Architecture
- **Multi-tenancy:** company_id foreign key on all business tables
- **Tenant Isolation:** All queries filtered by auth()->user()->company_id
- **Subscription Plans:** Trial, Basic, Pro, Enterprise with limits on users/transactions/accounts/parties
- **Payment Processing:** Razorpay orders, webhooks, and payment tracking
- **Company Approval:** Registration -> Pending Approval -> Active flow
- **Theme Customization:** Per-company theme (colors, fonts, dark mode, logo)

## 2.6 Authentication Flow

### Web Authentication
```
1. User visits /admin/login
2. LoginController::showLoginForm() returns Blade view
3. User submits credentials via AJAX POST
4. LoginController::login() validates via LoginRequest
5. AuthenticatesUser trait verifies credentials
6. Checks: is_active, is_pending (blocks pending companies)
7. Creates session, updates last_login_at/ip
8. LoginHistoryService::recordLogin() logs the event
9. Returns JSON {success, message, redirect: /admin/dashboard}
```

### API Authentication (Sanctum)
```
1. Mobile app sends POST /api/v1/login with {email, password, device_name}
2. AuthController::login() validates via Api\LoginRequest
3. AuthService::login() verifies credentials
4. Creates Sanctum personal access token
5. LoginHistoryService::recordLogin() logs with device info
6. Returns token in response
7. Mobile app stores token
8. Subsequent requests include Authorization: Bearer {token}
```

### PIN Authentication (Mobile)
```
1. Mobile sends POST /api/v1/pin/login with {pin}
2. SecurityApiController::pinLogin() validates via PinLoginRequest
3. Finds user by hashed PIN match
4. Creates Sanctum token
5. Returns token in response
```

## 2.7 Authorization Flow

```
Request -> Middleware -> CheckPermission/CheckRole -> Controller -> Service -> Repository -> Model

1. Route defines middleware: CheckPermission::class . ':dashboard.view'
2. CheckPermission middleware calls $request->user()->hasPermission('dashboard.view')
3. User::hasPermission() checks:
   a. If admin role -> always returns true
   b. Checks roles relationship -> permissions relationship
   c. Matches permission slug
4. If denied -> returns 403 JSON or abort
5. If granted -> proceeds to controller
6. Controller calls appropriate Service
7. Service applies company_id scoping
```

### Default Permission Matrix

| Module | Permission | Admin | Manager | Accountant | Viewer |
|--------|-----------|-------|---------|------------|--------|
| Dashboard | dashboard.view | Y | Y | Y | Y |
| Users | users.view/create/edit/delete/manage-roles | Y | N | N | Y(view) |
| Roles | roles.view/create/edit/delete | Y | N | N | Y(view) |
| Accounts | accounts.view/create/edit/delete | Y | Y | Y | Y(view) |
| Parties | parties.view/create/edit/delete | Y | Y | Y | Y(view) |
| Vouchers | vouchers.view/create/edit/delete/approve | Y | Y | Y | Y(view) |
| Reports | reports.view/export | Y | Y | Y | Y(both) |
| Settings | settings.view/edit | Y | N | N | Y(view) |
| Financial Years | financial-years.view/create/close | Y | Y | N | Y(view) |
| Audit Logs | audit-logs.view | Y | N | N | Y(view) |

---

# 3. Database Documentation

## 3.1 Complete Table Documentation

### 3.1.1 users
| Field | Type | Null | Default | Notes |
|-------|------|------|---------|-------|
| id | bigint | NO | auto-increment | PK |
| uuid | uuid | NO | - | UNIQUE |
| name | varchar(255) | NO | - | |
| email | varchar(255) | NO | - | UNIQUE |
| email_verified_at | timestamp | YES | NULL | |
| password | varchar(255) | NO | - | Hashed |
| pin | varchar(255) | YES | NULL | Hashed PIN for mobile |
| has_pin | boolean | NO | false | |
| app_lock_enabled | boolean | NO | false | |
| biometric_enabled | boolean | NO | false | |
| auto_lock_timeout | integer | NO | 5 | Minutes |
| phone | varchar(255) | YES | NULL | |
| avatar | varchar(255) | YES | NULL | |
| role | enum | NO | 'viewer' | admin,manager,accountant,viewer |
| status | enum | NO | 'active' | active,inactive,suspended |
| last_login_at | timestamp | YES | NULL | |
| last_login_ip | varchar(255) | YES | NULL | |
| company_id | bigint | YES | NULL | FK -> companies.id |
| remember_token | varchar(100) | YES | NULL | |
| created_by | bigint | YES | NULL | FK -> users.id |
| updated_by | bigint | YES | NULL | FK -> users.id |
| created_by_ip | varchar(255) | YES | NULL | |
| updated_by_ip | varchar(255) | YES | NULL | |
| deleted_by | varchar(255) | YES | NULL | Name |
| deleted_by_id | bigint | YES | NULL | FK -> users.id |
| version | integer | NO | 1 | Offline sync |
| synced_at | timestamp | YES | NULL | |
| created_at | timestamp | YES | NULL | |
| updated_at | timestamp | YES | NULL | |
| deleted_at | timestamp | YES | NULL | Soft deletes |

**Indexes:** UNIQUE(email), UNIQUE(uuid)
**Relationships:** BelongsTo Company, BelongsToMany Roles

### 3.1.2 companies
| Field | Type | Null | Default | Notes |
|-------|------|------|---------|-------|
| id | bigint | NO | auto-increment | PK |
| uuid | uuid | NO | - | UNIQUE |
| name | varchar(255) | NO | - | |
| slug | varchar(255) | NO | - | UNIQUE |
| email | varchar(255) | YES | NULL | |
| phone | varchar(255) | YES | NULL | |
| address | text | YES | NULL | |
| city/state/country | varchar(255) | YES | NULL | |
| postal_code | varchar(255) | YES | NULL | |
| country_id/state_id/city_id | bigint | YES | NULL | FK -> locations |
| website/domain | varchar(255) | YES | NULL | |
| gst_number/pan_number | varchar(255) | YES | NULL | |
| logo/favicon | varchar(255) | YES | NULL | |
| currency | varchar(3) | NO | 'INR' | |
| timezone | varchar(255) | NO | 'Asia/Kolkata' | |
| financial_year_start/end | varchar(255) | NO | '04-01'/'03-31' | |
| is_active | boolean | NO | true | |
| created_by/updated_by/deleted_by | - | - | - | Audit fields |
| version | integer | NO | 1 | |
| synced_at | timestamp | YES | NULL | |
| created_at/updated_at/deleted_at | - | - | - | Timestamps + soft deletes |

**Indexes:** UNIQUE(uuid), UNIQUE(slug)
**Relationships:** HasMany Users, Roles, FinancialYears, Accounts, Parties, Vouchers, Subscriptions; HasOne ActiveSubscription, Theme, CurrentFinancialYear

### 3.1.3 permissions
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| name | varchar(255) | NO | - |
| slug | varchar(255) | NO | - UNIQUE |
| module | varchar(255) | YES | NULL |
| description | text | YES | NULL |
| is_active | boolean | NO | true |
| created_by/updated_by/deleted_by_id | - | - | Audit FKs |
| created_at/updated_at/deleted_at | - | - | Timestamps + soft deletes |

**Indexes:** UNIQUE(slug), INDEX(module)
**Relationships:** BelongsToMany Roles (via permission_role)

### 3.1.4 roles
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| company_id | bigint | YES | NULL FK -> companies.id |
| name | varchar(255) | NO | - |
| slug | varchar(255) | NO | - UNIQUE |
| description | text | YES | NULL |
| is_default/is_active | boolean | NO | false/true |
| Audit + sync fields | - | - | Standard audit/offline sync |
| created_at/updated_at/deleted_at | - | - | Timestamps + soft deletes |

**Indexes:** UNIQUE(uuid), UNIQUE(slug), INDEX(company_id, slug)
**Relationships:** BelongsTo Company; BelongsToMany Users, Permissions

### 3.1.5 permission_role (Pivot)
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| permission_id | bigint | FK -> permissions.id CASCADE |
| role_id | bigint | FK -> roles.id CASCADE |

**Indexes:** UNIQUE(permission_id, role_id)

### 3.1.6 role_user (Pivot)
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| role_id | bigint | FK -> roles.id CASCADE |
| user_id | bigint | FK -> users.id CASCADE |

**Indexes:** UNIQUE(role_id, user_id)

### 3.1.7 settings
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| company_id | bigint | YES | NULL FK -> companies.id |
| group | varchar(255) | NO | 'general' |
| key | varchar(255) | NO | - |
| value | text | YES | NULL |
| type | varchar(255) | NO | 'text' |
| description | text | YES | NULL |
| created_by/updated_by | bigint | YES | FK -> users.id |

**Indexes:** UNIQUE(company_id, group, key), INDEX(group)

### 3.1.8 financial_years
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| company_id | bigint | NO | FK -> companies.id CASCADE |
| name | varchar(255) | NO | - |
| start_date/end_date | date | NO | - |
| is_current | boolean | NO | false |
| is_closed | boolean | NO | false |
| closed_at | date | YES | NULL |
| Audit + sync fields | - | - | Standard |
| created_at/updated_at/deleted_at | - | - | Soft deletes |

**Indexes:** UNIQUE(uuid), INDEX(company_id, is_current), INDEX(company_id, start_date, end_date)

### 3.1.9 accounts
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| company_id | bigint | NO | FK -> companies.id CASCADE |
| financial_year_id | bigint | YES | FK -> financial_years.id |
| account_code | varchar(255) | NO | - UNIQUE |
| account_name | varchar(255) | NO | - |
| account_type | enum | NO | asset,liability,income,expense,equity |
| parent_id | bigint | YES | FK -> accounts.id (self-ref) |
| opening_balance | decimal(15,2) | NO | 0 |
| opening_date | date | YES | NULL |
| remarks | text | YES | NULL |
| is_active | boolean | NO | true |
| is_system | boolean | NO | false |
| is_bank_account | boolean | NO | false |
| sort_order | integer | NO | 0 |
| Audit + sync fields | - | - | Standard |
| created_at/updated_at/deleted_at | - | - | Soft deletes |

**Indexes:** UNIQUE(uuid), UNIQUE(account_code), INDEX(company_id, account_type), INDEX(company_id, is_active)
**Relationships:** BelongsTo Company, FinancialYear, Parent (self); HasMany Children (self)

### 3.1.10 parties
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| company_id | bigint | NO | FK -> companies.id CASCADE |
| financial_year_id | bigint | YES | FK -> financial_years.id |
| party_code | varchar(255) | NO | - UNIQUE |
| name | varchar(255) | NO | - |
| type | enum | NO | debtor,creditor |
| mobile/email | varchar(255) | YES | NULL |
| address | text | YES | NULL |
| city/state/country | varchar(255) | YES | NULL |
| city_id/state_id/country_id | bigint | YES | NULL FK -> locations |
| postal_code | varchar(255) | YES | NULL |
| gst_number/pan_number | varchar(255) | YES | NULL |
| opening_balance | decimal(15,2) | NO | 0 |
| credit_limit | integer | NO | 0 |
| payment_terms_days | integer | NO | 30 |
| opening_date | date | YES | NULL |
| remarks | text | YES | NULL |
| is_active | boolean | NO | true |
| Audit + sync fields | - | - | Standard |
| created_at/updated_at/deleted_at | - | - | Soft deletes |

**Indexes:** UNIQUE(uuid), UNIQUE(party_code), INDEX(company_id, type), INDEX(company_id, is_active)
**Relationships:** BelongsTo Company, FinancialYear, Country, State, City; HasMany Vouchers

### 3.1.11 vouchers
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| company_id | bigint | NO | FK -> companies.id CASCADE |
| financial_year_id | bigint | YES | FK -> financial_years.id |
| party_id | bigint | YES | FK -> parties.id |
| sales_invoice_id | bigint | YES | FK -> sales_invoices.id |
| purchase_invoice_id | bigint | YES | FK -> purchase_invoices.id |
| voucher_number | varchar(255) | NO | - UNIQUE |
| voucher_type | enum | NO | income,expense,receipt,payment,journal,adjustment |
| voucher_date | date | NO | - |
| narration | text | YES | NULL |
| total_debit | decimal(15,2) | NO | 0 |
| total_credit | decimal(15,2) | NO | 0 |
| status | enum | NO | 'draft' - draft,posted,cancelled |
| remarks | text | YES | NULL |
| Audit + sync fields | - | - | Standard |
| created_at/updated_at/deleted_at | - | - | Soft deletes |

**Indexes:** UNIQUE(uuid), UNIQUE(voucher_number), INDEX(company_id, voucher_type), INDEX(company_id, voucher_date), INDEX(company_id, status)
**Relationships:** BelongsTo Company, FinancialYear, Party, SalesInvoice, PurchaseInvoice; HasMany VoucherLines

### 3.1.12 voucher_lines
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| voucher_id | bigint | NO | FK -> vouchers.id CASCADE |
| account_id | bigint | NO | FK -> accounts.id CASCADE |
| debit | decimal(15,2) | NO | 0 |
| credit | decimal(15,2) | NO | 0 |
| description | text | YES | NULL |
| sort_order | integer | NO | 0 |
| created_by/updated_by | bigint | YES | FK -> users.id |
| version/synced_at | - | - | Offline sync |

**Indexes:** UNIQUE(uuid)
**Relationships:** BelongsTo Voucher, Account

### 3.1.13 ledgers
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| company_id | bigint | NO | FK -> companies.id CASCADE |
| financial_year_id | bigint | YES | FK -> financial_years.id |
| account_id | bigint | NO | FK -> accounts.id CASCADE |
| party_id | bigint | YES | FK -> parties.id |
| voucher_id | bigint | YES | FK -> vouchers.id |
| transaction_date | date | NO | - |
| reference_type | varchar(255) | YES | NULL |
| reference_id | bigint | YES | NULL |
| description | text | YES | NULL |
| debit | decimal(15,2) | NO | 0 |
| credit | decimal(15,2) | NO | 0 |
| running_balance | decimal(15,2) | NO | 0 |
| balance_type | enum | NO | 'debit' - debit,credit |
| created_by/updated_by | bigint | YES | FK -> users.id |
| version/synced_at | - | - | Offline sync |

**Indexes:** UNIQUE(uuid), INDEX(company_id, account_id, transaction_date), INDEX(company_id, financial_year_id), INDEX(reference_type, reference_id)
**Relationships:** BelongsTo Company, FinancialYear, Account, Party, Voucher

### 3.1.14 sales_invoices
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| company_id | bigint | NO | FK -> companies.id CASCADE |
| financial_year_id | bigint | YES | FK -> financial_years.id |
| party_id | bigint | YES | FK -> parties.id |
| invoice_number | varchar(255) | NO | - UNIQUE |
| invoice_date/due_date | date | NO | - |
| reference_number | varchar(255) | YES | NULL |
| notes | text | YES | NULL |
| subtotal | decimal(15,2) | NO | 0 |
| discount_amount | decimal(10,2) | NO | 0 |
| discount_percentage | decimal(5,2) | NO | 0 |
| tax_amount | decimal(15,2) | NO | 0 |
| total | decimal(15,2) | NO | 0 |
| amount_paid | decimal(15,2) | NO | 0 |
| balance_due | decimal(15,2) | NO | 0 |
| currency | varchar(3) | NO | 'INR' |
| status | enum | NO | 'draft' - draft,sent,partial,paid,overdue,cancelled,credit_note |
| is_recurring | boolean | NO | false |
| Audit + sync fields | - | - | Standard |
| created_at/updated_at/deleted_at | - | - | Soft deletes |

**Indexes:** UNIQUE(uuid), UNIQUE(invoice_number), INDEX(company_id, status), INDEX(company_id, invoice_date), INDEX(party_id)
**Relationships:** BelongsTo Company, FinancialYear, Party; HasMany Lines, Vouchers

### 3.1.15 sales_invoice_lines
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| sales_invoice_id | bigint | NO | FK -> sales_invoices.id CASCADE |
| item_id | bigint | YES | FK -> items.id |
| account_id | bigint | YES | FK -> accounts.id |
| tax_rate_id | bigint | YES | FK -> tax_rates.id |
| description | varchar(255) | YES | NULL |
| quantity | decimal(15,3) | NO | 1 |
| unit_price | decimal(15,2) | NO | 0 |
| discount_percentage | decimal(5,2) | NO | 0 |
| discount_amount | decimal(10,2) | NO | 0 |
| tax_amount | decimal(15,2) | NO | 0 |
| total | decimal(15,2) | NO | 0 |
| sort_order | integer | NO | 0 |
| version/synced_at | - | - | Offline sync |

**Indexes:** UNIQUE(uuid), INDEX(sales_invoice_id)
**Relationships:** BelongsTo SalesInvoice, Item, Account, TaxRate

### 3.1.16 purchase_invoices
Same structure as sales_invoices with these differences:
- Additional field: supplier_invoice_number (varchar, nullable)
- Status values: draft, verified, partial, paid, overdue, cancelled, debit_note

### 3.1.17 purchase_invoice_lines
Same structure as sales_invoice_lines but references purchase_invoice_id.

### 3.1.18 items
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| company_id | bigint | NO | FK -> companies.id CASCADE |
| item_code | varchar(255) | NO | - UNIQUE |
| name | varchar(255) | NO | - |
| hsn_sac_code | varchar(255) | YES | NULL |
| type | enum | NO | 'goods' - goods,service |
| tax_rate_id | bigint | YES | FK -> tax_rates.id |
| income_account_id | bigint | YES | FK -> accounts.id |
| expense_account_id | bigint | YES | FK -> accounts.id |
| purchase_price | decimal(15,2) | NO | 0 |
| selling_price | decimal(15,2) | NO | 0 |
| unit | varchar(255) | NO | 'nos' |
| description | text | YES | NULL |
| barcode | varchar(255) | YES | NULL |
| opening_stock | decimal(15,2) | NO | 0 |
| current_stock | decimal(15,2) | NO | 0 |
| reorder_level | decimal(15,2) | NO | 0 |
| is_active | boolean | NO | true |
| is_stockable | boolean | NO | true |
| Audit + sync fields | - | - | Standard |
| created_at/updated_at/deleted_at | - | - | Soft deletes |

**Relationships:** BelongsTo Company, TaxRate, IncomeAccount (Account), ExpenseAccount (Account)

### 3.1.19 tax_rates
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| company_id | bigint | NO | FK -> companies.id CASCADE |
| name | varchar(255) | NO | - |
| code | varchar(255) | YES | NULL |
| rate | decimal(5,2) | NO | - |
| type | enum | NO | 'gst' - gst,igst,cgst_sgst,vat,exempt |
| category | varchar(255) | YES | NULL |
| calculation_type | enum | NO | 'addition' - addition,deduction |
| cgst_rate/sgst_rate/igst_rate | decimal(5,2) | NO | 0 |
| is_inclusive | boolean | NO | false |
| notes | text | YES | NULL |
| is_active | boolean | NO | true |
| version/synced_at | - | - | Offline sync |

**Relationships:** BelongsTo Company; HasMany Items

### 3.1.20 bank_accounts
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| company_id | bigint | NO | FK -> companies.id CASCADE |
| account_id | bigint | YES | FK -> accounts.id |
| bank_name | varchar(255) | NO | - |
| branch_name | varchar(255) | YES | NULL |
| account_number | varchar(255) | NO | - |
| ifsc_code | varchar(255) | YES | NULL |
| account_holder_name | varchar(255) | YES | NULL |
| account_type | enum | NO | 'current' - savings,current,fixed_deposit,cc_od |
| opening_balance | decimal(15,2) | NO | 0 |
| opening_date | date | YES | NULL |
| upi_id | varchar(255) | YES | NULL |
| is_default | boolean | NO | false |
| is_active | boolean | NO | true |
| remarks | text | YES | NULL |
| Audit + sync fields | - | - | Standard |
| created_at/updated_at/deleted_at | - | - | Soft deletes |

**Relationships:** BelongsTo Company, Account

### 3.1.21 audit_logs
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| company_id | bigint | YES | FK -> companies.id |
| user_id | bigint | YES | FK -> users.id |
| action | varchar(255) | NO | - create,update,delete,login,logout,status_change |
| module | varchar(255) | NO | - users,accounts,parties,vouchers,etc. |
| record_id | bigint | YES | NULL |
| old_values/new_values | json | YES | NULL |
| ip_address/user_agent | varchar(255) | YES | NULL |
| description | text | YES | NULL |

**Indexes:** INDEX(company_id, module), INDEX(user_id, action), INDEX(created_at)

### 3.1.22 subscriptions
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| company_id | bigint | NO | FK -> companies.id CASCADE |
| plan_id | bigint | NO | FK -> subscription_plans.id RESTRICT |
| status | enum | NO | 'trial' - trial,active,past_due,cancelled,expired,paused |
| billing_cycle | enum | NO | 'monthly' - monthly,yearly,lifetime |
| start_date | date | NO | - |
| trial_end_date | date | YES | NULL |
| current_period_start/end | date | YES | NULL |
| cancelled_at/pause_until | date | YES | NULL |
| amount | decimal(10,2) | NO | - |
| currency | varchar(3) | NO | 'INR' |
| razorpay_subscription_id | varchar(255) | YES | NULL |
| metadata | json | YES | NULL |
| version/synced_at | - | - | Offline sync |
| created_at/updated_at/deleted_at | - | - | Soft deletes |

**Relationships:** BelongsTo Company, Plan; HasMany Invoices, Payments, RazorpayOrders

### 3.1.23 subscription_plans
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| name | varchar(255) | NO | - |
| slug | varchar(255) | NO | - UNIQUE |
| description | text | YES | NULL |
| monthly_price/yearly_price/lifetime_price | decimal | NO | 0 |
| currency | varchar(3) | NO | 'INR' |
| trial_days | integer | NO | 0 |
| max_users/max_transactions/max_accounts/max_parties | integer | NO | Various |
| features | json | YES | NULL |
| is_active/is_default/is_visible | boolean | NO | Various |
| sort_order | integer | NO | 0 |

**Relationships:** HasOne PricingDisplay

### 3.1.24 subscription_invoices
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| uuid | uuid | UNIQUE |
| invoice_number | varchar(255) | UNIQUE |
| company_id | bigint | FK -> companies.id |
| subscription_id | bigint | FK -> subscriptions.id |
| subtotal/tax_amount/discount_amount/total | decimal | Amounts |
| status | enum | draft,sent,paid,overdue,cancelled,refunded |
| invoice_date/due_date | date | Dates |
| paid_at | timestamp | Nullable |
| line_items | json | Nullable |
| created_at/updated_at/deleted_at | - | Soft deletes |

### 3.1.25 subscription_payments
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| uuid | uuid | UNIQUE |
| company_id | bigint | FK -> companies.id |
| subscription_id | bigint | FK -> subscriptions.id |
| invoice_id | bigint | FK -> subscription_invoices.id |
| razorpay_payment_id/order_id | varchar | Razorpay IDs |
| amount/currency | - | Payment details |
| status | enum | pending,processing,completed,failed,refunded |
| payment_method | varchar | Nullable |
| gateway_response | json | Nullable |
| paid_at/failure_reason | - | Status details |

### 3.1.26 razorpay_orders
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| uuid | uuid | UNIQUE |
| company_id | bigint | FK -> companies.id |
| subscription_id | bigint | FK -> subscriptions.id |
| razorpay_order_id | varchar | UNIQUE |
| amount/currency | - | Order details |
| status | enum | created,attempted,paid,failed |
| attempts | integer | Default 0 |
| notes/gateway_response | json | Nullable |
| receipt | varchar | Nullable |

### 3.1.27 razorpay_webhooks
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| event_type | varchar | Razorpay event type |
| razorpay_event_id | varchar | UNIQUE |
| payload | json | Full webhook payload |
| status | enum | received,processed,failed,ignored |
| error_message/retry_count/processed_at | - | Processing details |

### 3.1.28 themes
| Field | Type | Null | Default |
|-------|------|------|---------|
| id | bigint | NO | auto-increment |
| uuid | uuid | NO | - UNIQUE |
| company_id | bigint | YES | FK -> companies.id |
| name | varchar(255) | NO | 'Default' |
| primary_color | varchar(7) | NO | '#6366f1' |
| secondary_color | varchar(7) | NO | '#8b5cf6' |
| accent_color | varchar(7) | NO | '#06b6d4' |
| sidebar_color | varchar(7) | NO | '#1e1b4b' |
| header_color | varchar(7) | NO | '#ffffff' |
| text_color | varchar(7) | NO | '#1f2937' |
| bg_color | varchar(7) | NO | '#f9fafb' |
| font_family | varchar(255) | NO | 'Inter' |
| logo_url/favicon_url/login_bg_url | varchar | YES | NULL |
| dark_mode | boolean | NO | false |
| custom_css | json | YES | NULL |
| is_active/is_default | boolean | NO | Various |

### 3.1.29 login_history
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| user_id | bigint | FK -> users.id |
| company_id | bigint | FK -> companies.id |
| ip_address | varchar(45) | IPv4/IPv6 |
| user_agent | text | Full UA string |
| device_type/device_name/device_os/browser | varchar | Device details |
| location | varchar | Geolocation |
| status | enum | success,failed,blocked |
| failure_reason | varchar | Nullable |
| session_id | varchar | Nullable |
| logged_out_at | timestamp | Nullable |

### 3.1.30 user_devices
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| uuid | uuid | UNIQUE |
| user_id | bigint | FK -> users.id CASCADE |
| company_id | bigint | FK -> companies.id |
| device_id | varchar | Unique fingerprint |
| device_type | varchar | web,android,ios |
| device_name/device_os | varchar | Nullable |
| push_token/fcm_token | varchar | Push notification tokens |
| is_active/is_trusted | boolean | Device trust status |
| last_active_at | timestamp | Last activity |
| metadata | json | Extra device info |

### 3.1.31 otp_verifications
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| identifier | varchar | Email or phone |
| otp | varchar(10) | 6-digit code |
| purpose | varchar | signup,login,reset_password,verify_phone |
| status | enum | pending,verified,expired |
| attempts/max_attempts | integer | 0/3 |
| expires_at | timestamp | OTP expiry |
| verified_at | timestamp | When verified |

### 3.1.32 notifications
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| uuid | uuid | UNIQUE |
| company_id | bigint | FK -> companies.id |
| user_id | bigint | FK -> users.id |
| type/title/message | varchar/text | Notification content |
| priority | varchar | low,normal,high,urgent |
| icon/color | varchar | Bootstrap classes |
| link_module/link_id | varchar | Deep link targets |
| is_read/read_at | boolean/timestamp | Read status |
| channel | varchar | in_app,email,sms,push |
| data | json | Extra payload |

### 3.1.33 receivable_reminders
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| uuid | uuid | UNIQUE |
| company_id | bigint | FK -> companies.id |
| sales_invoice_id | bigint | FK -> sales_invoices.id |
| party_id | bigint | FK -> parties.id |
| due_date/reminder_date | date | Dates |
| reminder_sequence | integer | 1st,2nd,3rd |
| channel | varchar | whatsapp,sms,email |
| template_name/phone_number/email_address | varchar | Delivery details |
| message_content | text | Message body |
| status | enum | pending,scheduled,sent,failed,cancelled |
| invoice_total/amount_paid/balance_due | decimal | Amount snapshot |
| days_overdue | integer | Computed overdue days |
| type | varchar | automatic,manual |

### 3.1.34 whatsapp_logs
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| uuid | uuid | UNIQUE |
| company_id | bigint | FK -> companies.id |
| party_id | bigint | FK -> parties.id |
| sales_invoice_id | bigint | FK -> sales_invoices.id |
| phone_number | varchar | Target number |
| template_name/message_content | varchar/text | Message |
| message_type | varchar | text,template,media,document |
| status | enum | queued,sent,delivered,read,failed |
| external_message_id | varchar | WhatsApp msg ID |
| sent_at/delivered_at/read_at | timestamps | Delivery tracking |
| request_payload/response_metadata | json | API payloads |
| retry_count | integer | Retry attempts |

### 3.1.35 sync_queue
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| uuid | uuid | UNIQUE |
| table_name | varchar | Source table |
| record_uuid | varchar | Record UUID to sync |
| operation | enum | create,update,delete |
| payload | json | Full record data |
| metadata | json | Conflict resolution context |
| status | enum | pending,processing,completed,failed |
| retry_count/max_retries | integer | 0/3 |
| error_message/processed_at | - | Processing status |
| device_id | varchar | Source device |
| user_id/company_id | bigint | FK references |
| local_version/server_version | integer | Version tracking |
| conflict_resolution | varchar | server_wins,client_wins,manual |

### 3.1.36 attachments
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| uuid | uuid | UNIQUE |
| company_id | bigint | FK -> companies.id |
| module_type | varchar | Polymorphic type |
| module_id | bigint | Polymorphic ID |
| file_name/original_name | varchar | File names |
| file_path/file_disk | varchar | Storage path |
| file_size | bigint | Bytes |
| mime_type/extension | varchar | File type info |
| category/description | varchar | Classification |
| created_at/updated_at/deleted_at | - | Soft deletes |

### 3.1.37 countries
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| name | varchar | Country name |
| iso2 | varchar(2) | UNIQUE |
| iso3 | varchar(3) | Nullable |
| phone_code | varchar(10) | Nullable |
| currency | varchar(3) | Nullable |
| is_active | boolean | Default true |
| sort_order | integer | Default 0 |

### 3.1.38 states
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| country_id | bigint | FK -> countries.id CASCADE |
| name | varchar | State name |
| code | varchar(10) | Nullable |
| is_active | boolean | Default true |
| sort_order | integer | Default 0 |

### 3.1.39 cities
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| state_id | bigint | FK -> states.id CASCADE |
| country_id | bigint | FK -> countries.id CASCADE |
| name | varchar | City name |
| is_active | boolean | Default true |
| sort_order | integer | Default 0 |

### 3.1.40 website_pages
| Field | Type | Notes |
|-------|------|-------|
| id | bigint | PK |
| slug/title | varchar | UNIQUE slug |
| meta_title/meta_description | - | SEO fields |
| content | longText | Page body |
| template | varchar | Default 'default' |
| status | enum | draft,published,archived |
| show_in_nav/nav_order | boolean/int | Navigation settings |
| created_at/updated_at/deleted_at | - | Soft deletes |

### 3.1.41 faqs
| Field | Type | Notes |
|-------|------|-------|
| id/uuid | bigint/uuid | PK/unique |
| question/answer | varchar/text | Content |
| category | varchar | Nullable |
| sort_order/is_active | int/boolean | Display settings |

### 3.1.42 testimonials
| Field | Type | Notes |
|-------|------|-------|
| id/uuid | bigint/uuid | PK/unique |
| client_name/company_name/designation | varchar | Client info |
| testimonial | text | Review text |
| avatar/rating | varchar/int | Media + rating |
| is_featured/is_active/sort_order | boolean/int | Display settings |

### 3.1.43 contact_submissions
| Field | Type | Notes |
|-------|------|-------|
| id/uuid | bigint/uuid | PK/unique |
| name/email/phone/subject/message | - | Contact form data |
| status | enum | new,read,replied,archived |
| admin_notes/read_at/replied_at | - | Admin workflow |
| replied_by/replied_by_id | - | Reply tracking |

### 3.1.44 pricing_displays
| Field | Type | Notes |
|-------|------|-------|
| id/uuid | bigint/uuid | PK/unique |
| plan_id | bigint | FK -> subscription_plans.id CASCADE |
| badge/highlight_color | varchar | Visual badges |
| description_short/description_long | text | Plan descriptions |
| features_list | json | Feature list |
| sort_order/is_active | int/boolean | Display settings |

### 3.1.45-52 Framework Tables
- personal_access_tokens (Sanctum API tokens)
- sessions (Laravel sessions)
- cache / cache_locks (Laravel cache)
- jobs / job_batches / failed_jobs (Laravel queues)
- password_reset_tokens (Password resets)

## 3.2 ER Diagram (Text Format)

```
                    +--------------+
                    |  companies   |
                    +------+-------+
                           |
          +----------------+------------------------+
          |                |                         |
          v                v                         v
    +----------+    +----------+              +--------------+
    |  users   |    |  roles   |              | financial_   |
    +----+-----+    +----+-----+              |   years      |
         |               |                     +------+-------+
         |    +----------+----------+                 |
         |    |                     |                 |
         v    v                     v                 v
    +----------+       +------------------+    +----------+
    | role_user|       | permission_role  |    | accounts |
    +----------+       +--------+---------+    | (self-ref|
                                |               |  parent) |
                                v               +----+-----+
                        +----------+                |
                        |permissions|               |
                        +----------+                |
                                                    |
          +------------------+-----------+----------+
          |                  |           |            |
          v                  v           v            v
    +----------+       +----------+ +----------+ +----------+
    | parties  |       |  items   | |tax_rates | |bank_accts|
    +----+-----+       +----+-----+ +----------+ +----------+
         |                  |
         |                  |
         v                  v
    +----------+       +----------+
    | vouchers |       |   ledgers|
    +----+-----+       +----------+
         |
         v
    +--------------+
    | voucher_lines|
    +--------------+

    +--------------+        +--------------+
    |sales_invoices|------->|sales_invoice |
    +------+-------+        |   _lines     |
           |                +--------------+
           v
    +--------------+        +------------------+
    |purchase_     |------->|purchase_invoice  |
    | invoices     |        |     _lines       |
    +--------------+        +------------------+

    +--------------+    +------------------+
    |subscriptions |--->|subscription_     |
    +------+-------+    |   invoices       |
           |            +------------------+
           v
    +------------------+
    |subscription_     |
    |   payments       |
    +------------------+

    +--------------+    +--------------+
    | bank_accounts|    |    sync      |
    +--------------+    |    _queue    |
                        +--------------+
```

## 3.3 Table Dependency Flow

```
1.  companies (Tenant root - no FK dependencies)
2.  users -> companies
3.  permissions (standalone)
4.  roles -> companies
5.  permission_role -> permissions, roles
6.  role_user -> roles, users
7.  settings -> companies, users
8.  financial_years -> companies, users
9.  accounts -> companies, financial_years, users, self (parent_id)
10. parties -> companies, financial_years, users, countries/states/cities
11. tax_rates -> companies, users
12. items -> companies, tax_rates, accounts, users
13. bank_accounts -> companies, accounts, users
14. vouchers -> companies, financial_years, parties, sales_invoices, purchase_invoices, users
15. voucher_lines -> vouchers, accounts, users
16. ledgers -> companies, financial_years, accounts, parties, vouchers, users
17. sales_invoices -> companies, financial_years, parties, users
18. sales_invoice_lines -> sales_invoices, items, accounts, tax_rates
19. purchase_invoices -> companies, financial_years, parties, users
20. purchase_invoice_lines -> purchase_invoices, items, accounts, tax_rates
21. subscriptions -> companies, subscription_plans, users
22. subscription_invoices -> companies, subscriptions, users
23. subscription_payments -> companies, subscriptions, subscription_invoices
24. razorpay_orders -> companies, subscriptions
25. themes -> companies, users
26. audit_logs -> companies, users
27. login_history -> users, companies
28. user_devices -> users, companies
29. otp_verifications (standalone)
30. notifications -> companies, users
31. receivable_reminders -> companies, sales_invoices, parties, users
32. whatsapp_logs -> companies, parties, sales_invoices, users
33. sync_queue -> users, companies
34. attachments -> companies, users (polymorphic)
35. countries (standalone)
36. states -> countries
37. cities -> states, countries
```

---

# 4. Accounting Engine Documentation

## 4.1 Sales Invoice Module

### Table Flow
```
Sales Invoice Creation:
sales_invoices (header) -> sales_invoice_lines (line items)
    |
    v (on generate voucher)
vouchers (income type) -> voucher_lines (DR: Party, CR: Sales Account)
    |
    v
ledgers (debit/credit entries per account)
```

### Service Flow (SalesInvoiceService)
1. create(array $data, array $lines):
   - Generates invoice number (INV-YYYYMM-XXXX)
   - Creates sales_invoices record
   - Creates sales_invoice_lines with tax calculation per line
   - Calls calculateTotals() to update header totals
   - Returns created invoice

2. generateVoucher():
   - Creates a Voucher of type income
   - Lines: DR (Debit) to Party account, CR (Credit) to Income/Sales account
   - Calls VoucherService::post() to post and create ledger entries

3. recordPayment():
   - Updates amount_paid and balance_due
   - Changes status to paid or partial
   - Generates a receipt voucher via VoucherService

### Database Flow
```
Input: {party_id, invoice_date, due_date, lines: [{item_id, quantity, unit_price, tax_rate_id}]}
    |
    v
sales_invoices: INSERT with computed total = sum(line.total) - discount + tax
    |
    v
sales_invoice_lines: INSERT per line with quantity x unit_price = line_total, tax = line_total x rate
    |
    v
On generateVoucher():
vouchers: INSERT (type=income, total_debit=total, total_credit=total)
voucher_lines: INSERT (2 lines: DR party account, CR sales account)
ledgers: INSERT per voucher line with running_balance calculation
```

## 4.2 Purchase Invoice Module

### Table Flow
```
Purchase Invoice Creation:
purchase_invoices (header) -> purchase_invoice_lines (line items)
    |
    v (on generate voucher)
vouchers (expense type) -> voucher_lines (DR: Purchase Account, CR: Party)
    |
    v
ledgers (debit/credit entries per account)
```

### Service Flow (PurchaseInvoiceService)
Same pattern as SalesInvoiceService:
1. create(): Generates PUR-XXXXXX number, creates header + lines
2. generateVoucher(): Creates expense-type voucher (DR: Expense, CR: Party)
3. recordPayment(): Updates paid amount, creates payment voucher

## 4.3 Voucher Module

### Table Flow
```
Voucher CRUD:
vouchers (header with type, date, narration, totals)
    |
    v
voucher_lines (multiple lines with account_id, debit, credit)
    |
    v (on post)
LedgerService::generateForVoucher()
    |
    v
ledgers (entries per line with running balances)
```

### Voucher Types
| Type | Prefix | Debit Side | Credit Side |
|------|--------|------------|-------------|
| Income | INC- | Party/Debtor | Income Account |
| Expense | EXP- | Expense Account | Party/Creditor |
| Receipt | RCT- | Bank/Cash Account | Party/Debtor |
| Payment | PAY- | Party/Creditor | Bank/Cash Account |
| Journal | JRN- | Various | Various |
| Adjustment | ADJ- | Various | Various |

### Service Flow (VoucherService)
1. create(): Generates voucher number, validates debit==credit, creates voucher + lines
2. post(): Calls LedgerService::generateForVoucher() to create ledger entries
3. cancel(): Changes status to cancelled (does not reverse ledgers - design gap)
4. createFromSalesInvoice(): Auto-creates income voucher from invoice data
5. createFromPurchaseInvoice(): Auto-creates expense voucher from invoice data

## 4.4 Voucher Lines Module
- Each voucher has 2+ voucher_lines
- Each line references one account_id
- debit and credit are mutually exclusive per line (one is 0)
- Total debits must equal total credits for balanced voucher
- Validation: VoucherRequest enforces sum(debit) == sum(credit) with 0.01 tolerance

## 4.5 Ledger Entries Module

### Ledger Generation Algorithm (LedgerService::generateForVoucher)
```
For each voucher_line:
    1. Get previous running_balance for this account
    2. If account is asset/expense type:
       running_balance += debit - credit (debit-normal)
    3. If account is liability/income/equity type:
       running_balance += credit - debit (credit-normal)
    4. INSERT ledger entry with:
       - account_id, voucher_id, transaction_date
       - debit/credit from voucher_line
       - running_balance (calculated above)
       - balance_type (debit/credit based on sign)
```

### Running Balance Calculation
```
Asset/Expense accounts (debit-normal):
    running_balance = previous_balance + debit - credit
    If running_balance >= 0: balance_type = 'debit'
    If running_balance < 0: balance_type = 'credit'

Liability/Income/Equity accounts (credit-normal):
    running_balance = previous_balance + credit - debit
    If running_balance >= 0: balance_type = 'credit'
    If running_balance < 0: balance_type = 'debit'
```

## 4.6 AR/AP (Accounts Receivable / Accounts Payable)

### AR (Debtors Outstanding)
```
Source: voucher_lines where account is a debtor party
Calculation: SUM(debit) - SUM(credit) per party
    Result > 0 -> Amount receivable
    Result < 0 -> Amount overpaid
```

### AP (Creditors Outstanding)
```
Source: voucher_lines where account is a creditor party
Calculation: SUM(credit) - SUM(debit) per party
    Result > 0 -> Amount payable
    Result < 0 -> Amount overpaid
```

## 4.7 Taxes Module

### Tax Calculation
```
For each invoice line:
    1. Get tax_rate from tax_rates table
    2. line_subtotal = quantity x unit_price
    3. discount = line_subtotal x (discount_percentage / 100)
    4. taxable_amount = line_subtotal - discount
    5. If tax_rate.is_inclusive:
        tax_amount = taxable_amount - (taxable_amount / (1 + rate/100))
        line_total = taxable_amount (inclusive)
    6. Else:
        tax_amount = taxable_amount x (rate / 100)
        line_total = taxable_amount + tax_amount
    7. For CGST/SGST: split tax_amount equally
    8. For IGST: full tax_amount as IGST
```

## 4.8 Items Module
- Items represent products or services
- Each item has income_account_id and expense_account_id for auto-voucher creation
- tax_rate_id defaults the tax rate on invoice lines
- Stock tracking: opening_stock, current_stock, reorder_level
- is_low_stock() check: current_stock <= reorder_level
- updateStock() method for stock adjustments

---

# 5. Transaction Flow Documentation

## 5.1 Sales Transaction

```
Step 1: User creates Sales Invoice
    Controller: SalesInvoiceController::store() / SalesInvoiceApiController::store()
    Request: SalesInvoiceRequest (validates party_id, lines, dates)
    Service: SalesInvoiceService::create()
    Tables: sales_invoices (INSERT), sales_invoice_lines (INSERT per line)
    Auto-generates: invoice_number (INV-XXXXXX)

Step 2: User generates Accounting Voucher
    Controller: SalesInvoiceController (via generateVoucher route)
    Service: SalesInvoiceService::generateVoucher() -> VoucherService::createFromSalesInvoice()
    Tables: vouchers (INSERT, type=income), voucher_lines (INSERT, 2+ lines)

Step 3: Voucher Posted (auto or manual)
    Controller: VoucherController::post() / VoucherApiController::post()
    Service: VoucherService::post() -> LedgerService::generateForVoucher()
    Tables: vouchers (UPDATE status=posted), ledgers (INSERT per voucher_line)

Step 4: Payment Received (optional)
    Controller: SalesInvoiceController::payment()
    Service: SalesInvoiceService::recordPayment()
    Tables: sales_invoices (UPDATE amount_paid, balance_due, status)
    Creates: Receipt voucher via VoucherService
```

## 5.2 Purchase Transaction

```
Step 1: User creates Purchase Invoice
    Controller: PurchaseInvoiceController::store()
    Request: PurchaseInvoiceRequest (validates party_id, lines, dates)
    Service: PurchaseInvoiceService::create()
    Tables: purchase_invoices (INSERT), purchase_invoice_lines (INSERT per line)
    Auto-generates: invoice_number (PUR-XXXXXX)

Step 2: Generate Accounting Voucher
    Service: PurchaseInvoiceService::generateVoucher()
    Tables: vouchers (INSERT, type=expense), voucher_lines (INSERT)

Step 3: Post Voucher
    Service: VoucherService::post()
    Tables: ledgers (INSERT per voucher_line)

Step 4: Payment Made
    Service: PurchaseInvoiceService::recordPayment()
    Tables: purchase_invoices (UPDATE amount_paid, status)
    Creates: Payment voucher via VoucherService
```

## 5.3 Receipt Transaction

```
User creates Receipt Voucher:
    Controller: VoucherController::store() with type=receipt
    Request: VoucherRequest (validates lines balance)
    Service: VoucherService::create()
    Tables: vouchers (INSERT, type=receipt, prefix=RCT-)
            voucher_lines (INSERT: DR Bank Account, CR Party/Debtor)

    On Post:
    Tables: ledgers (INSERT entries for both lines)

    Effect: Reduces debtor outstanding, increases bank balance
```

## 5.4 Payment Transaction

```
User creates Payment Voucher:
    Controller: VoucherController::store() with type=payment
    Service: VoucherService::create()
    Tables: vouchers (INSERT, type=payment, prefix=PAY-)
            voucher_lines (INSERT: DR Party/Creditor, CR Bank Account)

    On Post:
    Tables: ledgers (INSERT entries)

    Effect: Reduces creditor outstanding, decreases bank balance
```

## 5.5 Journal Transaction

```
User creates Journal Voucher:
    Controller: VoucherController::store() with type=journal
    Service: VoucherService::create()
    Tables: vouchers (INSERT, type=journal, prefix=JRN-)
            voucher_lines (INSERT: DR Account A, CR Account B)

    On Post:
    Tables: ledgers (INSERT entries)

    Effect: Transfers balance between two accounts
```

## 5.6 Adjustment Transaction

```
User creates Adjustment Voucher:
    Controller: VoucherController::store() with type=adjustment
    Service: VoucherService::create()
    Tables: vouchers (INSERT, type=adjustment, prefix=ADJ-)
            voucher_lines (INSERT: DR/CR as needed)

    On Post:
    Tables: ledgers (INSERT entries)

    Effect: Adjusts account balances (e.g., write-offs, corrections)
```

---

# 6. Service Layer Documentation

## 6.1 AuthService
- **Responsibility:** Authentication (login, register, logout), profile management, password changes
- **Dependencies:** UserRepositoryInterface
- **Methods:** login(), register(), logout(), getAuthenticatedUser(), updateProfile(), changePassword()
- **Called By:** AuthController, SecurityApiController, AdminAuthController, RegisterController
- **Calls To:** UserRepository, LoginHistoryService (indirectly)

## 6.2 VoucherService
- **Responsibility:** Voucher CRUD, posting, cancellation, invoice-to-voucher conversion
- **Dependencies:** VoucherRepositoryInterface, VoucherLineRepositoryInterface, LedgerService
- **Methods:** getAll(), getPaginated(), getById(), create(), update(), delete(), post(), cancel(), getStatistics(), createFromSalesInvoice(), createFromPurchaseInvoice()
- **Called By:** VoucherController, VoucherApiController, SalesInvoiceService, PurchaseInvoiceService
- **Calls To:** LedgerService, VoucherRepository, VoucherLineRepository

## 6.3 LedgerService
- **Responsibility:** Ledger entry creation, balance calculation, trial balance, account ledger
- **Dependencies:** None (uses Eloquent directly)
- **Methods:** generateForVoucher(), createEntry(), getAccountLedger(), getOpeningBalance(), getTrialBalance(), getAccountBalance(), recalculateBalances()
- **Called By:** VoucherService, ReportService, ExportService, DashboardService, LedgerApiController
- **Calls To:** None (uses Eloquent models directly)

## 6.4 ReportService
- **Responsibility:** Financial report generation (P&L, Balance Sheet, Trial Balance, Day Book, Outstanding reports)
- **Dependencies:** LedgerService
- **Methods:** getProfitLoss(), getBalanceSheet(), getTrialBalance(), getDayBook(), getReceiptPayment(), getDebtorsOutstanding(), getCreditorsOutstanding()
- **Called By:** ReportController, ReportApiController, DashboardService, ExportService
- **Calls To:** LedgerService

## 6.5 SalesInvoiceService
- **Responsibility:** Sales invoice CRUD, payment recording, voucher generation, overdue tracking
- **Dependencies:** VoucherService
- **Methods:** getAll(), getPaginated(), getById(), create(), generateVoucher(), update(), updateWithLines(), recordPayment(), delete(), getOverdue(), generateInvoiceNumber()
- **Called By:** SalesInvoiceController, SalesInvoiceApiController
- **Calls To:** VoucherService

## 6.6 PurchaseInvoiceService
- **Responsibility:** Purchase invoice CRUD, payment recording, voucher generation
- **Dependencies:** VoucherService
- **Methods:** Same pattern as SalesInvoiceService
- **Called By:** PurchaseInvoiceController, PurchaseInvoiceApiController
- **Calls To:** VoucherService

## 6.7 AccountService
- **Responsibility:** Chart of accounts management, tree structure, code generation
- **Dependencies:** None
- **Methods:** getAll(), getPaginated(), getById(), create(), update(), delete(), getGrouped(), getTree(), getForDropdown()
- **Called By:** AccountController, AccountApiController, BankAccountController, ItemController

## 6.8 PartyService
- **Responsibility:** Debtor/Creditor management, outstanding calculations
- **Dependencies:** None
- **Methods:** getAll(), getPaginated(), getById(), create(), update(), delete(), getForDropdown(), getDebtorsOutstanding(), getCreditorsOutstanding()
- **Called By:** PartyController, PartyApiController, VoucherService

## 6.9 ItemService
- **Responsibility:** Item/inventory management, stock tracking
- **Dependencies:** None
- **Methods:** Standard CRUD + getLowStock(), toggleStatus()
- **Called By:** ItemController, ItemApiController

## 6.10 TaxRateService
- **Responsibility:** Tax rate CRUD and management
- **Dependencies:** None
- **Methods:** Standard CRUD + toggleStatus()
- **Called By:** TaxRateController, TaxRateApiController

## 6.11 RoleService
- **Responsibility:** Role and permission management
- **Dependencies:** RoleRepositoryInterface, PermissionRepositoryInterface
- **Methods:** Standard CRUD + getPermissionsGrouped(), assignPermission(), removePermission()
- **Called By:** RoleController

## 6.12 BankAccountService
- **Responsibility:** Bank account management, default account handling
- **Dependencies:** None
- **Methods:** Standard CRUD + getDefault(), setDefault()
- **Called By:** BankAccountController, BankAccountApiController

## 6.13 DashboardService
- **Responsibility:** Dashboard statistics, charts, trends
- **Dependencies:** ReportService
- **Methods:** getStatistics(), getRecentTransactions(), getMonthlyData(), getReceivablesTrend(), getPayablesTrend()
- **Called By:** DashboardController, DashboardApiController
- **Calls To:** ReportService, LedgerService

## 6.14 ExportService
- **Responsibility:** PDF, Excel, CSV export generation
- **Dependencies:** ReportService, LedgerService
- **Methods:** exportProfitLossPdf(), exportBalanceSheetPdf(), exportTrialBalancePdf(), exportLedgerPdf(), exportVoucherPdf(), exportToExcel(), exportToCsv()
- **Called By:** ExportController, ExportApiController
- **Calls To:** ReportService, LedgerService, DomPDF, Maatwebsite Excel

## 6.15 SubscriptionService
- **Responsibility:** Plan management, subscription lifecycle, limit enforcement
- **Dependencies:** None
- **Methods:** getPlans(), getActiveSubscription(), subscribe(), changePlan(), cancelActiveSubscription(), getInvoices(), getPayments(), hasFeature(), hasReachedLimit(), handleExpiredSubscriptions()
- **Called By:** SubscriptionController, SubscriptionApiController

## 6.16 SettingsService
- **Responsibility:** Company settings, theme settings, CSS generation
- **Dependencies:** None
- **Methods:** getAllGrouped(), get(), update(), updateCompanySettings(), updateThemeSettings(), getThemeCss()
- **Called By:** SettingsController, SettingsApiController

## 6.17 LoginHistoryService
- **Responsibility:** Login/logout tracking, device management
- **Dependencies:** None
- **Methods:** recordLogin(), recordLogout(), getUserHistory(), getCompanyHistory(), getFailedAttempts(), registerDevice(), getUserDevices(), deactivateDevice()
- **Called By:** Auth controllers, SecurityApiController

## 6.18 AuditLogService
- **Responsibility:** Audit trail management
- **Dependencies:** None
- **Methods:** getAll(), getPaginated(), getForRecord(), getStatistics(), cleanOldLogs()
- **Called By:** AuditLogController

## 6.19 ThemeService
- **Responsibility:** Theme CRUD, application, dark mode toggle
- **Dependencies:** None
- **Methods:** getAll(), getForCompany(), create(), update(), applyToCompany(), toggleDarkMode()
- **Called By:** ThemeController, ThemeApiController

## 6.20 OtpService
- **Responsibility:** OTP generation and verification
- **Dependencies:** None
- **Methods:** sendOtp(), verifyOtp(), isVerified(), sendEmailOtp() (TODO), sendSmsOtp() (TODO)
- **Called By:** SecurityApiController (pending implementation)

## 6.21 WebsiteService
- **Responsibility:** Public website content management
- **Dependencies:** None
- **Methods:** getPage(), getNavItems(), getFaqs(), getTestimonials(), getPricingPlans(), submitContact(), getSiteSettings()
- **Called By:** WebsiteController, CmsPageController, FaqController, TestimonialController

---

# 7. Repository Layer Documentation

## 7.1 BaseRepository
- **Responsibility:** Generic CRUD operations for all entities
- **Tables Used:** Any Eloquent model
- **Methods:** all(), paginate(), find(), findBy(), getWhere(), create(), update(), delete(), exists(), count(), withTrashed(), onlyTrashed(), restore(), forceDelete()

## 7.2 UserRepository
- **Responsibility:** User-specific data access
- **Tables Used:** users
- **Methods:** findByEmail(), getByCompany(), getByRole(), getActive(), updateLastLogin()

## 7.3 RoleRepository
- **Responsibility:** Role-specific data access
- **Tables Used:** roles
- **Methods:** getByCompany(), getActive(), getDefault(), findBySlug()

## 7.4 PermissionRepository
- **Responsibility:** Permission-specific data access
- **Tables Used:** permissions
- **Methods:** getByModule(), getActive(), findBySlug(), getGrouped() (grouped by module)

## 7.5 VoucherRepository
- **Responsibility:** Voucher-specific data access
- **Tables Used:** vouchers
- **Methods:** findByNumber(), getByCompany(), getByType(), getByStatus()

## 7.6 VoucherLineRepository
- **Responsibility:** Voucher line-specific data access
- **Tables Used:** voucher_lines
- **Methods:** getByVoucher(), getByAccount(), deleteByVoucher()

**Note:** Many services (AccountService, PartyService, ItemService, TaxRateService, etc.) bypass repositories and use Eloquent models directly. This is a technical debt item.

---

# 8. API Documentation

## 8.1 Authentication APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/login | POST | No | Login with email/password, returns Sanctum token |
| /api/v1/register | POST | No | Register new company + user, returns user data |
| /api/v1/logout | POST | Yes | Revoke current token |
| /api/v1/me | GET | Yes | Get authenticated user profile |
| /api/v1/profile | PUT | Yes | Update name, email, phone |
| /api/v1/change-password | PUT | Yes | Change password (requires current_password) |

## 8.2 Security APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/pin/login | POST | No | Login with PIN (4-6 digits) |
| /api/v1/pin/set | POST | Yes | Set/change PIN |
| /api/v1/pin/verify | POST | Yes | Verify current PIN |
| /api/v1/security/app-lock | PUT | Yes | Toggle app lock on/off |
| /api/v1/security/settings | GET | Yes | Get security settings |
| /api/v1/security/settings | PUT | Yes | Update biometric/lock timeout |

## 8.3 Dashboard APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/dashboard | GET | Yes | Dashboard statistics + recent transactions |
| /api/v1/dashboard/monthly-data | GET | Yes | 12-month income/expense arrays |
| /api/v1/dashboard/receivables-trend | GET | Yes | Monthly AR trend data |
| /api/v1/dashboard/payables-trend | GET | Yes | Monthly AP trend data |

## 8.4 Account APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/accounts | GET | Yes | List all accounts with balances |
| /api/v1/accounts/{id} | GET | Yes | Get account detail |
| /api/v1/accounts/by-type | GET | Yes | Filter by type (asset/liability/income/expense/equity) |

## 8.5 Party APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/parties | GET | Yes | List all parties |
| /api/v1/parties/{id} | GET | Yes | Get party detail |
| /api/v1/parties/by-type | GET | Yes | Filter by type (debtor/creditor) |

## 8.6 Item APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/items | GET | Yes | List all items |
| /api/v1/items/{id} | GET | Yes | Get item detail |
| /api/v1/items | POST | Yes | Create new item |
| /api/v1/items/{id} | PUT | Yes | Update item |
| /api/v1/items/low-stock | GET | Yes | Items below reorder level |

## 8.7 Tax Rate APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/tax-rates | GET | Yes | List all tax rates |
| /api/v1/tax-rates/{id} | GET | Yes | Get tax rate detail |
| /api/v1/tax-rates | POST | Yes | Create new tax rate |
| /api/v1/tax-rates/{id} | PUT | Yes | Update tax rate |

## 8.8 Sales Invoice APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/sales-invoices | GET | Yes | List all sales invoices |
| /api/v1/sales-invoices/{id} | GET | Yes | Get invoice detail with lines |
| /api/v1/sales-invoices | POST | Yes | Create new sales invoice |
| /api/v1/sales-invoices/{id}/payment | POST | Yes | Record payment against invoice |
| /api/v1/sales-invoices/{id}/pdf | GET | Yes | Export invoice PDF |
| /api/v1/sales-invoices/overdue | GET | Yes | List overdue invoices |
| /api/v1/sales-invoices/{id} | PUT | Yes | Update sales invoice |
| /api/v1/sales-invoices/{id} | DELETE | Yes | Delete sales invoice |

## 8.9 Purchase Invoice APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/purchase-invoices | GET | Yes | List all purchase invoices |
| /api/v1/purchase-invoices/{id} | GET | Yes | Get invoice detail |
| /api/v1/purchase-invoices | POST | Yes | Create new purchase invoice |
| /api/v1/purchase-invoices/{id}/payment | POST | Yes | Record payment |
| /api/v1/purchase-invoices/{id} | PUT | Yes | Update purchase invoice |
| /api/v1/purchase-invoices/{id} | DELETE | Yes | Delete purchase invoice |

## 8.10 Voucher APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/vouchers | GET | Yes | List all vouchers |
| /api/v1/vouchers/{id} | GET | Yes | Get voucher detail with lines |
| /api/v1/vouchers | POST | Yes | Create new voucher |
| /api/v1/vouchers/{id}/post | PATCH | Yes | Post voucher (create ledgers) |
| /api/v1/vouchers/{id}/cancel | PATCH | Yes | Cancel voucher |
| /api/v1/vouchers/{id} | PUT | Yes | Update draft voucher |
| /api/v1/vouchers/{id} | DELETE | Yes | Delete draft voucher |

## 8.11 Ledger APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/ledgers | GET | Yes | All accounts with current balances |
| /api/v1/ledgers/{id} | GET | Yes | Ledger detail for specific account |
| /api/v1/ledgers/{id}/entries | GET | Yes | Paginated ledger entries for account |

## 8.12 Report APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/reports/profit-loss | GET | Yes | Profit and Loss statement |
| /api/v1/reports/balance-sheet | GET | Yes | Balance Sheet |
| /api/v1/reports/trial-balance | GET | Yes | Trial Balance |
| /api/v1/reports/day-book | GET | Yes | Day Book (voucher list for date) |
| /api/v1/reports/receipt-payment | GET | Yes | Receipt & Payment report |
| /api/v1/reports/ledger | GET | Yes | Account ledger report |
| /api/v1/reports/debtors-outstanding | GET | Yes | AR outstanding report |
| /api/v1/reports/creditors-outstanding | GET | Yes | AP outstanding report |

## 8.13 Bank Account APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/bank-accounts | GET | Yes | List all bank accounts |
| /api/v1/bank-accounts/{id} | GET | Yes | Get bank account detail |
| /api/v1/bank-accounts | POST | Yes | Create new bank account |
| /api/v1/bank-accounts/{id} | PUT | Yes | Update bank account |
| /api/v1/bank-accounts/{id}/default | PATCH | Yes | Set as default bank account |

## 8.14 Settings APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/settings | GET | Yes | All settings |
| /api/v1/settings/company | GET | Yes | Company settings |
| /api/v1/settings/theme | GET | Yes | Theme settings |
| /api/v1/settings/financial-years | GET | Yes | All financial years |
| /api/v1/settings/financial-year/current | GET | Yes | Current active financial year |

## 8.15 Export APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/export/profit-loss/pdf | GET | Yes | P&L PDF export |
| /api/v1/export/balance-sheet/pdf | GET | Yes | Balance Sheet PDF |
| /api/v1/export/trial-balance/pdf | GET | Yes | Trial Balance PDF |
| /api/v1/export/day-book/pdf | GET | Yes | Day Book PDF |
| /api/v1/export/ledger/pdf | GET | Yes | Ledger PDF |
| /api/v1/export/debtors-outstanding/pdf | GET | Yes | Debtors outstanding PDF |
| /api/v1/export/creditors-outstanding/pdf | GET | Yes | Creditors outstanding PDF |
| /api/v1/export/voucher/{id}/pdf | GET | Yes | Voucher PDF |
| /api/v1/export/sales-invoice/{id}/pdf | GET | Yes | Sales invoice PDF |

## 8.16 Subscription APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/subscriptions/plans | GET | Yes | List all plans |
| /api/v1/subscriptions/current | GET | Yes | Current subscription |
| /api/v1/subscriptions/subscribe | POST | Yes | Subscribe to plan |
| /api/v1/subscriptions/change-plan | POST | Yes | Change plan |
| /api/v1/subscriptions/cancel | POST | Yes | Cancel subscription |

## 8.17 Theme APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/themes/current | GET | Yes | Current active theme |
| /api/v1/themes | GET | Yes | List all themes |
| /api/v1/themes | PUT | Yes | Update theme settings |
| /api/v1/themes/apply | POST | Yes | Apply theme to company |
| /api/v1/themes/toggle-dark-mode | POST | Yes | Toggle dark mode |

## 8.18 Location APIs
| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| /api/v1/locations/countries | GET | Yes | List all countries |
| /api/v1/locations/{countryId}/states | GET | Yes | States for country |
| /api/v1/locations/{stateId}/cities | GET | Yes | Cities for state |

---

# 9. Web Application Modules

## 9.1 Implemented Modules
| Module | Controller | Status |
|--------|-----------|--------|
| Authentication | LoginController, RegisterController, ForgotPasswordController, ResetPasswordController | 100% |
| Dashboard | DashboardController | 90% |
| Roles and Permissions | RoleController | 100% |
| Company Settings | SettingsController | 80% |
| Financial Years | FinancialYearController | 90% |
| Account Master | AccountController | 100% |
| Party Master | PartyController | 100% |
| Voucher Management | VoucherController | 100% |
| Sales Invoices | SalesInvoiceController | 100% |
| Purchase Invoices | PurchaseInvoiceController | 100% |
| Bank Accounts | BankAccountController | 100% |
| Tax Rates | TaxRateController | 100% |
| Items | ItemController | 100% |
| Reports | ReportController | 100% |
| Export | ExportController | 100% |
| Audit Logs | AuditLogController | 100% |
| Themes | ThemeController | 100% |
| Subscriptions | SubscriptionController | 80% |
| Subscription Plans | SubscriptionPlanController | 100% |
| Company Approval | CompanyApprovalController | 100% |
| CMS Pages | CmsPageController | 100% |
| FAQs | FaqController | 100% |
| Testimonials | TestimonialController | 100% |
| Contact Submissions | ContactSubmissionController | 100% |
| Locations | LocationController | 100% |
| Website | WebsiteController | 100% |

## 9.2 Pending Modules
1. User Management (Admin CRUD) - Routes registered, no controller/views
2. Recurring Invoice Automation - is_recurring field exists, no scheduler
3. Notification Center - Model exists, no admin UI for management
4. Attachment/File Upload UI - Model exists, no upload views
5. GST Returns (GSTR-1, GSTR-3B) - Not started
6. Multi-currency - Schema exists, no conversion logic
7. Budget Module - Not started
8. Cost Center Module - Not started
9. Fixed Assets Module - Not started

## 9.3 Routes Summary
- Public Routes: 12 (website pages + registration)
- Auth Routes: 8 (login, register, password reset, email verification)
- Admin Authenticated Routes: 100+ (all CRUD operations across all modules)
- API Routes: 60+ (REST endpoints for mobile/offline)

---

# 10. Mobile Application Requirements

## 10.1 Existing APIs Consumed
All 60+ API endpoints documented in Section 8 are ready for mobile consumption.

## 10.2 Pending APIs
1. Sync API - Upload/download batch sync endpoint (not implemented)
2. Offline Data Preload - Initial data fetch for offline use
3. Push Notification Registration - FCM token registration endpoint
4. File Upload API - Attachment upload (multipart)
5. WhatsApp Integration - Send reminders via WhatsApp Business API

## 10.3 Offline Requirements
1. Offline-First Data Model: All business tables have uuid, version, synced_at fields
2. Local Storage: SQLite or Realm for offline data storage
3. CRUD Operations: Create, Update, Delete must work offline
4. Auto-Sync: Background sync when connectivity is restored
5. Conflict Resolution: Version-based conflict detection (server_wins by default)
6. Queue-based Sync: sync_queue table for ordered processing

## 10.4 Sync Requirements
1. Bidirectional Sync: Server and Mobile data synchronization
2. Delta Sync: Only changed records synced (based on updated_at / version)
3. Batch Processing: Multiple records per sync request
4. Conflict Detection: local_version vs server_version comparison
5. Retry Logic: Max 3 retries per sync entry
6. Device Identification: device_id per sync request

## 10.5 Local Storage Requirements
1. Users: Current user profile, auth token, PIN settings
2. Company: Company details, theme, settings
3. Financial Year: Current FY data
4. Accounts: Full chart of accounts (for offline voucher creation)
5. Parties: All debtors and creditors
6. Items: All items with stock levels
7. Tax Rates: All active tax rates
8. Vouchers: Recent vouchers (last 90 days or current FY)
9. Sales/Purchase Invoices: Current FY invoices
10. Bank Accounts: All bank accounts
11. Ledger Entries: Current FY entries for balance display

---

# 11. SaaS Documentation

## 11.1 Company Structure
```
Company (Tenant)
  |-- Users (multiple)
  |-- Roles (multiple, with permissions)
  |-- Financial Years (multiple, one current)
  |-- Accounts (Chart of Accounts)
  |-- Parties (Debtors + Creditors)
  |-- Vouchers (all transaction types)
  |-- Ledgers (accounting entries)
  |-- Sales Invoices + Lines
  |-- Purchase Invoices + Lines
  |-- Bank Accounts
  |-- Tax Rates
  |-- Items
  |-- Theme
  |-- Settings
  |-- Subscription (one active)
  |-- Notifications
  |-- Audit Logs
  +-- Attachments
```

## 11.2 Subscription Structure
| Plan | Price (Monthly) | Max Users | Max Transactions | Max Accounts | Max Parties | Trial Days |
|------|----------------|-----------|-----------------|-------------|-------------|------------|
| Trial | Free | 1 | 100 | 50 | 50 | 14 |
| Basic | Rs 499 | 3 | 500 | 200 | 200 | 0 |
| Pro | Rs 999 | 10 | 2000 | 500 | 500 | 0 |
| Enterprise | Rs 2999 | Unlimited | Unlimited | Unlimited | Unlimited | 0 |

**Billing Cycles:** Monthly, Yearly, Lifetime
**Payment Gateway:** Razorpay
**Subscription Status Flow:** trial -> active -> past_due -> expired/cancelled/paused

## 11.3 Roles and Permissions
- 4 Default Roles: Administrator, Manager, Accountant, Viewer
- 26 Permissions across 9 modules
- Role Hierarchy: Admin > Manager > Accountant > Viewer
- Admin Override: Admin role always returns true for all permission checks
- Custom Roles: Companies can create custom roles with specific permission sets

## 11.4 Multi Business Support
- Each company is an isolated tenant
- All queries are scoped by company_id
- Companies have independent: Chart of Accounts, Parties, Vouchers/Ledgers, Invoices, Financial Years, Subscription, Theme, Settings
- Cross-company data leakage is prevented by service-layer scoping

## 11.5 Tenant Isolation Strategy
```
Every Service Method:
1. Gets company_id from auth()->user()->company_id
2. Scopes all queries by company_id
3. No cross-company queries allowed
4. Foreign keys cascade within company scope
```

---

# 12. Sync Engine Documentation

## 12.1 Offline Storage Strategy (Designed)
```
Mobile Local DB (SQLite):
  |-- Mirror of server tables with uuid references
  |-- Local version tracking per record
  |-- Pending sync queue (unsynced changes)
  +-- Conflict resolution log

Server DB:
  |-- sync_queue table for processing
  |-- version field on all business tables
  |-- synced_at timestamp for last sync
  +-- Device identification per sync entry
```

## 12.2 Sync Queue Strategy (Designed)
```
1. Mobile creates/updates/deletes record locally
2. Record added to local sync queue with operation type
3. When online, mobile batches sync queue entries
4. Each entry sent to POST /api/v1/sync/upload
5. Server processes entries in order:
   a. Check version mismatch
   b. If match -> apply change
   c. If mismatch -> conflict resolution
6. Server returns processed entries
7. Mobile updates local sync queue status
```

## 12.3 Conflict Resolution Strategy (Designed)
```
Three strategies available:
1. server_wins (default): Server version always takes precedence
2. client_wins: Mobile version overwrites server
3. manual: Flag for user resolution

Conflict Detection:
- Compare local_version with server_version
- If versions differ -> conflict exists
- Metadata field stores conflict context
```

## 12.4 Upload Flow (Designed)
```
Mobile -> Server:
POST /api/v1/sync/upload
{
    "device_id": "device-uuid",
    "entries": [
        {
            "table_name": "vouchers",
            "record_uuid": "record-uuid",
            "operation": "create|update|delete",
            "payload": { ... full record data ... },
            "local_version": 1
        }
    ]
}

Server Response:
{
    "processed": [
        {
            "record_uuid": "...",
            "status": "completed|conflict|failed",
            "server_version": 1,
            "conflict_resolution": "server_wins"
        }
    ]
}
```

## 12.5 Download Flow (Designed)
```
Mobile <- Server:
GET /api/v1/sync/download?since={timestamp}&device_id={id}

Server Response:
{
    "entries": [
        {
            "table_name": "accounts",
            "record_uuid": "...",
            "operation": "create|update|delete",
            "payload": { ... },
            "server_version": 1
        }
    ],
    "has_more": false
}
```

---

# 13. Reports Documentation

## 13.1 Balance Sheet
**Source Tables:** accounts, ledgers
**Formula:**
```
Assets = sum(account_balance WHERE type = 'asset')
Liabilities = sum(account_balance WHERE type = 'liability')
Equity = sum(account_balance WHERE type = 'equity') + Net Profit
Balance Check: Assets = Liabilities + Equity
```
**Implementation:** ReportService::getBalanceSheet()

## 13.2 Profit and Loss
**Source Tables:** accounts, ledgers
**Formula:**
```
Total Income = sum(account_balance WHERE type = 'income')
Total Expenses = sum(account_balance WHERE type = 'expense')
Net Profit = Total Income - Total Expenses
```
**Implementation:** ReportService::getProfitLoss()

## 13.3 Receipt & Payment
**Source Tables:** accounts, ledgers
**Implementation:** ReportService::getReceiptPayment($companyId, $dateFrom, $dateTo, $financialYearId)
Cash, bank, and OD movement for the period grouped by contra ledger head, with opening and closing balances.
Cash Flow, Cash Book, and Bank Book were removed; the legacy cash-flow URL redirects here.

## 13.4 Trial Balance
**Source Tables:** accounts, ledgers
**Formula:**
```
For each account:
    Total Debit = SUM(ledger.debit) WHERE account_id = X
    Total Credit = SUM(ledger.credit) WHERE account_id = X
    Balance = Opening Balance + Total Debit - Total Credit

Summary:
    Total All Debits = sum(account.total_debit + opening_balance_debit)
    Total All Credits = sum(account.total_credit + opening_balance_credit)
    Must be equal
```
**Implementation:** LedgerService::getTrialBalance()

## 13.5 Ledger Reports
**Source Tables:** ledgers, accounts
**Output:** Chronological list of entries for a specific account
```
For account X in financial year Y:
    1. Get opening balance
    2. List all ledger entries ordered by date
    3. Calculate running balance per entry
    4. Show closing balance
```
**Implementation:** LedgerService::getAccountLedger()

## 13.6 AR/AP Outstanding Reports

### Debtors Outstanding
**Source Tables:** voucher_lines, parties
**Formula:**
```
For each debtor party:
    Total Debit = SUM(voucher_lines.debit) WHERE party_account = X
    Total Credit = SUM(voucher_lines.credit) WHERE party_account = X
    Outstanding = Total Debit - Total Credit (if positive)
```

### Creditors Outstanding
**Formula:**
```
For each creditor party:
    Outstanding = Total Credit - Total Debit (if positive)
```
**Implementation:** ReportService::getDebtorsOutstanding() / getCreditorsOutstanding()

## 13.7 Day Book
**Source Tables:** vouchers, voucher_lines, parties
**Output:** All posted vouchers for a specific date
**Implementation:** ReportService::getDayBook()

---

# 14. Folder Structure Documentation

## 14.1 Complete Folder Tree
```
Reco/
  |-- app/
  |   |-- Console/
  |   |   +-- Kernel.php
  |   |-- Docs/                    # OpenAPI/Swagger annotations (16 files)
  |   |-- Helpers/
  |   |   +-- ResponseHelper.php
  |   |-- Http/
  |   |   |-- Controllers/
  |   |   |   |-- Auth/            # 7 auth controllers
  |   |   |   |-- Admin/           # 20 admin controllers
  |   |   |   +-- Api/             # 17 API controllers
  |   |   |-- Middleware/           # 4 middleware
  |   |   |-- Requests/
  |   |   |   |-- Auth/            # 1 request
  |   |   |   |-- Admin/           # 8 requests
  |   |   |   +-- Api/             # 4 requests
  |   |   +-- Resources/           # 16 API resources
  |   |-- Interfaces/              # 7 interfaces
  |   |-- Models/                  # 42 models
  |   |-- Providers/
  |   |-- Repositories/            # 6 repositories
  |   |-- Services/                # 21 services
  |   +-- Traits/                  # HasAuditFields, HasUuid
  |-- config/
  |-- database/
  |   |-- factories/               # 6 factories
  |   |-- migrations/              # 52 migrations
  |   +-- seeders/                 # 15 seeders
  |-- public/
  |   +-- assets/
  |       |-- css/                 # login.css, website.css
  |       +-- js/                  # common.js (450 lines)
  |-- resources/
  |   +-- views/
  |       |-- admin/               # Admin panel views
  |       |-- auth/                # Auth views
  |       |-- errors/              # Error pages (403)
  |       |-- layouts/             # app.blade.php, auth.blade.php
  |       +-- website/             # Public website views
  |-- routes/
  |   |-- api.php                  # 60+ API routes
  |   |-- console.php
  |   +-- web.php                  # 100+ web routes
  |-- storage/
  |-- tests/
  |   |-- Feature/                 # 1 test
  |   +-- Unit/                    # 4 tests
  |-- bootstrap/
  |   +-- app.php                  # Laravel 12 app config
  |-- .github/
  |   +-- copilot-instructions.md
  |-- composer.json
  +-- PROJECT_STATUS.md
```

## 14.2 Purpose of Each Directory

### Controllers
- **Auth Controllers:** Handle authentication flows (login, register, password reset, email verification)
- **Admin Controllers:** Handle web admin panel CRUD operations, return Blade views or AJAX JSON
- **API Controllers:** Handle REST API endpoints, return JSON via ResponseHelper and API Resources

### Services
- Contain all business logic (SOLID Single Responsibility)
- Each service encapsulates one domain concern
- Services call repositories/models and other services
- Controllers are thin and delegate to services

### Repositories
- Abstract database access layer
- Only 6 implemented (User, Role, Permission, Voucher, VoucherLine + Base)
- Many services bypass repositories and use Eloquent directly (tech debt)

### Requests
- Form request validation classes
- Encapsulate validation rules, authorization, and custom messages
- Used by both Admin and API controllers

### Resources
- API resource transformers
- Convert Eloquent models to consistent JSON format
- Handle nested relationships and computed attributes

### Policies
- NOT IMPLEMENTED - Directory does not exist
- Authorization handled via CheckPermission/CheckRole middleware instead

### Jobs
- NOT IMPLEMENTED - Directory does not exist
- No async job processing yet

### Events/Listeners
- NOT IMPLEMENTED - Neither directory exists
- No event-driven architecture yet

### Traits
- **HasAuditFields:** Auto-populates created_by, updated_by, deleted_by with IP addresses
- **HasUuid:** Auto-generates UUID on model creation

---

# 15. Development Progress Report

## 15.1 Completed %
| Area | Completion |
|------|-----------|
| Backend Architecture | 90% |
| Database Schema | 95% |
| Authentication | 100% |
| Roles and Permissions | 100% |
| Account Master | 100% |
| Party Master | 100% |
| Voucher Management | 100% |
| Ledger Engine | 95% |
| Sales Invoices | 100% |
| Purchase Invoices | 100% |
| Bank Accounts | 100% |
| Tax Rates | 100% |
| Items/Inventory | 90% |
| Reports | 85% |
| Export Engine | 90% |
| Dashboard | 85% |
| Audit Logging | 90% |
| Subscription System | 75% |
| Theme System | 90% |
| Website CMS | 100% |
| API Endpoints | 80% |
| OpenAPI Documentation | 70% |
| Tests | 5% |
| **Overall** | **~65%** |

## 15.2 Pending %
| Area | Pending Work |
|------|-------------|
| Offline Sync Engine | 100% (not started) |
| Mobile App | 100% (not started) |
| Background Jobs | 100% (not started) |
| Events/Listeners | 100% (not started) |
| Policies | 100% (not started) |
| User Management CRUD | 80% (routes exist, no views) |
| Notification System | 70% (model exists, no UI) |
| File Upload System | 60% (model exists, no UI) |
| Email Templates | 100% (not started) |
| WhatsApp Integration | 90% (log structure ready) |
| GST Reports | 100% (not started) |
| Unit/Feature Tests | 95% (only 5 tests) |
| Production Deployment | 100% (not started) |

## 15.3 Risk Areas
1. **Offline Sync Complexity:** The sync engine is the most complex feature and has not been started
2. **Double-Entry Correctness:** Ledger calculations need thorough testing with edge cases
3. **Race Conditions:** Concurrent voucher posting could cause ledger balance issues
4. **Missing Repositories:** Many services use Eloquent directly, violating the intended architecture
5. **No Background Jobs:** Long-running operations (exports, sync, reminders) have no async processing
6. **Test Coverage:** Only 5 unit tests exist for a system of this complexity
7. **No Event System:** Audit logging happens in service methods rather than via events, making it fragile

## 15.4 Technical Debt
1. **Inconsistent Repository Usage:** 15 of 21 services bypass the repository pattern
2. **No Policies:** Authorization is middleware-only, not model-level
3. **No Events/Listeners:** Business logic is tightly coupled (no event-driven extensibility)
4. **No Jobs:** All processing is synchronous (blocks HTTP requests)
5. **Hardcoded Company ID:** Some service methods accept company_id as parameter instead of deriving from auth
6. **Missing Soft Deletes:** Some models (Ledger, VoucherLine) do not have soft deletes
7. **No Rate Limiting:** API endpoints have no rate limiting configured
8. **No API Versioning Strategy:** Only v1 exists, no deprecation plan
9. **No Database Transactions:** Some multi-table operations lack explicit DB::transaction wrapping
10. **No Caching:** Dashboard and report queries are not cached

## 15.5 Known Issues
1. **Voucher Cancellation:** Cancels voucher but does not reverse ledger entries
2. **OTP Sending:** sendEmailOtp() and sendSmsOtp() are TODO stubs
3. **Stock Updates:** Item stock is not automatically updated on invoice creation
4. **Subscription Limits:** hasReachedLimit() is defined but not enforced in controllers
5. **Financial Year Overlap:** Validation exists but edge cases may slip through
6. **Registration Approval:** Company rejection deletes all related data (destructive)

---

# 16. Recommended Next Development Steps

## Phase 1: Core Stabilization (2-3 weeks)
1. Fix Voucher Cancellation: Implement ledger reversal on cancel
2. Add Database Transactions: Wrap multi-table operations in DB::transaction
3. Implement Remaining Repositories: Create repositories for Account, Party, Item, TaxRate, SalesInvoice, PurchaseInvoice, BankAccount
4. Add Unit Tests: Minimum 80% coverage for LedgerService, VoucherService, ReportService
5. Fix Known Issues: OTP stubs, stock updates, subscription limit enforcement

## Phase 2: Background Processing (2-3 weeks)
1. Implement Jobs: ExportJob, SyncJob, ReminderJob, NotificationJob
2. Add Events/Listeners: VoucherPosted, InvoiceCreated, PaymentReceived
3. Implement Queues: Configure Redis/Database queue for async processing
4. Add Rate Limiting: API throttle middleware
5. Implement Caching: Dashboard statistics, report data

## Phase 3: Offline Sync Engine (4-6 weeks)
1. Sync Upload API: POST endpoint for batch record upload
2. Sync Download API: GET endpoint with delta sync
3. Conflict Resolution: Implement server_wins strategy first
4. Version Tracking: Ensure all models properly increment version
5. Device Registration: FCM token storage and push notification delivery
6. Sync Monitoring: Admin dashboard for sync status

## Phase 4: Mobile App Foundation (6-8 weeks)
1. Choose Framework: React Native or Flutter
2. Authentication: Login, Register, PIN login, Biometric
3. Offline Storage: SQLite/Realm setup with schema mirroring
4. Core Screens: Dashboard, Accounts, Parties, Vouchers
5. Basic CRUD: Create/Edit/Delete for all entities
6. Sync Integration: Upload/download with conflict UI

## Phase 5: Advanced Features (4-6 weeks)
1. GST Reports: GSTR-1, GSTR-3B report generation
2. Recurring Invoices: Scheduler for auto-generation
3. WhatsApp Integration: Business API for reminders
4. Multi-currency: Currency conversion and tracking
5. Budget Module: Budget vs Actual reporting
6. Fixed Assets: Depreciation tracking

## Phase 6: Production Readiness (3-4 weeks)
1. Security Audit: OWASP checklist, penetration testing
2. Performance Optimization: Query optimization, caching, CDN
3. Monitoring: Application performance monitoring, error tracking
4. Backup Strategy: Automated database backups
5. CI/CD Pipeline: GitHub Actions for testing and deployment
6. Documentation: API docs completion, user manual
7. Deployment: Docker, nginx, SSL, domain configuration

---

*End of Project Audit Document*
*Generated: June 17, 2026*
