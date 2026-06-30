<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface RepositoryInterface
{
    /**
     * Get all records
     */
    public function all(array $columns = ['*'], array $relations = []): Collection;

    /**
     * Get all records with pagination
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator;

    /**
     * Find record by ID
     */
    public function find(int $id, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Find record by criteria
     */
    public function findBy(array $criteria, array $columns = ['*'], array $relations = []): ?Model;

    /**
     * Get records by criteria
     */
    public function getWhere(array $criteria, array $columns = ['*'], array $relations = []): Collection;

    /**
     * Create a new record
     */
    public function create(array $data): Model;

    /**
     * Update a record
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a record
     */
    public function delete(int $id): bool;

    /**
     * Check if record exists
     */
    public function exists(array $criteria): bool;

    /**
     * Count records
     */
    public function count(array $criteria = []): int;

    /**
     * Get records with soft deletes
     */
    public function withTrashed(): Collection;

    /**
     * Get only trashed records
     */
    public function onlyTrashed(): Collection;

    /**
     * Restore a soft deleted record
     */
    public function restore(int $id): bool;

    /**
     * Force delete a record
     */
    public function forceDelete(int $id): bool;
}
