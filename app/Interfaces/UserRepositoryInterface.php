<?php

namespace App\Interfaces;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User;

    /**
     * Get users by company
     */
    public function getByCompany(int $companyId): Collection;

    /**
     * Get users by role
     */
    public function getByRole(string $role): Collection;

    /**
     * Get active users
     */
    public function getActive(): Collection;

    /**
     * Update last login information
     */
    public function updateLastLogin(int $userId, string $ip): bool;
}
