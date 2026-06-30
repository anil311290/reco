<?php

namespace App\Interfaces;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

interface PermissionRepositoryInterface extends RepositoryInterface
{
    /**
     * Get permissions by module
     */
    public function getByModule(string $module): Collection;

    /**
     * Get active permissions
     */
    public function getActive(): Collection;

    /**
     * Find permission by slug
     */
    public function findBySlug(string $slug): ?Permission;

    /**
     * Get permissions grouped by module
     */
    public function getGrouped(): Collection;
}
