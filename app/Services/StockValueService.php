<?php

namespace App\Services;

use App\Models\FinancialYear;
use App\Models\StockValueEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class StockValueService
{
    public function list(int $companyId, int $financialYearId, ?string $fromDate = null, ?string $toDate = null): Collection
    {
        $financialYear = FinancialYear::query()
            ->where('company_id', $companyId)
            ->findOrFail($financialYearId);

        $periodStart = $fromDate ?: $financialYear->start_date->format('Y-m-d');

        $entries = StockValueEntry::query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->when($fromDate, fn ($query) => $query->whereDate('valuation_date', '>=', $fromDate))
            ->when($toDate, fn ($query) => $query->whereDate('valuation_date', '<=', $toDate))
            ->orderBy('valuation_date')
            ->orderBy('id')
            ->get();

        $openingSource = StockValueEntry::query()
            ->where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->whereDate('valuation_date', '<=', $periodStart)
            ->orderByDesc('valuation_date')
            ->orderByDesc('id')
            ->first();

        $displayEntries = new Collection();
        $hasEntryOnPeriodStart = $entries->contains(
            fn (StockValueEntry $entry) => $entry->valuation_date->format('Y-m-d') === $periodStart
        );

        if (!$hasEntryOnPeriodStart) {
            $openingRow = new StockValueEntry();
            $openingRow->exists = false;
            $openingRow->forceFill([
                'valuation_date' => $periodStart,
                'stock_value' => round((float) ($openingSource?->stock_value ?? 0), 2),
                'remarks' => $fromDate ? 'Opening Balance b/f' : 'Opening Balance',
            ]);
            $openingRow->setAttribute('is_opening_row', true);
            $openingRow->setAttribute('is_synthetic', true);
            $displayEntries->push($openingRow);
        }

        foreach ($entries as $entry) {
            if ($entry->valuation_date->format('Y-m-d') === $periodStart) {
                $entry->setAttribute('is_opening_row', true);
                $entry->setAttribute('is_synthetic', false);
            }

            $displayEntries->push($entry);
        }

        return $displayEntries;
    }

    public function save(int $companyId, int $financialYearId, array $data, ?int $entryId = null): StockValueEntry
    {
        $userId = Auth::id();

        $financialYear = FinancialYear::query()
            ->where('company_id', $companyId)
            ->findOrFail($financialYearId);

        if ($data['valuation_date'] < $financialYear->start_date->format('Y-m-d')
            || $data['valuation_date'] > $financialYear->end_date->format('Y-m-d')) {
            throw new \InvalidArgumentException('Stock value date must be inside the selected financial year.');
        }

        return DB::transaction(function () use ($companyId, $financialYearId, $data, $entryId, $userId) {
            $entry = $entryId
                ? StockValueEntry::query()->where('company_id', $companyId)->findOrFail($entryId)
                : new StockValueEntry([
                    'uuid' => (string) Str::uuid(),
                    'company_id' => $companyId,
                    'financial_year_id' => $financialYearId,
                    'created_by' => $userId,
                    'created_by_ip' => request()->ip(),
                ]);

            $entry->fill([
                'valuation_date' => $data['valuation_date'],
                'stock_value' => round((float) $data['stock_value'], 2),
                'remarks' => $data['remarks'] ?? null,
                'updated_by' => $userId,
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
