# Flutter App Changes — Match Web / Backend

Thorough checklist for **RecoFlutter** vs Laravel web API (local `main`).  
Covers: services-as-items, invoice cancel/payment, masters locks, UI parity, bugs, reports, sync.

**App root:** `RecoFlutter/`  
**Last deep audit:** code-verified against `RecoFlutter/lib/**`, `resources/views/admin/**`, `routes/api.php`.

---

## Status summary

| Area | Web | Flutter |
|------|-----|---------|
| Separate Service Sale Invoices module removed | Done | Done |
| Dashboard silent refresh / SQLSTATE sanitizing | Done | Done |
| Notes / Narration labels (one field per context) | Done | Mostly done (detail nit) |
| Services as Items (catalog + sales `lines[]`) | Done | **Critical bug** |
| Masters → Service opens Item form | Done | **Wrong** → Account form |
| Item service form (hide purchase/barcode, Default Rate) | Done | **Partial** |
| Purchase = goods only | Done | **Wrong** (still has services) |
| Party OB confirm + edit lock | Done | **Missing** |
| Party type lock when used | Done | **Missing** |
| Account OB / type / mode lock when in use | Done | **Missing** |
| Quick-add party from invoice | Done | **Missing** |
| Invoice cancel (`POST .../cancel`) | Done | **Missing** |
| Invoice record payment | Done | **Missing** |
| Invoice edit / PDF / detail lines | Done | **Missing** |
| Trial Balance Opening/Txn/Closing + BS/PL | Done | Optional |
| Bank/Cash/Ledger `particulars` | Done | **Bug** (wrong field) |
| Day book serial `#` | Done | Optional |
| Client period lock | Done (server) | **Missing** client preview |
| English-only UI copy | Done | **Hindi/Hinglish** leftovers |

---

## Part A — Already done in RecoFlutter

### A1. Removed separate Service Sale Invoices module

| Action | File |
|--------|------|
| Deleted | service sales controllers / screens / tabs |
| Removed | `ServiceSalesInvoiceFormController`, `isServiceInvoice`, `invoice_type` |
| Removed | `ApiEndpoints.serviceSalesInvoices` |

Use single **Sales Invoice** for goods + services.

### A2. Sales invoice entity cleanup

`lib/data/models/transactions/transaction_entities.dart` — `fromSalesInvoice` hard-codes kind `'sales'`.

### A3. Unified sales form flag (scaffold only)

`base_invoice_form_controller.dart` — `usesUnifiedSalesRows` exists, but payload still wrong (see **Bugs** / **B3**).

### A4. Dashboard / API error hardening

- `dashboard_repository.dart` — `silentError: true`; cache on fail  
- `api_client.dart` — sanitizes SQLSTATE / connection errors  

### A5. Notes / Narration labels (mostly done)

| Context | UI label | API field | Status |
|---------|----------|-----------|--------|
| Invoices | Notes | `notes` | Done |
| Vouchers | Narration | `narration` | Done |
| Parties (full form) | Notes | `remarks` | Done |
| Accounts | Notes | `remarks` | Done |
| Invoice detail field | should be Notes | — | **Nit:** still “Narration / Notes” (`transaction_detail_screen.dart` ~L91) |
| Party quick-add (web) | Remarks | `remarks` | N/A until quick-add built |

### A6. Partial item form for `type=service`

`item_form_sheet.dart` — type dropdown + hide stock for service.  
Still missing vs web: purchase price, barcode, expense account, Default Rate label, SAC label, `hrs` unit, English info text. See **B1** / **Part I**.

### A7. Voucher cancel (manual vouchers only)

Payments / receipts / adjustments can cancel.  
**Not** invoice cancel — see **Part F**.  
All-vouchers tab can still try cancel on invoice-linked income/expense → API error (see **Part M**).

---

## Critical bugs (wrong behavior — fix first)

