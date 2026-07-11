<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Ledger;
use App\Models\Voucher;
use App\Models\VoucherLine;
use App\Services\LedgerService;
use App\Services\LedgerPartyHistoryService;
use App\Services\JournalEntryService;
use App\Services\VoucherService;
use App\Services\ReportService;
use App\Repositories\LedgerRepository;
use App\Repositories\AccountRepository;
use App\Repositories\VoucherRepository;
use App\Repositories\VoucherLineRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VoucherLedgerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected FinancialYear $financialYear;
    protected Account $assetAccount;
    protected Account $incomeAccount;

    public function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->financialYear = FinancialYear::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Create chart of accounts
        $this->assetAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'account_type' => 'asset',
            'opening_balance' => 10000,
        ]);

        $this->incomeAccount = Account::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'account_type' => 'income',
            'opening_balance' => 0,
        ]);
    }

    public function testVoucherPostingGeneratesLedgerEntries()
    {
        // Create and post a voucher
        $voucher = Voucher::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'status' => 'draft',
        ]);

        // Add voucher lines
        VoucherLine::factory()->create([
            'voucher_id' => $voucher->id,
            'account_id' => $this->assetAccount->id,
            'debit' => 5000,
            'credit' => 0,
        ]);

        VoucherLine::factory()->create([
            'voucher_id' => $voucher->id,
            'account_id' => $this->incomeAccount->id,
            'debit' => 0,
            'credit' => 5000,
        ]);

        // Update totals
        $voucher->update([
            'total_debit' => 5000,
            'total_credit' => 5000,
        ]);

        // Inject services
        $ledgerRepository = new LedgerRepository(new Ledger());
        $accountRepository = new AccountRepository(new Account());
        $voucherRepository = new VoucherRepository(new Voucher());
        $voucherLineRepository = new VoucherLineRepository(new VoucherLine());
        $historyService = $this->app->make(LedgerPartyHistoryService::class);
        $ledgerService = new LedgerService($ledgerRepository, $accountRepository, $historyService);
        $journalEntryService = $this->app->make(JournalEntryService::class);

        $voucherService = new VoucherService(
            $voucherRepository,
            $voucherLineRepository,
            $ledgerService,
            $journalEntryService
        );

        // Post the voucher
        $voucherService->post($voucher->id);

        // Verify ledger entries were created
        $ledgerEntries = Ledger::where('voucher_id', $voucher->id)->get();
        $this->assertCount(2, $ledgerEntries);

        // Verify first entry (asset debit) - running balance starts from opening balance
        $assetEntry = $ledgerEntries->firstWhere('account_id', $this->assetAccount->id);
        $this->assertNotNull($assetEntry);
        $this->assertEquals(5000, $assetEntry->debit);
        $this->assertEquals(0, $assetEntry->credit);
        // Running balance is 10000 (opening) + 5000 (debit) = 15000
        $this->assertEquals(15000, $assetEntry->running_balance);

        // Verify second entry (income credit) - starts with opening balance of 0
        $incomeEntry = $ledgerEntries->firstWhere('account_id', $this->incomeAccount->id);
        $this->assertNotNull($incomeEntry);
        $this->assertEquals(0, $incomeEntry->debit);
        $this->assertEquals(5000, $incomeEntry->credit);
        // For income (credit normal), running balance = 0 + 5000 (credit) = 5000
        $this->assertEquals(5000, $incomeEntry->running_balance);
    }

    public function testReportServiceUsesLedgerEntries()
    {
        // Create and post a voucher first
        $transactionDate = '2026-06-19';
        
        $voucher = Voucher::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'status' => 'draft',
            'voucher_date' => $transactionDate,
            'total_debit' => 5000,
            'total_credit' => 5000,
        ]);

        VoucherLine::factory()->create([
            'voucher_id' => $voucher->id,
            'account_id' => $this->assetAccount->id,
            'debit' => 5000,
            'credit' => 0,
        ]);

        VoucherLine::factory()->create([
            'voucher_id' => $voucher->id,
            'account_id' => $this->incomeAccount->id,
            'debit' => 0,
            'credit' => 5000,
        ]);

        // Post the voucher
        $ledgerRepository = new LedgerRepository(new Ledger());
        $accountRepository = new AccountRepository(new Account());
        $voucherRepository = new VoucherRepository(new Voucher());
        $voucherLineRepository = new VoucherLineRepository(new VoucherLine());
        $historyService = $this->app->make(LedgerPartyHistoryService::class);
        $ledgerService = new LedgerService($ledgerRepository, $accountRepository, $historyService);
        $journalEntryService = $this->app->make(JournalEntryService::class);

        $voucherService = new VoucherService(
            $voucherRepository,
            $voucherLineRepository,
            $ledgerService,
            $journalEntryService
        );

        // Post the voucher
        $posted = $voucherService->post($voucher->id);
        $this->assertTrue($posted, 'Voucher should be posted successfully');

        // Verify voucher status changed
        $voucherRefreshed = Voucher::find($voucher->id);
        $this->assertEquals('posted', $voucherRefreshed->status);

        // Verify ledger entries were created for this voucher
        $ledgerCount = Ledger::where('voucher_id', $voucher->id)->count();
        $this->assertGreaterThan(0, $ledgerCount, 'Ledger entries should be created when voucher is posted');

        // Get day book report using ledger / voucher lines
        $reportService = new ReportService($ledgerService, $ledgerRepository);
        $dayBook = $reportService->getDayBook(
            $this->company->id,
            $transactionDate
        );

        // Verify day book contains vouchers / rows for the date
        $this->assertIsArray($dayBook);
        $this->assertArrayHasKey('vouchers', $dayBook);
        $this->assertArrayHasKey('rows', $dayBook);
        $this->assertGreaterThan(0, $dayBook['vouchers']->count());
        $this->assertGreaterThan(0, count($dayBook['rows']));
        
        // Verify totals match ledger entries
        $this->assertEquals(5000, $dayBook['total_debit']);
        $this->assertEquals(5000, $dayBook['total_credit']);
    }
}
