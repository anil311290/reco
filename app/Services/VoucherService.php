<?php

namespace App\Services;

use App\Interfaces\VoucherRepositoryInterface;
use App\Interfaces\VoucherLineRepositoryInterface;
use App\Models\Voucher;
use App\Models\VoucherLine;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoucherService
{
    protected VoucherRepositoryInterface $voucherRepository;
    protected VoucherLineRepositoryInterface $voucherLineRepository;
    protected LedgerService $ledgerService;
    protected JournalEntryService $journalEntryService;

    public function __construct(
        VoucherRepositoryInterface $voucherRepository,
        VoucherLineRepositoryInterface $voucherLineRepository,
        LedgerService $ledgerService,
        JournalEntryService $journalEntryService
    ) {
        $this->voucherRepository = $voucherRepository;
        $this->voucherLineRepository = $voucherLineRepository;
        $this->ledgerService = $ledgerService;
        $this->journalEntryService = $journalEntryService;
    }
    /**
     * Get all vouchers with filters
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Voucher::with(['party', 'lines.account']);

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['voucher_type'])) {
            $query->where('voucher_type', $filters['voucher_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('voucher_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('voucher_date', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('voucher_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('narration', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('voucher_date', 'desc')->orderBy('id', 'desc')->get();
    }

    /**
     * Get paginated vouchers
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Voucher::with(['party']);

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['voucher_type'])) {
            $query->where('voucher_type', $filters['voucher_type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('voucher_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('narration', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('voucher_date', 'desc')->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Get voucher by ID
     */
    public function getById(int $id): ?Voucher
    {
        return $this->voucherRepository->find($id, ['*'], ['party', 'lines.account', 'company', 'financialYear']);
    }

    /**
     * Create voucher with lines
     */
    public function create(array $data): Voucher
    {
        try {
            DB::beginTransaction();

            // Generate voucher number if not provided
            if (empty($data['voucher_number'])) {
                $data['voucher_number'] = Voucher::generateNumber(
                    $data['voucher_type'],
                    $data['company_id'],
                    $data['financial_year_id']
                );
            }

            // Calculate totals
            $totalDebit = 0;
            $totalCredit = 0;

            if (isset($data['lines'])) {
                foreach ($data['lines'] as $line) {
                    $totalDebit += $line['debit'] ?? 0;
                    $totalCredit += $line['credit'] ?? 0;
                }
            }

            // Validate voucher balance
            if ($totalDebit != $totalCredit) {
                throw new \Exception('Voucher must be balanced. Total debit (' . round($totalDebit, 2) . ') must equal total credit (' . round($totalCredit, 2) . ')');
            }

            $data['total_debit'] = $totalDebit;
            $data['total_credit'] = $totalCredit;

            $lines = $data['lines'] ?? [];
            unset(
                $data['lines'],
                $data['payment_rows'],
                $data['adjustment_rows'],
                $data['payment_mode'],
                $data['cash_bank_account_id']
            );

            // Payment / Receipt / Adjustment: CA style — post immediately so
            // ledger + journal_entries are available for reports.
            if (empty($data['status']) && in_array($data['voucher_type'] ?? '', ['payment', 'receipt', 'journal', 'adjustment'], true)) {
                $data['status'] = 'posted';
            }

            // Create voucher
            $voucher = $this->voucherRepository->create($data);

            // Create voucher lines
            foreach ($lines as $lineData) {
                $lineData['voucher_id'] = $voucher->id;
                $lineData['created_by'] = $data['created_by'] ?? auth('sanctum')->user()?->id;
                $this->voucherLineRepository->create($lineData);
            }

            $voucher = $voucher->load(['party', 'lines.account']);

            if ($voucher->status === 'posted') {
                $this->ledgerService->generateForVoucher($voucher);
                $this->journalEntryService->syncFromVoucher($voucher);
            }

            DB::commit();

            return $voucher->fresh(['party', 'lines.account']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update voucher
     */
    public function update(int $id, array $data): bool
    {
        $voucher = $this->voucherRepository->find($id);

        if (!$voucher) {
            return false;
        }

        // Only draft vouchers can be updated
        if ($voucher->status !== 'draft') {
            throw new \Exception('Only draft vouchers can be updated.');
        }

        try {
            DB::beginTransaction();

            // Calculate totals if lines are updated
            if (isset($data['lines'])) {
                $totalDebit = 0;
                $totalCredit = 0;

                foreach ($data['lines'] as $line) {
                    $totalDebit += $line['debit'] ?? 0;
                    $totalCredit += $line['credit'] ?? 0;
                }

                $data['total_debit'] = $totalDebit;
                $data['total_credit'] = $totalCredit;

                // Delete existing lines and recreate
                $this->voucherLineRepository->deleteByVoucher($voucher->id);

                foreach ($data['lines'] as $lineData) {
                    $lineData['voucher_id'] = $voucher->id;
                    $lineData['created_by'] = $data['created_by'] ?? auth('sanctum')->user()?->id;
                    $this->voucherLineRepository->create($lineData);
                }

                unset($data['lines']);
            }

            unset(
                $data['payment_rows'],
                $data['adjustment_rows'],
                $data['payment_mode'],
                $data['cash_bank_account_id']
            );

            $this->voucherRepository->update($voucher->id, $data);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete voucher
     */
    public function delete(int $id): bool
    {
        $voucher = $this->voucherRepository->find($id);

        if (!$voucher) {
            return false;
        }

        // Only draft vouchers can be deleted
        if ($voucher->status !== 'draft') {
            throw new \Exception('Only draft vouchers can be deleted.');
        }

        return $this->voucherRepository->delete($id);
    }

    /**
     * Post voucher
     */
    public function post(int $id): bool
    {
        $voucher = $this->voucherRepository->find($id);

        if (!$voucher) {
            return false;
        }

        $result = $voucher->post();

        // Generate ledger + journal entries when voucher is posted
        if ($result) {
            $voucher->refresh();
            $voucher->load(['party', 'lines.account']);
            $this->ledgerService->generateForVoucher($voucher);
            $this->journalEntryService->syncFromVoucher($voucher);
        }

        return $result;
    }

    /**
     * Cancel voucher
     */
    public function cancel(int $id): bool
    {
        $voucher = $this->voucherRepository->find($id);

        if (!$voucher) {
            return false;
        }

        $result = $voucher->cancel();

        if ($result) {
            $voucher->refresh();
            $this->ledgerService->deleteEntriesByReference('voucher', $voucher->id);
            $this->journalEntryService->cancelForVoucher($voucher);
        }

        return $result;
    }

    /**
     * Get voucher statistics
     */
    public function getStatistics(int $companyId, ?int $financialYearId = null): array
    {
        $query = Voucher::where('company_id', $companyId)
            ->where('status', 'posted');

        if ($financialYearId) {
            $query->where('financial_year_id', $financialYearId);
        }

        $income = (clone $query)->where('voucher_type', 'income')->sum('total_debit');
        $expense = (clone $query)->where('voucher_type', 'expense')->sum('total_debit');
        $receipt = (clone $query)->where('voucher_type', 'receipt')->sum('total_debit');
        $payment = (clone $query)->where('voucher_type', 'payment')->sum('total_debit');

        return [
            'income' => $income,
            'expense' => $expense,
            'receipt' => $receipt,
            'payment' => $payment,
            'profit' => $income - $expense,
            'cash_balance' => $receipt - $payment,
        ];
    }

    /**
     * Create voucher from sales invoice and generate ledger entries.
     */
    public function createFromSalesInvoice(array $invoiceData, array $lines): Voucher
    {
        return DB::transaction(function () use ($invoiceData, $lines) {
            // Create voucher via repository
            $voucher = $this->voucherRepository->create([
                'uuid' => Str::uuid(),
                'company_id' => $invoiceData['company_id'],
                'financial_year_id' => $invoiceData['financial_year_id'],
                'party_id' => $invoiceData['party_id'],
                'voucher_number' => Voucher::generateNumber(
                    'income',
                    $invoiceData['company_id'],
                    $invoiceData['financial_year_id']
                ),
                'voucher_type' => 'income',
                'voucher_date' => $invoiceData['voucher_date'],
                'narration' => $invoiceData['narration'],
                'total_debit' => $invoiceData['total'],
                'total_credit' => $invoiceData['total'],
                'status' => 'posted',
                'sales_invoice_id' => $invoiceData['sales_invoice_id'],
            ]);

            // Create voucher lines via repository
            foreach ($lines as $line) {
                $this->voucherLineRepository->create(array_merge($line, [
                    'uuid' => Str::uuid(),
                    'voucher_id' => $voucher->id,
                ]));
            }

            // Generate ledger entries
            $this->ledgerService->generateForVoucher($voucher);
            $this->journalEntryService->syncFromVoucher($voucher, 'sales_invoice', 'sales');

            return $voucher;
        });
    }

    /**
     * Create voucher from purchase invoice and generate ledger entries.
     */
    public function createFromPurchaseInvoice(array $invoiceData, array $lines): Voucher
    {
        return DB::transaction(function () use ($invoiceData, $lines) {
            // Create voucher via repository
            $voucher = $this->voucherRepository->create([
                'uuid' => Str::uuid(),
                'company_id' => $invoiceData['company_id'],
                'financial_year_id' => $invoiceData['financial_year_id'],
                'party_id' => $invoiceData['party_id'],
                'voucher_number' => Voucher::generateNumber(
                    'expense',
                    $invoiceData['company_id'],
                    $invoiceData['financial_year_id']
                ),
                'voucher_type' => 'expense',
                'voucher_date' => $invoiceData['voucher_date'],
                'narration' => $invoiceData['narration'],
                'total_debit' => $invoiceData['total'],
                'total_credit' => $invoiceData['total'],
                'status' => 'posted',
                'purchase_invoice_id' => $invoiceData['purchase_invoice_id'],
            ]);

            // Create voucher lines via repository
            foreach ($lines as $line) {
                $this->voucherLineRepository->create(array_merge($line, [
                    'uuid' => Str::uuid(),
                    'voucher_id' => $voucher->id,
                ]));
            }

            // Generate ledger entries
            $this->ledgerService->generateForVoucher($voucher);
            $this->journalEntryService->syncFromVoucher($voucher, 'purchase_invoice', 'purchase');

            return $voucher;
        });
    }
}
