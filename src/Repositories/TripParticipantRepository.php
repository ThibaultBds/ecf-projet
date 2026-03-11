<?php

namespace App\Repositories;

use App\Models\TripParticipant;

class TripParticipantRepository
{
    public function findByTrip(int $tripId): array
    {
        return TripParticipant::byTrip($tripId);
    }

    public function removeParticipation(int $tripId, int $userId)
    {
        TripParticipant::removeParticipation($tripId, $userId);
    }
}
