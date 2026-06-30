# Tax Master Refactor Plan

Project: Reco Accounting SaaS

Scope:
- Align Tax Master with approved client workflow.
- Do not redesign the accounting engine.
- Do not redesign voucher architecture.
- Do not redesign database architecture beyond Tax Master alignment and required settings support.

## 1. Existing Tax Module Review

Current implementation is spread across these files:
- `app/Models/TaxRate.php`
- `app/Services/TaxRateService.php`
- `app/Http/Requests/Admin/TaxRateRequest.php`
- `app/Http/Controllers/Admin/TaxRateController.php`
- `app/Http/Controllers/Api/TaxRateApiController.php`
- `app/Http/Resources/TaxRateResource.php`
- `resources/views/admin/tax-rates/index.blade.php`
- `resources/views/admin/tax-rates/create.blade.php`
- `resources/views/admin/tax-rates/edit.blade.php`
- `app/Docs/TaxRateDocs.php`

Current database shape:
- `name`
- `code`
- `rate`
- `type`
- `cgst_rate`
- `sgst_rate`
- `igst_rate`
- `is_inclusive`
- `calculation_type`
- `category`
- `notes`
- `is_active`

Current usage pattern:
- Items store `tax_rate_id` and preload TaxRate for selection.
- Sales and purchase invoice lines store `tax_rate_id`.
- Invoice services calculate `tax_amount` from `tax_rate.rate`.
- Voucher posting currently does not split tax into separate posting lines.

Important finding:
- No direct ledger mapping fields were found in Tax Master. There is no current `ledger_id`, `sales_ledger_id`, or `purchase_ledger_id` dependency in the tax module.

## 2. Database Impact Analysis

Client-approved Tax Master structure:
- `id`
- `uuid`
- `company_id`
- `tax_code`
- `tax_name`
- `tax_rate`
- `tax_type`
- `tax_category`
- `notes`
- `status`
- audit fields
- soft delete fields
- `version`

Current schema misalignment:
- `name` should become `tax_name`
- `code` should become `tax_code`
- `rate` should become `tax_rate`
- `calculation_type` should become `tax_type`
- `category` should become `tax_category`
- `type` in current form is not client-approved and should be removed
- `cgst_rate`, `sgst_rate`, `igst_rate`, `is_inclusive` are outside approved scope and should be removed
- `is_active` should be normalized to project-standard status handling

Database impact level:
- Medium for Tax Master itself
- Low for existing relations because references remain by `tax_rate_id`
- Medium for API/resource compatibility because field names will change

Soft delete gap:
- `tax_rates` currently does not include `deleted_at`, `deleted_by`, `deleted_by_ip`
- This should be aligned with project database standards if the module is being refactored now

## 3. Fields To Remove

Remove from Tax Master schema and contract:
- `type` with current meaning (`gst`, `igst`, `cgst_sgst`, `vat`, `exempt`)
- `cgst_rate`
- `sgst_rate`
- `igst_rate`
- `is_inclusive`

Direct ledger dependency fields:
- None currently exist in the tax module

Remove from UI:
- CGST Rate
- SGST Rate
- IGST Rate
- Tax Inclusive

Remove from docs and API payloads:
- `cgst_rate`
- `sgst_rate`
- `igst_rate`
- `is_inclusive`
- old meaning of `type`

## 4. Fields To Keep

Keep conceptually:
- tax code
- tax name
- tax rate
- tax type
- tax category
- notes
- status
- uuid
- company_id
- version
- audit fields
- soft delete fields

Approved values:

Tax Type:
- `addition`
- `deduction`

Tax Category:
- `GST`
- `CGST`
- `SGST`
- `IGST`
- `TDS`
- `TCS`
- `CESS`
- `OTHER`

## 5. Service Layer Changes

### TaxRateService

Required changes:
- Update filters and ordering to use new normalized field names
- Filter by `tax_type` instead of current `calculation_type`
- Filter by constrained `tax_category`
- Search by `tax_name` and `tax_code`

### Validation Changes

Admin and API validation must enforce:
- `tax_name`: required
- `tax_code`: nullable but unique per company
- `tax_rate`: required numeric
- `tax_type`: required enum `addition|deduction`
- `tax_category`: required enum `GST|CGST|SGST|IGST|TDS|TCS|CESS|OTHER`
- `notes`: nullable
- `status`: normalized boolean or status enum based on project convention

### Invoice Calculation

Do not redesign invoice calculation flow.

