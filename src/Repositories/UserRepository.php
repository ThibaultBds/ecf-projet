<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    private User $userModel;

    public function __construct(?User $userModel = null)
    {
        $this->userModel = $userModel ?? new User();
    }

    public function findById(int $id): ?array
    {
        $user = $this->userModel->find($id);
        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $user = $this->userModel->findByEmail($email);
        return $user ?: null;
    }

    public function findAll(): array
    {
        return $this->userModel->findAll();
    }
}
