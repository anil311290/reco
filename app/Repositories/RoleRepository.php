<?php

namespace App\Repositories;

use App\Interfaces\RoleRepositoryInterface;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->get();
    }

    public function getActive(): Collection
    {
        return $this->model->where('is_active', true)->get();
    }

    public function getDefault(): ?Role
    {
        return $this->model->where('is_default', true)->first();
    }

    public function findBySlug(string $slug): ?Role
    {
        return $this->model->where('slug', $slug)->first();
    }
}
