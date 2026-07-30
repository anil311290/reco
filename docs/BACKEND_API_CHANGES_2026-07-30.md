# Backend API Changes — 30 July 2026

This document is the backend handoff for the mobile app developer. It describes the API contract only; no mobile app implementation is included.

## Base URL

All endpoints below use the existing API prefix:

```text
/api/v1
```

## Registration Flow

### 1. Load plans before registration

```http
GET /api/v1/plans
```

- Authentication: not required
- Returns only active and publicly visible plans.
- Use `data[].slug` as the registration `plan_slug`.

Example response:

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Trial",
      "slug": "trial",
      "description": "Free 14-day trial with basic features",
      "monthly_price": 0,
      "yearly_price": 0,
      "lifetime_price": 0,
      "currency": "INR",
      "trial_days": 14,
      "max_users": 2,
      "max_transactions": 100,
      "max_accounts": 30,
      "max_parties": 30,
      "features": [
        "basic_reports",
        "voucher_management",
        "party_management"
      ],
      "is_default": true
    }
  ]
}
```

### 2. Register company owner

```http
POST /api/v1/register
Content-Type: application/json
```

Required request:

```json
{
  "name": "Anil Prajapati",
  "email": "anil@example.com",
  "company_name": "ABC IT Solutions",
  "plan_slug": "trial",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Optional request field:

```json
{
  "phone": "+91 9876543210"
}
```

Important:

- `plan_slug` is required and must come from `GET /api/v1/plans`.
- `company_email` has been removed. Do not send it.
- The owner `email` is automatically stored as the company email.
- A unique company slug is generated automatically. Companies may have the same display name.
- The owner is assigned the company administrator role.
- The company and user are created in pending/inactive state until admin approval.
- The selected subscription and current financial year are created during registration.

Successful response:

```json
{
  "success": true,
  "message": "Registration successful. Your account is pending admin approval.",
  "data": {
    "id": 10,
    "name": "Anil Prajapati",
    "email": "anil@example.com",
    "status": "pending"
  }
}
```

Validation response (`422`):

```json
{
  "success": false,
  "message": "Please select a subscription plan",
  "errors": {
    "plan_slug": [
      "Please select a subscription plan"
    ]
  }
}
```

### 3. Pending-account login

```http
POST /api/v1/login
```

Pending users cannot log in. The API returns HTTP `422` with an email validation error until an administrator activates the company and account.

The mobile app should show the returned API message and must not create a local authenticated session.

## Database and Tenant Safety

- Role slugs are unique per company, not globally.
- Multiple companies can receive the same default role slugs (`admin`, `manager`, `accountant`, `viewer`).
- User status supports `pending`.
- Seeders are insert-only: existing seeded records are not replaced, duplicated, restored, or deleted.

## Removed Request Field

Remove this field from all registration payloads:

```json
{
  "company_email": "company@example.com"
}
```

Company email remains editable later through the authenticated company settings API.

## Soft-Deleted Party Create Conflict

```http
POST /api/v1/parties
```

If a soft-deleted party already exists with the same normalized name, the first create request returns HTTP `409`:

```json
{
  "success": false,
  "code": "SOFT_DELETED_PARTY_EXISTS",
  "message": "A deleted party with this name already exists.",
  "data": {
    "party_code": "AR001",
    "party_name": "Default Customer"
  }
}
```

Retry with one of:

```json
{
  "duplicate_action": "restore"
}
```

or

```json
{
  "duplicate_action": "new_entry"
}
```

- `restore` restores and updates the deleted party.
- `new_entry` creates a new party and generates a new AR/AP code.

## Payment / Receipt Vouchers

Do not send `payment_mode` on payment or receipt vouchers.

Required fields:

```json
{
  "voucher_date": "2026-07-30",
  "cash_bank_account_id": 12,
  "payment_rows": [
    {
      "account_id": 45,
      "amount": 1000
    }
  ]
}
```

`cash_bank_account_id` may be any Cash, Bank, or OD ledger from `GET /api/v1/accounts/cash-bank`.

Invoice settlement still requires `payment_mode` + `cash_bank_account_id` + `amount`. That is separate from voucher create.

## Items

- Do not send `item_code` on create. The server generates `ITEM-001`, `ITEM-002`, etc.
- Use `opening_stock` for opening quantity.
- Updating `opening_stock` adjusts `current_stock` by the opening delta.

## Account List Deletion Controls

```http
GET /api/v1/accounts
```

Every account now includes:

```json
{
  "uuid": "0198...",
  "version": 1,
  "entry_source": "manual",
  "is_system": false
}
```

App delete-button rule:

```text
Show Delete only when entry_source == "manual" AND is_system == false.
```

The backend remains authoritative. A manual account can still reject deletion when real accounting transactions are linked to it.

## App Developer Checklist

- Fetch plans from `GET /api/v1/plans` when opening registration.
- Require the user to select a plan.
- Submit the selected `slug` as `plan_slug`.
- Do not submit `company_email`.
- Display the pending-approval success message after HTTP `201`.
- Redirect to login after successful registration.
- Display HTTP `422` field errors directly.
- Do not attempt automatic login after registration.

## Verification Completed

- Public plan list works without a token.
- Hidden plans are excluded from the public list.
- Missing and invalid plan slugs return HTTP `422`.
- Registration creates the company, pending owner, owner role, subscription, financial year, and default ledgers.
- Owner email is used as company email.
- Duplicate company names receive unique slugs.
- Pending users receive HTTP `422` when attempting login.

