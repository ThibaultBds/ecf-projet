<?php

namespace App\Models;

use App\Core\Hydratable;

class User implements BaseModel
{
    use Hydratable;

    public int $userId;
    public string $username;
    public string $email;
    public string $password = '';
    public int $credits;
    public string $role;
    public bool $isDriver;
    public bool $isPassenger;
    public ?string $photo;
    public bool $suspended;
}
