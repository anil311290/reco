<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Services\LedgerService;
use App\Services\ReportService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialBalanceAndBankBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_trial_balance_exposes_opening_transaction_closing_and_stays_balanced(): void
    {
        [$company, $fy, $bank, $loan] = $this->seedBooks();

        /** @var LedgerService $ledgerService */
        $ledgerService = app(LedgerService::class);
        /** @var VoucherService $voucherService */
        $voucherService = app(VoucherService::class);

        $voucherService->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'voucher_type' => 'receipt',
            'voucher_date' => '2026-04-10',
            'narration' => 'Loan received in bank',
            'lines' => [
                ['account_id' => $bank->id, 'debit' => 60000, 'credit' => 0, 'description' => 'Bank'],
                ['account_id' => $loan->id, 'debit' => 0, 'credit' => 60000, 'description' => 'Loan'],
            ],
        ]);

        $trial = $ledgerService->getTrialBalance($company->id, $fy->id);

        $this->assertTrue($trial['is_balanced']);
        $this->assertArrayHasKey('total_opening_debit', $trial);
        $this->assertArrayHasKey('total_transaction_debit', $trial);

        $bankRow = collect($trial['accounts'])->first(
            fn (array $row) => (int) $row['account']->id === (int) $bank->id
        );
        $this->assertNotNull($bankRow);
        $this->assertEquals('BS', $bankRow['destination']);
        $this->assertEqualsWithDelta(60000.0, (float) $bankRow['transaction_debit'], 0.01);
        $this->assertEqualsWithDelta(60000.0, (float) $bankRow['debit'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $bankRow['opening_debit'] + (float) $bankRow['opening_credit'], 0.01);

        $loanRow = collect($trial['accounts'])->first(
            fn (array $row) => (int) $row['account']->id === (int) $loan->id
        );
        $this->assertNotNull($loanRow);
        $this->assertEquals('BS', $loanRow['destination']);
        $this->assertEqualsWithDelta(60000.0, (float) $loanRow['credit'], 0.01);
    }

    public function test_bank_book_opening_is_not_closing_and_particulars_show_contra_ledger(): void
    {
        [$company, $fy, $bank, $loan] = $this->seedBooks();

        /** @var VoucherService $voucherService */
        $voucherService = app(VoucherService::class);
        /** @var ReportService $reportService */
        $reportService = app(ReportService::class);
        /** @var LedgerService $ledgerService */
        $ledgerService = app(LedgerService::class);

        $voucherService->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'voucher_type' => 'receipt',
            'voucher_date' => '2026-04-10',
            'narration' => 'Loan received in bank',
            'lines' => [
                ['account_id' => $bank->id, 'debit' => 60000, 'credit' => 0, 'description' => 'Bank'],
                ['account_id' => $loan->id, 'debit' => 0, 'credit' => 60000, 'description' => 'Loan'],
            ],
        ]);

        $book = $reportService->getCashBankBook($company->id, 'bank', $bank->id, null, null, $fy->id);
        $this->assertNotNull($book['report']);
        $this->assertEqualsWithDelta(0.0, (float) $book['report']['opening_balance']['balance'], 0.01);
        $this->assertEqualsWithDelta(60000.0, (float) $book['report']['closing_balance']['balance'], 0.01);
        $this->assertEqualsWithDelta(60000.0, (float) $book['report']['total_debit'], 0.01);

        $entry = $book['report']['entries']->first();
        $this->assertNotNull($entry);
        $this->assertStringContainsString($loan->account_name, (string) $entry->particulars);

        $midPeriod = $ledgerService->getAccountLedger(
            $bank->id,
            $company->id,
            $fy->id,
            '2026-04-11',
            null
        );
        $this->assertEqualsWithDelta(60000.0, (float) $midPeriod['opening_balance']['balance'], 0.01);
        $this->assertEquals('debit', $midPeriod['opening_balance']['type']);
    }

    /**
     * @return array{0: Company, 1: FinancialYear, 2: Account, 3: Account}
     */
    protected function seedBooks(): array
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
        ]);

        $bank = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => 'BANK01',
            'account_name' => 'HDFC Bank',
            'account_type' => 'asset',
            'transaction_mode' => 'bank',
            'balance_type' => 'debit',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        $loan = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => 'LOAN01',
            'account_name' => "Manjunath's Loan",
            'account_type' => 'liability',
            'balance_type' => 'credit',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        return [$company, $fy, $bank, $loan];
    }
}
