<?php

namespace App\Services;

use App\Repositories\TripRepository;

class TripService
{
    private TripRepository $tripRepository;

    public function __construct(?TripRepository $tripRepository = null)
    {
        $this->tripRepository = $tripRepository ?? new TripRepository();
    }

    public function buildSearchFiltersFromQuery(array $query): array
    {
        return [
            'depart' => $query['depart'] ?? '',
            'arrivee' => $query['arrivee'] ?? '',
            'date' => $this->normalizeDateInput($query['date'] ?? ''),
            'prix_max' => $query['prix_max'] ?? null,
            'note_min' => $query['note_min'] ?? null,
            'ecologique' => $query['ecologique'] ?? '',
            'duree_max' => $query['duree_max'] ?? null,
        ];
    }

    public function searchTrips(array $filters): array
    {
        $hasSearched = !empty($filters['depart']) || !empty($filters['arrivee']) || !empty($filters['date']);
        $trips = $hasSearched ? $this->tripRepository->search($filters) : [];

        $nearestDate = null;
        if ($hasSearched && empty($trips)) {
            $nearestDate = $this->tripRepository->nearestDate($filters['depart'], $filters['arrivee']);
        }

        return [
            'hasSearched' => $hasSearched,
            'trips' => $trips,
            'nearestDate' => $nearestDate,
            'filters' => $filters,
        ];
    }

    public function searchTripsForApi(array $filters): array
    {
        return $this->tripRepository->search($filters);
    }

    public function findTripWithDetails(int $tripId): ?array
    {
        return $this->tripRepository->findWithDetails($tripId);
    }

    public function normalizeDateInput(string $rawDate): string
    {
        $rawDate = trim($rawDate);
        if (preg_match('#^(\d{2})\s*/\s*(\d{2})\s*/\s*(\d{4})$#', $rawDate, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        return $rawDate;
    }

    public function getMyTripsData(int $userId, bool $isDriver): array
    {
        $trajetsConduits = $isDriver ? $this->tripRepository->byDriver($userId) : [];
        $participations = $this->tripRepository->byPassenger($userId);

        $upcomingStatuses = ['scheduled', 'started'];

        return [
            'upcoming_conduits' => array_values(array_filter($trajetsConduits, fn($t) => in_array($t['status'], $upcomingStatuses, true))),
            'past_conduits' => array_values(array_filter($trajetsConduits, fn($t) => !in_array($t['status'], $upcomingStatuses, true))),
            'upcoming_participations' => array_values(array_filter($participations, fn($t) => in_array($t['status'], $upcomingStatuses, true))),
            'past_participations' => array_values(array_filter($participations, fn($t) => !in_array($t['status'], $upcomingStatuses, true))),
        ];
    }
}
