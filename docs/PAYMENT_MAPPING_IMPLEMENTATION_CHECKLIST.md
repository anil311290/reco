# Invoice-to-Payment Mapping: Implementation Checklist

**Project**: Reco  
**Feature**: Tally-Compliant Invoice-to-Payment Mapping  
**Option Selected**: _____ (A / B / C)  
**Start Date**: _________  
**Target Date**: _________  

---

## Pre-Implementation (Week 0)

- [ ] **Approval**: Team/PM approves Option A/B/C
- [ ] **Resource Allocation**: Assign developer(s)
  - [ ] Lead Developer: ___________
  - [ ] Code Reviewer: ___________
  - [ ] QA Tester: ___________
- [ ] **Branch Creation**: Create feature branch `feature/invoice-payment-mapping`
- [ ] **Documentation**: Print/share INVOICE_PAYMENT_MAPPING_ANALYSIS.md & DECISION_GUIDE.md
- [ ] **Testing Plan**: Schedule staging/UAT test date
- [ ] **Backup**: Recent database backup taken

---

## OPTION A: Full Implementation Checklist

### Week 1: Core Database & Models

#### Phase 1.1: Migration & Model (Day 1)
- [ ] **Create Migration File**
  - [ ] File: `database/migrations/YYYY_MM_DD_HHMMSS_create_payment_invoice_mappings_table.php`
  - [ ] Table name: `payment_invoice_mappings`
  - [ ] Columns created:
    - [ ] `id` (BIGINT PK)
    - [ ] `company_id` (BIGINT FK)
    - [ ] `payment_voucher_id` (BIGINT FK → vouchers.id, CASCADE)
    - [ ] `invoice_type` (ENUM: 'sales', 'purchase')
    - [ ] `invoice_id` (BIGINT)
    - [ ] `invoice_original_balance` (DECIMAL 12,2)
    - [ ] `amount_allocated` (DECIMAL 12,2)
    - [ ] `amount_settled` (DECIMAL 12,2, default 0)
    - [ ] `status` (ENUM: 'pending', 'partial', 'full', 'reversed')
    - [ ] `notes` (TEXT, nullable)
    - [ ] `created_by` (BIGINT, nullable)
    - [ ] `updated_by` (BIGINT, nullable)
    - [ ] `created_by_ip` (VARCHAR 45)
    - [ ] `updated_by_ip` (VARCHAR 45)
    - [ ] `deleted_at` (TIMESTAMP, nullable)
    - [ ] `created_at` (TIMESTAMP)
    - [ ] `updated_at` (TIMESTAMP)
  - [ ] Indexes created:
    - [ ] `unique_mapping` (company_id, payment_voucher_id, invoice_type, invoice_id)
    - [ ] `idx_payment_voucher` (payment_voucher_id)
    - [ ] `idx_invoice` (invoice_type, invoice_id)
    - [ ] `idx_company_type` (company_id, invoice_type)
    - [ ] `idx_status` (status)
  - [ ] Foreign keys:
    - [ ] company_id → companies.id
    - [ ] payment_voucher_id → vouchers.id (CASCADE DELETE)
  - [ ] Test run locally: `php artisan migrate`

- [ ] **Create Model**
  - [ ] File: `app/Models/PaymentInvoiceMapping.php`
  - [ ] Traits added: HasAuditFields, SoftDeletes
  - [ ] $fillable configured
  - [ ] $casts configured
  - [ ] Relations defined:
    - [ ] `paymentVoucher()` (BelongsTo Voucher)
  - [ ] Methods added:
    - [ ] `invoice()` - getter for polymorphic invoice
    - [ ] `isFullySettled()` - helper method
    - [ ] `isPartiallySettled()` - helper method
  - [ ] Test: `php artisan tinker` → create test record

- [ ] **Verify Migration Rollback**
  - [ ] Test: `php artisan migrate:refresh`
  - [ ] Confirm: Table drops correctly
  - [ ] Confirm: Can re-run migration

#### Phase 1.2: Repository Pattern (Day 2)
- [ ] **Create Repository Interface**
  - [ ] File: `app/Interfaces/PaymentInvoiceMappingRepositoryInterface.php`
  - [ ] Methods defined:
    - [ ] `createMapping()`
    - [ ] `getMappingsByPaymentVoucher()`
    - [ ] `getMappingsByInvoice()`
    - [ ] `updateMappingSettlement()`
    - [ ] `reverseMapping()`
    - [ ] `deleteMapping()`

