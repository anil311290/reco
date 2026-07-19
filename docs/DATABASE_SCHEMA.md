# RECO Database Schema (Production)

Active tables used by the live application.

## Mobile & Support
| Table | Purpose |
|-------|---------|
| `notifications` | In-app alerts (web + mobile) |
| `sync_queue` | Offline sync upload queue |
| `support_tickets` / `support_ticket_messages` | Tenant ↔ SuperAdmin support chat |
| `user_devices` | Registered mobile devices |
| Table | Purpose |
|-------|---------|
| `financial_years` | Company financial year periods |
| `accounts` | Chart of accounts |
| `parties` | Customers (debtors) and suppliers (creditors) |
| `vouchers` | Income, expense, payment, receipt, journal vouchers |
| `voucher_lines` | Debit/credit lines per voucher |
| `ledgers` | Posted ledger entries with running balances |
| `ledger_party_histories` | Party-wise ledger history snapshots |
| `tax_rates` | GST and other tax rates |
| `items` | Inventory and service items |
| `item_categories` | Item grouping |

## Invoicing
| Table | Purpose |
|-------|---------|
| `sales_invoices` / `sales_invoice_lines` | Sales (item & service) invoices |
| `purchase_invoices` / `purchase_invoice_lines` | Purchase invoices |

## Multi-Tenancy & Auth
| Table | Purpose |
|-------|---------|
| `companies` | Tenant companies |
| `users` | Admin users |
| `roles`, `permissions`, `permission_role`, `role_user` | RBAC |
| `personal_access_tokens` | API (Sanctum) tokens |
| `login_history` | Login audit trail |
| `otp_verifications` | OTP for verification flows |

## Settings & CMS
| Table | Purpose |
|-------|---------|
| `settings` | Company key-value settings |
| `themes` | UI theme configuration |
| `website_pages`, `faqs`, `testimonials`, `pricing_displays`, `contact_submissions` | Public website CMS |
| `audit_logs` | Admin action audit trail |

## Subscriptions & Payments
| Table | Purpose |
|-------|---------|
| `subscription_plans`, `subscriptions`, `subscription_invoices`, `subscription_payments` | SaaS billing |
| `razorpay_orders`, `razorpay_webhooks` | Razorpay integration |

## Locations
| Table | Purpose |
|-------|---------|
| `countries`, `states`, `cities` | Address dropdowns |

## Laravel Framework
`cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`

For column-level detail, inspect `database/migrations/` or run `php artisan schema:dump`.
