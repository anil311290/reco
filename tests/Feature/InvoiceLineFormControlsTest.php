<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Party;
use App\Models\Company;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceLineFormControlsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();

        $this->user = User::where('email', 'superadmin@reco.app')->firstOrFail();
    }

    public function test_sales_invoice_forms_expose_quick_add_and_searchable_items(): void
    {
        $invoice = $this->createSalesInvoice();

        foreach (['/admin/sales-invoices/create', "/admin/sales-invoices/{$invoice->id}/edit"] as $path) {
            $response = $this->actingAs($this->user)->get($path);

            $response->assertOk();
            $response->assertSee('id="quickAddItem"', false);
            $response->assertSee('id="quickAddItemModal"', false);
            $response->assertSee('id="lineRowTemplate"', false);
            $response->assertSee('particular-select w-100" data-searchable="true"', false);
        }
    }

    public function test_purchase_invoice_forms_expose_quick_add_and_searchable_items(): void
    {
        $invoice = $this->createPurchaseInvoice();

        foreach (['/admin/purchase-invoices/create', "/admin/purchase-invoices/{$invoice->id}/edit"] as $path) {
            $response = $this->actingAs($this->user)->get($path);

            $response->assertOk();
            $response->assertSee('id="quickAddItem"', false);
            $response->assertSee('id="quickAddItemModal"', false);
            $response->assertSee('id="lineRowTemplate"', false);
            $response->assertSee('item-select w-100" data-searchable="true"', false);
        }
    }

    public function test_sales_invoice_duplicate_prefills_source_data_with_fresh_dates(): void
    {
        $invoice = $this->createSalesInvoice();
        $invoice->update([
            'reference_number' => 'PO-123',
            'payment_terms' => 'Net 15',
            'notes' => 'Duplicate sales note',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/admin/sales-invoices/create?duplicate={$invoice->id}");

        $response->assertOk();
        $response->assertSee('value="PO-123"', false);
        $response->assertSee('value="Net 15"', false);
        $response->assertSee('Duplicate sales note', false);
        $response->assertSee('Test line', false);
        $response->assertSee('value="2026-09-17"', false);
        $response->assertSee('value="2026-10-17"', false);
    }

    public function test_purchase_invoice_duplicate_prefills_source_data_and_rejects_other_company(): void
    {
        $invoice = $this->createPurchaseInvoice();
        $invoice->update([
            'supplier_invoice_number' => 'SUP-123',
            'payment_terms' => 'Net 30',
            'notes' => 'Duplicate purchase note',
        ]);

        $response = $this->actingAs($this->user)
            ->get("/admin/purchase-invoices/create?duplicate={$invoice->id}");

        $response->assertOk();
        $response->assertSee('value="SUP-123"', false);
        $response->assertSee('value="Net 30"', false);
        $response->assertSee('Duplicate purchase note', false);
        $response->assertSee('Test line', false);
        $response->assertSee('value="2026-09-17"', false);
        $response->assertSee('value="2026-10-17"', false);

        $otherCompany = Company::factory()->create();
        $otherInvoice = $invoice->replicate();
        $otherInvoice->uuid = (string) Str::uuid();
        $otherInvoice->company_id = $otherCompany->id;
        $otherInvoice->invoice_number = 'PINV-OTHER/0001';
        $otherInvoice->save();

        $this->actingAs($this->user)
            ->get("/admin/purchase-invoices/create?duplicate={$otherInvoice->id}")
            ->assertNotFound();
    }

    private function createSalesInvoice(): SalesInvoice
    {
        $invoice = SalesInvoice::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $this->user->company_id,
            'party_id' => $this->debtor()->id,
            'invoice_number' => 'INV-TEST/0001',
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-31',
            'subtotal' => 100,
            'tax_amount' => 0,
            'total' => 100,
            'balance_due' => 100,
            'status' => 'draft',
        ]);

        SalesInvoiceLine::create([
            'uuid' => (string) Str::uuid(),
            'sales_invoice_id' => $invoice->id,
            'line_type' => 'item',
            'item_id' => $this->item()->id,
            'description' => 'Test line',
            'quantity' => 1,
            'unit_price' => 100,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 100,
            'sort_order' => 0,
        ]);

        return $invoice->fresh();
    }

    private function createPurchaseInvoice(): PurchaseInvoice
    {
        $invoice = PurchaseInvoice::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $this->user->company_id,
            'party_id' => $this->creditor()->id,
            'invoice_number' => 'PINV-TEST/0001',
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-31',
            'subtotal' => 100,
            'tax_amount' => 0,
            'total' => 100,
            'balance_due' => 100,
            'status' => 'draft',
        ]);

        PurchaseInvoiceLine::create([
            'uuid' => (string) Str::uuid(),
            'purchase_invoice_id' => $invoice->id,
            'line_type' => 'item',
            'item_id' => $this->item()->id,
            'description' => 'Test line',
            'quantity' => 1,
            'unit_price' => 100,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total' => 100,
            'sort_order' => 0,
        ]);

        return $invoice->fresh();
    }

    private function item(): Item
    {
        return Item::firstOrCreate(
            [
                'company_id' => $this->user->company_id,
                'item_code' => 'ITEM-TEST',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Test Widget',
                'type' => 'goods',
                'unit' => 'nos',
                'purchase_price' => 80,
                'selling_price' => 100,
                'is_stockable' => true,
                'is_active' => true,
            ]
        );
    }

    private function debtor(): Party
    {
        return Party::firstOrCreate(
            [
                'company_id' => $this->user->company_id,
                'type' => 'debtor',
                'name' => 'Test Debtor',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'party_code' => 'DBT-TEST',
                'mobile' => '9999999999',
                'address' => 'Test Address',
                'opening_balance' => 0,
                'opening_balance_type' => 'debit',
                'opening_date' => '2026-04-01',
                'is_active' => true,
            ]
        );
    }

    private function creditor(): Party
    {
        return Party::firstOrCreate(
            [
                'company_id' => $this->user->company_id,
                'type' => 'creditor',
                'name' => 'Test Creditor',
            ],
            [
                'uuid' => (string) Str::uuid(),
                'party_code' => 'CDT-TEST',
                'mobile' => '8888888888',
                'address' => 'Test Address',
                'opening_balance' => 0,
                'opening_balance_type' => 'credit',
                'opening_date' => '2026-04-01',
                'is_active' => true,
            ]
        );
    }
}
