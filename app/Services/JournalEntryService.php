<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\Party;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JournalEntryService
{
    /**
     * Replace journal rows for a voucher source with current voucher lines.
     */
    public function syncFromVoucher(Voucher $voucher, ?string $sourceType = null, ?string $module = null): void
    {
        $voucher->loadMissing('lines.account');

        $moduleName = $module ?: $this->mapVoucherTypeToModule($voucher->voucher_type);
        $resolvedSourceType = $sourceType ?: $this->mapVoucherTypeToSourceType($voucher->voucher_type);
        $sourceId = $this->resolveSourceId($voucher, $resolvedSourceType);
        $partyByAccountId = $this->resolvePartyIdsByAccount($voucher);

        DB::transaction(function () use ($voucher, $resolvedSourceType, $moduleName, $sourceId, $partyByAccountId) {
            // Clear previous rows for this voucher (covers module renames / re-posts)
            JournalEntry::where('company_id', $voucher->company_id)
                ->where('voucher_id', $voucher->id)
                ->delete();

            JournalEntry::where('company_id', $voucher->company_id)
                ->where('module', $moduleName)
                ->where('source_type', $resolvedSourceType)
                ->where('source_id', $sourceId)
                ->where(function ($query) use ($voucher) {
                    $query->whereNull('voucher_id')
                        ->orWhere('voucher_id', $voucher->id);
                })
                ->delete();

            foreach ($voucher->lines as $index => $line) {
                JournalEntry::create([
                    'uuid' => Str::uuid()->toString(),
                    'company_id' => $voucher->company_id,
                    'financial_year_id' => $voucher->financial_year_id,
                    'entry_date' => $voucher->voucher_date,
                    'module' => $moduleName,
                    'source_type' => $resolvedSourceType,
                    'source_id' => $sourceId,
                    'voucher_id' => $voucher->id,
                    'account_id' => $line->account_id,
                    'party_id' => $partyByAccountId[$line->account_id]
                        ?? $voucher->party_id,
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                    'amount_signed' => round((float) $line->debit - (float) $line->credit, 2),
                    'head_name_snapshot' => $line->account?->account_name,
                    'narration' => $line->description ?: $voucher->narration,
                    'line_no' => $index + 1,
                    'status' => $voucher->status === 'cancelled' ? 'cancelled' : 'posted',
                    'created_by' => $voucher->created_by,
                    'updated_by' => $voucher->updated_by,
                    'created_by_ip' => $voucher->created_by_ip,
                    'updated_by_ip' => $voucher->updated_by_ip,
                ]);
            }
        });
    }

    /**
     * Mark voucher-linked journal rows cancelled and remove them from active reports.
     */
    public function cancelForVoucher(Voucher $voucher): void
    {
        JournalEntry::where('company_id', $voucher->company_id)
            ->where('voucher_id', $voucher->id)
            ->update([
                'status' => 'cancelled',
                'updated_by' => $voucher->updated_by,
                'updated_by_ip' => $voucher->updated_by_ip,
            ]);

        JournalEntry::where('company_id', $voucher->company_id)
            ->where('voucher_id', $voucher->id)
            ->delete();
    }

    protected function resolvePartyIdsByAccount(Voucher $voucher): array
    {
        $accountIds = $voucher->lines->pluck('account_id')->filter()->unique()->values()->all();

        if (empty($accountIds)) {
            return [];
        }

        return Party::where('company_id', $voucher->company_id)
            ->whereIn('account_id', $accountIds)
            ->pluck('id', 'account_id')
            ->all();
    }

    protected function resolveSourceId(Voucher $voucher, string $sourceType): int
    {
        return match ($sourceType) {
            'sales_invoice' => (int) ($voucher->sales_invoice_id ?: $voucher->id),
            'purchase_invoice' => (int) ($voucher->purchase_invoice_id ?: $voucher->id),
            default => (int) $voucher->id,
        };
    }

    protected function mapVoucherTypeToModule(string $voucherType): string
    {
        return match ($voucherType) {
            'income' => 'sales',
            'expense' => 'purchase',
            'receipt' => 'receipt',
            'payment' => 'payment',
            'adjustment', 'journal' => 'adjustment',
            default => 'voucher',
        };
    }

    protected function mapVoucherTypeToSourceType(string $voucherType): string
    {
        return match ($voucherType) {
            'income' => 'sales_invoice',
            'expense' => 'purchase_invoice',
            'receipt' => 'receipt',
            'payment' => 'payment',
            'adjustment', 'journal' => 'adjustment',
            default => 'voucher',
        };
    }
}