- [ ] **Implement Repository**
  - [ ] File: `app/Repositories/PaymentInvoiceMappingRepository.php`
  - [ ] Implements interface
  - [ ] Methods implement:
    - [ ] `createMapping()` - with validation
    - [ ] `getMappingsByPaymentVoucher()` - with eager loading
    - [ ] `getMappingsByInvoice()` - with status filtering
    - [ ] `updateMappingSettlement()` - with validation
    - [ ] `reverseMapping()` - soft delete or status update
    - [ ] `deleteMapping()` - hard delete if needed
  - [ ] Validation added:
    - [ ] Invoice exists check
    - [ ] Balance sufficient check
    - [ ] No duplicate mapping check
  - [ ] Test: Unit tests in `tests/Unit/PaymentInvoiceMappingRepositoryTest.php`

- [ ] **Service Layer**
  - [ ] File: `app/Services/PaymentInvoiceMappingService.php`
  - [ ] Constructor injection: Repository
  - [ ] Methods:
    - [ ] `autoMapPayment()` - intelligent mapping for single/multi-invoice
    - [ ] `settlePayment()` - mark mapped invoices as settled
    - [ ] `reverseAllMappings()` - called by voucher cancel
    - [ ] `getSummary()` - settlement summary for reports
  - [ ] Test: Unit tests in `tests/Unit/PaymentInvoiceMappingServiceTest.php`

- [ ] **Register in Service Container**
  - [ ] File: `app/Providers/AppServiceProvider.php`
  - [ ] Binding added: `PaymentInvoiceMappingRepositoryInterface::class` → Repository
  - [ ] Binding added: `PaymentInvoiceMappingService::class` → Service
  - [ ] Test: `php artisan tinker` → resolve via container

#### Phase 1.3: Model Relations (Day 3)
- [ ] **Update Voucher Model**
  - [ ] File: `app/Models/Voucher.php`
  - [ ] Add relation method:
    ```php
    public function paymentInvoiceMappings() {
        return $this->hasMany(PaymentInvoiceMapping::class, 'payment_voucher_id');
    }
    ```
  - [ ] Add helper method:
    ```php
    public function getMappedInvoices() {
        return $this->paymentInvoiceMappings()
            ->where('status', '!=', 'reversed')
            ->get();
    }
    ```
  - [ ] Test: Load relation in tinker

- [ ] **Update SalesInvoice Model**
  - [ ] File: `app/Models/SalesInvoice.php`
  - [ ] Add relation:
    ```php
    public function paymentMappings() {
        return $this->hasMany(PaymentInvoiceMapping::class, 'invoice_id')
            ->where('invoice_type', 'sales');
    }
    ```
  - [ ] Add helper:
    ```php
    public function getSettlementDetails() {
        return $this->paymentMappings()
            ->where('status', '!=', 'reversed')
            ->with('paymentVoucher')
            ->get();
    }
    ```
  - [ ] Test: Verify relation

- [ ] **Update PurchaseInvoice Model**
  - [ ] File: `app/Models/PurchaseInvoice.php`
  - [ ] Add relation:
    ```php
    public function paymentMappings() {
        return $this->hasMany(PaymentInvoiceMapping::class, 'invoice_id')
            ->where('invoice_type', 'purchase');
    }
    ```
  - [ ] Add helper: same as SalesInvoice
  - [ ] Test: Verify relation

#### Phase 1.4: Service Layer Updates (Day 4)
- [ ] **Update SalesInvoiceService**
  - [ ] File: `app/Services/SalesInvoiceService.php`
  - [ ] Inject `PaymentInvoiceMappingService`
  - [ ] Update `recordPayment()` method:
    - [ ] Accept optional `mappings` parameter
    - [ ] After creating receipt voucher, call:
      ```php
      $this->paymentMappingService->autoMapPayment(
          $receipt->id, 
          'sales', 
          $mappings ?? [['invoice_id' => $invoiceId, 'amount' => $amount]]
      );
      ```
    - [ ] Test: Record payment, verify mapping created

- [ ] **Update PurchaseInvoiceService**
  - [ ] File: `app/Services/PurchaseInvoiceService.php`
  - [ ] Inject `PaymentInvoiceMappingService`
  - [ ] Update `recordPayment()` method (same as SalesInvoice)
  - [ ] Test: Record payment, verify mapping created

