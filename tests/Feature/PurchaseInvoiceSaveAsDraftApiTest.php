<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Item;
use App\Models\Party;
use App\Models\TaxRate;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseInvoiceSaveAsDraftApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_keeps_purchase_invoice_as_draft_when_save_as_draft_is_true(): void
    {
        [$company, $user, $party, $item, $taxRate] = $this->seedPurchaseInvoiceContext();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/purchase-invoices', [
            'party_id' => 'party:' . $party->id,
            'invoice_date' => '2026-09-01',
            'due_date' => '2026-10-01',
            'supplier_invoice_number' => 'SUP-001',
            'payment_terms' => '30 days',
            'delivery_terms' => 'FOB',
            'notes' => 'Draft purchase invoice',
            'discount_percentage' => 0,
            'save_as_draft' => true,
            'lines' => [
                [
                    'item_id' => $item->id,
                    'account_id' => $item->expense_account_id,
                    'tax_rate_id' => $taxRate->id,
                    'description' => 'Test goods',
                    'quantity' => 1,
                    'unit_price' => 200,
                    'discount_percentage' => 0,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Purchase invoice saved as draft')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.status_label', 'Draft');

        $invoiceId = (int) $response->json('data.id');
        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $invoiceId,
            'company_id' => $company->id,
            'status' => 'draft',
        ]);
        $this->assertSame(
            0,
            Voucher::query()->where('purchase_invoice_id', $invoiceId)->count()
        );
    }

    public function test_api_posts_purchase_invoice_when_save_as_draft_is_false(): void
    {
        [$company, $user, $party, $item, $taxRate] = $this->seedPurchaseInvoiceContext();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/purchase-invoices', [
            'party_id' => 'party:' . $party->id,
            'invoice_date' => '2026-09-01',
            'due_date' => '2026-10-01',
            'discount_percentage' => 0,
            'save_as_draft' => false,
            'lines' => [
                [
                    'item_id' => $item->id,
                    'account_id' => $item->expense_account_id,
                    'tax_rate_id' => $taxRate->id,
                    'description' => 'Test goods',
                    'quantity' => 1,
                    'unit_price' => 200,
                    'discount_percentage' => 0,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Invoice created')
            ->assertJsonPath('data.status', 'verified')
            ->assertJsonPath('data.status_label', 'Verified');

        $invoiceId = (int) $response->json('data.id');
        $this->assertDatabaseHas('purchase_invoices', [
            'id' => $invoiceId,
            'company_id' => $company->id,
            'status' => 'verified',
        ]);
        $this->assertSame(
            1,
            Voucher::query()
                ->where('purchase_invoice_id', $invoiceId)
                ->where('status', 'posted')
                ->count()
        );
    }

    /**
     * @return array{0: Company, 1: User, 2: Party, 3: Item, 4: TaxRate}
     */
    private function seedPurchaseInvoiceContext(): array
    {
        $company = Company::factory()->create();
        $financialYear = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $accountsPayable = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'account_code' => Account::CODE_AP,
            'account_name' => 'Accounts Payable',
            'account_type' => 'liability',
            'balance_type' => 'credit',
            'is_system' => true,
        ]);

        $purchaseExpense = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'account_code' => Account::CODE_AP_EXPENSE,
            'account_name' => 'Purchase Expenses',
            'account_type' => 'expense',
            'balance_type' => 'debit',
            'is_system' => true,
        ]);

        $party = Party::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'type' => 'creditor',
            'account_id' => $accountsPayable->id,
        ]);

        $taxRateId = DB::table('tax_rates')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'name' => 'GST 18%',
            'code' => 'GST18',
            'rate' => 18,
            'type' => 'gst',
            'category' => 'GST',
            'calculation_type' => 'addition',
            'is_active' => true,
            'tax_name' => 'GST 18%',
            'tax_code' => 'GST18',
            'tax_rate' => 18,
            'tax_type' => 'addition',
            'tax_category' => 'GST',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $taxRate = TaxRate::findOrFail($taxRateId);

        $item = Item::create([
            'company_id' => $company->id,
            'name' => 'Test goods item',
            'type' => 'goods',
            'purchase_price' => 200,
            'selling_price' => 250,
            'unit' => 'nos',
            'expense_account_id' => $purchaseExpense->id,
            'tax_rate_id' => $taxRate->id,
            'is_active' => true,
            'is_stockable' => true,
            'opening_stock' => 10,
            'current_stock' => 10,
        ]);

        return [$company, $user, $party, $item, $taxRate];
    }
}
