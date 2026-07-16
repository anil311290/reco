<?php

namespace App\Services;

use App\Models\FinancialYear;
use Carbon\Carbon;

class PeriodLockService
{
    /**
     * Ensure a transaction date can be written for the company.
     * Blocks closed financial years and dates outside the FY range.
     */
    public function assertWritable(int $companyId, string|\DateTimeInterface $date, ?int $financialYearId = null): FinancialYear
    {
        $transactionDate = Carbon::parse($date)->startOfDay();

        $financialYear = $this->resolveFinancialYear($companyId, $transactionDate, $financialYearId);

        if (!$financialYear) {
            throw new \RuntimeException(
                'No financial year covers ' . $transactionDate->format('d M Y') . '. Create or open an FY first.'
            );
        }

        if ($financialYear->is_closed) {
            throw new \RuntimeException(
                'Financial year "' . $financialYear->name . '" is closed. Backdated create/edit is not allowed.'
            );
        }

        $start = Carbon::parse($financialYear->start_date)->startOfDay();
        $end = Carbon::parse($financialYear->end_date)->startOfDay();

        if ($transactionDate->lt($start) || $transactionDate->gt($end)) {
            throw new \RuntimeException(
                'Date ' . $transactionDate->format('d M Y') . ' is outside financial year "'
                . $financialYear->name . '" (' . $start->format('d M Y') . ' – ' . $end->format('d M Y') . ').'
            );
        }

        return $financialYear;
    }

    protected function resolveFinancialYear(
        int $companyId,
        Carbon $transactionDate,
        ?int $financialYearId
    ): ?FinancialYear {
        if ($financialYearId) {
            return FinancialYear::query()
                ->where('company_id', $companyId)
                ->where('id', $financialYearId)
                ->first();
        }

        return FinancialYear::query()
            ->where('company_id', $companyId)
            ->whereDate('start_date', '<=', $transactionDate->toDateString())
            ->whereDate('end_date', '>=', $transactionDate->toDateString())
            ->orderByDesc('is_current')
            ->first();
    }
}
