<?php

namespace App\Services;

use App\Models\Account;
use App\Models\BankAccount;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Party;
use App\Models\SyncQueue;
use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncService
{
    /** @var array<string, class-string<Model>> */
    protected array $syncableModels = [
        'accounts' => Account::class,
        'parties' => Party::class,
        'items' => Item::class,
        'item_categories' => ItemCategory::class,
        'tax_rates' => TaxRate::class,
        'bank_accounts' => BankAccount::class,
    ];

    public function queueUpload(array $entries, int $userId, int $companyId, ?string $deviceId = null): array
    {
        $queued = [];

        foreach ($entries as $entry) {
            $item = SyncQueue::create([
                'uuid' => Str::uuid(),
                'table_name' => $entry['table_name'],
                'record_uuid' => $entry['record_uuid'],
                'operation' => $entry['operation'],
                'payload' => $entry['payload'] ?? null,
                'metadata' => $entry['metadata'] ?? null,
                'status' => 'pending',
                'device_id' => $deviceId,
                'user_id' => $userId,
                'company_id' => $companyId,
                'local_version' => $entry['local_version'] ?? null,
            ]);

            $queued[] = $item;
        }

        return $queued;
    }

    public function processPending(int $companyId, ?int $userId = null, ?string $deviceId = null): array
    {
        $query = SyncQueue::where('company_id', $companyId)
            ->whereIn('status', ['pending', 'failed'])
            ->whereRaw('retry_count < max_retries')
            ->ordered();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        $results = [];

        foreach ($query->get() as $item) {
            $results[] = $this->processQueueItem($item);
        }

        return $results;
    }

    public function processQueueItem(SyncQueue $item): array
    {
        try {
            $item->markAsProcessing();

            if (!isset($this->syncableModels[$item->table_name])) {
                throw new \RuntimeException("Table {$item->table_name} is not syncable yet.");
            }

            $modelClass = $this->syncableModels[$item->table_name];
            $existing = $modelClass::where('company_id', $item->company_id)
                ->where('uuid', $item->record_uuid)
                ->first();

            if ($item->operation === 'delete') {
                if ($existing) {
                    $existing->delete();
                }
                $item->markAsCompleted($existing?->version);

                return $this->result($item, 'completed');
            }

            $payload = $item->payload ?? [];

            if ($existing && $item->local_version !== null && (int) $existing->version > (int) $item->local_version) {
                $item->update([
                    'server_version' => $existing->version,
                    'conflict_resolution' => 'server_wins',
                    'status' => 'completed',
                    'processed_at' => now(),
                ]);

                return $this->result($item, 'conflict', 'Server version is newer; server_wins applied.');
            }

            $data = $this->sanitizePayload($payload, $item->company_id);

            if ($existing) {
                $existing->update($data);
                $record = $existing->fresh();
            } else {
                $data['uuid'] = $item->record_uuid;
                $data['company_id'] = $item->company_id;
                $record = $modelClass::create($data);
            }

            if ($record && $this->hasColumn($record, 'synced_at')) {
                $record->update(['synced_at' => now()]);
            }

            $item->markAsCompleted($record->version ?? null);

            return $this->result($item, 'completed');
        } catch (\Throwable $e) {
            $item->markAsFailed($e->getMessage());

            return $this->result($item, 'failed', $e->getMessage());
        }
    }

    public function download(int $companyId, ?string $since = null, ?array $tables = null, int $page = 1, int $perPage = 100): array
    {
        $tables = $tables ?: array_keys($this->syncableModels);
        $sinceTime = $since ? \Carbon\Carbon::parse($since) : null;
        $data = [];

        foreach ($tables as $table) {
            if (!isset($this->syncableModels[$table])) {
                continue;
            }

            $modelClass = $this->syncableModels[$table];
            $query = $modelClass::where('company_id', $companyId);

            if ($sinceTime) {
                $query->where('updated_at', '>', $sinceTime);
            }

            $data[$table] = $query->orderBy('updated_at')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get()
                ->toArray();
        }

        return [
            'since' => $since,
            'page' => $page,
            'per_page' => $perPage,
            'data' => $data,
        ];
    }

    public function bootstrap(int $companyId): array
    {
        $data = [];

        foreach ($this->syncableModels as $table => $modelClass) {
            $data[$table] = $modelClass::where('company_id', $companyId)
                ->orderBy('id')
                ->get()
                ->toArray();
        }

        return [
            'company_id' => $companyId,
            'synced_at' => now()->toIso8601String(),
            'data' => $data,
        ];
    }

    public function getStatus(int $companyId, ?string $deviceId = null): array
    {
        $query = SyncQueue::where('company_id', $companyId);

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        return [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'processing' => (clone $query)->where('status', 'processing')->count(),
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'last_sync_at' => SyncQueue::where('company_id', $companyId)
                ->where('status', 'completed')
                ->max('processed_at'),
        ];
    }

    public function getSyncableTables(): array
    {
        return array_keys($this->syncableModels);
    }

    protected function sanitizePayload(array $payload, int $companyId): array
    {
        unset($payload['id'], $payload['uuid'], $payload['company_id'], $payload['created_at'], $payload['updated_at']);

        return $payload;
    }

    protected function hasColumn(Model $model, string $column): bool
    {
        return in_array($column, $model->getFillable(), true)
            || array_key_exists($column, $model->getCasts());
    }

    protected function result(SyncQueue $item, string $status, ?string $message = null): array
    {
        return [
            'queue_uuid' => $item->uuid,
            'record_uuid' => $item->record_uuid,
            'table_name' => $item->table_name,
            'status' => $status,
            'server_version' => $item->server_version,
            'message' => $message,
        ];
    }
}
