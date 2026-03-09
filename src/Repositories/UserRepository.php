<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    public function findBYId(int $id): ?array
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?array
    {
        return User::findBY('email', $email);
    }

    public function findAll(): array
    {
        return User::all();
    }
}
