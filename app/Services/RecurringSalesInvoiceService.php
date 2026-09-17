<?php

namespace App\Services;

use App\Models\FinancialYear;
use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class RecurringSalesInvoiceService
{
    public function __construct(
        protected SalesInvoiceService $salesInvoiceService
    ) {
    }

    public function nextRunDate(
        Carbon $fromDate,
        string $frequency,
        ?int $dayOfWeek = null,
        ?string $monthlyType = null,
        ?int $dayOfMonth = null
    ): Carbon {
        if ($frequency === 'weekly') {
            $targetDay = $dayOfWeek ?? 1;
            $nextDate = $fromDate->copy()->next($targetDay);

            return $nextDate->startOfDay();
        }

        $nextMonth = $fromDate->copy()->startOfMonth()->addMonth();

        return match ($monthlyType) {
            'first_day' => $nextMonth->startOfMonth(),
            'last_day' => $nextMonth->endOfMonth()->startOfDay(),
            'custom_day' => $nextMonth->day(min($dayOfMonth ?? 1, $nextMonth->daysInMonth))->startOfDay(),
            default => throw new \InvalidArgumentException('Invalid monthly recurrence type.'),
        };
    }

    public function initialNextRunDate(
        Carbon $fromDate,
        string $frequency,
        ?int $dayOfWeek = null,
        ?string $monthlyType = null,
        ?int $dayOfMonth = null
    ): Carbon {
        if ($frequency === 'weekly') {
            return $this->nextRunDate($fromDate, $frequency, $dayOfWeek);
        }

        $targetDate = match ($monthlyType) {
            'first_day' => $fromDate->copy()->startOfMonth(),
            'last_day' => $fromDate->copy()->endOfMonth()->startOfDay(),
            'custom_day' => $fromDate->copy()->day(min($dayOfMonth ?? 1, $fromDate->daysInMonth))->startOfDay(),
            default => throw new \InvalidArgumentException('Invalid monthly recurrence type.'),
        };

        return $targetDate->greaterThan($fromDate->copy()->startOfDay())
            ? $targetDate
            : $this->nextRunDate($fromDate, $frequency, null, $monthlyType, $dayOfMonth);
    }

    public function createDueInvoices(?Carbon $runDate = null): int
    {
        $runDate ??= now()->startOfDay();
        $dueInvoices = SalesInvoice::query()
            ->with('lines')
            ->where('is_recurring', true)
            ->whereNotNull('recurrence_next_run_at')
            ->whereDate('recurrence_next_run_at', '<=', $runDate)
            ->whereNull('deleted_at')
            ->where('status', '!=', 'cancelled')
            ->get();

        $createdCount = 0;
        foreach ($dueInvoices as $invoice) {
            if ($this->recreateInvoice($invoice, $runDate)) {
                $createdCount++;
            }
        }

        return $createdCount;
    }

    private function recreateInvoice(SalesInvoice $source, Carbon $invoiceDate): bool
    {
        $financialYear = FinancialYear::getCurrent($source->company_id);
        if (!$financialYear) {
            return false;
        }

        $lines = [];
        $serviceLines = [];
        foreach ($source->lines as $line) {
            $lineData = [
                'item_id' => $line->item_id,
                'account_id' => $line->account_id,
                'tax_rate_id' => $line->tax_rate_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'discount_percentage' => $line->discount_percentage,
            ];

            if ($line->line_type === 'service' && !$line->item_id) {
                $serviceLines[] = [
                    'account_id' => $line->account_id,
                    'tax_rate_id' => $line->tax_rate_id,
                    'description' => $line->description,
                    'amount' => $line->unit_price,
                ];
            } else {
                $lines[] = $lineData;
            }
        }

        $paymentTermDays = max(0, Carbon::parse($source->invoice_date)->diffInDays($source->due_date, false));
        $newInvoice = $this->salesInvoiceService->create([
            'company_id' => $source->company_id,
            'financial_year_id' => $financialYear->id,
            'party_id' => $source->party_id,
            'account_id' => $source->account_id,
            'invoice_date' => $invoiceDate->toDateString(),
            'due_date' => $invoiceDate->copy()->addDays($paymentTermDays)->toDateString(),
            'reference_number' => $source->reference_number,
            'notes' => $source->notes,
            'payment_terms' => $source->payment_terms,
            'delivery_terms' => $source->delivery_terms,
            'discount_percentage' => $source->discount_percentage,
            'currency' => $source->currency,
            'status' => 'draft',
            'is_recurring' => false,
            'created_by' => $source->created_by,
            'created_by_ip' => 'scheduler',
        ], $lines, $serviceLines);

        $this->salesInvoiceService->generateVoucher($newInvoice);

        $source->update([
            'recurrence_last_run_at' => $invoiceDate->toDateString(),
            'recurrence_next_run_at' => $this->nextRunDate(
                $invoiceDate,
                $source->recurrence_frequency,
                $source->recurrence_day_of_week,
                $source->recurrence_monthly_type,
                $source->recurrence_day_of_month
            )->toDateString(),
        ]);

        return true;
    }
}