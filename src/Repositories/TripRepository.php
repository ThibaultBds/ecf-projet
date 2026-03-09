<?php

namespace App\Repositories;

use App\Models\Trip;

class TripRepository
{
    public function search(array $filters): array
    {
        return Trip::search($filters);
    }

    public function findById(int $id): ?array
    {
        return Trip::find($id);
    }

    public function findByDriver(int $driverId): array
    {
        return Trip::where('chauffeur_id', $driverId);
    }
}
