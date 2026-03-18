<?php

namespace App\Models;

use App\Core\Hydratable;

class Review implements BaseModel
{
    use Hydratable;

    public int $id;
    public int $tripId;
    public int $reviewerId;
    public int $driverId;
    public int $rating;
    public ?string $comment;
    public string $status;
    public string $createdAt;

    // Champs joints
    public ?string $reviewerName = null;
    public ?string $driverName = null;
    public ?string $reviewerEmail = null;
}