| # | Bug | Where | Correct behavior |
|---|-----|--------|------------------|
| 1 | Masters → Service opens **Account** form | `masters_screen.dart` ~L183 | `ItemFormSheet(initialType: 'service')` |
| 2 | Catalog maps `services[]` → income **accounts**; fallback `_loadServiceAccounts('income')` | `transaction_form_lookup_controller.dart` ~L128–132 | `services[].id` = **item id** |
| 3 | Sales payload sends `service_lines[{account_id, amount}]` | `base_invoice_form_controller.dart` ~L341–364 | `lines[{item_id, quantity, unit_price, …}]` |
| 4 | Purchase `supportsServices => true` + Service Lines UI | `base_invoice_form_controller.dart` ~L443–444; `invoice_form_screen.dart` ~L130–131 | Goods only; no service lines |
| 5 | Offline catalog `'services': []` | `items_repository.dart` ~L121–125 | Local `type==service` items in `services` |
| 6 | Service rows hide Qty/Discount; discount forced 0; totals use amount-only | `invoice_form_screen.dart` ~L240–274; `transaction_form_models.dart` ~L141–142 | Qty × rate + disc % like goods |
| 7 | `delivery_terms` copied from same `paymentTermsController` as `payment_terms` | `base_invoice_form_controller.dart` ~L313–318 | Separate field or omit unused key |
| 8 | Header `discount_percentage` sent with **no UI** | `discountController` in controller, no form field | Expose UI or stop sending |
| 9 | Purchase status filter omits `cancelled` | `purchase_invoices_controller.dart` ~L31–38 | Include `cancelled` |
| 10 | Invoice cancel (if enabled) would use **PATCH** voucher cancel | `base_transactions_tab_controller.dart` ~L179–186 | `POST /sales-invoices/{id}/cancel` (and purchase) |
| 11 | Ledger / Bank / Cash “Particulars” bind narration/description | `ledger_report_screen.dart` ~L219; bank/cash ~L286 | Prefer API `particulars` |
| 12 | Service item form still shows Purchase Price + Expense Account + Barcode; label “Selling Price” | `item_form_sheet.dart` | Match web conditionals (Part I) |

---

## Part B — Services as items (pending)

### B1. Masters → Service → Item form + service form polish

**Files:** `masters_screen.dart`, `item_form_sheet.dart`

| Web rule | Flutter action |
|----------|----------------|
| Service opens item form | Fix Masters button |
| Hide Purchase Price (force 0) | Hide for service |
| Hide Barcode | Hide for service |
| Selling Price → **Default Rate** + helper | Rename + hint |
| HSN/SAC → **SAC Code** for service | Conditional label |
| Unit default `hrs` for service | Add `hrs` to unit list; auto-set |
| No expense account on service | Hide expense account |
| Info: non-stockable, posts via income | English alert (remove Hindi card) |
| Type locked on edit | Match web (hidden type + badge) |

### B2. Catalog: services are Items (BREAKING)

**API:** `GET /api/v1/items/dropdown` — `services[].id` = item id.

Update:
- `items_repository.dart` — offline split goods/services  
- `transaction_form_lookup_controller.dart` — stop income-ledger fallback for sales  
- `transaction_form_models.dart` — `InvoiceCatalogOption.service` → item, not account  

### B3. Sales payload → all `lines[]` + `item_id`

```json
{
  "lines": [
    { "item_id": 10, "quantity": 2, "unit_price": 500, "discount_percentage": 0, "tax_rate_id": 3 },
    { "item_id": 12, "quantity": 3, "unit_price": 1500, "discount_percentage": 0, "tax_rate_id": 3 }
  ]
}
```

- No new `service_lines` with income `account_id`  
- Qty / discount for services  
- UI: Goods / Services optgroups (web uses `<optgroup>`; app today uses `[Item]` / `[Service]` prefixes — upgrade preferred)

**Line calc (match web exactly):**

```
base = qty × unit_price
discount = base × (disc% / 100)
afterDisc = base − discount
tax = afterDisc × (taxRate / 100)
line_total = afterDisc + tax
```

**Legacy edit only:** old `line_type=service` + `account_id` without `item_id` — show as `(legacy)`; qty/disc readonly.

### B4. Remove account-as-service dead code

Stop: `serviceAccounts` for sales, `InvoiceServiceRowModel`, `_ServiceLinesSection`, `service_lines` in new saves.  
Keep Account form for Chart of Accounts only.

### B5. Purchase — goods only

- `supportsServices => false`  
- Item picker: `type=goods` only  
- No Service Lines section  
- Server already rejects services — app must not offer them  

### B6. Party opening balance

