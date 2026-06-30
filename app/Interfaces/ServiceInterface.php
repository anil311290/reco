<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface ServiceInterface
{
    /**
     * Get all records
     */
    public function getAll(array $filters = []): Collection;

    /**
     * Get all records with pagination
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find record by ID
     */
    public function getById(int $id): ?Model;

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
}