- [ ] **Update VoucherService**
  - [ ] File: `app/Services/VoucherService.php`
  - [ ] Inject `PaymentInvoiceMappingService`
  - [ ] Update `cancel()` method:
    - [ ] Before deleting/cancelling voucher, call:
      ```php
      if (in_array($voucher->voucher_type, ['receipt', 'payment'], true)) {
          $this->paymentMappingService->reverseAllMappings($voucher->id);
      }
      ```
    - [ ] Test: Cancel payment, verify mappings marked as 'reversed'

#### Phase 1.5: Testing (Day 5)
- [ ] **Unit Tests**
  - [ ] `tests/Unit/Models/PaymentInvoiceMappingTest.php`
    - [ ] Test creation with valid data
    - [ ] Test creation with invalid invoice
    - [ ] Test creation with insufficient balance
    - [ ] Test soft delete
  - [ ] `tests/Unit/Services/PaymentInvoiceMappingServiceTest.php`
    - [ ] Test auto-map single invoice
    - [ ] Test auto-map multiple invoices
    - [ ] Test reversal logic
    - [ ] Test settlement summary calculation
  - [ ] Run: `php artisan test tests/Unit/`

- [ ] **Integration Tests**
  - [ ] `tests/Feature/PaymentMappingIntegrationTest.php`
    - [ ] Test flow: Create Sales Invoice → Record Payment → Verify Mapping
    - [ ] Test flow: Create Purchase Invoice → Record Payment → Verify Mapping
    - [ ] Test flow: Cancel payment → Verify mapping reversed + balance restored
    - [ ] Test flow: Multi-invoice payment
  - [ ] Run: `php artisan test tests/Feature/`

- [ ] **Manual Testing**
  - [ ] Test in local environment:
    - [ ] Create sales invoice (₹100k)
    - [ ] Record ₹50k payment
    - [ ] Verify mapping created in DB
    - [ ] Verify invoice balance updated
    - [ ] Cancel payment
    - [ ] Verify balance restored
  - [ ] Test edge cases:
    - [ ] Zero amount payment
    - [ ] Negative amount payment
    - [ ] Payment exceeding invoice
    - [ ] Deleted invoice reference

- [ ] **Database Integrity**
  - [ ] Run: `php artisan tinker`
    - [ ] Verify constraints work
    - [ ] Verify indexes performant
    - [ ] Verify cascading on FK delete

---

### Week 2: Reports & Data Integration

#### Phase 2.1: Report Service Enhancements (Day 1)
- [ ] **Update ReportService**
  - [ ] File: `app/Services/ReportService.php`
  - [ ] Inject `PaymentInvoiceMappingRepository`
  - [ ] Add new methods:
    ```php
    public function getInvoiceSettlementDetails(
        int $companyId,
        string $invoiceType,
        int $invoiceId
    ): array {
        // Return all mappings for this invoice
    }
    
    public function getPaymentSettlementDetails(
        int $companyId,
        int $paymentVoucherId
    ): array {
        // Return all invoices settled by this payment
    }
    
    public function getSettlementAuditReport(
        int $companyId,
        ?Carbon $fromDate = null,
        ?Carbon $toDate = null
    ): array {
        // Return all payment-invoice mappings for audit
    }
    ```
  - [ ] Test: Tinker queries for correctness

#### Phase 2.2: Enhance Debtors Outstanding Report (Day 2)
- [ ] **Update View**
  - [ ] File: `resources/views/admin/reports/debtors-outstanding.blade.php`
  - [ ] Add new column: "Settlement Details"
  - [ ] Show in table:
    - [ ] Which payments settled this invoice
    - [ ] Amount settled by each payment
    - [ ] Outstanding balance
  - [ ] Example output:
    ```
    Invoice | Amount | Paid | Balance | Settlement Details
    INV-001 | 100k   | 50k  | 50k     | Paid ₹50k via RCT-001 (2026-08-15)
    ```

- [ ] **Update Controller**
  - [ ] File: `app/Http/Controllers/Admin/ReportController.php` (or similar)
  - [ ] In debtors action method:
    - [ ] Load additional data: `$reportData->map(fn($row) => [
        ...$row,
        'settlement_details' => $reportService->getInvoiceSettlementDetails(...)
      ])`
    - [ ] Test: View renders correctly with new column

- [ ] **Test**
  - [ ] Create test invoices with payments
  - [ ] Verify settlement details display
  - [ ] Verify balance calculation correct
  - [ ] Verify export includes settlement details

