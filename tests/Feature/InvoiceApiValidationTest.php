<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureActiveSubscription;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Item;
use App\Models\Party;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceApiValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_and_purchase_invoice_api_reject_blank_required_data(): void
    {
        $this->authenticateUser();

        $this->postJson('/api/v1/sales-invoices', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['party_id', 'invoice_date', 'due_date', 'lines']);

        $this->postJson('/api/v1/purchase-invoices', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['party_id', 'invoice_date', 'due_date', 'lines']);
    }

    public function test_sales_invoice_api_creates_recurring_monthly_invoice(): void
    {
        [$user, $company] = $this->authenticateUser();
        $party = Party::factory()->create([
            'company_id' => $company->id,
            'type' => 'debtor',
        ]);
        $item = Item::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'item_code' => 'API-ITEM-001',
            'name' => 'API Recurring Item',
            'type' => 'goods',
            'unit' => 'nos',
            'selling_price' => 100,
            'purchase_price' => 50,
            'is_stockable' => true,
            'is_active' => true,
        ]);

        $invoiceDate = Carbon::parse('2026-09-17');
        $response = $this->postJson('/api/v1/sales-invoices', [
            'party_id' => 'party:' . $party->id,
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $invoiceDate->copy()->addMonth()->toDateString(),
            'save_as_draft' => true,
            'is_recurring' => true,
            'recurrence_frequency' => 'monthly',
            'recurrence_monthly_type' => 'last_day',
            'lines' => [[
                'item_id' => $item->id,
                'quantity' => 1,
                'unit_price' => 100,
            ]],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_recurring', true)
            ->assertJsonPath('data.recurrence_frequency', 'monthly')
            ->assertJsonPath('data.recurrence_monthly_type', 'last_day')
            ->assertJsonPath('data.recurrence_next_run_at', '2026-09-30');
    }

    private function authenticateUser(): array
    {
        $company = Company::factory()->create();
        FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $this->withoutMiddleware(EnsureActiveSubscription::class);
        Sanctum::actingAs($user);

        return [$user, $company];
    }
}