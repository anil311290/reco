<?php

namespace App\Interfaces;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

interface RoleRepositoryInterface extends RepositoryInterface
{
    /**
     * Get roles by company
     */
    public function getByCompany(int $companyId): Collection;

    /**
     * Get active roles
     */
    public function getActive(): Collection;

    /**
     * Get default role
     */
    public function getDefault(): ?Role;

    /**
     * Find role by slug
     */
    public function findBySlug(string $slug): ?Role;
}