#### Phase 2.3: Enhance Creditors Outstanding Report (Day 3)
- [ ] **Update View**
  - [ ] File: `resources/views/admin/reports/creditors-outstanding.blade.php`
  - [ ] Same as Debtors Outstanding
  - [ ] Column: "Settlement Details"
  - [ ] Show: Which payments settled which bills

- [ ] **Update Controller**
  - [ ] Same pattern as Debtors
  - [ ] Load settlement details for each invoice

- [ ] **Test**
  - [ ] Create test purchase invoices with payments
  - [ ] Verify settlement details display
  - [ ] Verify balance calculation correct

#### Phase 2.4: New Settlement Audit Report (Day 4)
- [ ] **Create New Report Page** (Optional but recommended)
  - [ ] File: `resources/views/admin/reports/settlement-audit.blade.php`
  - [ ] Display all payment-invoice mappings:
    ```
    Payment Voucher | Invoice Type | Invoice Number | Amount Allocated | Amount Settled | Status | Date
    RCT-001         | Sales        | INV-001        | ₹50k             | ₹50k           | Full   | 2026-08-15
    ```
  - [ ] Filters:
    - [ ] Date range
    - [ ] Payment status
    - [ ] Invoice type (Sales/Purchase)
  - [ ] Test: Display, filter, sort

- [ ] **Create Controller Route**
  - [ ] Route: `/admin/reports/settlement-audit`
  - [ ] Method: Load via ReportService
  - [ ] Test: Route loads correctly

#### Phase 2.5: Testing (Day 5)
- [ ] **Report Accuracy**
  - [ ] Test debtors outstanding:
    - [ ] Balance = Invoice Total - Settled Amount ✓
    - [ ] Settlement details accurate ✓
    - [ ] Multiple payments on one invoice ✓
    - [ ] Partial settlements ✓
  - [ ] Test creditors outstanding: (same as debtors)

- [ ] **Data Consistency**
  - [ ] Invoice balance ≥ sum of settled amounts ✓
  - [ ] Ledger balance matches invoice balance ✓
  - [ ] Cancelled payments reflected correctly ✓

- [ ] **Performance**
  - [ ] Query count acceptable (use Laravel Debugbar)
  - [ ] No N+1 problems
  - [ ] Index usage confirmed (via EXPLAIN)

- [ ] **Export Testing**
  - [ ] PDF export includes settlement details
  - [ ] Excel export includes settlement details
  - [ ] Format readable in exported files

---

### Week 3: UI & Final Polish

#### Phase 3.1: Payment Recording UI (Day 1-2)
- [ ] **Update Payment Recording Form**
  - [ ] File: `resources/views/admin/invoices/record-payment.blade.php` (or similar)
  - [ ] Add "Multi-Invoice Payment" toggle
  - [ ] When toggle ON:
    - [ ] Show invoice selector (multiselect)
    - [ ] Show allocation breakdown
    - [ ] Calculate total & balance
  - [ ] When toggle OFF:
    - [ ] Show single invoice only (current behavior)
  - [ ] Add validation:
    - [ ] Sum of allocations = payment total
    - [ ] Each allocation ≤ invoice balance
  - [ ] Test: Form validation, toggle behavior

- [ ] **Update Payment Controller**
  - [ ] File: `app/Http/Controllers/Admin/InvoiceController.php`
  - [ ] In `recordPayment()` action:
    - [ ] Extract multi-invoice mappings from request
    - [ ] Pass to `SalesInvoiceService::recordPayment()` as `mappings` param
    - [ ] Handle response & redirect
  - [ ] Test: Create payment, verify mapping created

- [ ] **Payment Recording Blade Template Updates**
  - [ ] Show allocation breakdown in preview
  - [ ] SweetAlert confirmation with settlement details
  - [ ] Success message shows which invoices settled

#### Phase 3.2: Invoice Detail View Enhancements (Day 2)
- [ ] **Update Invoice Show View**
  - [ ] File: `resources/views/admin/invoices/show.blade.php`
  - [ ] Add section: "Settlement History"
  - [ ] Table showing:
    - [ ] Payment Voucher #
    - [ ] Payment Date
    - [ ] Amount Settled
    - [ ] Status (partial/full)
  - [ ] Action: View payment details
  - [ ] Test: Invoice with multiple payments

- [ ] **Update Purchase Invoice Show View**
  - [ ] Same as sales invoice
  - [ ] Test: Purchase invoice with payments

