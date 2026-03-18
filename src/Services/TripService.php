<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Mailer;
use App\Core\MongoDB;
use App\Repositories\TripRepository;
use App\Repositories\TripParticipantRepository;
use App\Repositories\UserRepository;
use Exception;

class TripService
{
    private TripRepository $tripRepository;
    private TripParticipantRepository $participantRepository;
    private UserRepository $userRepository;

    public function __construct(
        ?TripRepository $tripRepository = null,
        ?TripParticipantRepository $participantRepository = null,
        ?UserRepository $userRepository = null
    ) {
        $this->tripRepository        = $tripRepository ?? new TripRepository();
        $this->participantRepository = $participantRepository ?? new TripParticipantRepository();
        $this->userRepository        = $userRepository ?? new UserRepository();
    }

    public function buildSearchFiltersFromQuery(array $query): array
    {
        return [
            'depart'     => $query['depart'] ?? '',
            'arrivee'    => $query['arrivee'] ?? '',
            'date'       => $this->normalizeDateInput($query['date'] ?? ''),
            'prix_max'   => $query['prix_max'] ?? null,
            'note_min'   => $query['note_min'] ?? null,
            'ecologique' => $query['ecologique'] ?? '',
            'duree_max'  => $query['duree_max'] ?? null,
        ];
    }

    public function searchTrips(array $filters): array
    {
        $hasSearched = !empty($filters['depart']) || !empty($filters['arrivee']) || !empty($filters['date']);
        $trips       = $hasSearched ? $this->tripRepository->search($filters) : [];
        $nearestDate = null;

        if ($hasSearched && empty($trips)) {
            $nearestDate = $this->tripRepository->nearestDate($filters['depart'], $filters['arrivee']);
        }

        return ['hasSearched' => $hasSearched, 'trips' => $trips, 'nearestDate' => $nearestDate, 'filters' => $filters];
    }

    public function searchTripsForApi(array $filters): array
    {
        return $this->tripRepository->search($filters);
    }

    public function findTripWithDetails(int $tripId): ?\App\Models\Trip
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
        $trajetsConduits  = $isDriver ? $this->tripRepository->byDriver($userId) : [];
        $participations   = $this->tripRepository->byPassenger($userId);
        $upcomingStatuses = ['scheduled', 'started'];