Keep:
- `tax_rate_id` on items and invoice lines
- tax amount calculation from master percentage

Adjust:
- invoice services should read the normalized rate field name after refactor

### Voucher Posting Changes

Do not redesign VoucherService architecture.

Required alignment:
- Sales invoice posting must separate revenue and tax posting
- Purchase invoice posting must separate expense and tax posting
- Tax ledger selection must come from settings, not Tax Master

Expected sales posting target:
- Accounts Receivable Dr gross total
- Sales Account Cr base amount
- Sales Tax Ledger Cr tax amount

Expected purchase posting target:
- Purchase Account Dr base amount
- Purchase Tax Ledger Dr tax amount
- Accounts Payable Cr gross total

Implementation note:
- This is a service-layer behavior adjustment in invoice-to-voucher preparation, not a voucher architecture redesign

## 6. Settings Changes

Existing settings infrastructure already exists and should be reused.

Add or validate these settings keys:
- `sales_tax_ledger_id`
- `purchase_tax_ledger_id`
- `tds_ledger_id`
- `tcs_ledger_id`
- `cess_ledger_id`

Required settings work:
- Add company-scoped settings entries
- Add admin settings UI inputs for selecting ledgers
- Validate each configured ledger exists and belongs to the company
- Use these settings in invoice voucher generation

Suggested mapping logic:
- `GST`, `CGST`, `SGST`, `IGST` on sales -> `sales_tax_ledger_id`
- `GST`, `CGST`, `SGST`, `IGST` on purchase -> `purchase_tax_ledger_id`
- `TDS` -> `tds_ledger_id`
- `TCS` -> `tcs_ledger_id`
- `CESS` -> `cess_ledger_id`
- `OTHER` -> follow configured default or fail validation until mapping strategy is defined

## 7. API Impact Analysis

Impacted API surfaces:
- `TaxRateApiController`
- `TaxRateResource`
- nested tax objects in item and invoice line resources
- Swagger/OpenAPI docs in `app/Docs/TaxRateDocs.php`

Breaking changes if done directly:
- `name` -> `tax_name`
- `code` -> `tax_code`
- `rate` -> `tax_rate`
- `calculation_type` -> `tax_type`
- `category` -> `tax_category`
- removal of `cgst_rate`, `sgst_rate`, `igst_rate`, `is_inclusive`

Recommended migration approach:
- Phase 1: normalize internal storage and resource mapping
- Phase 2: expose approved names in API responses
- Phase 3: remove deprecated names after mobile/API consumers are aligned

If backward compatibility is required immediately:
- return both new and old keys temporarily from the resource layer
- mark old fields as deprecated in docs

## 8. UI Changes Required

### Tax Master Form

Keep only these fields:
- Tax Code
- Tax Name
- Tax Rate
- Type
- Category
- Notes
- Status

Field behavior:
- Type: dropdown with `Addition`, `Deduction`
- Category: dropdown with approved values only

Remove from create/edit screens:
- current tax split inputs
- inclusive/exclusive selector
- old GST/IGST/VAT/EXEMPT type selector

### Tax Master Index

Approved columns:
- Tax Code
- Tax Name
- Category
- Type
- Rate
- Status
- Actions

Remove current columns:
- CGST
- SGST
- IGST
- Inclusive
- old type display

## Execution Checklist

Priority 1: Schema and model contract
- Add migration to normalize tax fields and remove deprecated columns
- Align `TaxRate` model fillable/casts/scopes
- Add soft delete and audit-delete support if missing

Priority 2: Validation and service layer
- Update admin request validation
- Update admin controller validation where still inline
- Update API controller validation
- Update `TaxRateService` filters/search

Priority 3: Admin UI
- Simplify create/edit forms
- Simplify index columns and filters
- Align labels with approved client wording

Priority 4: API and resource layer
- Update `TaxRateResource`
- Update Swagger docs
- Review item and invoice line nested resources for tax payload compatibility

Priority 5: Settings support
- Add tax ledger setting keys
- Add settings UI controls
- Add validation for configured ledger ownership

Priority 6: Voucher posting alignment
- Update sales invoice voucher preparation to split tax line from base revenue
- Update purchase invoice voucher preparation to split tax line from base expense
- Resolve tax ledgers from settings by category

## Out of Scope

Do not do the following in this refactor:
- redesign general ledger engine
- redesign voucher persistence model
- redesign invoice architecture
- attach ledger mapping directly to Tax Master
- change item or invoice line foreign key pattern away from `tax_rate_id`
