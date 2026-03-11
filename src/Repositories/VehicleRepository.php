<?php

namespace App\Repositories;

use App\Models\Vehicle;

class VehicleRepository
{
    private Vehicle $vehicleModel;

    public function __construct(?Vehicle $vehicleModel = null)
    {
        $this->vehicleModel = $vehicleModel ?? new Vehicle();
    }

    public function findByUser(int $userId): array
    {
        return $this->vehicleModel->byUser($userId);
    }
}
