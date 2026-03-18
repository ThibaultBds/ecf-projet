<?php

namespace App\Models;

use App\Core\Hydratable;

class ContactMessage implements BaseModel
{
    use Hydratable;

    public int $id;
    public string $nom;
    public string $email;
    public string $sujet;
    public string $message;
    public bool $isRead;
    public string $createdAt;
}
