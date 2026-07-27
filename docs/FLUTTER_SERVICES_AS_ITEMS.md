# Flutter App Changes — Full Checklist

This document covers **every RecoFlutter change already done** on branch `fix/services-as-items-web` / local `main`, plus **everything still pending** so the app matches the new web/backend behavior (unified sales + services-as-items + party OB lock).

---

## Part A — Already done in RecoFlutter (committed)

These files were changed/deleted in commit `9fb522d` and are on local `main`.

### A1. Removed separate Service Sale Invoices module

| Action | File |
|--------|------|
| Deleted | `lib/presentation/controllers/transactions/service_sales_invoices_controller.dart` |
| Deleted | `lib/presentation/views/transactions/create/service_sales_invoice_screen.dart` |
| Deleted | `lib/presentation/views/transactions/tabs/service_sales_invoices_tab_screen.dart` |
| Removed class | `ServiceSalesInvoiceFormController` from `base_invoice_form_controller.dart` |
| Removed flag | `isServiceInvoice` and `invoice_type: service` from sales payload |
| Removed endpoint | `ApiEndpoints.serviceSalesInvoices` from `api_endpoints.dart` |

**Meaning:** App no longer has a separate Service Sale Invoice screen/API. Use the single **Sales Invoice** flow for goods + services.

### A2. Sales invoice entity cleanup

**File:** `lib/data/models/transactions/transaction_entities.dart`

- `TransactionRecord.fromSalesInvoice` no longer reads `invoice_type` from API.
- Hard-codes display kind as `'sales'` (column `invoice_type` dropped on backend).

### A3. Unified sales form flags

**File:** `lib/presentation/controllers/transactions/create/base_invoice_form_controller.dart`

- `usesUnifiedSalesRows` now = items + services + not purchase (no `isServiceInvoice` check).
- Payload no longer sends `invoice_type`.
- Still currently builds **both** `lines` and `service_lines` (see Part B — this must change for services-as-items).

### A4. Dashboard / API error hardening

**File:** `lib/data/repositories/dashboard/dashboard_repository.dart`

- Dashboard refresh uses Dio `extra: { silentError: true }`.
- On failure, returns **cached** dashboard instead of throwing a toast-breaking error.

**File:** `lib/core/network/api_client.dart`

- Sanitizes raw DB/connection messages (`SQLSTATE`, `Operation not permitted`, `Connection:`) into:
  - `Unable to reach the server database. Please try again.`
- Respects `silentError` so dashboard blips do not spam snackbars.

---

## Part B — Still pending (must do to match web)

Web already treats **services as Items**. App still mixes **income ledgers as services**. Complete the items below.

### B1. Masters → Add Item: Service must open Item form (CRITICAL)

**Current (wrong):**  
`masters_screen.dart` → Service button → `AccountFormSheet()` (ledger create, often defaults to asset).

**Required:**

```dart
Get.to(() => const ItemFormSheet(/* type: service */));
```

**Files:**
- `lib/presentation/views/masters/masters_screen.dart` (Goods/Service dialog ~lines 140–200)
- `lib/presentation/views/masters/forms/item_form_sheet.dart`
  - Accept initial `type: 'service' | 'goods'`
  - For service: `isStockable = false`, hide/zero stock fields, SAC label, unit hours/nos
  - Do **not** require expense account for services
- Stop using `AccountFormSheet` for billable services

**Items list:**
- `items_tab_screen.dart` / `items_controller.dart` — filter All / Goods / Service must show `type=service` rows from `GET /api/v1/items`

### B2. Sales catalog: `services` are Items, not Accounts (BREAKING)

**API:** `GET /api/v1/items/dropdown`

```json
{
  "items": [ /* goods only */ ],
  "services": [
    {
      "id": 12,
      "kind": "service",
      "type": "service",
      "name": "Consulting",
      "item_code": "SRV-001",
      "selling_price": 1500,
      "tax_rate_id": 3,
      "income_account_id": 45,
      "text": "SRV-001 - Consulting",
      "is_stockable": false
    }
  ]
}
```

**Breaking:** `services[].id` = **item id**, not income `account_id`.

**Files to update:**
- `lib/data/repositories/masters/items_repository.dart`
  - Offline: today returns `services: []` — must include local items where `type == service`
  - Online: map `services` as item-shaped records
- `lib/presentation/controllers/transactions/create/transaction_form_lookup_controller.dart`
  - Stop `_loadServiceAccounts('income')` for sales catalog
  - Build service options from catalog `services` / local service items
  - Can keep `serviceAccounts` only for true voucher/account pickers if needed elsewhere
- `lib/presentation/controllers/transactions/create/transaction_form_models.dart`
  - Change `InvoiceCatalogOption.service` to hold an **`ItemEntity`** (or shared item id), not `LookupOption account`
  - Update `label` / `identityKey` to `service-item:{id}`
  - Update `InvoiceLineRowModel.serviceAccount` → item-based field (or reuse item notifier)

### B3. Sales invoice payload: put services in `lines[]` (CRITICAL)

**Current (wrong):** `base_invoice_form_controller.dart` still sends:

```dart
'service_lines': [
  { 'account_id': ..., 'amount': ..., 'tax_rate_id': ... }
]
```

from `validServiceRows` + `validMixedServiceRows` / `row.serviceAccount`.

**Required (match web):**

```json
{
  "lines": [
    {
      "item_id": 10,
      "quantity": 2,
      "unit_price": 500,
      "discount_percentage": 0,
      "tax_rate_id": 3,
      "description": "Widget"
    },
    {
      "item_id": 12,
      "quantity": 3,
      "unit_price": 1500,
      "discount_percentage": 0,
      "tax_rate_id": 3,
      "description": "Consulting hours"
    }
  ]
}
```

