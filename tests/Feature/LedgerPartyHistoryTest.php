<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Ledger;
use App\Models\Party;
use App\Models\Voucher;
use App\Services\PartyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerPartyHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected FinancialYear $financialYear;
    protected PartyService $partyService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->financialYear = FinancialYear::factory()->create([
            'company_id' => $this->company->id,
            'is_current' => true,
        ]);

        Account::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'account_code' => Account::CODE_AR,
            'account_name' => 'Accounts Receivable',
            'account_type' => 'asset',
            'opening_balance' => 0,
            'is_system' => true,
        ]);

        Account::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'account_code' => Account::CODE_AP,
            'account_name' => 'Accounts Payable',
            'account_type' => 'liability',
            'opening_balance' => 0,
            'is_system' => true,
        ]);

        Account::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'account_code' => Account::CODE_SUSPENSE,
            'account_name' => 'Opening Balance Difference',
            'account_type' => 'asset',
            'opening_balance' => 0,
            'is_system' => true,
        ]);

        $this->partyService = $this->app->make(PartyService::class);
    }

    public function test_opening_balance_party_creation_logs_ledger_party_history(): void
    {
        $partyData = [
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'name' => 'Test Customer',
            'type' => 'debtor',
            'opening_balance' => 1750.00,
            'opening_balance_type' => 'debit',
            'opening_date' => now()->toDateString(),
        ];

        $party = $this->partyService->create($partyData);

        $this->assertDatabaseHas('parties', [
            'id' => $party->id,
            'name' => 'Test Customer',
            'type' => 'debtor',
            'opening_balance' => 1750.00,
        ]);

        $openingVoucher = Voucher::where('company_id', $this->company->id)
            ->where('narration', 'like', "[OB:party:{$party->id}]%")
            ->firstOrFail();
        $ledgerEntries = Ledger::where('voucher_id', $openingVoucher->id)->get();

        // Two balanced ledger legs (AR + suspense), but only the party leg is
        // tagged with the party under the control-account model.
        $this->assertCount(2, $ledgerEntries);

        $partyLeg = $ledgerEntries->firstWhere('party_id', $party->id);
        $this->assertNotNull($partyLeg);

        $this->assertDatabaseCount('ledger_party_histories', 1);
        $this->assertDatabaseHas('ledger_party_histories', [
            'ledger_id' => $partyLeg->id,
            'party_id' => $party->id,
            'reference_type' => 'voucher',
            'reference_id' => $openingVoucher->id,
        ]);
    }
}
