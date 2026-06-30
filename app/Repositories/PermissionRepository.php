<?php

namespace App\Repositories;

use App\Interfaces\PermissionRepositoryInterface;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionRepository extends BaseRepository implements PermissionRepositoryInterface
{
    public function __construct(Permission $model)
    {
        parent::__construct($model);
    }

    public function getByModule(string $module): Collection
    {
        return $this->model->where('module', $module)->get();
    }

    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    public function findBySlug(string $slug): ?Permission
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function getGrouped(): Collection
    {
        return $this->model->orderBy('module')->orderBy('name')->get()->groupBy('module');
    }
}