        return [
            'upcoming_conduits'       => array_values(array_filter($trajetsConduits, fn($t) => in_array($t->status, $upcomingStatuses, true))),
            'past_conduits'           => array_values(array_filter($trajetsConduits, fn($t) => !in_array($t->status, $upcomingStatuses, true))),
            'upcoming_participations' => array_values(array_filter($participations,  fn($t) => in_array($t->status, $upcomingStatuses, true))),
            'past_participations'     => array_values(array_filter($participations,  fn($t) => !in_array($t->status, $upcomingStatuses, true))),
        ];
    }

    public function getResolvedIncidents(int $userId): array
    {
        $resolvedIncidents = [];
        try {
            $mongo     = MongoDB::getInstance();
            $incidents = $mongo->find('trip_incidents', ['reporter_id' => $userId, 'status' => 'resolved']);
            foreach ($incidents as $incident) {
                $resolvedIncidents[(int) $incident['trip_id']] = $incident['decision'] ?? '';
            }
        } catch (\Throwable $e) {
        }
        return $resolvedIncidents;
    }

    public function createTrip(int $userId, int $vehicleId, array $data): void
    {
        $pdo = Database::getInstance()->getConnection();

        $cityDepartId  = $this->tripRepository->findOrCreateCity($data['city_depart']);
        $cityArrivalId = $this->tripRepository->findOrCreateCity($data['city_arrival']);

        $pdo->beginTransaction();

        $this->tripRepository->create([
            'chauffeur_id'       => $userId,
            'vehicle_id'         => $vehicleId,
            'city_depart_id'     => $cityDepartId,
            'city_arrival_id'    => $cityArrivalId,
            'departure_datetime' => $data['departure_datetime'],
            'arrival_datetime'   => $data['arrival_datetime'],
            'price'              => $data['price'],
            'available_seats'    => $data['available_seats'],
            'status'             => 'scheduled',
        ]);

        $this->userRepository->deductCredits($userId, (int) $data['price'], 'debit', 'Creation trajet', null);
        $this->userRepository->deductCredits($userId, 2, 'platform_fee', 'Frais plateforme creation', null);

        $pdo->commit();
    }

    public function cancelParticipation(int $tripId, int $userId): void
    {
        $pdo = Database::getInstance()->getConnection();
        try {
            $pdo->beginTransaction();
            $this->participantRepository->removeParticipation($tripId, $userId);
            $stmt = $pdo->prepare("UPDATE trips SET available_seats = available_seats + 1 WHERE trip_id = ?");
            $stmt->execute([$tripId]);
            $trip = $this->tripRepository->findById($tripId);
            if ($trip) {
                $this->userRepository->addCredits($userId, (int) $trip->price + 2, 'refund', 'Annulation participation', $tripId);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur annulation participation : " . $e->getMessage());
        }
    }

    public function validateTrip(int $tripId, int $userId): void
    {
        $pdo  = Database::getInstance()->getConnection();
        $trip = $this->tripRepository->findById($tripId);
        if (!$trip || $trip->status !== 'completed') {
            return;
        }
        if (!$this->participantRepository->isParticipating($tripId, $userId)) {
            return;
        }
        $participant = $this->participantRepository->find($tripId, $userId);
        if (!$participant || $participant->status === 'validated') {
            return;
        }
        try {
            $pdo->beginTransaction();
            $this->participantRepository->updateStatus($tripId, $userId, 'validated');
            $this->userRepository->addCredits($trip->chauffeurId, (int) $trip->price, 'credit', 'Validation trajet passager', $tripId);
            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            error_log("Erreur validation trajet : " . $e->getMessage());
        }
    }

    public function updateTripStatus(int $tripId, int $userId, string $newStatus): void
    {
        $validStatuses = ['started', 'completed', 'cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            return;
        }

        $pdo  = Database::getInstance()->getConnection();
        $trip = $this->tripRepository->findById($tripId);
        if (!$trip || $trip->chauffeurId != $userId) {
            return;
        }
        if ($newStatus === 'started'   && $trip->status !== 'scheduled') {
            return;
        }
        if ($newStatus === 'completed' && $trip->status !== 'started') {
            return;
        }
        if ($newStatus === 'cancelled' && !in_array($trip->status, ['scheduled', 'started'])) {
            return;
        }

        try {
            $pdo->beginTransaction();
            $this->tripRepository->update($tripId, ['status' => $newStatus]);
            $participants = $this->participantRepository->byTrip($tripId);

            if ($newStatus === 'cancelled') {
                foreach ($participants as $p) {
                    $this->userRepository->addCredits($p->userId, (int) $trip->price, 'refund', 'Remboursement annulation trajet', $tripId);
                    $passenger = $this->userRepository->findById($p->userId);
                    if ($passenger) {
                        (new Mailer())->send($passenger->email, "Trajet annule - EcoRide",
                            "Bonjour {$passenger->username},\n\nLe trajet #{$tripId} a ete annule par le chauffeur.\nVous avez ete rembourse de {$trip->price} credits.\n\nEcoRide");
                    }
                }
                $this->userRepository->addCredits($userId, 2, 'refund', 'Remboursement frais plateforme', $tripId);
            }

            if ($newStatus === 'completed') {
                foreach ($participants as $p) {
                    $passenger = $this->userRepository->findById($p->userId);
                    if ($passenger) {
                        (new Mailer())->send($passenger->email, "Votre trajet est termine - EcoRide",
                            "Bonjour {$passenger->username},\n\nVotre trajet #{$tripId} est termine.\nRendez-vous dans votre espace pour valider le trajet et laisser un avis.\n\nEcoRide");
                    }
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur mise a jour statut : " . $e->getMessage());
        }
    }

    public function reportProblem(int $tripId, int $userId, string $comment): void
    {
        $trip = $this->tripRepository->findById($tripId);
        if (!$trip || $trip->status !== 'completed') {
            return;
        }
        if (!$this->participantRepository->isParticipating($tripId, $userId)) {
            return;
        }
        $comment = trim($comment);
        try {
            MongoDB::getInstance()->insertOne('trip_incidents', [
                'trip_id'      => $tripId,
                'reporter_id'  => $userId,
                'chauffeur_id' => $trip->chauffeurId,
                'comment'      => $comment,
                'status'       => 'pending',
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
            $pdo  = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("UPDATE trip_participants SET status = 'disputed' WHERE trip_id = ? AND user_id = ?");
            $stmt->execute([$tripId, $userId]);
        } catch (Exception $e) {
            error_log("Erreur signalement : " . $e->getMessage());
        }
    }

    public function joinTrip(int $tripId, int $userId): array
    {
        $pdo = Database::getInstance()->getConnection();
        try {
            $trip = $this->tripRepository->findById($tripId);
            $user = $this->userRepository->findById($userId);

            if (!$trip) {
                return ['success' => false, 'message' => 'Trajet non trouve.'];
            }
            if ($trip->chauffeurId === $userId) {
                return ['success' => false, 'message' => 'Vous ne pouvez pas participer a votre propre trajet.'];
            }
            if ($this->participantRepository->isParticipating($tripId, $userId)) {
                return ['success' => false, 'message' => 'Vous participez deja a ce trajet.'];
            }
            if ($trip->availableSeats <= 0) {
                return ['success' => false, 'message' => 'Plus de places disponibles.'];
            }

            $tripPrice   = (int) $trip->price;
            $platformFee = 2;
            if ($user->credits < $tripPrice + $platformFee) {
                return ['success' => false, 'message' => 'Credits insuffisants.'];
            }

            $pdo->beginTransaction();
            if (!$this->userRepository->deductCredits($userId, $tripPrice, 'debit', 'Participation au trajet', $tripId)) {
                throw new Exception("Erreur debit prix");
            }
            if (!$this->userRepository->deductCredits($userId, $platformFee, 'platform_fee', 'Frais plateforme', $tripId)) {
                throw new Exception("Erreur frais plateforme");
            }
            $this->userRepository->addCredits($trip->chauffeurId, $tripPrice, 'credit', 'Revenu trajet', $tripId);
            $this->participantRepository->create(['trip_id' => $tripId, 'user_id' => $userId]);
            $stmt = $pdo->prepare("UPDATE trips SET available_seats = available_seats - 1 WHERE trip_id = ?");
            $stmt->execute([$tripId]);
            $pdo->commit();

            $updatedUser = $this->userRepository->findById($userId);
            return ['success' => true, 'message' => 'Participation confirmee !', 'new_credits' => $updatedUser->credits];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erreur participation : " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur technique, reessayez plus tard.'];
        }
    }

    public function cancelTrip(int $tripId, int $userId): array
    {
        $pdo    = Database::getInstance()->getConnection();
        $mailer = new Mailer();
        try {
            $trip = $this->tripRepository->findById($tripId);
            if (!$trip || $trip->chauffeurId !== $userId) {
                return ['success' => false, 'message' => 'Trajet non trouve ou non autorise.'];
            }
            if ($trip->status !== 'scheduled') {
                return ['success' => false, 'message' => 'Ce trajet ne peut plus etre annule.'];
            }

            $pdo->beginTransaction();
            $this->tripRepository->update($tripId, ['status' => 'cancelled']);
            $participants = $this->participantRepository->byTrip($tripId);

            foreach ($participants as $participant) {
                $this->userRepository->addCredits($participant->userId, (int) $trip->price, 'refund', 'Remboursement annulation', $tripId);
                $passenger = $this->userRepository->findById($participant->userId);
                if ($passenger) {
                    $mailer->send($passenger->email, "Trajet annule - EcoRide",
                        "Bonjour {$passenger->username},\n\nLe trajet #{$tripId} a ete annule par le chauffeur.\nVous avez ete rembourse de {$trip->price} credits.\n\nEcoRide");
                }
            }
            $this->userRepository->addCredits($userId, 2, 'refund', 'Remboursement frais plateforme', $tripId);
            $pdo->commit();

            return ['success' => true, 'message' => 'Trajet annule. ' . count($participants) . ' passager(s) rembourse(s).'];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erreur annulation : " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur technique.'];
        }
    }
}
