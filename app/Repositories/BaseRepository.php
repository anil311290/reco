<?php

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseRepository implements RepositoryInterface
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage, $columns);
    }

    public function find(int $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->select($columns)->find($id);
    }

    public function findBy(array $criteria, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->select($columns)->where($criteria)->first();
    }

    public function getWhere(array $criteria, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->select($columns)->where($criteria)->get();
    }

    public function create(array $data): Model
    {
        // Ensure version and synced_at fields are set for offline-sync readiness
        if (!array_key_exists('version', $data)) {
            $data['version'] = $data['version'] ?? 1;
        }
        if (array_key_exists('synced_at', $data) === false) {
            $data['synced_at'] = $data['synced_at'] ?? null;
        }

        return $this->model->create($data);
    }

    public function update(int $id, array $data): bool
    {
        $record = $this->model->find($id);
        if (!$record) {
            return false;
        }

        // Increment version for offline sync and mark unsynced
        if (\Illuminate\Support\Facades\Schema::hasColumn($this->model->getTable(), 'version')) {
            $current = $record->version ?? 1;
            $data['version'] = $current + 1;
            $data['synced_at'] = null;
        }

        return $record ? $record->update($data) : false;
    }

    public function delete(int $id): bool
    {
        $record = $this->model->find($id);
        return $record ? $record->delete() : false;
    }

    public function exists(array $criteria): bool
    {
        return $this->model->where($criteria)->exists();
    }

    public function count(array $criteria = []): int
    {
        return $this->model->where($criteria)->count();
    }

    public function withTrashed(): Collection
    {
        return $this->model->withTrashed()->get();
    }

    public function onlyTrashed(): Collection
    {
        return $this->model->onlyTrashed()->get();
    }

    public function restore(int $id): bool
    {
        $record = $this->model->withTrashed()->find($id);
        return $record ? $record->restore() : false;
    }

    public function forceDelete(int $id): bool
    {
        $record = $this->model->withTrashed()->find($id);
        return $record ? $record->forceDelete() : false;
    }
}
