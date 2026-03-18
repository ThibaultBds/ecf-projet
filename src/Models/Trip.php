<?php

namespace App\Models;

use App\Core\Hydratable;

class Trip implements BaseModel
{
    use Hydratable;

    // Champs de base
    public int $tripId;
    public int $chauffeurId;
    public int $vehicleId;
    public int $cityDepartId;
    public int $cityArrivalId;
    public string $departureDatetime;
    public string $arrivalDatetime;
    public float $price;
    public int $availableSeats;
    public string $status;

    // Champs joints (requêtes avec JOIN)
    public ?string $villeDepart = null;
    public ?string $villeArrivee = null;
    public ?string $conducteur = null;
    public ?string $conducteurPhoto = null;
    public ?int $nbParticipants = null;
    public ?string $participantStatus = null;
    public ?int $hasReviewed = null;
    public ?float $noteConducteur = null;
    public ?string $brand = null;
    public ?string $model = null;
    public ?string $color = null;
    public ?string $energyType = null;
    public ?int $seatsAvailable = null;
    public ?int $chauffeurUserId = null;
    public ?string $roleTrajet = null;
}
