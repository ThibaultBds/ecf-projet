<?php

namespace App\Models;

use App\Core\Hydratable;

class TripParticipant implements BaseModel
{
    use Hydratable;

    public int $id;
    public int $tripId;
    public int $userId;
    public string $status;

    // Champs joints
    public ?string $username = null;
    public ?string $email = null;
}