#### Phase 3.3: Voucher Detail View (Day 3)
- [ ] **Update Voucher Show View**
  - [ ] File: `resources/views/admin/vouchers/show.blade.php`
  - [ ] Add section: "Invoice Settlements" (for payment/receipt vouchers)
  - [ ] Table showing:
    - [ ] Invoice Number
    - [ ] Invoice Type (Sales/Purchase)
    - [ ] Amount Allocated
    - [ ] Amount Settled
    - [ ] Status
  - [ ] Test: Receipt/Payment vouchers

#### Phase 3.4: API Endpoints (Day 3)
- [ ] **Create API Endpoint for Settlement Details**
  - [ ] Endpoint: `GET /api/v1/invoices/{id}/settlements`
  - [ ] Return: Settlement details for mobile app
  - [ ] Auth: Sanctum
  - [ ] Test: Verify response format

- [ ] **Create API Endpoint for Payment Mappings**
  - [ ] Endpoint: `POST /api/v1/payments/multi-invoice`
  - [ ] Accept: Payment data + invoice mappings
  - [ ] Return: Created payment voucher + mappings
  - [ ] Validation: Ensure mappings valid
  - [ ] Test: Create multi-invoice payment via API

#### Phase 3.5: Documentation & Cleanup (Day 4)
- [ ] **Code Documentation**
  - [ ] Add PHPDoc to all new methods
  - [ ] Document mapping logic in README
  - [ ] Document API endpoints in OpenAPI/Swagger

- [ ] **Migration Notes**
  - [ ] Create `docs/PAYMENT_MAPPING_MIGRATION.md`
  - [ ] Document:
    - [ ] How existing payments are handled
    - [ ] Auto-population logic
    - [ ] Rollback procedure

- [ ] **Cleanup**
  - [ ] Remove any TODO comments
  - [ ] Verify no console.log in JS
  - [ ] Remove debug code
  - [ ] Format code per Laravel standards
  - [ ] Run: `php artisan format`

#### Phase 3.6: Testing & QA (Day 4-5)
- [ ] **Full Regression Testing**
  - [ ] All existing payment flows work ✓
  - [ ] All existing reports accurate ✓
  - [ ] No errors in logs ✓
  - [ ] No performance regression ✓

- [ ] **New Feature Testing**
  - [ ] Multi-invoice payment creation ✓
  - [ ] Settlement details in reports ✓
  - [ ] Invoice show pages ✓
  - [ ] Voucher show pages ✓
  - [ ] API endpoints ✓

- [ ] **Staging Deployment**
  - [ ] Deploy to staging server
  - [ ] Run migrations on staging DB
  - [ ] Test all features on staging
  - [ ] Have business user test UX
  - [ ] Verify email notifications (if any)
  - [ ] Check audit logs for issues

- [ ] **Documentation Review**
  - [ ] PM reviews feature
  - [ ] Business user confirms meets requirements
  - [ ] Technical lead reviews code
  - [ ] QA lead signs off on testing

---

## OPTION B: Quick Implementation Checklist

### Day 1: Add Indexes & Queries

- [ ] **Create Migration**
  - [ ] File: `database/migrations/YYYY_MM_DD_index_voucher_invoice_columns.php`
  - [ ] Add indexes:
    ```php
    Schema::table('vouchers', function (Blueprint $table) {
        $table->index(['sales_invoice_id', 'voucher_type']);
        $table->index(['purchase_invoice_id', 'voucher_type']);
    });
    ```
  - [ ] Test: `php artisan migrate`

- [ ] **Update Report Queries**
  - [ ] File: `app/Services/ReportService.php`
  - [ ] Add method:
    ```php
    public function getInvoiceSettlementVoucher(string $type, int $invoiceId) {
        if ($type === 'sales') {
            return Voucher::where('sales_invoice_id', $invoiceId)
                ->where('voucher_type', 'receipt')
                ->where('status', 'posted')
                ->first();
        }
        return Voucher::where('purchase_invoice_id', $invoiceId)
            ->where('voucher_type', 'payment')
            ->where('status', 'posted')
            ->first();
    }
    ```

- [ ] **Test**: Verify query performance

### Day 2: Update Report Views

- [ ] **Debtors Outstanding Report**
  - [ ] File: `resources/views/admin/reports/debtors-outstanding.blade.php`
  - [ ] Add column: "Settled By Voucher"
  - [ ] Show: Settlement voucher number if exists
  - [ ] Test: Render correctly

- [ ] **Creditors Outstanding Report**
  - [ ] File: `resources/views/admin/reports/creditors-outstanding.blade.php`
  - [ ] Same as Debtors Outstanding

