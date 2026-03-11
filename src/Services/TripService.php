<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Mailer;
use App\Core\MongoDB;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use App\Repositories\TripRepository;
use Exception;

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

    public function getResolvedIncidents(int $userId): array
    {
        $resolvedIncidents = [];

        try {
            $mongo = MongoDB::getInstance();
            $incidents = $mongo->find('trip_incidents', [
                'reporter_id' => $userId,
                'status' => 'resolved',
            ]);

            foreach ($incidents as $incident) {
                $resolvedIncidents[(int) $incident['trip_id']] = $incident['decision'] ?? '';
            }
        } catch (\Throwable $e) {
            // MongoDB indisponible: on garde un tableau vide.
        }

        return $resolvedIncidents;
    }

    public function cancelParticipation(int $tripId, int $userId): void
    {
        $pdo = Database::getInstance()->getConnection();
        $tripModel = new Trip();
        $participantModel = new TripParticipant();
        $userModel = new User();

        try {
            $pdo->beginTransaction();

            $participantModel->removeParticipation((int) $tripId, (int) $userId);

            $stmt = $pdo->prepare("UPDATE trips SET available_seats = available_seats + 1 WHERE trip_id = ?");
            $stmt->execute([$tripId]);

            $trip = $tripModel->find((int) $tripId);
            if ($trip) {
                $userModel->addCredits($userId, (int) $trip['price'] + 2, 'refund', 'Annulation participation', $tripId);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur annulation participation : " . $e->getMessage());
        }
    }

    public function validateTrip(int $tripId, int $userId): void
    {
        $tripModel = new Trip();
        $participantModel = new TripParticipant();
        $userModel = new User();
        $pdo = Database::getInstance()->getConnection();

        $trip = $tripModel->find($tripId);
        if (!$trip || $trip['status'] !== 'completed') return;
        if (!$participantModel->isParticipating($tripId, $userId)) return;

        $stmt = $pdo->prepare("SELECT * FROM trip_participants WHERE trip_id = ? AND user_id = ?");
        $stmt->execute([$tripId, $userId]);
        $participant = $stmt->fetch();

        if (!$participant || $participant['status'] === 'validated') return;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE trip_participants SET status = 'validated' WHERE trip_id = ? AND user_id = ?");
            $stmt->execute([$tripId, $userId]);

            $userModel->addCredits($trip['chauffeur_id'], (int) $trip['price'], 'credit', 'Validation trajet passager', $tripId);

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

        $tripModel = new Trip();
        $participantModel = new TripParticipant();
        $userModel = new User();
        $pdo = Database::getInstance()->getConnection();

        $trip = $tripModel->find((int) $tripId);
        if (!$trip || $trip['chauffeur_id'] != $userId) {
            return;
        }

        if ($newStatus === 'started' && $trip['status'] !== 'scheduled') return;
        if ($newStatus === 'completed' && $trip['status'] !== 'started') return;
        if ($newStatus === 'cancelled' && !in_array($trip['status'], ['scheduled', 'started'])) return;

        try {
            $pdo->beginTransaction();

            $tripModel->update((int) $tripId, ['status' => $newStatus]);
            $participants = $participantModel->byTrip((int) $tripId);

            if ($newStatus === 'cancelled') {
                foreach ($participants as $p) {
                    $userModel->addCredits($p['user_id'], (int) $trip['price'], 'refund', 'Remboursement annulation trajet', $tripId);
                    $passenger = $userModel->find((int) $p['user_id']);
                    if ($passenger) {
                        (new Mailer())->send(
                            $passenger['email'],
                            "Trajet annule - EcoRide",
                            "Bonjour {$passenger['username']},\n\nLe trajet #{$tripId} a ete annule par le chauffeur.\nVous avez ete rembourse de {$trip['price']} credits.\n\nEcoRide"
                        );
                    }
                }
                $userModel->addCredits($userId, 2, 'refund', 'Remboursement frais plateforme', $tripId);
            }

            if ($newStatus === 'completed') {
                foreach ($participants as $p) {
                    $passenger = $userModel->find((int) $p['user_id']);
                    if ($passenger) {
                        (new Mailer())->send(
                            $passenger['email'],
                            "Votre trajet est termine - EcoRide",
                            "Bonjour {$passenger['username']},\n\nVotre trajet #{$tripId} est termine.\nRendez-vous dans votre espace pour valider le trajet et laisser un avis.\n\nEcoRide"
                        );
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
        $tripModel = new Trip();
        $participantModel = new TripParticipant();

        $trip = $tripModel->find($tripId);
        if (!$trip || $trip['status'] !== 'completed') return;
        if (!$participantModel->isParticipating($tripId, $userId)) return;

        $comment = trim($comment);

        try {
            $mongo = MongoDB::getInstance();
            $mongo->insertOne('trip_incidents', [
                'trip_id' => $tripId,
                'reporter_id' => $userId,
                'chauffeur_id' => (int) $trip['chauffeur_id'],
                'comment' => $comment,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("UPDATE trip_participants SET status = 'disputed' WHERE trip_id = ? AND user_id = ?");
            $stmt->execute([$tripId, $userId]);
        } catch (Exception $e) {
            error_log("Erreur signalement : " . $e->getMessage());
        }
    }

    public function joinTrip(int $tripId, int $userId): array
    {
        $tripModel = new Trip();
        $participantModel = new TripParticipant();
        $userModel = new User();
        $pdo = Database::getInstance()->getConnection();

        try {
            $trip = $tripModel->find($tripId);
            $user = $userModel->find($userId);

            if (!$trip) {
                return ['success' => false, 'message' => 'Trajet non trouve.'];
            }

            if ((int) $trip['chauffeur_id'] === $userId) {
                return ['success' => false, 'message' => 'Vous ne pouvez pas participer a votre propre trajet.'];
            }

            if ($participantModel->isParticipating($tripId, $userId)) {
                return ['success' => false, 'message' => 'Vous participez deja a ce trajet.'];
            }

            if ((int) $trip['available_seats'] <= 0) {
                return ['success' => false, 'message' => 'Plus de places disponibles.'];
            }

            $tripPrice = (int) $trip['price'];
            $platformFee = 2;
            $total = $tripPrice + $platformFee;

            if ((int) ($user['credits'] ?? 0) < $total) {
                return ['success' => false, 'message' => 'Credits insuffisants.'];
            }

            $pdo->beginTransaction();

            if (!$userModel->deductCredits($userId, $tripPrice, 'debit', 'Participation au trajet', $tripId)) {
                throw new Exception("Erreur debit prix");
            }

            if (!$userModel->deductCredits($userId, $platformFee, 'platform_fee', 'Frais plateforme', $tripId)) {
                throw new Exception("Erreur frais plateforme");
            }

            $userModel->addCredits((int) $trip['chauffeur_id'], $tripPrice, 'credit', 'Revenu trajet', $tripId);

            $participantModel->create([
                'trip_id' => $tripId,
                'user_id' => $userId,
            ]);

            $stmt = $pdo->prepare("UPDATE trips SET available_seats = available_seats - 1 WHERE trip_id = ?");
            $stmt->execute([$tripId]);

            $pdo->commit();

            $updatedUser = $userModel->find($userId);
            $newCredits = (int) ($updatedUser['credits'] ?? 0);

            return [
                'success' => true,
                'message' => 'Participation confirmee !',
                'new_credits' => $newCredits,
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erreur participation : " . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur technique, reessayez plus tard.',
            ];
        }
    }

    public function cancelTrip(int $tripId, int $userId): array
    {
        $tripModel = new Trip();
        $participantModel = new TripParticipant();
        $userModel = new User();
        $mailer = new Mailer();
        $pdo = Database::getInstance()->getConnection();

        try {
            $trip = $tripModel->find($tripId);

            if (!$trip || (int) $trip['chauffeur_id'] !== $userId) {
                return ['success' => false, 'message' => 'Trajet non trouve ou non autorise.'];
            }

            if ($trip['status'] !== 'scheduled') {
                return ['success' => false, 'message' => 'Ce trajet ne peut plus etre annule.'];
            }

            $pdo->beginTransaction();

            $tripModel->update($tripId, ['status' => 'cancelled']);
            $participants = $participantModel->byTrip($tripId);

            foreach ($participants as $participant) {
                $userModel->addCredits(
                    (int) $participant['user_id'],
                    (int) $trip['price'],
                    'refund',
                    'Remboursement annulation',
                    $tripId
                );

                $passenger = $userModel->find((int) $participant['user_id']);
                if ($passenger) {
                    $mailer->send(
                        $passenger['email'],
                        "Trajet annule - EcoRide",
                        "Bonjour {$passenger['username']},\n\nLe trajet #{$tripId} a ete annule par le chauffeur.\nVous avez ete rembourse de {$trip['price']} credits.\n\nEcoRide"
                    );
                }
            }

            $userModel->addCredits(
                $userId,
                2,
                'refund',
                'Remboursement frais plateforme',
                $tripId
            );

            $pdo->commit();

            return [
                'success' => true,
                'message' => 'Trajet annule. ' . count($participants) . ' passager(s) rembourse(s).',
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erreur annulation : " . $e->getMessage());

            return ['success' => false, 'message' => 'Erreur technique.'];
        }
    }
}
