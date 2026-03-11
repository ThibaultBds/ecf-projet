<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findById(int $id): ?array
    {
        return 
    }

    public function findByEmail(string $email): ?array
    {
        return User::findBy('email', $email);
    }

    public function findAll(): array
    {
        return User::all();
    }
}
