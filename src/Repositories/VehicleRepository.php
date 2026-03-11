<?php

namespace App\Repositories;

use App\Models\Vehicle;

class VehicleRepository
{
    public function findByUser(int $userId): array
    {
        return Vehicle::byUser($userId);
    }
}