Rules:
- Goods **and** service catalog picks → `lines[]` with `item_id`
- Qty / discount allowed for services (hours × rate)
- Do **not** send new `service_lines` with income `account_id`
- Optional: still send `account_id` = item’s `incomeAccountId` on the line (backend also resolves from item)

**Files:**
- `lib/presentation/controllers/transactions/create/base_invoice_form_controller.dart`
  - `buildPayload` / `validServiceRows` / mixed service rows
  - Prefer one list of item rows for unified sales
- `lib/presentation/views/transactions/create/invoice_form_screen.dart`
  - UI: Goods + Services optgroups, both item-based
  - Remove income-account service amount-only row for new creates

**Legacy edit only:** old invoices with `line_type=service` + `account_id` and no `item_id` may still display; do not create new ones that way.

### B4. Remove dead account-as-service paths

After B2–B3:

| Remove / stop using for sales | Where |
|-------------------------------|--------|
| `InvoiceCatalogOption.service(LookupOption account)` | `transaction_form_models.dart` |
| Loading income ledgers into sales services | `transaction_form_lookup_controller.dart` |
| `service_lines` for new sales saves | `base_invoice_form_controller.dart` |
| Mixed row `serviceAccount` for billable services | models + invoice form UI |

Keep Account form / income ledgers for **real Chart of Accounts** (Sales Revenue, etc.) — just not as the billable service catalog.

### B5. Purchase invoices

No functional change:
- Goods items only
- Do not list `type=service` items on purchase lines

### B6. Party opening balance (match web lock + confirm)

Web behavior (already live):
- **Create party:** confirm opening balance before save (cannot edit later)
- **Edit party:** opening balance / type / date read-only; backend strips OB fields

**App still editable on edit** — update:

**File:** `lib/presentation/views/masters/forms/party_form_sheet.dart`

| Mode | Required UX |
|------|-------------|
| Create | Before save, show confirm dialog with amount, Dr/Cr, date; warn it cannot be changed later |
| Edit | Make opening balance, balance type, opening date read-only / disabled; do not send changed OB (or omit those keys on update) |

Optional: same confirm on any quick-add party sheet if the app has one.

### B7. Local DB / sync

- Sync `items.type`, `items.is_stockable`, `items.income_account_id`
- Service items sync like goods; **no stock movement** on sale
- Clear any offline logic that treated income accounts as the services catalog
- After backend deploy, force refresh of items + sales catalog cache

### B8. Navigation / bindings cleanup (verify)

Confirm no remaining routes/bindings/menu entries for:
- `ServiceSalesInvoices*`
- `/service-sales-invoices`
- `invoice_type=service` filters

(Grep already clean for `serviceSales` / `ServiceSales` after Part A — re-check after your local edits.)

---

## Part C — Suggested implementation order

1. **B1** Service → ItemForm  
2. **B2** Catalog map services → items  
3. **B3** Payload → all `lines` + `item_id`  
4. **B4** Delete account-as-service dead code  
5. **B6** Party OB lock + create confirm  
6. **B7** Offline/sync  
7. QA checklist (Part D)

---

## Part D — QA checklist

### Already done (Part A)

- [x] No Service Sale Invoices tab/screen/endpoint in app code
- [x] Sales invoice records do not depend on `invoice_type`
- [x] Dashboard failure falls back to cache without SQLSTATE toast spam

### Still pending

- [ ] Create Service from Masters → Item form (`type=service`), appears in Items list
- [ ] Service item does **not** open Account/ledger form
- [ ] Sales Services group lists **service items** (not Sales Revenue / income ledgers)
- [ ] New sales invoice with goods + service → payload only `lines` with `item_id`
- [ ] Service qty/rate/tax calculate correctly; stock unchanged for service
- [ ] Offline: service items available after sync
- [ ] Purchase does not list service items
- [ ] Edit old ledger-based service line does not crash
- [ ] Party create: OB confirmation dialog
- [ ] Party edit: OB fields read-only

---

## Part E — Backend / web reference (already shipped)

| Area | Behavior |
|------|----------|
| Items create | Service stays on item form; `is_stockable=false`; default income = Sales Revenue |
| Items list | Goods + Services |
| Sales invoice UI | Goods + Services from `items`; submit `lines[]` |
| `GET /items/dropdown` | `services` = service-type items |
| `SalesInvoiceService` | Service item → `line_type=service`, `account_id` from item income |
| Stock | Skips non-goods / non-stockable |
| Legacy | `service_lines[{account_id}]` still accepted for old data |
| Party | OB locked after create; create-time confirm on web |
| API | `/service-sales-invoices` removed; no `invoice_type` on sales |

---

## Part F — Out of scope

- Creating one income ledger per service name (not required; share Sales Revenue or optional income picker on item later)
- Purchasing services as items (use Payment / expense ledgers)
- Pushing local `main` to origin (ask before push)

---

## Quick file map

| Topic | Primary files |
|-------|----------------|
| Done: remove service sales module | deleted controllers/screens; `api_endpoints.dart`; `base_invoice_form_controller.dart` |
| Done: dashboard errors | `dashboard_repository.dart`; `api_client.dart` |
| Done: invoice_type | `transaction_entities.dart` |
| Pending: Service → Item | `masters_screen.dart`; `item_form_sheet.dart` |
| Pending: catalog | `items_repository.dart`; `transaction_form_lookup_controller.dart`; `transaction_form_models.dart` |
| Pending: payload/UI | `base_invoice_form_controller.dart`; `invoice_form_screen.dart` |
| Pending: party OB | `party_form_sheet.dart` |
