# Flutter App Changes — Services as Items

## Why this change

Web admin now matches Tally-style item invoicing:

| Before (wrong) | After (correct) |
|----------------|-----------------|
| Service → create Income ledger | Service → create Item (`type=service`) |
| Sales dropdown Services = income accounts | Sales dropdown Services = service items |
| Opening balance on “service” ledger | Opening balance only on real ledgers/parties |
| Items list had no services | Items master lists Goods + Services |

**Ledgers** = Chart of Accounts (posting).  
**Items** = billable catalog (goods + services).  
Service items are **non-stockable** and post income via `income_account_id` (default: Sales Revenue).

Backend is live on web. Flutter must be updated to match or sales-service UX will break / diverge.

---

## Breaking API behavior

### `GET /api/v1/items/dropdown` (sales catalog)

```json
{
  "items": [ /* goods items only */ ],
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
      "description": "...",
      "is_active": true,
      "is_stockable": false
    }
  ]
}
```

**Breaking:** `services[].id` is now an **item id**, not an income `account_id`.

Do **not** send that id as `service_lines[].account_id`.

---

## Required Flutter changes

### 1. Masters → Add Item (Goods / Service)

**Files (approx):**
- `lib/presentation/views/masters/masters_screen.dart`
- `lib/presentation/views/masters/forms/item_form_sheet.dart`
- `lib/presentation/views/masters/forms/account_form_sheet.dart`

**Change:**
- Choosing **Service** must open **Item form** with `type=service`, `isStockable=false`.
- Do **not** open Account form / redirect to ledger create.
- Hide stock fields for service (or force stock = 0).
- Prefer SAC / hours unit; keep selling price + tax rate.
- Items tab filter All / Goods / Service must list service items from `GET /api/v1/items?type=service`.

### 2. Sales invoice catalog mapping

**Files (approx):**
- `lib/data/repositories/masters/items_repository.dart`
- `lib/presentation/controllers/transactions/create/transaction_form_lookup_controller.dart`
- `lib/presentation/controllers/transactions/create/transaction_form_models.dart`
- `lib/presentation/controllers/transactions/create/base_invoice_form_controller.dart`
- `lib/presentation/views/transactions/create/invoice_form_screen.dart`

**Change:**
- Treat catalog `services` as **items** (`InvoiceCatalogOption` / item row), not as account options.
- Map `services[].id` → `item_id`.
- Use `income_account_id` only for display/posting metadata if needed; posting is resolved on server from the item.
- Offline cache: include local items with `type=service` in services list (today offline returns `services: []`).

### 3. Sales invoice payload

**Stop building income-ledger `service_lines` for new catalog services.**

Preferred payload (same as web):

```json
{
  "party_id": 1,
  "invoice_date": "2026-07-27",
  "due_date": "2026-08-03",
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

Both goods and service rows go in `lines[]` with `item_id`.  
Qty/discount allowed for services (hours × rate).

**Legacy only:** keep reading old invoices that have `line_type=service` + `account_id` without `item_id`. Do not create new ones that way.

Backend still accepts `service_lines[{account_id, amount, ...}]` for backward compatibility, but the app should not use it for new entries after this update.

### 4. Remove / simplify service-row UI split

If `usesUnifiedSalesRows` still splits “item vs service account”:
- Unify to one picker: Goods optgroup + Services optgroup, both item-based.
- Remove Account dropdown for billable services on sales create.
- `validServiceRows` / `serviceAccount` paths that send `service_lines` should be removed or limited to legacy edit.

### 5. Purchase invoices

No change: purchase remains **goods items only**. Do not offer service items on purchase lines.

### 6. Local DB / sync

- Ensure local `items` table syncs `type`, `is_stockable`, `income_account_id`.
- Service items must sync like goods (no stock movements).
- Re-seed or clear stale “service as account” assumptions in offline catalog builders.

---

## Suggested implementation order

1. Item create/edit: Service → ItemForm (`type=service`).
2. Catalog mapper: `services` → item options with `item_id`.
3. Invoice payload: all billable rows → `lines[]`.
4. Offline catalog: include service items.
5. QA checklist below.
6. Remove dead account-as-service code paths.

---

## QA checklist (app)

- [ ] Create Service item from Masters → appears under Items (type Service).
- [ ] Service item does not change stock on sales.
- [ ] Sales invoice Services group lists service items (not Sales Revenue / random income ledgers).
- [ ] Save sales invoice with goods + service items → success; totals/tax correct.
- [ ] Payload contains only `lines` with `item_id` for services (no `service_lines` for new bills).
- [ ] Edit old invoice that had ledger-based service line still opens without crash.
- [ ] Offline: service items available from local cache after sync.
- [ ] Purchase invoice does not list service items.

---

## Backend reference (already done on web)

| Area | Behavior |
|------|----------|
| `Item` create | `type=service` → `is_stockable=false`, default `income_account_id` = Sales Revenue |
| Sales create UI | Goods + Services from `items` table |
| `SalesInvoiceService` | Service item line → `line_type=service`, `account_id` from item income account |
| Stock | Skips non-goods / non-stockable |
| Legacy | `service_lines` with `account_id` still accepted |

---

## Out of scope for this doc

- Creating a new income ledger per service (not required; share Sales Revenue or pick a dedicated income ledger on the item later if UI adds account picker).
- Purchase of services (still via Payment voucher / expense ledgers).
