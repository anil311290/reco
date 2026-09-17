<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\User;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoucherShowApiLinesTest extends TestCase
{
    use RefreshDatabase;

    public function test_voucher_show_api_returns_line_items(): void
    {
        $company = Company::factory()->create();
        $year = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $cash = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'account_code' => '1001',
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'is_cash_bank_od' => true,
            'opening_balance' => 0,
        ]);
        $expense = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'account_code' => '5001',
            'account_name' => 'Office Rent',
            'account_type' => 'expense',
            'opening_balance' => 0,
        ]);

        $voucher = app(VoucherService::class)->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'voucher_type' => 'payment',
            'voucher_date' => '2026-07-30',
            'narration' => 'Rent payment',
            'status' => 'posted',
            'created_by' => $user->id,
            'lines' => [
                [
                    'account_id' => $expense->id,
                    'debit' => 1500,
                    'credit' => 0,
                    'description' => 'Office Rent',
                ],
                [
                    'account_id' => $cash->id,
                    'debit' => 0,
                    'credit' => 1500,
                    'description' => 'Cash',
                ],
            ],
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/vouchers/' . $voucher->id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $voucher->id)
            ->assertJsonCount(2, 'data.lines')
            ->assertJsonPath('data.lines.0.account_id', $expense->id)
            ->assertJsonPath('data.lines.0.account.account_name', 'Office Rent')
            ->assertJsonPath('data.lines.0.debit', '1500.00')
            ->assertJsonPath('data.lines.1.account_id', $cash->id)
            ->assertJsonPath('data.lines.1.credit', '1500.00');
    }

    public function test_standalone_payment_duplicate_prefills_create_form_and_is_company_scoped(): void
    {
        $this->seed();

        $user = User::where('email', 'superadmin@reco.app')->firstOrFail();
        $company = $user->company;
        $year = FinancialYear::where('company_id', $company->id)->firstOrFail();
        $cash = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'is_cash_bank_od' => true,
        ]);
        $expense = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'account_name' => 'Duplicated Expense',
        ]);
        $voucher = app(VoucherService::class)->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'voucher_type' => 'payment',
            'voucher_date' => '2026-07-30',
            'narration' => 'Copied payment narration',
            'reference_number' => 'PAY-REF-1',
            'status' => 'draft',
            'created_by' => $user->id,
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 1500, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 1500],
            ],
        ]);

        $response = $this->actingAs($user)->get("/admin/vouchers/create/payment?duplicate={$voucher->id}");

        $response->assertOk();
        $response->assertSee('Copied payment narration', false);
        $response->assertSee('value="PAY-REF-1"', false);
        $response->assertSee('const duplicateVoucher =', false);
        $response->assertSee('Duplicated Expense', false);

        $otherCompany = Company::factory()->create();
        $otherVoucher = $voucher->replicate();
        $otherVoucher->uuid = (string) Str::uuid();
        $otherVoucher->company_id = $otherCompany->id;
        $otherVoucher->voucher_number = 'PAY-OTHER-1';
        $otherVoucher->save();

        $this->actingAs($user)
            ->get("/admin/vouchers/create/payment?duplicate={$otherVoucher->id}")
            ->assertNotFound();
    }
}
