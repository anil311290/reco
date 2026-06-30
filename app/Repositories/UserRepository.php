<?php

namespace App\Repositories;

use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function getByCompany(int $companyId): Collection
    {
        return $this->model->where('company_id', $companyId)->get();
    }

    public function getByRole(string $role): Collection
    {
        return $this->model->where('role', $role)->get();
    }

    public function getActive(): Collection
    {
        return $this->model->where('status', 'active')->get();
    }

    public function updateLastLogin(int $userId, string $ip): bool
    {
        return $this->update($userId, [
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}