| Mode | Required |
|------|----------|
| Create | SweetAlert-style confirm (see Part I dialogs) |
| Edit | OB amount / type / date **read-only**; omit OB keys on update |

### B7. Offline / sync

- Cache `items.type`, `is_stockable`, `income_account_id`  
- Offline `services` must list local service items  
- No stock movement for service sales  
- Force refresh catalog after backend deploy  

### B8. Navigation cleanup

Re-grep: no `ServiceSales*`, `/service-sales-invoices`, `invoice_type=service`.

---

## Part C — Implementation order

1. Fix **Critical bugs 1–6, 12** (B1–B5)  
2. Fix payload bugs **7–8**  
3. **Part F** invoice cancel + status filter (**9–10**)  
4. **Part I** party/account locks + quick-add  
5. **Part J** payment, edit, detail, PDF  
6. **Part K** reports (`particulars`, TB, day book)  
7. **Part L** period lock + sync notes  
8. **Part M** voucher cancel UX + English copy  
9. QA (Part D)

---

## Part D — QA checklist

### Done
- [x] No Service Sale Invoices module  
- [x] No `invoice_type` dependency  
- [x] Dashboard cache / silent errors  
- [x] Notes / Narration labels on create forms  

### Critical / services
- [ ] Masters Service → Item form  
- [ ] Service form: no purchase price / barcode / expense; Default Rate; SAC; `hrs`  
- [ ] Sales catalog = service **items** (not income ledgers)  
- [ ] Sales payload only `lines` + `item_id`  
- [ ] Service qty / rate / discount; stock unchanged  
- [ ] Offline service items in catalog  
- [ ] Purchase: no services  
- [ ] Legacy service line edit does not crash  

### Invoice workflows
- [ ] Cancel sales / purchase (`POST`) + confirm copy  
- [ ] Purchase filter includes `cancelled`  
- [ ] Record payment (Received In / Paid From)  
- [ ] Edit invoice (block paid / partial / cancelled)  
- [ ] Detail: lines, Notes, terms, FY, overdue text  
- [ ] Sales PDF  

### Masters locks / UI
- [ ] Party create OB confirm  
- [ ] Party edit OB readonly + type lock when used  
- [ ] Account locks when in use / system  
- [ ] Transaction mode only for asset accounts  
- [ ] Quick-add party on invoice (debtor/creditor)  
- [ ] English-only user-facing strings  

### Reports / other
- [ ] Prefer `particulars` on ledger / bank / cash  
- [ ] TB Opening / Transaction / Closing + destination  
- [ ] Day book `#` serial  
- [ ] Fix `delivery_terms` / header discount  
- [ ] Hide cancel on invoice-linked income/expense vouchers  
- [ ] Period-lock friendly date errors  

---

## Part E — Backend / web reference (shipped)

| Area | Behavior |
|------|----------|
| Items | Service on item form; non-stockable; Default Rate; hide purchase/barcode |
| `GET /items/dropdown` | `services` = service items (`id` = item id) |
| Sales | Goods + Services → `lines[]` |
| Purchase | Goods only; validates `type=goods` |
| Party | OB confirm + lock; type lock when used |
| Account | Lock type/mode/OB when in use or system |
| Invoice cancel | `POST .../cancel` — reverses vouchers, ledger, stock |
| Invoice payment | `POST .../payment` — bill-wise receipt/payment |
| Sales PDF | `GET /sales-invoices/{id}/pdf` (web show has PDF; purchase show does not) |
| Labels | Invoice Notes; voucher Narration; party/account Notes → `remarks` |
| Period lock | Server `PeriodLockService` on writes |

---

## Part F — Invoice cancel (web done, Flutter missing)

| Method | Endpoint |
|--------|----------|
| `POST` | `/api/v1/sales-invoices/{id}/cancel` |
| `POST` | `/api/v1/purchase-invoices/{id}/cancel` |

**Server:** cancel linked receipts/payments → cancel income/expense posting → delete ledger → reverse stock → `status=cancelled`, paid/due = 0.

**Flutter needs:**
- Endpoints in `lib/core/config/api_endpoints.dart`  
- **POST** helper (do not reuse voucher PATCH cancel)  
- List + detail Cancel when `status ≠ cancelled`  
- Confirm copy (exact):

