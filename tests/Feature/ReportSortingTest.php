<?php

namespace Tests\Feature;

use App\Models\Party;
use App\Models\SalesInvoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_debtors_outstanding_report_sorts_by_balance(): void
    {
        $this->seed();

        $user = User::where('email', 'superadmin@reco.app')->firstOrFail();
        $companyId = $user->company_id;

        $partyLow = Party::factory()->create(['company_id' => $companyId, 'type' => 'debtor']);
        $partyHigh = Party::factory()->create(['company_id' => $companyId, 'type' => 'debtor']);

        $this->createOutstandingSalesInvoice($companyId, $partyLow->id, 'INV-SORT-LOW', 100);
        $this->createOutstandingSalesInvoice($companyId, $partyHigh->id, 'INV-SORT-HIGH', 900);

        $this->actingAs($user)
            ->get(route('admin.reports.debtors-outstanding', ['sort' => 'balance', 'dir' => 'asc']))
            ->assertOk()
            ->assertSeeInOrder(['INV-SORT-LOW', 'INV-SORT-HIGH']);

        $this->actingAs($user)
            ->get(route('admin.reports.debtors-outstanding', ['sort' => 'balance', 'dir' => 'desc']))
            ->assertOk()
            ->assertSeeInOrder(['INV-SORT-HIGH', 'INV-SORT-LOW']);
    }

    private function createOutstandingSalesInvoice(int $companyId, int $partyId, string $invoiceNumber, float $total): void
    {
        SalesInvoice::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $companyId,
            'party_id' => $partyId,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'subtotal' => $total,
            'tax_amount' => 0,
            'total' => $total,
            'balance_due' => $total,
            'status' => 'draft',
        ]);
    }
}
