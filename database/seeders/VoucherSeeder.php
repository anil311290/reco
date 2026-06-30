<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\Ledger;
use App\Models\Party;
use App\Models\Voucher;
use App\Models\VoucherLine;
use Illuminate\Database\Seeder;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();
        if (!$company) return;

        $findAccount = function(array $codes, array $names = []) {
            foreach ($codes as $code) {
                $account = Account::where('account_code', $code)->first();
                if ($account) {
                    return $account;
                }
            }
            foreach ($names as $name) {
                $account = Account::where('account_name', $name)->first();
                if ($account) {
                    return $account;
                }
            }
            return null;
        };

        $findPartyId = function(array $codes) {
            foreach ($codes as $code) {
                $party = Party::where('party_code', $code)->first();
                if ($party) {
                    return $party->id;
                }
            }
            return null;
        };

        $cashAccount = $findAccount(['1000']);
        $bankAccount = $findAccount(['1001']);
        $salesAccount = $findAccount(['1502', '3000']);
        $serviceAccount = $findAccount(['1503', '3001']);
        $purchaseAccount = $findAccount(['1752', '4000']);
        $salaryAccount = $findAccount(['1753', '4001']);
        $rentAccount = $findAccount(['1754', '4002']);
        $electricityAccount = $findAccount(['1755', '4003']);
        $officeSupplies = $findAccount(['1756', '4004']);
        $travelAccount = $findAccount(['1757', '4005']);
        $marketingAccount = $findAccount(['1758', '4006']);
        $arAccount = $findAccount(['1250', '1010']);
        $apAccount = $findAccount(['1500', '2000']);

        if (!$cashAccount || !$bankAccount || !$arAccount || !$apAccount || !$salesAccount || !$serviceAccount || !$purchaseAccount || !$salaryAccount || !$rentAccount || !$electricityAccount || !$officeSupplies || !$travelAccount || !$marketingAccount) {
            return;
        }

        $vouchers = [
            // Income vouchers
            [
                'voucher_number' => 'INC-001', 'voucher_type' => 'income', 'voucher_date' => '2026-04-05',
                'narration' => 'Sales to Reliance Industries - Invoice #INV-001',
                'party_id' => $findPartyId(['AR001', 'DEB-001']),
                'lines' => [['account_id' => $arAccount->id, 'debit' => 125000], ['account_id' => $salesAccount->id, 'credit' => 125000]],
            ],
            [
                'voucher_number' => 'INC-002', 'voucher_type' => 'income', 'voucher_date' => '2026-04-10',
                'narration' => 'Service revenue from TCS - Project Alpha',
                'party_id' => $findPartyId(['AR002', 'DEB-002']),
                'lines' => [['account_id' => $arAccount->id, 'debit' => 89000], ['account_id' => $serviceAccount->id, 'credit' => 89000]],
            ],
            [
                'voucher_number' => 'INC-003', 'voucher_type' => 'income', 'voucher_date' => '2026-04-15',
                'narration' => 'Sales to Infosys - Hardware supply',
                'party_id' => $findPartyId(['AR003', 'DEB-003']),
                'lines' => [['account_id' => $arAccount->id, 'debit' => 67000], ['account_id' => $salesAccount->id, 'credit' => 67000]],
            ],
            [
                'voucher_number' => 'INC-004', 'voucher_type' => 'income', 'voucher_date' => '2026-05-01',
                'narration' => 'Consulting fee from Wipro',
                'party_id' => $findPartyId(['AR004', 'DEB-004']),
                'lines' => [['account_id' => $bankAccount->id, 'debit' => 45000], ['account_id' => $serviceAccount->id, 'credit' => 45000]],
            ],
            [
                'voucher_number' => 'INC-005', 'voucher_type' => 'income', 'voucher_date' => '2026-05-10',
                'narration' => 'Sales to L&T - Equipment supply',
                'party_id' => $findPartyId(['AR007', 'DEB-007']),
                'lines' => [['account_id' => $arAccount->id, 'debit' => 156000], ['account_id' => $salesAccount->id, 'credit' => 156000]],
            ],
            // Expense vouchers
            [
                'voucher_number' => 'EXP-001', 'voucher_type' => 'expense', 'voucher_date' => '2026-04-01',
                'narration' => 'Monthly salary for April 2026',
                'lines' => [['account_id' => $salaryAccount->id, 'debit' => 180000], ['account_id' => $bankAccount->id, 'credit' => 180000]],
            ],
            [
                'voucher_number' => 'EXP-002', 'voucher_type' => 'expense', 'voucher_date' => '2026-04-05',
                'narration' => 'Office rent for April 2026',
                'lines' => [['account_id' => $rentAccount->id, 'debit' => 35000], ['account_id' => $bankAccount->id, 'credit' => 35000]],
            ],
            [
                'voucher_number' => 'EXP-003', 'voucher_type' => 'expense', 'voucher_date' => '2026-04-10',
                'narration' => 'Electricity bill payment - March 2026',
                'lines' => [['account_id' => $electricityAccount->id, 'debit' => 8500], ['account_id' => $bankAccount->id, 'credit' => 8500]],
            ],
            [
                'voucher_number' => 'EXP-004', 'voucher_type' => 'expense', 'voucher_date' => '2026-04-15',
                'narration' => 'Purchase from Samsung - Inventory restocking',
                'party_id' => $findPartyId(['AP001', 'CRE-001']),
                'lines' => [['account_id' => $purchaseAccount->id, 'debit' => 95000], ['account_id' => $apAccount->id, 'credit' => 95000]],
            ],
            [
                'voucher_number' => 'EXP-005', 'voucher_type' => 'expense', 'voucher_date' => '2026-04-20',
                'narration' => 'Office supplies purchase',
                'lines' => [['account_id' => $officeSupplies->id, 'debit' => 12000], ['account_id' => $cashAccount->id, 'credit' => 12000]],
            ],
            [
                'voucher_number' => 'EXP-006', 'voucher_type' => 'expense', 'voucher_date' => '2026-05-01',
                'narration' => 'Monthly salary for May 2026',
                'lines' => [['account_id' => $salaryAccount->id, 'debit' => 180000], ['account_id' => $bankAccount->id, 'credit' => 180000]],
            ],
            [
                'voucher_number' => 'EXP-007', 'voucher_type' => 'expense', 'voucher_date' => '2026-05-05',
                'narration' => 'Office rent for May 2026',
                'lines' => [['account_id' => $rentAccount->id, 'debit' => 35000], ['account_id' => $bankAccount->id, 'credit' => 35000]],
            ],
            // Receipt vouchers
            [
                'voucher_number' => 'REC-001', 'voucher_type' => 'receipt', 'voucher_date' => '2026-04-20',
                'narration' => 'Payment received from Reliance - against INV-001',
                'party_id' => $findPartyId(['AR001', 'DEB-001']),
                'lines' => [['account_id' => $bankAccount->id, 'debit' => 125000], ['account_id' => $arAccount->id, 'credit' => 125000]],
            ],
            [
                'voucher_number' => 'REC-002', 'voucher_type' => 'receipt', 'voucher_date' => '2026-05-05',
                'narration' => 'Partial payment from TCS - ₹50,000',
                'party_id' => $findPartyId(['AR002', 'DEB-002']),
                'lines' => [['account_id' => $bankAccount->id, 'debit' => 50000], ['account_id' => $arAccount->id, 'credit' => 50000]],
            ],
            [
                'voucher_number' => 'REC-003', 'voucher_type' => 'receipt', 'voucher_date' => '2026-05-12',
                'narration' => 'Payment received from Infosys',
                'party_id' => $findPartyId(['AR003', 'DEB-003']),
                'lines' => [['account_id' => $bankAccount->id, 'debit' => 67000], ['account_id' => $arAccount->id, 'credit' => 67000]],
            ],
            // Payment vouchers
            [
                'voucher_number' => 'PAY-001', 'voucher_type' => 'payment', 'voucher_date' => '2026-04-25',
                'narration' => 'Payment to Samsung - against purchase',
                'party_id' => $findPartyId(['AP001', 'CRE-001']),
                'lines' => [['account_id' => $apAccount->id, 'debit' => 95000], ['account_id' => $bankAccount->id, 'credit' => 95000]],
            ],
            [
                'voucher_number' => 'PAY-002', 'voucher_type' => 'payment', 'voucher_date' => '2026-05-01',
                'narration' => 'Marketing expense - Google Ads',
                'lines' => [['account_id' => $marketingAccount->id, 'debit' => 25000], ['account_id' => $bankAccount->id, 'credit' => 25000]],
            ],
            [
                'voucher_number' => 'PAY-003', 'voucher_type' => 'payment', 'voucher_date' => '2026-05-08',
                'narration' => 'Travel expense - Client visit Delhi',
                'lines' => [['account_id' => $travelAccount->id, 'debit' => 18000], ['account_id' => $cashAccount->id, 'credit' => 18000]],
            ],
            // Journal vouchers
            [
                'voucher_number' => 'JRN-001', 'voucher_type' => 'journal', 'voucher_date' => '2026-04-30',
                'narration' => 'Depreciation entry for April 2026',
                'lines' => [
                    ['account_id' => Account::where('account_code', '1761')->first()?->id, 'debit' => 15000],
                    ['account_id' => Account::where('account_code', '1005')->first()?->id, 'credit' => 15000],
                ],
            ],
        ];

        foreach ($vouchers as $data) {
            $totalDebit = collect($data['lines'])->sum('debit');
            $totalCredit = collect($data['lines'])->sum('credit');

            $voucher = Voucher::firstOrCreate(
                ['voucher_number' => $data['voucher_number']],
                [
                    'voucher_type' => $data['voucher_type'],
                    'voucher_date' => $data['voucher_date'],
                    'narration' => $data['narration'],
                    'party_id' => $data['party_id'] ?? null,
                    'company_id' => $company->id,
                    'financial_year_id' => \App\Models\FinancialYear::where('company_id', $company->id)->first()?->id,
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'status' => 'posted',
                    'created_by' => 1,
                    'updated_by' => 1,
                    'created_by_ip' => '127.0.0.1',
                    'updated_by_ip' => '127.0.0.1',
                ]
            );

            foreach ($data['lines'] as $line) {
                if (empty($line['account_id'])) continue;
                VoucherLine::create([
                    'voucher_id' => $voucher->id,
                    'account_id' => $line['account_id'],
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $data['narration'],
                ]);

                // Create ledger entry
                $account = Account::find($line['account_id']);
                if ($account) {
                    $isDebitNormal = in_array($account->account_type, ['asset', 'expense']);
                    $debit = $line['debit'] ?? 0;
                    $credit = $line['credit'] ?? 0;

                    $lastEntry = Ledger::where('company_id', $company->id)
                        ->where('account_id', $account->id)
                        ->orderBy('transaction_date', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();

                    $prevBalance = $lastEntry ? $lastEntry->running_balance : $account->opening_balance;
                    $prevType = $lastEntry ? $lastEntry->balance_type : 'debit';

                    if ($isDebitNormal) {
                        $newBalance = $prevBalance + $debit - $credit;
                    } else {
                        $newBalance = $prevBalance + $credit - $debit;
                    }

                    Ledger::create([
                        'company_id' => $company->id,
                        'financial_year_id' => $voucher->financial_year_id,
                        'account_id' => $account->id,
                        'voucher_id' => $voucher->id,
                        'transaction_date' => $data['voucher_date'],
                        'reference_type' => 'voucher',
                        'reference_id' => $voucher->id,
                        'description' => $data['narration'],
                        'debit' => $debit,
                        'credit' => $credit,
                        'running_balance' => abs($newBalance),
                        'balance_type' => $newBalance >= 0 ? 'debit' : 'credit',
                        'created_by' => 1,
                        'updated_by' => 1,
                        'created_by_ip' => '127.0.0.1',
                        'updated_by_ip' => '127.0.0.1',
                    ]);
                }
            }
        }
    }
}