| Type | Dialog text |
|------|-------------|
| Sales | “Linked receipts and sales posting will be cancelled, ledgers reversed, and stock restored.” |
| Purchase | “Linked payments and purchase posting will be cancelled, ledgers reversed, and stock adjusted.” |

Title: “Cancel this invoice?” / Confirm: “Yes, cancel it”

---

## Part G — Reports (web shipped, Flutter partial)

| Report | Web | Flutter gap |
|--------|-----|-------------|
| Trial Balance | Opening / Transaction / Closing + `destination` (BS/PL) | Closing only |
| Day book | Serial `#`, chronological, invoice links | No `#` / links |
| Bank / Cash / Ledger | Column Particulars = API `particulars` | Uses narration/description (**bug**) |
| Report PDF/Excel | Available | Largely wired |
| Invoice PDF | Sales API + web | Not in app endpoints/UI |

Additive TB fields keep `debit`/`credit` as closing for BC.

---

## Part H — Secondary invoice features

| Feature | Web | Flutter |
|---------|-----|---------|
| Record payment | Show modal → POST payment | Missing |
| Edit invoice | Edit for draft/sent/verified | Create-only |
| Detail lines | Full table | Header/amounts only |
| Detail extras | Terms, FY, Bill To address/GSTIN, overdue days (sales) | Missing |
| Sales PDF | Show button | Missing |
| Purchase PDF | Not on web show | Optional |

**Payment modal parity**

| | Sales | Purchase |
|---|-------|----------|
| Account label | Received In | Paid From |
| Helper | Posts Receipt (Dr Cash/Bank, Cr Party) | Posts Payment (Dr Party, Cr Cash/Bank) |
| Fields | Date *, Mode (cash/bank/od) *, account filtered by mode *, amount ≤ balance_due * | Same |

---

## Part I — Masters locks & UI (missing from earlier doc)

### Party (`party_form_sheet.dart` vs `parties/create|edit.blade.php`)

**Create confirm (must mirror):**
- Title: “Confirm Opening Balance”
- Amount > 0: “Opening balance of **₹{amount}** ({Dr/Cr}) dated **{date}** will be posted and **cannot be edited later**.”
- Amount = 0: “No opening balance will be posted. Opening balance **cannot be set later** after the party is created.”
- Confirm: “Yes, create party” / Cancel: “Review again”
- Helper under OB field: “Cannot be edited after create.”

**Edit:**
- OB amount / type / date readonly  
- Party type disabled when transactionally used (web: `typeLocked` from API — app needs this flag)  
- Warning: opening balance locked after create  

**Fields parity:** Name*, Type*, Address*, State*, City*, Pincode*, Mobile, Email, GSTIN, PAN, Notes→`remarks`, Active.

### Account (`account_form_sheet.dart` vs `accounts/create|edit.blade.php`)

| Rule | Flutter |
|------|---------|
| Transaction mode **only if** `account_type == asset` | Always shown today — hide otherwise |
| When in use / system: lock type, mode, OB | Always editable — need `is_system` / in-use from API |
| Name + Notes + Active editable when in use | Match web |
| System banner | “This is a system account…” |
| In-use banner | Classification + OB locked; rename/notes/active OK |

### Item service UI checklist (full)

- [ ] Blocking type pick (Goods/Service) like web modal — or Masters already chooses type  
- [ ] Hide purchase price, barcode  
- [ ] Default Rate label + helper  
- [ ] SAC Code label for service  
- [ ] Unit list includes `hrs`; default hrs for service  
- [ ] Hide expense account for service  
- [ ] English info (no Hindi)  
- [ ] Edit: type locked + Goods/Service badge  

### Quick-add party (web has; Flutter missing)

- On sales/purchase invoice party field  
- Prefill type debtor / creditor  
- Fields: Name*, Mobile, Email, GSTIN, Address*, State*, City*, Pincode*, OB, Balance Type, **Remarks**  
- Same OB confirm as full party create  
- No PAN / opening date / Active (server defaults date)  
- On success: select new party in dropdown  

### English UI

Replace Hindi/Hinglish snackbars / info cards (item form, invoice/voucher validation, export mixins, support copy) with English to match web admin.

---

## Part J — Invoice UI parity checklist

