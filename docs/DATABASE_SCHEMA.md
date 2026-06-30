# Reco – Complete Database Schema Documentation

> **Database**: `laravel_onlinefirstman`  
> **Engine**: MySQL 8.x | **Port**: 3307  
> **Total Tables**: 49 (44 business + 5 new)  
> **Last Updated**: 2026-06-05

---

## Table of Contents

1. [Core System Tables](#1-core-system-tables)
2. [Multi-Tenancy & Users](#2-multi-tenancy--users)
3. [Roles & Permissions](#3-roles--permissions)
4. [Settings & Configuration](#4-settings--configuration)
5. [Accounting Core](#5-accounting-core)
6. [Invoicing](#6-invoicing)
7. [Subscriptions & Billing](#7-subscriptions--billing)
8. [Website & CMS](#8-website--cms)
9. [Security & Audit](#9-security--audit)
10. [Notifications & Communication](#10-notifications--communication)
11. [Offline Sync](#11-offline-sync)
12. [File Attachments](#12-file-attachments)
13. [Entity Relationship Diagram](#13-entity-relationship-diagram)

---

## 1. Core System Tables

### `users`

Primary user table for authentication and profile management.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | Primary key |
| `uuid` | CHAR(36) | NO | — | Unique identifier for offline sync |
| `company_id` | BIGINT (FK) | YES | NULL | → `companies.id` ON DELETE SET NULL |
| `name` | VARCHAR(255) | NO | — | Full name |
| `email` | VARCHAR(255) | NO | — | Login email (unique) |
| `phone` | VARCHAR(255) | YES | NULL | Phone number |
| `avatar` | VARCHAR(255) | YES | NULL | Avatar file path |
| `password` | VARCHAR(255) | NO | — | Hashed password |
| `pin` | VARCHAR(255) | YES | NULL | Mobile app PIN |
| `has_pin` | BOOLEAN | NO | false | Whether PIN is set |
| `app_lock_enabled` | BOOLEAN | NO | false | App lock enabled |
| `biometric_enabled` | BOOLEAN | NO | false | Biometric auth enabled |
| `role` | ENUM | NO | 'viewer' | `admin`, `manager`, `accountant`, `viewer` |
| `status` | ENUM | NO | 'active' | `active`, `inactive`, `suspended` |
| `email_verified_at` | TIMESTAMP | YES | NULL | Email verification timestamp |
| `last_login_at` | TIMESTAMP | YES | NULL | Last login timestamp |
| `last_login_ip` | VARCHAR(45) | YES | NULL | Last login IP |
| `remember_token` | VARCHAR(100) | YES | NULL | Remember me token |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` ON DELETE SET NULL |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` ON DELETE SET NULL |
| `deleted_by` | VARCHAR(255) | YES | NULL | Who soft-deleted |
| `deleted_by_id` | BIGINT (FK) | YES | NULL | → `users.id` ON DELETE SET NULL |
| `version` | INT | NO | 1 | Offline sync version |
| `synced_at` | TIMESTAMP | YES | NULL | Last sync timestamp |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `email` (UNIQUE), `uuid` (UNIQUE), `company_id`

---

### `sessions`

Laravel session storage.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | VARCHAR(255) (PK) | NO | — | Session ID |
| `user_id` | BIGINT (FK) | YES | NULL | → `users.id` |
| `ip_address` | VARCHAR(45) | YES | NULL | Client IP |
| `user_agent` | TEXT | YES | NULL | Browser agent |
| `payload` | LONGTEXT | NO | — | Session data |
| `last_activity` | INT | NO | — | Unix timestamp |

**Indexes**: `user_id`, `last_activity`

---

### `password_reset_tokens`

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `email` | VARCHAR(255) (PK) | NO | — | User email |
| `token` | VARCHAR(255) | NO | — | Reset token |
| `created_at` | TIMESTAMP | YES | NULL | — |

---

### `cache` / `cache_locks`

Laravel cache storage (database driver).

| Table | Key Column | Description |
|-------|-----------|-------------|
| `cache` | `key` (PK) | Cache key with mediumText value and expiration |
| `cache_locks` | `key` (PK) | Distributed lock mechanism |

---

### `jobs` / `job_batches` / `failed_jobs`

Queue system tables.

| Table | Purpose |
|-------|---------|
| `jobs` | Pending queue jobs |
| `job_batches` | Batch job tracking |
| `failed_jobs` | Failed job log with exception details |

---

### `personal_access_tokens`

Laravel Sanctum API tokens.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `tokenable_type` | VARCHAR(255) | NO | — | Polymorphic type |
| `tokenable_id` | BIGINT | NO | — | Polymorphic ID |
| `name` | TEXT | NO | — | Token name |
| `token` | VARCHAR(64) | NO | — | Hashed token (unique) |
| `abilities` | TEXT | YES | NULL | JSON abilities |
| `last_used_at` | TIMESTAMP | YES | NULL | — |
| `expires_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `tokenable_type + tokenable_id`, `token` (UNIQUE), `expires_at`

---

## 2. Multi-Tenancy & Users

### `companies`

Tenant/company entity — each business is a separate company.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | Unique identifier |
| `name` | VARCHAR(255) | NO | — | Company name |
| `slug` | VARCHAR(255) | NO | — | URL-friendly name (unique) |
| `email` | VARCHAR(255) | YES | NULL | Company email |
| `phone` | VARCHAR(255) | YES | NULL | — |
| `address` | TEXT | YES | NULL | — |
| `city` | VARCHAR(255) | YES | NULL | — |
| `state` | VARCHAR(255) | YES | NULL | — |
| `country` | VARCHAR(255) | YES | NULL | — |
| `postal_code` | VARCHAR(255) | YES | NULL | — |
| `website` | VARCHAR(255) | YES | NULL | — |
| `domain` | VARCHAR(255) | YES | NULL | Custom domain |
| `gst_number` | VARCHAR(255) | YES | NULL | GST registration |
| `pan_number` | VARCHAR(255) | YES | NULL | PAN number |
| `logo` | VARCHAR(255) | YES | NULL | Logo file path |
| `favicon` | VARCHAR(255) | YES | NULL | Favicon file path |
| `currency` | VARCHAR(3) | NO | 'INR' | Default currency |
| `timezone` | VARCHAR(255) | NO | 'Asia/Kolkata' | Default timezone |
| `financial_year_start` | VARCHAR(5) | NO | '04-01' | FY start (MM-DD) |
| `financial_year_end` | VARCHAR(5) | NO | '03-31' | FY end (MM-DD) |
| `is_active` | BOOLEAN | NO | true | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `deleted_by` | VARCHAR(255) | YES | NULL | — |
| `deleted_by_id` | BIGINT (FK) | YES | NULL | → `users.id` |
| `version` | INT | NO | 1 | Offline sync |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `uuid` (UNIQUE), `slug` (UNIQUE)

---

### `user_devices`

Mobile device registration for push notifications.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | Unique identifier |
| `user_id` | BIGINT (FK) | NO | — | → `users.id` CASCADE |
| `company_id` | BIGINT (FK) | YES | NULL | → `companies.id` SET NULL |
| `device_id` | VARCHAR(255) | NO | — | Device fingerprint |
| `device_name` | VARCHAR(255) | YES | NULL | e.g. "iPhone 15" |
| `device_os` | VARCHAR(255) | YES | NULL | e.g. "iOS 18" |
| `push_token` | VARCHAR(255) | YES | NULL | APNs token |
| `fcm_token` | VARCHAR(255) | YES | NULL | Firebase token |
| `is_active` | BOOLEAN | NO | true | — |
| `is_trusted` | BOOLEAN | NO | false | — |
| `last_active_at` | TIMESTAMP | YES | NULL | — |
| `metadata` | JSON | YES | NULL | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |
| `updated_by_ip` | VARCHAR(255) | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `(user_id, is_active)`, `(company_id, device_type)`, `device_id`

---

## 3. Roles & Permissions

### `permissions`

Granular permission definitions.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `name` | VARCHAR(255) | NO | — | Display name |
| `slug` | VARCHAR(255) | NO | — | Code identifier (unique) |
| `module` | VARCHAR(255) | YES | NULL | Module grouping |
| `description` | TEXT | YES | NULL | — |
| `is_active` | BOOLEAN | NO | true | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `deleted_by` | VARCHAR(255) | YES | NULL | — |
| `deleted_by_id` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `slug` (UNIQUE), `module`

---

### `roles`

Role definitions scoped to companies.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | YES | NULL | → `companies.id` SET NULL |
| `name` | VARCHAR(255) | NO | — | Role name |
| `slug` | VARCHAR(255) | NO | — | Code identifier (unique) |
| `description` | TEXT | YES | NULL | — |
| `is_default` | BOOLEAN | NO | false | Auto-assigned to new users |
| `is_active` | BOOLEAN | NO | true | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `deleted_by` | VARCHAR(255) | YES | NULL | — |
| `deleted_by_id` | BIGINT (FK) | YES | NULL | → `users.id` |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `uuid` (UNIQUE), `slug` (UNIQUE), `(company_id, slug)`

---

### `permission_role` (Pivot)

Many-to-many: Permissions ↔ Roles.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `permission_id` | BIGINT (FK) | NO | — | → `permissions.id` CASCADE |
| `role_id` | BIGINT (FK) | NO | — | → `roles.id` CASCADE |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `(permission_id, role_id)` (UNIQUE)

---

### `role_user` (Pivot)

Many-to-many: Roles ↔ Users.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `role_id` | BIGINT (FK) | NO | — | → `roles.id` CASCADE |
| `user_id` | BIGINT (FK) | NO | — | → `users.id` CASCADE |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `(role_id, user_id)` (UNIQUE)

---

## 4. Settings & Configuration

### `settings`

Key-value settings scoped by company and group.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `company_id` | BIGINT (FK) | YES | NULL | → `companies.id` SET NULL |
| `group` | VARCHAR(255) | NO | 'general' | Setting group |
| `key` | VARCHAR(255) | NO | — | Setting key |
| `value` | TEXT | YES | NULL | Setting value |
| `description` | TEXT | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |
| `updated_by_ip` | VARCHAR(255) | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `(company_id, group, key)` (UNIQUE), `group`

---

### `themes`

Company-level theme/branding configuration.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | YES | NULL | → `companies.id` SET NULL |
| `name` | VARCHAR(255) | NO | 'Default' | Theme name |
| `primary_color` | VARCHAR(7) | NO | '#6366f1' | Hex color |
| `secondary_color` | VARCHAR(7) | NO | '#8b5cf6' | Hex color |
| `accent_color` | VARCHAR(7) | NO | '#06b6d4' | Hex color |
| `sidebar_color` | VARCHAR(7) | NO | '#1e1b4b' | Hex color |
| `header_color` | VARCHAR(7) | NO | '#ffffff' | Hex color |
| `text_color` | VARCHAR(7) | NO | '#1f2937' | Hex color |
| `bg_color` | VARCHAR(7) | NO | '#f9fafb' | Hex color |
| `font_family` | VARCHAR(255) | NO | 'Inter' | — |
| `logo_url` | VARCHAR(255) | YES | NULL | — |
| `favicon_url` | VARCHAR(255) | YES | NULL | — |
| `login_bg_url` | VARCHAR(255) | YES | NULL | — |
| `dark_mode` | BOOLEAN | NO | false | — |
| `custom_css` | JSON | YES | NULL | — |
| `is_active` | BOOLEAN | NO | true | — |
| `is_default` | BOOLEAN | NO | false | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |
| `updated_by_ip` | VARCHAR(255) | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `(company_id, is_active)`

---

## 5. Accounting Core

### `financial_years`

Financial year periods for each company.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `start_date` | DATE | NO | — | FY start date |
| `end_date` | DATE | NO | — | FY end date |
| `is_current` | BOOLEAN | NO | false | Active FY flag |
| `is_closed` | BOOLEAN | NO | false | Year-end closed |
| `closed_at` | DATE | YES | NULL | When closed |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `deleted_by` | VARCHAR(255) | YES | NULL | — |
| `deleted_by_id` | BIGINT (FK) | YES | NULL | → `users.id` |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `uuid` (UNIQUE), `(company_id, is_current)`, `(company_id, start_date, end_date)`

---

### `accounts`

Chart of accounts with hierarchical structure.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `financial_year_id` | BIGINT (FK) | YES | NULL | → `financial_years.id` SET NULL |
| `account_code` | VARCHAR(255) | NO | — | Unique code (e.g. "1001") |
| `account_name` | VARCHAR(255) | NO | — | Display name |
| `account_type` | ENUM | NO | — | `asset`, `liability`, `income`, `expense`, `equity` |
| `parent_id` | BIGINT (FK) | YES | NULL | → `accounts.id` SET NULL (self-ref) |
| `opening_balance` | DECIMAL(15,2) | NO | 0 | — |
| `opening_date` | DATE | YES | NULL | — |
| `remarks` | TEXT | YES | NULL | — |
| `is_active` | BOOLEAN | NO | true | — |
| `is_system` | BOOLEAN | NO | false | System-generated account |
| `is_bank_account` | BOOLEAN | NO | false | Linked to bank |
| `sort_order` | INT | NO | 0 | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `deleted_by` | VARCHAR(255) | YES | NULL | — |
| `deleted_by_id` | BIGINT (FK) | YES | NULL | → `users.id` |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `account_code` (UNIQUE), `uuid` (UNIQUE), `(company_id, account_type)`, `(company_id, is_active)`

**Relationships**: `parent_id` → self (hierarchical accounts)

---

### `parties`

Debtors and creditors (customers & suppliers).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `financial_year_id` | BIGINT (FK) | YES | NULL | → `financial_years.id` SET NULL |
| `party_code` | VARCHAR(255) | NO | — | Unique code |
| `name` | VARCHAR(255) | NO | — | Party name |
| `type` | ENUM | NO | — | `debtor`, `creditor` |
| `mobile` | VARCHAR(255) | YES | NULL | — |
| `email` | VARCHAR(255) | YES | NULL | — |
| `address` | TEXT | YES | NULL | — |
| `city` | VARCHAR(255) | YES | NULL | — |
| `state` | VARCHAR(255) | YES | NULL | — |
| `country` | VARCHAR(255) | YES | NULL | — |
| `postal_code` | VARCHAR(255) | YES | NULL | — |
| `gst_number` | VARCHAR(255) | YES | NULL | — |
| `pan_number` | VARCHAR(255) | YES | NULL | — |
| `opening_balance` | DECIMAL(15,2) | NO | 0 | — |
| `credit_limit` | INT | NO | 0 | — |
| `payment_terms_days` | INT | NO | 30 | — |
| `opening_date` | DATE | YES | NULL | — |
| `remarks` | TEXT | YES | NULL | — |
| `is_active` | BOOLEAN | NO | true | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `deleted_by` | VARCHAR(255) | YES | NULL | — |
| `deleted_by_id` | BIGINT (FK) | YES | NULL | → `users.id` |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `party_code` (UNIQUE), `uuid` (UNIQUE), `(company_id, type)`, `(company_id, is_active)`

---

### `vouchers`

Financial vouchers (income, expense, receipt, payment, journal, adjustment).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `financial_year_id` | BIGINT (FK) | YES | NULL | → `financial_years.id` SET NULL |
| `party_id` | BIGINT (FK) | YES | NULL | → `parties.id` SET NULL |
| `sales_invoice_id` | BIGINT (FK) | YES | NULL | → `sales_invoices.id` (deferred FK) |
| `purchase_invoice_id` | BIGINT (FK) | YES | NULL | → `purchase_invoices.id` (deferred FK) |
| `voucher_number` | VARCHAR(255) | NO | — | Auto-generated (unique) |
| `voucher_type` | ENUM | NO | — | `income`, `expense`, `receipt`, `payment`, `journal`, `adjustment` |
| `voucher_date` | DATE | NO | — | — |
| `narration` | TEXT | YES | NULL | — |
| `total_debit` | DECIMAL(15,2) | NO | 0 | — |
| `total_credit` | DECIMAL(15,2) | NO | 0 | — |
| `status` | ENUM | NO | 'draft' | `draft`, `posted`, `cancelled` |
| `remarks` | TEXT | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `deleted_by` | VARCHAR(255) | YES | NULL | — |
| `deleted_by_id` | BIGINT (FK) | YES | NULL | → `users.id` |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `voucher_number` (UNIQUE), `uuid` (UNIQUE), `(company_id, voucher_type)`, `(company_id, voucher_date)`, `(company_id, status)`

**Validation Rule**: `total_debit` must equal `total_credit`

---

### `voucher_lines`

Individual debit/credit line items within a voucher.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `voucher_id` | BIGINT (FK) | NO | — | → `vouchers.id` CASCADE |
| `account_id` | BIGINT (FK) | NO | — | → `accounts.id` CASCADE |
| `debit` | DECIMAL(15,2) | NO | 0 | — |
| `credit` | DECIMAL(15,2) | NO | 0 | — |
| `description` | TEXT | YES | NULL | — |
| `sort_order` | INT | NO | 0 | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE)

---

### `ledgers`

Auto-generated ledger entries from all transactions.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `financial_year_id` | BIGINT (FK) | YES | NULL | → `financial_years.id` SET NULL |
| `account_id` | BIGINT (FK) | NO | — | → `accounts.id` CASCADE |
| `party_id` | BIGINT (FK) | YES | NULL | → `parties.id` SET NULL |
| `voucher_id` | BIGINT (FK) | YES | NULL | → `vouchers.id` SET NULL |
| `transaction_date` | DATE | NO | — | — |
| `reference_id` | BIGINT | YES | NULL | Polymorphic ref |
| `description` | TEXT | YES | NULL | — |
| `debit` | DECIMAL(15,2) | NO | 0 | — |
| `credit` | DECIMAL(15,2) | NO | 0 | — |
| `running_balance` | DECIMAL(15,2) | NO | 0 | — |
| `balance_type` | ENUM | NO | 'debit' | `debit`, `credit` |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `(company_id, account_id, transaction_date)`, `(company_id, financial_year_id)`, `(reference_type, reference_id)`

---

### `tax_rates`

Tax rate configurations (GST, VAT, etc.).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `type` | ENUM | NO | 'gst' | `gst`, `igst`, `cgst_sgst`, `vat`, `exempt` |
| `cgst_rate` | DECIMAL(5,2) | NO | 0 | — |
| `sgst_rate` | DECIMAL(5,2) | NO | 0 | — |
| `igst_rate` | DECIMAL(5,2) | NO | 0 | — |
| `is_inclusive` | BOOLEAN | NO | false | Tax inclusive pricing |
| `is_active` | BOOLEAN | NO | true | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |
| `updated_by_ip` | VARCHAR(255) | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `(company_id, is_active)`

---

### `items`

Product and service items for invoicing.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `item_code` | VARCHAR(255) | NO | — | Unique code |
| `name` | VARCHAR(255) | NO | — | Item name |
| `hsn_sac_code` | VARCHAR(255) | YES | NULL | HSN/SAC code |
| `type` | ENUM | NO | 'goods' | `goods`, `service` |
| `tax_rate_id` | BIGINT (FK) | YES | NULL | → `tax_rates.id` SET NULL |
| `income_account_id` | BIGINT (FK) | YES | NULL | → `accounts.id` SET NULL |
| `expense_account_id` | BIGINT (FK) | YES | NULL | → `accounts.id` SET NULL |
| `purchase_price` | DECIMAL(15,2) | NO | 0 | — |
| `selling_price` | DECIMAL(15,2) | NO | 0 | — |
| `unit` | VARCHAR(255) | NO | 'nos' | Unit of measurement |
| `description` | TEXT | YES | NULL | — |
| `barcode` | VARCHAR(255) | YES | NULL | — |
| `opening_stock` | DECIMAL(15,2) | NO | 0 | — |
| `current_stock` | DECIMAL(15,2) | NO | 0 | — |
| `reorder_level` | DECIMAL(15,2) | NO | 0 | — |
| `is_active` | BOOLEAN | NO | true | — |
| `is_stockable` | BOOLEAN | NO | true | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `deleted_by` | VARCHAR(255) | YES | NULL | — |
| `deleted_by_id` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `item_code` (UNIQUE), `uuid` (UNIQUE), `(company_id, type)`, `(company_id, is_active)`

---

### `bank_accounts`

Bank account details linked to chart of accounts.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `account_id` | BIGINT (FK) | YES | NULL | → `accounts.id` SET NULL |
| `bank_name` | VARCHAR(255) | NO | — | — |
| `branch_name` | VARCHAR(255) | YES | NULL | — |
| `account_number` | VARCHAR(255) | NO | — | — |
| `ifsc_code` | VARCHAR(255) | YES | NULL | — |
| `account_holder_name` | VARCHAR(255) | YES | NULL | — |
| `account_type` | ENUM | NO | 'current' | `savings`, `current`, `fixed_deposit`, `cc_od` |
| `opening_balance` | DECIMAL(15,2) | NO | 0 | — |
| `opening_date` | DATE | YES | NULL | — |
| `upi_id` | VARCHAR(255) | YES | NULL | — |
| `is_default` | BOOLEAN | NO | false | Default bank |
| `is_active` | BOOLEAN | NO | true | — |
| `remarks` | TEXT | YES | NULL | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `deleted_by` | VARCHAR(255) | YES | NULL | — |
| `deleted_by_id` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `uuid` (UNIQUE), `(company_id, is_active)`

---

## 6. Invoicing

### `sales_invoices`

Sales invoices issued to customers (debtors).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `financial_year_id` | BIGINT (FK) | YES | NULL | → `financial_years.id` SET NULL |
| `party_id` | BIGINT (FK) | YES | NULL | → `parties.id` SET NULL |
| `invoice_number` | VARCHAR(255) | NO | — | Auto-generated (unique) |
| `invoice_date` | DATE | NO | — | — |
| `due_date` | DATE | NO | — | — |
| `reference_number` | VARCHAR(255) | YES | NULL | External reference |
| `notes` | TEXT | YES | NULL | — |
| `subtotal` | DECIMAL(15,2) | NO | 0 | — |
| `discount_amount` | DECIMAL(10,2) | NO | 0 | — |
| `discount_percentage` | DECIMAL(5,2) | NO | 0 | — |
| `tax_amount` | DECIMAL(15,2) | NO | 0 | — |
| `total` | DECIMAL(15,2) | NO | 0 | — |
| `amount_paid` | DECIMAL(15,2) | NO | 0 | — |
| `balance_due` | DECIMAL(15,2) | NO | 0 | — |
| `currency` | VARCHAR(3) | NO | 'INR' | — |
| `status` | ENUM | NO | 'draft' | `draft`, `sent`, `partial`, `paid`, `overdue`, `cancelled`, `credit_note` |
| `is_recurring` | BOOLEAN | NO | false | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `deleted_by` | VARCHAR(255) | YES | NULL | — |
| `deleted_by_id` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `invoice_number` (UNIQUE), `uuid` (UNIQUE), `(company_id, status)`, `(company_id, invoice_date)`, `party_id`

---

### `sales_invoice_lines`

Line items for sales invoices.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `sales_invoice_id` | BIGINT (FK) | NO | — | → `sales_invoices.id` CASCADE |
| `item_id` | BIGINT (FK) | YES | NULL | → `items.id` SET NULL |
| `account_id` | BIGINT (FK) | YES | NULL | → `accounts.id` SET NULL |
| `tax_rate_id` | BIGINT (FK) | YES | NULL | → `tax_rates.id` SET NULL |
| `description` | VARCHAR(255) | YES | NULL | — |
| `quantity` | DECIMAL(15,3) | NO | 1 | — |
| `unit_price` | DECIMAL(15,2) | NO | 0 | — |
| `discount_percentage` | DECIMAL(5,2) | NO | 0 | — |
| `discount_amount` | DECIMAL(10,2) | NO | 0 | — |
| `tax_amount` | DECIMAL(15,2) | NO | 0 | — |
| `total` | DECIMAL(15,2) | NO | 0 | — |
| `sort_order` | INT | NO | 0 | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `sales_invoice_id`

---

### `purchase_invoices`

Purchase invoices received from suppliers (creditors).

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `financial_year_id` | BIGINT (FK) | YES | NULL | → `financial_years.id` SET NULL |
| `party_id` | BIGINT (FK) | YES | NULL | → `parties.id` SET NULL |
| `invoice_number` | VARCHAR(255) | NO | — | Auto-generated (unique) |
| `supplier_invoice_number` | VARCHAR(255) | YES | NULL | Supplier's ref |
| `invoice_date` | DATE | NO | — | — |
| `due_date` | DATE | NO | — | — |
| `notes` | TEXT | YES | NULL | — |
| `subtotal` | DECIMAL(15,2) | NO | 0 | — |
| `discount_amount` | DECIMAL(10,2) | NO | 0 | — |
| `discount_percentage` | DECIMAL(5,2) | NO | 0 | — |
| `tax_amount` | DECIMAL(15,2) | NO | 0 | — |
| `total` | DECIMAL(15,2) | NO | 0 | — |
| `amount_paid` | DECIMAL(15,2) | NO | 0 | — |
| `balance_due` | DECIMAL(15,2) | NO | 0 | — |
| `currency` | VARCHAR(3) | NO | 'INR' | — |
| `status` | ENUM | NO | 'draft' | `draft`, `verified`, `partial`, `paid`, `overdue`, `cancelled`, `debit_note` |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `deleted_by` | VARCHAR(255) | YES | NULL | — |
| `deleted_by_id` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `invoice_number` (UNIQUE), `uuid` (UNIQUE), `(company_id, status)`, `(company_id, invoice_date)`, `party_id`

---

### `purchase_invoice_lines`

Line items for purchase invoices.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `purchase_invoice_id` | BIGINT (FK) | NO | — | → `purchase_invoices.id` CASCADE |
| `item_id` | BIGINT (FK) | YES | NULL | → `items.id` SET NULL |
| `account_id` | BIGINT (FK) | YES | NULL | → `accounts.id` SET NULL |
| `tax_rate_id` | BIGINT (FK) | YES | NULL | → `tax_rates.id` SET NULL |
| `description` | VARCHAR(255) | YES | NULL | — |
| `quantity` | DECIMAL(15,3) | NO | 1 | — |
| `unit_price` | DECIMAL(15,2) | NO | 0 | — |
| `discount_percentage` | DECIMAL(5,2) | NO | 0 | — |
| `discount_amount` | DECIMAL(10,2) | NO | 0 | — |
| `tax_amount` | DECIMAL(15,2) | NO | 0 | — |
| `total` | DECIMAL(15,2) | NO | 0 | — |
| `sort_order` | INT | NO | 0 | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `purchase_invoice_id`

---

## 7. Subscriptions & Billing

### `subscription_plans`

Available subscription plans.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `name` | VARCHAR(255) | NO | — | Plan name |
| `slug` | VARCHAR(255) | NO | — | URL-friendly (unique) |
| `description` | TEXT | YES | NULL | — |
| `monthly_price` | DECIMAL(10,2) | NO | — | — |
| `yearly_price` | DECIMAL(10,2) | YES | NULL | — |
| `currency` | VARCHAR(3) | NO | 'INR' | — |
| `trial_days` | INT | NO | 0 | — |
| `max_users` | INT | NO | 5 | — |
| `max_transactions` | INT | NO | 1000 | — |
| `max_accounts` | INT | NO | 50 | — |
| `max_parties` | INT | NO | 100 | — |
| `features` | JSON | YES | NULL | Feature list |
| `sort_order` | INT | NO | 0 | — |
| `is_active` | BOOLEAN | NO | true | — |
| `is_default` | BOOLEAN | NO | false | — |
| `is_visible` | BOOLEAN | NO | true | Show on pricing page |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |
| `updated_by_ip` | VARCHAR(255) | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `slug` (UNIQUE)

---

### `subscriptions`

Company-level subscriptions.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `plan_id` | BIGINT (FK) | NO | — | → `subscription_plans.id` RESTRICT |
| `status` | ENUM | NO | 'trial' | `trial`, `active`, `past_due`, `cancelled`, `expired`, `paused` |
| `billing_cycle` | ENUM | NO | 'monthly' | `monthly`, `yearly` |
| `start_date` | DATE | NO | — | — |
| `trial_end_date` | DATE | YES | NULL | — |
| `current_period_start` | DATE | YES | NULL | — |
| `current_period_end` | DATE | YES | NULL | — |
| `cancelled_at` | DATE | YES | NULL | — |
| `pause_until` | DATE | YES | NULL | — |
| `amount` | DECIMAL(10,2) | NO | — | — |
| `currency` | VARCHAR(3) | NO | 'INR' | — |
| `razorpay_subscription_id` | VARCHAR(255) | YES | NULL | — |
| `metadata` | JSON | YES | NULL | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |
| `updated_by_ip` | VARCHAR(255) | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `uuid` (UNIQUE), `(company_id, status)`, `current_period_end`

---

### `subscription_invoices`

Invoices generated for subscription billing.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `invoice_number` | VARCHAR(255) | NO | — | Unique |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `subscription_id` | BIGINT (FK) | NO | — | → `subscriptions.id` CASCADE |
| `subtotal` | DECIMAL(12,2) | NO | — | — |
| `tax_amount` | DECIMAL(10,2) | NO | 0 | — |
| `discount_amount` | DECIMAL(10,2) | NO | 0 | — |
| `total` | DECIMAL(12,2) | NO | — | — |
| `currency` | VARCHAR(3) | NO | 'INR' | — |
| `status` | ENUM | NO | 'draft' | `draft`, `sent`, `paid`, `overdue`, `cancelled`, `refunded` |
| `invoice_date` | DATE | NO | — | — |
| `due_date` | DATE | NO | — | — |
| `paid_at` | TIMESTAMP | YES | NULL | — |
| `line_items` | JSON | YES | NULL | — |
| `notes` | TEXT | YES | NULL | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |
| `updated_by_ip` | VARCHAR(255) | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `invoice_number` (UNIQUE), `uuid` (UNIQUE), `(company_id, status)`

---

### `subscription_payments`

Payment records for subscription invoices via Razorpay.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `subscription_id` | BIGINT (FK) | NO | — | → `subscriptions.id` CASCADE |
| `invoice_id` | BIGINT (FK) | YES | NULL | → `subscription_invoices.id` SET NULL |
| `razorpay_payment_id` | VARCHAR(255) | YES | NULL | — |
| `razorpay_order_id` | VARCHAR(255) | YES | NULL | — |
| `amount` | DECIMAL(12,2) | NO | — | — |
| `currency` | VARCHAR(3) | NO | 'INR' | — |
| `status` | ENUM | NO | 'pending' | `pending`, `captured`, `failed`, `refunded` |
| `method` | VARCHAR(50) | YES | NULL | Payment method |
| `paid_at` | TIMESTAMP | YES | NULL | — |
| `error_code` | VARCHAR(100) | YES | NULL | — |
| `error_description` | TEXT | YES | NULL | — |
| `metadata` | JSON | YES | NULL | — |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `razorpay_payment_id`, `(company_id, status)`

---

### `razorpay_orders`

Razorpay order records.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `subscription_id` | BIGINT (FK) | YES | NULL | → `subscriptions.id` SET NULL |
| `razorpay_order_id` | VARCHAR(255) | NO | — | Razorpay order ID |
| `amount` | DECIMAL(12,2) | NO | — | — |
| `currency` | VARCHAR(3) | NO | 'INR' | — |
| `status` | ENUM | NO | 'created' | `created`, `attempted`, `paid`, `failed` |
| `receipt` | VARCHAR(255) | YES | NULL | — |
| `notes` | JSON | YES | NULL | — |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `razorpay_order_id` (UNIQUE)

---

### `razorpay_webhooks`

Webhook event log from Razorpay.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `event` | VARCHAR(255) | NO | — | Event type |
| `payload` | JSON | NO | — | Full webhook payload |
| `processed` | BOOLEAN | NO | false | — |
| `processed_at` | TIMESTAMP | YES | NULL | — |
| `error` | TEXT | YES | NULL | Processing error |
| `created_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `event`, `processed`

---

## 8. Website & CMS

### `website_pages`

CMS-managed website pages.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `slug` | VARCHAR(255) | NO | — | URL slug (unique) |
| `title` | VARCHAR(255) | NO | — | Page title |
| `meta_title` | VARCHAR(255) | YES | NULL | SEO title |
| `meta_description` | TEXT | YES | NULL | SEO description |
| `content` | LONGTEXT | YES | NULL | HTML content |
| `template` | VARCHAR(255) | NO | 'default' | Blade template |
| `status` | ENUM | NO | 'draft' | `draft`, `published`, `archived` |
| `show_in_nav` | BOOLEAN | NO | false | — |
| `nav_order` | INT | NO | 0 | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |
| `updated_by_ip` | VARCHAR(255) | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `slug` (UNIQUE), `status`, `(show_in_nav, nav_order)`

---

### `faqs`

FAQ entries for the website.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `question` | VARCHAR(500) | NO | — | — |
| `answer` | TEXT | NO | — | — |
| `category` | VARCHAR(100) | YES | NULL | Grouping |
| `sort_order` | INT | NO | 0 | — |
| `is_active` | BOOLEAN | NO | true | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |
| `updated_by_ip` | VARCHAR(255) | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `(is_active, sort_order)`, `category`

---

### `testimonials`

Client testimonials for the website.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `client_name` | VARCHAR(255) | NO | — | — |
| `company_name` | VARCHAR(255) | YES | NULL | — |
| `designation` | VARCHAR(255) | YES | NULL | — |
| `testimonial` | TEXT | NO | — | — |
| `avatar` | VARCHAR(255) | YES | NULL | — |
| `rating` | INT | NO | 5 | 1-5 stars |
| `is_featured` | BOOLEAN | NO | false | — |
| `is_active` | BOOLEAN | NO | true | — |
| `sort_order` | INT | NO | 0 | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |
| `updated_by_ip` | VARCHAR(255) | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `(is_active, is_featured)`

---

### `pricing_displays`

Display configuration for pricing plans on the website.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `plan_id` | BIGINT (FK) | NO | — | → `subscription_plans.id` CASCADE |
| `badge` | VARCHAR(255) | YES | NULL | e.g. "Popular" |
| `highlight_color` | VARCHAR(7) | YES | NULL | Hex color |
| `description_short` | TEXT | YES | NULL | — |
| `description_long` | TEXT | YES | NULL | — |
| `features_list` | JSON | YES | NULL | Array of feature strings |
| `sort_order` | INT | NO | 0 | — |
| `is_active` | BOOLEAN | NO | true | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |
| `updated_by_ip` | VARCHAR(255) | YES | NULL | — |

**Indexes**: `(is_active, sort_order)`

---

### `contact_submissions`

Contact form submissions from the website.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `name` | VARCHAR(255) | NO | — | — |
| `email` | VARCHAR(255) | NO | — | — |
| `phone` | VARCHAR(20) | YES | NULL | — |
| `subject` | VARCHAR(255) | YES | NULL | — |
| `message` | TEXT | NO | — | — |
| `status` | ENUM | NO | 'new' | `new`, `read`, `replied`, `archived` |
| `admin_notes` | TEXT | YES | NULL | Internal notes |
| `read_at` | TIMESTAMP | YES | NULL | — |
| `replied_at` | TIMESTAMP | YES | NULL | — |
| `replied_by` | VARCHAR(255) | YES | NULL | — |
| `replied_by_id` | BIGINT (FK) | YES | NULL | → `users.id` SET NULL |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `status`, `created_at`

---

## 9. Security & Audit

### `audit_logs`

Comprehensive activity tracking.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `company_id` | BIGINT (FK) | YES | NULL | → `companies.id` SET NULL |
| `user_id` | BIGINT (FK) | YES | NULL | → `users.id` SET NULL |
| `module` | VARCHAR(255) | YES | NULL | Module name |
| `action` | VARCHAR(255) | YES | NULL | create, update, delete, etc. |
| `record_id` | BIGINT | YES | NULL | Affected record ID |
| `old_values` | JSON | YES | NULL | Before state |
| `new_values` | JSON | YES | NULL | After state |
| `ip_address` | VARCHAR(255) | YES | NULL | — |
| `user_agent` | VARCHAR(255) | YES | NULL | — |
| `description` | TEXT | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `(company_id, module)`, `(user_id, action)`, `created_at`

---

### `login_history`

Login attempt tracking.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `user_id` | BIGINT (FK) | YES | NULL | → `users.id` SET NULL |
| `company_id` | BIGINT (FK) | YES | NULL | → `companies.id` SET NULL |
| `ip_address` | VARCHAR(45) | YES | NULL | — |
| `user_agent` | TEXT | YES | NULL | — |
| `device_name` | VARCHAR(255) | YES | NULL | — |
| `device_os` | VARCHAR(255) | YES | NULL | — |
| `browser` | VARCHAR(255) | YES | NULL | — |
| `location` | VARCHAR(255) | YES | NULL | — |
| `status` | ENUM | NO | 'success' | `success`, `failed`, `blocked` |
| `failure_reason` | VARCHAR(255) | YES | NULL | — |
| `session_id` | VARCHAR(255) | YES | NULL | — |
| `logged_out_at` | TIMESTAMP | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `(user_id, status)`, `(company_id, created_at)`, `ip_address`

---

### `otp_verifications`

OTP verification for signup and password reset.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `identifier` | VARCHAR(255) | NO | — | Email or phone |
| `purpose` | VARCHAR(255) | NO | — | signup, reset, etc. |
| `otp` | VARCHAR(10) | NO | — | — |
| `status` | ENUM | NO | 'pending' | `pending`, `verified`, `expired` |
| `attempts` | INT | NO | 0 | — |
| `max_attempts` | INT | NO | 3 | — |
| `expires_at` | TIMESTAMP | NO | — | — |
| `verified_at` | TIMESTAMP | YES | NULL | — |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |

**Indexes**: `(identifier, purpose)`, `status`, `expires_at`

---

---

## 10. Notifications & Communication

### `notifications`

System notifications for subscription alerts, invoice alerts, receivable reminders, sync status, and system events.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | Unique identifier |
| `company_id` | BIGINT (FK) | YES | NULL | → `companies.id` SET NULL |
| `user_id` | BIGINT (FK) | YES | NULL | → `users.id` SET NULL |
| `type` | VARCHAR(255) | NO | — | Notification type (e.g. `subscription.expiring`, `invoice.overdue`) |
| `title` | VARCHAR(255) | NO | — | Short title |
| `message` | TEXT | NO | — | Full message body |
| `priority` | VARCHAR(255) | NO | 'normal' | `low`, `normal`, `high`, `urgent` |
| `icon` | VARCHAR(255) | YES | NULL | Bootstrap icon class |
| `color` | VARCHAR(255) | YES | NULL | Bootstrap color class |
| `link_module` | VARCHAR(255) | YES | NULL | Deep link module name |
| `link_id` | VARCHAR(255) | YES | NULL | Deep link record ID |
| `is_read` | BOOLEAN | NO | false | — |
| `read_at` | TIMESTAMP | YES | NULL | — |
| `channel` | VARCHAR(255) | NO | 'in_app' | `in_app`, `email`, `sms`, `push` |
| `sent_at` | TIMESTAMP | YES | NULL | — |
| `data` | JSON | YES | NULL | Extra payload |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `(company_id, user_id, is_read)`, `(user_id, is_read, created_at)`, `type`, `priority`, `created_at`

---

### `receivable_reminders`

Automated and manual receivable follow-up reminders linked to sales invoices.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `sales_invoice_id` | BIGINT (FK) | NO | — | → `sales_invoices.id` CASCADE |
| `party_id` | BIGINT (FK) | NO | — | → `parties.id` CASCADE |
| `due_date` | DATE | NO | — | Invoice due date |
| `reminder_date` | DATE | NO | — | When to send |
| `reminder_sequence` | INT | NO | 1 | 1st, 2nd, 3rd reminder |
| `channel` | VARCHAR(255) | NO | 'whatsapp' | `whatsapp`, `sms`, `email` |
| `template_name` | VARCHAR(255) | YES | NULL | Message template |
| `phone_number` | VARCHAR(255) | YES | NULL | — |
| `email_address` | VARCHAR(255) | YES | NULL | — |
| `message_content` | TEXT | YES | NULL | Final message |
| `status` | ENUM | NO | 'pending' | `pending`, `scheduled`, `sent`, `failed`, `cancelled` |
| `failure_reason` | TEXT | YES | NULL | — |
| `sent_at` | TIMESTAMP | YES | NULL | — |
| `invoice_total` | DECIMAL(15,2) | NO | 0 | Snapshot |
| `amount_paid` | DECIMAL(15,2) | NO | 0 | Snapshot |
| `balance_due` | DECIMAL(15,2) | NO | 0 | Snapshot |
| `days_overdue` | INT | NO | 0 | — |
| `type` | VARCHAR(255) | NO | 'automatic' | `automatic`, `manual` |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `updated_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `(company_id, status)`, `(party_id, status)`, `(sales_invoice_id, status)`, `(reminder_date, status)`, `due_date`

---

### `whatsapp_logs`

WhatsApp communication log for every message sent.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | NO | — | → `companies.id` CASCADE |
| `party_id` | BIGINT (FK) | YES | NULL | → `parties.id` SET NULL |
| `sales_invoice_id` | BIGINT (FK) | YES | NULL | → `sales_invoices.id` SET NULL |
| `phone_number` | VARCHAR(255) | NO | — | — |
| `template_name` | VARCHAR(255) | YES | NULL | — |
| `message_content` | TEXT | YES | NULL | — |
| `message_type` | VARCHAR(255) | NO | 'text' | `text`, `template`, `media`, `document` |
| `status` | ENUM | NO | 'queued' | `queued`, `sent`, `delivered`, `read`, `failed` |
| `external_message_id` | VARCHAR(255) | YES | NULL | WhatsApp API message ID |
| `sent_at` | TIMESTAMP | YES | NULL | — |
| `delivered_at` | TIMESTAMP | YES | NULL | — |
| `read_at` | TIMESTAMP | YES | NULL | — |
| `failure_reason` | TEXT | YES | NULL | — |
| `request_payload` | JSON | YES | NULL | — |
| `response_metadata` | JSON | YES | NULL | — |
| `retry_count` | INT | NO | 0 | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `(company_id, status)`, `(party_id, status)`, `phone_number`, `external_message_id`, `status`, `created_at`

---

## 11. Offline Sync

### `sync_queue`

Queue for mobile-to-server synchronization of records.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `table_name` | VARCHAR(255) | NO | — | Source table |
| `record_uuid` | VARCHAR(255) | NO | — | UUID of the record |
| `operation` | ENUM | NO | 'create' | `create`, `update`, `delete` |
| `payload` | JSON | YES | NULL | Full record data |
| `metadata` | JSON | YES | NULL | Conflict resolution context |
| `status` | ENUM | NO | 'pending' | `pending`, `processing`, `completed`, `failed` |
| `retry_count` | INT | NO | 0 | — |
| `max_retries` | INT | NO | 3 | — |
| `error_message` | TEXT | YES | NULL | — |
| `processed_at` | TIMESTAMP | YES | NULL | — |
| `device_id` | VARCHAR(255) | YES | NULL | Source device |
| `user_id` | BIGINT (FK) | YES | NULL | → `users.id` SET NULL |
| `company_id` | BIGINT (FK) | YES | NULL | → `companies.id` SET NULL |
| `local_version` | INT | YES | NULL | Version from mobile |
| `server_version` | INT | YES | NULL | Version on server |
| `conflict_resolution` | VARCHAR(255) | YES | NULL | `server_wins`, `client_wins`, `manual` |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |

**Indexes**: `uuid` (UNIQUE), `(table_name, status)`, `(status, created_at)`, `(user_id, status)`, `(company_id, status)`, `record_uuid`, `device_id`

---

## 12. File Attachments

### `attachments`

Polymorphic file storage for invoices, company documents, and user uploads.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT (PK) | NO | AUTO_INCREMENT | — |
| `uuid` | CHAR(36) | NO | — | — |
| `company_id` | BIGINT (FK) | YES | NULL | → `companies.id` SET NULL |
| `module_type` | VARCHAR(255) | NO | — | Polymorphic type (e.g. `sales_invoices`, `companies`) |
| `module_id` | BIGINT | NO | — | Polymorphic ID |
| `file_name` | VARCHAR(255) | NO | — | Stored file name |
| `original_name` | VARCHAR(255) | NO | — | Original upload name |
| `file_path` | VARCHAR(255) | NO | — | Storage path |
| `file_disk` | VARCHAR(255) | NO | 'local' | Storage disk |
| `file_size` | BIGINT | NO | 0 | Size in bytes |
| `mime_type` | VARCHAR(255) | YES | NULL | — |
| `extension` | VARCHAR(255) | YES | NULL | — |
| `category` | VARCHAR(255) | YES | NULL | `invoice_pdf`, `gst_certificate`, etc. |
| `description` | VARCHAR(255) | YES | NULL | — |
| `created_by` | BIGINT (FK) | YES | NULL | → `users.id` |
| `created_by_ip` | VARCHAR(255) | YES | NULL | — |
| `version` | INT | NO | 1 | — |
| `synced_at` | TIMESTAMP | YES | NULL | — |
| `created_at` | TIMESTAMP | YES | NULL | — |
| `updated_at` | TIMESTAMP | YES | NULL | — |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete |

**Indexes**: `uuid` (UNIQUE), `(module_type, module_id)`, `(company_id, module_type)`, `category`

---

## 13. Entity Relationship Diagram

```
┌─────────────┐     ┌──────────────┐     ┌──────────────┐
│  companies   │────<│    users      │────<│  login_history│
└──────┬──────┘     └──────┬───────┘     └──────────────┘
       │                   │
       │                   ├────< role_user >──── roles
       │                   │                      │
       │                   │                      ▼
       │                   │               permission_role
       │                   │                      │
       │                   │                      ▼
       │                   │                permissions
       │                   │
       ├────< financial_years
       │           │
       ├────< settings
       ├────< themes
       ├────< subscriptions ──── subscription_plans
       │           │
       │           ├────< subscription_invoices
       │           └────< subscription_payments
       │
       ├────< accounts (self-ref: parent_id)
       │           │
       │           ├──< voucher_lines
       │           ├──< ledgers
       │           ├──< sales_invoice_lines
       │           └──< purchase_invoice_lines
       │
       ├────< parties
       │           │
       │           ├──< vouchers
       │           ├──< sales_invoices
       │           └──< purchase_invoices
       │
       ├────< vouchers ────< voucher_lines
       │           │
       │           └──< ledgers
       │
       ├────< sales_invoices ────< sales_invoice_lines
       ├────< purchase_invoices ────< purchase_invoice_lines
       ├────< tax_rates
       │           │
       │           ├──< items
       │           ├──< sales_invoice_lines
       │           └──< purchase_invoice_lines
       │
       ├────< items
       ├────< bank_accounts
       ├────< audit_logs
       ├────< notifications
       ├────< attachments (polymorphic)
       └────< sync_queue

Communication:
┌─────────────────────┐
│ receivable_reminders │─── sales_invoices, parties
├─────────────────────┤
│    whatsapp_logs     │─── parties, sales_invoices
└─────────────────────┘

Website (no company_id):
┌──────────────┐
│ website_pages │
├──────────────┤
│     faqs      │
├──────────────┤
│ testimonials  │
├──────────────┤
│pricing_displays│─── subscription_plans
├──────────────┤
│contact_submissions│
└──────────────┘
```

---

## Migration Execution Order

| # | Migration | Tables |
|---|-----------|--------|
| 1 | `0001_01_01_000000` | `users`, `password_reset_tokens`, `sessions` |
| 2 | `0001_01_01_000001` | `cache`, `cache_locks` |
| 3 | `0001_01_01_000002` | `jobs`, `job_batches`, `failed_jobs` |
| 4 | `2026_06_04_091606` | `personal_access_tokens` |
| 5 | `2026_06_04_091655` | `companies` |
| 6 | `2026_06_04_091747` | Alter `users` (add uuid, company_id, role, status) |
| 7 | `2026_06_04_092448` | Alter `users` (add audit fields) |
| 8 | `2026_06_04_094840` | `permissions`, `roles` |
| 9 | `2026_06_04_094841` | `permission_role`, `role_user` |
| 10 | `2026_06_04_100445` | `settings` |
| 11 | `2026_06_04_100903` | `financial_years` |
| 12 | `2026_06_04_101335` | `accounts` |
| 13 | `2026_06_04_103234` | `parties` |
| 14 | `2026_06_04_103235` | `vouchers` |
| 15 | `2026_06_04_103240` | `voucher_lines` |
| 16 | `2026_06_04_104551` | `ledgers` |
| 17 | `2026_06_04_110120` | `audit_logs` |
| 18 | `2026_06_04_120959` | Alter `users` (add pin, biometric fields) |
| 19 | `2026_06_05_051326` | `themes` |
| 20 | `2026_06_05_051542` | `otp_verifications` |
| 21 | `2026_06_05_051543` | `login_history`, `subscription_plans`, `user_devices` |
| 22 | `2026_06_05_051553` | `tax_rates` |
| 23 | `2026_06_05_051554` | `items` |
| 24 | `2026_06_05_051555` | `bank_accounts` |
| 25 | `2026_06_05_051556` | `website_pages` |
| 26 | `2026_06_05_051557` | `contact_submissions`, `faqs`, `testimonials` |
| 27 | `2026_06_05_051558` | `pricing_displays` |
| 28 | `2026_06_05_060109` | `subscriptions` |
| 29 | `2026_06_05_060110` | `subscription_invoices` |
| 30 | `2026_06_05_060111` | `purchase_invoices`, `sales_invoices` |
| 31 | `2026_06_05_060112` | `sales_invoice_lines` |
| 32 | `2026_06_05_060113` | `purchase_invoice_lines` |
| 33 | `2026_06_05_060115` | `subscription_payments` |
| 34 | `2026_06_05_060117` | `razorpay_webhooks` |
| 35 | `2026_06_05_060118` | Alter `vouchers` (add invoice FKs) |
| 36 | `2026_06_05_075828` | `razorpay_orders` |
| 37 | `2026_06_05_100001` | `notifications` |
| 38 | `2026_06_05_100002` | `receivable_reminders` |
| 39 | `2026_06_05_100003` | `whatsapp_logs` |
| 40 | `2026_06_05_100004` | `sync_queue` |
| 41 | `2026_06_05_100005` | `attachments` |

---

## Conventions

| Pattern | Implementation |
|---------|---------------|
| **UUID** | All business tables have `uuid` column via `HasUuid` trait |
| **Soft Deletes** | Major entities use `deleted_at` + `deleted_by` + `deleted_by_id` |
| **Audit Fields** | `created_by`, `updated_by`, `created_by_ip`, `updated_by_ip` |
| **Sync Fields** | `version` (INT), `synced_at` (TIMESTAMP) for offline-first |
| **Money** | `DECIMAL(15,2)` for all monetary values |
| **Multi-Tenancy** | `company_id` FK on all business tables |
| **Status Enums** | Consistent enum values per domain |

---

*Generated on 2026-06-05 for Reco v1.0*

---

## Gap Analysis & Readiness Assessment

### Accounting Architecture Review

| Component | Status | Notes |
|-----------|--------|-------|
| Chart of Accounts | ✅ Complete | Hierarchical with `parent_id`, 5 account types |
| Vouchers | ✅ Complete | Double-entry with debit/credit validation |
| Voucher Lines | ✅ Complete | Linked to accounts, FK cascade |
| Ledgers | ✅ Complete | Running balance, balance type, polymorphic reference |
| Sales Invoices | ✅ Complete | Full lifecycle: draft → sent → paid |
| Purchase Invoices | ✅ Complete | Full lifecycle with supplier ref |
| Tax Rates | ✅ Complete | GST/IGST/CGST/SGST/VAT support |
| Items | ✅ Complete | HSN/SAC, stock tracking, tax linkage |
| Bank Accounts | ✅ Complete | Linked to chart of accounts |
| **Missing: Journal Templates** | ⚠️ Recommend | Pre-defined journal entry templates for recurring transactions |
| **Missing: Cost Centers** | ⚠️ Recommend | Department/project-wise expense allocation |

### SaaS Architecture Review

| Component | Status | Notes |
|-----------|--------|-------|
| Company isolation | ✅ Complete | `company_id` FK on all business tables |
| Subscription plans | ✅ Complete | Monthly/yearly, trial, limits |
| Subscription lifecycle | ✅ Complete | Trial → active → expired → cancelled |
| Payment gateway | ✅ Complete | Razorpay orders + webhooks |
| **Missing: Usage Tracking** | ⚠️ Recommend | Track actual usage vs plan limits (users, transactions) |
| **Missing: Company Invites** | ⚠️ Recommend | Invite users to company via email |

### Offline-First Review

| Component | Status | Notes |
|-----------|--------|-------|
| UUID on all tables | ✅ Complete | `HasUuid` trait auto-generates |
| Version tracking | ✅ Complete | `version` INT on all business tables |
| Sync timestamp | ✅ Complete | `synced_at` on all business tables |
| Sync queue | ✅ Complete | `sync_queue` table for mobile sync |
| Conflict resolution | ✅ Complete | `local_version`, `server_version`, `conflict_resolution` |
| **Missing: Delta Sync** | ⚠️ Recommend | Track only changed fields instead of full record |

### Missing Modules — Now Implemented

| Module | Table | Status |
|--------|-------|--------|
| Notifications | `notifications` | ✅ Created |
| Receivable Reminders | `receivable_reminders` | ✅ Created |
| WhatsApp Logs | `whatsapp_logs` | ✅ Created |
| Offline Sync Queue | `sync_queue` | ✅ Created |
| File Attachments | `attachments` | ✅ Created |

---

## Final Database Readiness Score

### **Score: 8.5 / 10** ⭐

### What's Complete (8.5 points)

| Category | Score | Max |
|----------|-------|-----|
| Core Accounting | 2.0 | 2.0 |
| Multi-Tenancy | 1.5 | 1.5 |
| Subscriptions & Billing | 1.0 | 1.0 |
| Website & CMS | 1.0 | 1.0 |
| Security & Audit | 1.0 | 1.0 |
| Offline-First | 1.0 | 1.0 |
| Communication | 1.0 | 1.0 |

### What's Required to Reach 10/10

| Missing Item | Impact | Priority |
|-------------|--------|----------|
| **Journal Templates** — Pre-defined recurring entries | Reduces manual data entry | Medium |
| **Cost Centers** — Department/project allocation | Required for multi-department businesses | Medium |
| **Usage Tracking** — Monitor plan limits | Required for SaaS billing enforcement | High |
| **Company Invites** — Email invitation flow | Required for multi-user onboarding | High |
| **Data Export/Import** — Bulk data migration | Required for onboarding from other software | Medium |
| **Report Snapshots** — Pre-computed report cache | Performance optimization for large datasets | Low |
| **Webhook Logs** — Generic webhook event tracking | Beyond Razorpay — for future integrations | Low |

### Production Readiness Checklist

- ✅ 49 tables, all with proper indexes
- ✅ Foreign keys with proper ON DELETE actions
- ✅ Soft deletes on all major entities
- ✅ Audit trail (created_by, updated_by, IP tracking)
- ✅ Offline sync metadata (uuid, version, synced_at)
- ✅ Double-entry accounting validation
- ✅ Subscription lifecycle management
- ✅ Polymorphic file attachments
- ✅ Notification system
- ✅ Communication logging

**Verdict**: The database is **production-ready** for a single-company launch. The remaining 1.5 points are enhancements needed for scale (multi-department, heavy SaaS usage, bulk migration).
