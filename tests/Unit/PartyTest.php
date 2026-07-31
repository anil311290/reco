<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Ledger;
use App\Models\Party;
use App\Models\Voucher;
use App\Services\PartyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyTest extends TestCase
{
    use RefreshDatabase;

    protected PartyService $partyService;
    protected Company $company;
    protected FinancialYear $financialYear;

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

    public function test_can_create_debtor_party(): void
    {
        $partyData = [
            'company_id' => $this->company->id,
            'name' => 'ABC Customer',
            'type' => 'debtor',
            'mobile' => '+91 9876543210',
        ];

        $party = $this->partyService->create($partyData);

        $this->assertInstanceOf(Party::class, $party);
        $this->assertEquals('ABC Customer', $party->name);
        $this->assertEquals('debtor', $party->type);
        $this->assertStringStartsWith('AR', $party->party_code);
    }

    public function test_can_create_creditor_party(): void
    {
        $partyData = [
            'company_id' => $this->company->id,
            'name' => 'XYZ Supplier',
            'type' => 'creditor',
        ];

        $party = $this->partyService->create($partyData);

        $this->assertEquals('creditor', $party->type);
        $this->assertStringStartsWith('AP', $party->party_code);
    }

    public function test_can_update_party(): void
    {
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $updated = $this->partyService->update($party->id, [
            'name' => 'Updated Party',
        ]);

        $this->assertTrue($updated);
        $this->assertEquals('Updated Party', $party->fresh()->name);
    }

    public function test_can_delete_party(): void
    {
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $deleted = $this->partyService->delete($party->id);

        $this->assertTrue($deleted);
        $this->assertNull(Party::find($party->id));
    }

    public function test_deleted_party_name_can_be_found_and_restored(): void
    {
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
            'party_code' => 'AR001',
            'name' => 'Acme Customer',
            'type' => 'debtor',
            'opening_balance' => 0,
        ]);
        $this->partyService->delete($party->id);

        $deletedParty = $this->partyService->findDeletedByName(
            $this->company->id,
            '  ACME CUSTOMER  '
        );

        $this->assertNotNull($deletedParty);

        $restoredParty = $this->partyService->restoreDeleted($deletedParty, [
            'name' => 'Acme Customer',
            'type' => 'debtor',
            'address' => 'Updated address',
            'opening_balance' => 0,
            'opening_balance_type' => 'debit',
            'updated_by_ip' => '127.0.0.1',
        ]);

        $this->assertSame($party->id, $restoredParty->id);
        $this->assertSame('AR001', $restoredParty->party_code);
        $this->assertSame('Updated address', $restoredParty->address);
        $this->assertNull($restoredParty->deleted_at);
    }

    public function test_new_party_code_does_not_reuse_soft_deleted_code(): void
    {
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
            'party_code' => 'AR001',
            'type' => 'debtor',
            'opening_balance' => 0,
        ]);
        $this->partyService->delete($party->id);

        $newParty = $this->partyService->create([
            'company_id' => $this->company->id,
            'name' => 'New Customer',
            'type' => 'debtor',
        ]);

        $this->assertSame('AR002', $newParty->party_code);
    }

    public function test_party_codes_and_opening_vouchers_are_scoped_per_company(): void
    {
        Party::factory()->create([
            'company_id' => $this->company->id,
            'party_code' => 'AR001',
            'type' => 'debtor',
            'opening_balance' => 0,
        ]);

        Voucher::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'voucher_number' => 'ADJ000001',
            'voucher_type' => 'adjustment',
        ]);

        $otherCompany = Company::factory()->create();
        $otherYear = FinancialYear::factory()->create([
            'company_id' => $otherCompany->id,
            'is_current' => true,
        ]);

        foreach ([
            [Account::CODE_AR, 'Accounts Receivable', 'asset'],
            [Account::CODE_AP, 'Accounts Payable', 'liability'],
            [Account::CODE_SUSPENSE, 'Opening Balance Difference', 'asset'],
        ] as [$code, $name, $type]) {
            Account::factory()->create([
                'company_id' => $otherCompany->id,
                'financial_year_id' => $otherYear->id,
                'account_code' => $code,
                'account_name' => $name,
                'account_type' => $type,
                'opening_balance' => 0,
                'is_system' => true,
            ]);
        }

        $party = $this->partyService->create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Co Customer',
            'type' => 'debtor',
            'opening_balance' => 5500,
            'opening_balance_type' => 'debit',
            'opening_date' => now()->toDateString(),
        ]);

        $this->assertSame('AR001', $party->party_code);
        $this->assertDatabaseHas('vouchers', [
            'company_id' => $otherCompany->id,
            'voucher_number' => 'ADJ000001',
            'voucher_type' => 'adjustment',
        ]);
        $this->assertSame(
            2,
            Voucher::withTrashed()->where('voucher_number', 'ADJ000001')->count()
        );
    }

    public function test_can_get_parties_by_type(): void
    {
        Party::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'type' => 'debtor',
        ]);

        Party::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'type' => 'creditor',
        ]);

        $debtors = $this->partyService->getAll([
            'company_id' => $this->company->id,
            'type' => 'debtor',
        ]);

        $this->assertCount(3, $debtors);
    }

    public function test_can_get_parties_for_dropdown(): void
    {
        Party::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'type' => 'debtor',
            'is_active' => true,
        ]);

        $dropdown = $this->partyService->getForDropdown($this->company->id, 'debtor');

        $this->assertCount(3, $dropdown);
        $this->assertArrayHasKey('id', $dropdown[0]);
        $this->assertArrayHasKey('text', $dropdown[0]);
    }

    public function test_party_type_label(): void
    {
        $debtor = Party::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'debtor',
        ]);

        $creditor = Party::factory()->create([
            'company_id' => $this->company->id,
            'type' => 'creditor',
        ]);

        $this->assertEquals('Debtor', $debtor->type_label);
        $this->assertEquals('Creditor', $creditor->type_label);
    }

    public function test_party_belongs_to_company(): void
    {
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertNotNull($party->company);
        $this->assertEquals($this->company->id, $party->company->id);
    }

    public function test_can_toggle_party_status(): void
    {
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->partyService->update($party->id, ['is_active' => false]);

        $this->assertFalse($party->fresh()->is_active);
    }

    public function test_create_party_with_opening_balance_creates_ledger_entries(): void
    {
        $openingBalanceAccount = Account::where('account_code', Account::CODE_SUSPENSE)->first();

        $partyData = [
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'name' => 'Debtor Opening Balance',
            'type' => 'debtor',
            'opening_balance' => 1250.00,
            'opening_balance_type' => 'debit',
            'opening_date' => now()->toDateString(),
        ];

        $party = $this->partyService->create($partyData);

        $this->assertDatabaseHas('parties', ['id' => $party->id, 'opening_balance' => 1250.00]);

        $openingVoucher = Voucher::where('company_id', $this->company->id)
            ->where('narration', 'like', "[OB:party:{$party->id}]%")
            ->firstOrFail();
        $ledgerEntries = Ledger::where('voucher_id', $openingVoucher->id)->get();

        $this->assertCount(2, $ledgerEntries);

        $partyEntry = $ledgerEntries->firstWhere('account_id', $party->account_id);
        $this->assertNotNull($partyEntry);
        $this->assertEquals(1250.00, (float) $partyEntry->debit);
        $this->assertEquals(0.00, (float) $partyEntry->credit);

        $offsetEntry = $ledgerEntries->firstWhere('account_id', $openingBalanceAccount->id);
        $this->assertNotNull($offsetEntry);
        $this->assertEquals(0.00, (float) $offsetEntry->debit);
        $this->assertEquals(1250.00, (float) $offsetEntry->credit);
    }

    public function test_can_delete_new_party_with_only_an_opening_balance_adjustment(): void
    {
        $party = $this->partyService->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'name' => 'Opening Balance Supplier',
            'type' => 'creditor',
            'opening_balance' => 1000,
            'opening_balance_type' => 'credit',
            'opening_date' => now()->toDateString(),
        ]);

        $openingVoucher = Voucher::where('company_id', $this->company->id)
            ->where('narration', 'like', "[OB:party:{$party->id}]%")
            ->firstOrFail();

        $deleted = $this->partyService->delete($party->id);

        $this->assertTrue($deleted);
        $this->assertNull(Party::find($party->id));
        $this->assertDatabaseMissing('vouchers', ['id' => $openingVoucher->id]);
        $this->assertDatabaseMissing('ledgers', ['voucher_id' => $openingVoucher->id]);
        $this->assertDatabaseMissing('voucher_lines', ['voucher_id' => $openingVoucher->id]);
    }

    public function test_cannot_delete_party_with_a_real_transaction(): void
    {
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'account_id' => Account::where('account_code', Account::CODE_AR)->value('id'),
            'type' => 'debtor',
        ]);

        Ledger::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'account_id' => $party->account_id,
            'party_id' => $party->id,
            'voucher_id' => null,
            'reference_type' => 'manual_test',
            'reference_id' => $party->id,
            'debit' => 100,
            'credit' => 0,
        ]);

        $this->expectException(\Exception::class);
        $this->partyService->delete($party->id);
    }

    public function test_party_code_is_unique(): void
    {
        $party1 = Party::factory()->create([
            'company_id' => $this->company->id,
            'party_code' => 'DEB0001',
        ]);

        $this->expectException(\Exception::class);

        Party::factory()->create([
            'company_id' => $this->company->id,
            'party_code' => 'DEB0001',
        ]);
    }

    public function test_invoice_account_selection_does_not_auto_create_party(): void
    {
        $cash = Account::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'account_code' => 'CASH01',
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'is_cash_bank_od' => true,
            'is_active' => true,
        ]);

        $beforeCount = Party::where('company_id', $this->company->id)->count();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not mapped to any party');

        try {
            $this->partyService->resolveInvoicePartySelection(
                'account:' . $cash->id,
                $this->company->id,
                'debtor'
            );
        } finally {
            $afterCount = Party::where('company_id', $this->company->id)->count();
            $this->assertSame($beforeCount, $afterCount);
        }
    }

    public function test_invoice_account_selection_reuses_existing_party_and_updates_linked_account(): void
    {
        $cash = Account::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'account_code' => 'CASH02',
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'is_cash_bank_od' => true,
            'is_active' => true,
        ]);

        $party = Party::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'type' => 'debtor',
            'name' => 'Cash',
            'account_id' => Account::where('company_id', $this->company->id)
                ->where('account_code', Account::CODE_AR)
                ->value('id'),
            'is_active' => true,
        ]);

        $resolvedId = $this->partyService->resolveInvoicePartySelection(
            'account:' . $cash->id,
            $this->company->id,
            'debtor'
        );

        $this->assertSame((int) $party->id, $resolvedId);
        $this->assertSame((int) $cash->id, (int) $party->fresh()->account_id);
    }

    public function test_purchase_invoice_account_selection_does_not_auto_create_party(): void
    {
        $bank = Account::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'account_code' => 'BANK99',
            'account_name' => 'Test Bank',
            'account_type' => 'asset',
            'is_cash_bank_od' => true,
            'is_active' => true,
        ]);

        $beforeCount = Party::where('company_id', $this->company->id)
            ->where('type', 'creditor')
            ->count();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not mapped to any party');

        try {
            $this->partyService->resolveInvoicePartySelection(
                'account:' . $bank->id,
                $this->company->id,
                'creditor'
            );
        } finally {
            $afterCount = Party::where('company_id', $this->company->id)
                ->where('type', 'creditor')
                ->count();
            $this->assertSame($beforeCount, $afterCount);
        }
    }
}
