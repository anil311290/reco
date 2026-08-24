<?php

namespace App\Services;

use App\Models\FinancialYear;
use App\Models\StockValueEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockValueService
{
    public function list(int $companyId, int $financialYearId, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        return StockValueEntry::query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->when($fromDate, fn ($query) => $query->whereDate('valuation_date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('valuation_date', '<=', $toDate))
            ->orderByDesc('valuation_date')
            ->orderByDesc('id')
            ->get();
    }

    public function save(int $companyId, int $financialYearId, array $data, ?int $entryId = null): StockValueEntry
    {
        $financialYear = FinancialYear::query()
            ->where('company_id', $companyId)
            ->findOrFail($financialYearId);

        if ($data['valuation_date'] < $financialYear->start_date->format('Y-m-d')
            || $data['valuation_date'] > $financialYear->end_date->format('Y-m-d')) {
            throw new \InvalidArgumentException('Stock value date must be inside the selected financial year.');
        }

        return DB::transaction(function () use ($companyId, $financialYearId, $data, $entryId) {
            $entry = $entryId
                ? StockValueEntry::query()->where('company_id', $companyId)->findOrFail($entryId)
                : new StockValueEntry([
                    'uuid' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'financial_year_id' => $financialYearId,
                    'created_by' => auth()->id(),
                    'created_by_ip' => request()->ip(),
                ]);

            $entry->fill([
                'valuation_date' => $data['valuation_date'],
                'stock_value' => round((float) $data['stock_value'], 2),
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => auth()->id(),
                'updated_by_ip' => request()->ip(),
            ]);
            $entry->save();

            return $entry->fresh();
        });
    }

    public function latestValue(int $companyId, int $financialYearId, ?string $date): float
    {
        return (float) (StockValueEntry::query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->when($date, fn ($query) => $query->whereDate('valuation_date', '<=', $date))
            ->latest('valuation_date')
            ->latest('id')
            ->value('stock_value') ?? 0);
    }
}