### Index
- Filters: search + status (sales includes cancelled; purchase includes **verified** + cancelled, not “sent”)  
- Edit button only if status ∉ {cancelled, paid, partial}  
- Status badge colors: draft=secondary, sent/verified=info, partial=warning, paid=success, overdue=danger, cancelled=dark  

### Create / Edit header
- Invoice # readonly auto  
- Date *, Due * (due ≥ invoice date)  
- Party * (debtor / creditor)  
- Reference # (sales) / Supplier Invoice # (purchase)  
- Payment/Delivery Terms (single combined field OK — but don’t duplicate wrong keys)  
- Notes  

### Lines
- Sales: Goods + Services optgroups; all columns Qty, Unit Price, Disc %, Tax, Total  
- Purchase: Goods only; default unit price from `purchase_price`  
- Cannot remove last row  
- Live summary: Subtotal, Discount, Tax, Total  

### Show / Detail
- Bill To / Supplier block with address + GST  
- Line table  
- Notes (label **Notes**, not Narration/Notes)  
- Paid + Balance Due  
- Actions: Payment / Cancel / PDF (sales) per conditions above  

---

## Part K — Reports parity checklist

- [ ] TB: `opening_*`, `transaction_*`, closing `debit`/`credit`, `destination`  
- [ ] Day book: `#` column  
- [ ] Ledger / bank / cash: `particulars` first  
- [ ] Optional: invoice PDF via `GET /sales-invoices/{id}/pdf`  

---

## Part L — Period lock & sync

### Period lock
- Server enforces (`PeriodLockService`)  
- Flutter: **no client checks** — add FY/date validation + clear error message before queue/save  

### Sync / offline (current)
Modules in local cache (`offline_first_repository.dart`): accounts, parties, items, categories, tax rates, FY, vouchers, sales/purchase invoices.

Declared but unused: `/sync/bootstrap`, `/sync/download`, `/sync/upload`, `/sync/run` in `api_endpoints.dart`.

**Risk:** Offline sales with services queues wrong `service_lines` until B2–B3 fixed; API auto-posts voucher on create.

---

## Part M — Vouchers

| Topic | Action |
|-------|--------|
| Narration only | Already OK |
| Cancel invoice-linked income/expense | Hide/disable in All Vouchers; message: “Cancel the invoice instead.” |
| Bill-wise settlement | Use invoice **payment** endpoint — standalone receipt/payment forms do **not** set `sales_invoice_id` / `purchase_invoice_id` |
| Voucher cancel confirm (manual) | “Cancel Voucher?” / “This action cannot be undone.” / “Yes, cancel it” |

---

## Dialog / copy reference (mirror web)

| Action | Title | Body |
|--------|-------|------|
| Party create OB | Confirm Opening Balance | See Part I |
| Cancel sales invoice | Cancel this invoice? | Linked receipts and sales posting will be cancelled, ledgers reversed, and stock restored. |
| Cancel purchase invoice | Cancel this invoice? | Linked payments and purchase posting will be cancelled, ledgers reversed, and stock adjusted. |
| Cancel voucher | Cancel Voucher? | This action cannot be undone. |
| Post voucher | Post Voucher? | This will mark the voucher as posted and it cannot be edited. |

---

## Quick file map

| Topic | Path | Status |
|-------|------|--------|
| Service → Account bug | `presentation/views/masters/masters_screen.dart` | Critical |
| Item service UI | `presentation/views/masters/forms/item_form_sheet.dart` | Partial |
| Catalog / income fallback | `.../transaction_form_lookup_controller.dart` | Critical |
| Payload `service_lines` / delivery_terms | `.../base_invoice_form_controller.dart` | Critical |
| Service row UX | `.../invoice_form_screen.dart`, `transaction_form_models.dart` | Critical |
| Offline services | `data/repositories/masters/items_repository.dart` | Critical |
| Party / account locks | `party_form_sheet.dart`, `account_form_sheet.dart` | Pending |
| Invoice cancel HTTP | `base_transactions_tab_controller.dart`, `api_endpoints.dart` | Missing |
| Purchase cancelled filter | `purchase_invoices_controller.dart` | Bug |
| Detail gaps | `transaction_detail_screen.dart` | Pending |
| Particulars bug | ledger / bank / cash report screens | Bug |
| Endpoints | `lib/core/config/api_endpoints.dart` | Add cancel/payment/pdf |
