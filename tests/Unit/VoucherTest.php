<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoucherTest extends TestCase
{
    use RefreshDatabase;

    protected VoucherService $voucherService;
    protected Company $company;
    protected FinancialYear $financialYear;
    protected Account $debitAccount;
    protected Account $creditAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->financialYear = FinancialYear::factory()->create([
            'company_id' => $this->company->id,
            'is_current' => true,
        ]);

        $this->debitAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type' => 'expense',
        ]);

        $this->creditAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type' => 'asset',
        ]);

        $this->voucherService = $this->app->make(VoucherService::class);
    }

    public function test_can_create_voucher(): void
    {
        $voucherData = [
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'voucher_type' => 'expense',
            'voucher_date' => now()->format('Y-m-d'),
            'narration' => 'Test expense voucher',
            'lines' => [
                [
                    'account_id' => $this->debitAccount->id,
                    'debit' => 1000,
                    'credit' => 0,
                    'description' => 'Expense entry',
                ],
                [
                    'account_id' => $this->creditAccount->id,
                    'debit' => 0,
                    'credit' => 1000,
                    'description' => 'Cash payment',
                ],
            ],
        ];

        $voucher = $this->voucherService->create($voucherData);

        $this->assertInstanceOf(Voucher::class, $voucher);
        $this->assertEquals('expense', $voucher->voucher_type);
        $this->assertEquals(1000, $voucher->total_debit);
        $this->assertEquals(1000, $voucher->total_credit);
        $this->assertCount(2, $voucher->lines);
    }

    public function test_voucher_number_is_auto_generated(): void
    {
        $voucherData = [
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'voucher_type' => 'income',
            'voucher_date' => now()->format('Y-m-d'),
            'lines' => [
                ['account_id' => $this->debitAccount->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $this->creditAccount->id, 'debit' => 0, 'credit' => 500],
            ],
        ];

        $voucher = $this->voucherService->create($voucherData);

        $this->assertStringStartsWith('INC', $voucher->voucher_number);
    }

    public function test_voucher_is_balanced(): void
    {
        $voucher = Voucher::factory()->create([
            'company_id' => $this->company->id,
            'total_debit' => 1000,
            'total_credit' => 1000,
        ]);

        $this->assertTrue($voucher->isBalanced());
    }

    public function test_voucher_is_not_balanced(): void
    {
        $voucher = Voucher::factory()->create([
            'company_id' => $this->company->id,
            'total_debit' => 1000,
            'total_credit' => 500,
        ]);

        $this->assertFalse($voucher->isBalanced());
    }

    public function test_can_post_balanced_voucher(): void
    {
        $voucher = Voucher::factory()->create([
            'company_id' => $this->company->id,
            'total_debit' => 1000,
            'total_credit' => 1000,
            'status' => 'draft',
        ]);

        $posted = $this->voucherService->post($voucher->id);

        $this->assertTrue($posted);
        $this->assertEquals('posted', $voucher->fresh()->status);
    }

    public function test_cannot_post_unbalanced_voucher(): void
    {
        $voucher = Voucher::factory()->create([
            'company_id' => $this->company->id,
            'total_debit' => 1000,
            'total_credit' => 500,
            'status' => 'draft',
        ]);

        $this->expectException(\Exception::class);
        $this->voucherService->post($voucher->id);
    }

    public function test_can_cancel_voucher(): void
    {
        $voucher = Voucher::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'posted',
        ]);

        $cancelled = $this->voucherService->cancel($voucher->id);

        $this->assertTrue($cancelled);
        $this->assertEquals('cancelled', $voucher->fresh()->status);
    }

    public function test_can_delete_draft_voucher(): void
    {
        $voucher = Voucher::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $deleted = $this->voucherService->delete($voucher->id);

        $this->assertTrue($deleted);
        $this->assertNull(Voucher::find($voucher->id));
    }

    public function test_cannot_delete_posted_voucher(): void
    {
        $voucher = Voucher::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'posted',
        ]);

        $this->expectException(\Exception::class);
        $this->voucherService->delete($voucher->id);
    }

    public function test_voucher_type_label(): void
    {
        $voucher = Voucher::factory()->create([
            'company_id' => $this->company->id,
            'voucher_type' => 'income',
        ]);

        $this->assertEquals('Income', $voucher->type_label);
    }

    public function test_voucher_belongs_to_company(): void
    {
        $voucher = Voucher::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertNotNull($voucher->company);
        $this->assertEquals($this->company->id, $voucher->company->id);
    }

    public function test_voucher_belongs_to_party(): void
    {
        $party = Party::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $voucher = Voucher::factory()->create([
            'company_id' => $this->company->id,
            'party_id' => $party->id,
        ]);

        $this->assertNotNull($voucher->party);
        $this->assertEquals($party->id, $voucher->party->id);
    }

    public function test_can_get_voucher_statistics(): void
    {
        Voucher::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'voucher_type' => 'income',
            'total_debit' => 5000,
            'status' => 'posted',
        ]);

        Voucher::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'voucher_type' => 'expense',
            'total_debit' => 3000,
            'status' => 'posted',
        ]);

        $stats = $this->voucherService->getStatistics($this->company->id, $this->financialYear->id);

        $this->assertEquals(5000, $stats['income']);
        $this->assertEquals(3000, $stats['expense']);
        $this->assertEquals(2000, $stats['profit']);
    }
}