- [ ] **Test**: Both reports show settlement info

### Day 3: Update Views

- [ ] **Invoice Show Page**
  - [ ] Add simple section: "Settlement Voucher: RCT-001 dated 2026-08-15"
  - [ ] Link to voucher details
  - [ ] Test: Display correctly

- [ ] **Test**: All changes work

### Day 4-5: Testing & Deployment

- [ ] **Regression Testing**
  - [ ] All existing features work ✓
  - [ ] No errors ✓
  - [ ] No performance issues ✓

- [ ] **New Feature Testing**
  - [ ] "Settled By" column displays ✓
  - [ ] Settlement links work ✓
  - [ ] Reports accurate ✓

- [ ] **Staging Deployment**
  - [ ] Deploy to staging
  - [ ] Run migrations
  - [ ] Test all features
  - [ ] Get sign-off

---

## OPTION C: Defer Checklist

- [ ] **Document Decision**
  - [ ] Create file: `docs/PAYMENT_MAPPING_DEFERRED.md`
  - [ ] Reason: _________________
  - [ ] Target version for implementation: ___
  - [ ] Estimated value/priority: ___

- [ ] **Notify Team**
  - [ ] Attach analysis documents to Jira/GitHub
  - [ ] Explain why deferred
  - [ ] Set reminder for next review

- [ ] **Create Future Task**
  - [ ] Jira/GitHub issue for Option A/B implementation
  - [ ] Link analysis documents
  - [ ] Set label: `deferred`, `tally-compliance`, `future-roadmap`
  - [ ] Estimate story points
  - [ ] Assign to: _____ (future sprint)

---

## Post-Implementation (All Options)

### Before Production Deployment

- [ ] **Backup**
  - [ ] Full database backup taken
  - [ ] Backup verified (can restore)

- [ ] **Performance Baseline**
  - [ ] Debtors report load time: _____ ms
  - [ ] Creditors report load time: _____ ms
  - [ ] Outstanding balance query time: _____ ms

- [ ] **Monitoring**
  - [ ] Error monitoring configured (Sentry/etc)
  - [ ] Performance monitoring configured
  - [ ] Database query logging enabled
  - [ ] User activity logging enabled

- [ ] **Rollback Plan**
  - [ ] Down migration tested locally
  - [ ] Rollback procedure documented
  - [ ] Team knows rollback steps

### After Production Deployment

- [ ] **Smoke Testing**
  - [ ] Create test invoice
  - [ ] Create test payment
  - [ ] Verify debtors report
  - [ ] Verify creditors report
  - [ ] Cancel payment & verify reversal

- [ ] **Monitor for 24 Hours**
  - [ ] Check error logs: _____ (no new errors?)
  - [ ] Check performance: _____ (baseline maintained?)
  - [ ] Check user reports: _____ (all good?)

- [ ] **User Communication**
  - [ ] Notify users of new features
  - [ ] Provide documentation/training if needed
  - [ ] Collect feedback

- [ ] **Close Tickets**
  - [ ] Mark all implementation tasks complete
  - [ ] Close feature branch
  - [ ] Create retrospective if needed
  - [ ] Document lessons learned

---

## Success Criteria

**All Options**:
- [ ] ✅ No breaking changes to existing functionality
- [ ] ✅ All existing tests pass
- [ ] ✅ No new errors in production logs
- [ ] ✅ No performance degradation
- [ ] ✅ Documentation complete

**Option A (Full)**:
- [ ] ✅ Multi-invoice payments work end-to-end
- [ ] ✅ Settlement details visible in reports
- [ ] ✅ API endpoints for mobile app ready
- [ ] ✅ 95% Tally compliance achieved

**Option B (Quick)**:
- [ ] ✅ "Settled By" column in reports
- [ ] ✅ Settlement lookup performant
- [ ] ✅ Database indexes optimized

**Option C (Defer)**:
- [ ] ✅ Decision documented
- [ ] ✅ Analysis preserved for future
- [ ] ✅ Feature scheduled for next release

---

## Sign-Off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Project Manager | _____ | _____ | _____ |
| Lead Developer | _____ | _____ | _____ |
| Tech Lead | _____ | _____ | _____ |
| QA Lead | _____ | _____ | _____ |

---

**Implementation Start**: _________  
**Estimated Completion**: _________  
**Actual Completion**: _________  

**Notes**:
```




```

---

**Document Version**: 1.0  
**Last Updated**: 2026-08-16  
**Status**: Ready for Execution
