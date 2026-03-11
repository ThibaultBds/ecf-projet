<?php

namespace App\Repositories;

use App\Models\TripParticipant;

class TripParticipantRepository
{
    private TripParticipant $tripParticipantModel;

    public function __construct(?TripParticipant $tripParticipantModel = null)
    {
        $this->tripParticipantModel = $tripParticipantModel ?? new TripParticipant();
    }

    public function findByTrip(int $tripId): array
    {
        return $this->tripParticipantModel->byTrip($tripId);
    }

    public function removeParticipation(int $tripId, int $userId)
    {
        $this->tripParticipantModel->removeParticipation($tripId, $userId);
    }
}
