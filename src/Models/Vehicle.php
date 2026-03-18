<?php

namespace App\Models;

use App\Core\Hydratable;

class Vehicle implements BaseModel
{
    use Hydratable;

    public int $vehicleId;
    public int $userId;
    public string $brand;
    public string $model;
    public string $color;
    public string $licensePlate;
    public string $energyType;
    public int $seatsAvailable;
    public string $registrationDate;
}
