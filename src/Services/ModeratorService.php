<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Mailer;
use App\Core\MongoDB;
use App\Repositories\TripRepository;
use App\Repositories\UserRepository;
use Throwable;

class ModeratorService
{
    private UserRepository $userRepository;
    private TripRepository $tripRepository;
    private UserService $userService;
    private Mailer $mailer;

    public function __construct(
        ?UserRepository $userRepository = null,
        ?TripRepository $tripRepository = null,
        ?UserService $userService = null,
        ?Mailer $mailer = null
    ) {
        $this->userRepository = $userRepository ?? new UserRepository();
        $this->tripRepository = $tripRepository ?? new TripRepository();
        $this->userService = $userService ?? new UserService($this->userRepository);
        $this->mailer = $mailer ?? new Mailer();
    }

    public function resolveIncident(int $tripId, int $reporterId, bool $creditDriver): array
    {
        if ($tripId <= 0 || $reporterId <= 0) {
            return ['success' => false, 'message' => 'Incident invalide.'];
        }

        $trip = $this->tripRepository->findById($tripId);
        if (!$trip) {
            return ['success' => false, 'message' => 'Trajet introuvable.'];
        }

        $participantStatus = $creditDriver ? 'validated' : 'disputed';

        $mongo = MongoDB::getInstance();
        $modified = $mongo->updateWhere(
            'trip_incidents',
            ['status' => 'pending', '$or' => [
                ['trip_id' => $tripId, 'reporter_id' => $reporterId],
                ['trip_id' => (string) $tripId, 'reporter_id' => (string) $reporterId],
            ]],
            [
                'status' => 'resolved',
                'resolved_at' => date('Y-m-d H:i:s'),
                'decision' => $creditDriver ? 'favor_driver' : 'favor_passenger',
            ]
        );

        if ($modified < 1) {
            return ['success' => false, 'message' => 'Incident deja resolu ou introuvable.'];
        }

        $driverId = $trip->chauffeurId;
        $tripPrice = (int) $trip->price;
        $driver = $this->userRepository->findById($driverId);
        $passenger = $this->userRepository->findById($reporterId);

        $pdo = Database::getInstance()->getConnection();
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT status FROM trip_participants
                 WHERE trip_id = ? AND user_id = ?
                 FOR UPDATE"
            );
            $stmt->execute([$tripId, $reporterId]);
            $participant = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$participant || $participant['status'] !== 'disputed') {
                throw new \RuntimeException('Participation introuvable ou deja traitee.');
            }

            $stmt = $pdo->prepare("UPDATE trip_participants SET status = ? WHERE trip_id = ? AND user_id = ?");
            $stmt->execute([$participantStatus, $tripId, $reporterId]);

            if ($creditDriver) {
                $this->userService->creditCredits($driverId, $tripPrice, 'credit', 'Resolution incident - decision en faveur du chauffeur', $tripId);
            } else {
                $refund = $tripPrice + 2;
                $this->userService->creditCredits($reporterId, $refund, 'refund', 'Remboursement incident - decision en faveur du passager', $tripId);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $mongo->updateWhere(
                'trip_incidents',
                ['status' => 'resolved', '$or' => [
                    ['trip_id' => $tripId, 'reporter_id' => $reporterId],
                    ['trip_id' => (string) $tripId, 'reporter_id' => (string) $reporterId],
                ]],
                [
                    'status' => 'pending',
                    'resolved_at' => null,
                    'decision' => null,
                ]
            );

            error_log('Erreur resolution incident : ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur technique pendant la resolution.'];
        }

        if ($creditDriver) {
            if ($driver) {
                $this->mailer->send(
                    $driver->email,
                    'Incident resolu - EcoRide',
                    "Bonjour {$driver->username},\n\nL incident signale sur le trajet #{$tripId} a ete examine. La decision est en votre faveur : vous avez ete credite du montant du trajet.\n\nEcoRide"
                );
            }

            return ['success' => true, 'message' => 'Incident resolu - decision en faveur du chauffeur.'];
        }

        $refund = $tripPrice + 2;
        if ($passenger) {
            $this->mailer->send(
                $passenger->email,
                'Incident resolu - EcoRide',
                "Bonjour {$passenger->username},\n\nL incident sur le trajet #{$tripId} a ete examine. La decision est en votre faveur : vous avez ete rembourse de {$refund} credits.\n\nEcoRide"
            );
        }

        if ($driver) {
            $this->mailer->send(
                $driver->email,
                'Incident resolu - EcoRide',
                "Bonjour {$driver->username},\n\nL incident signale sur le trajet #{$tripId} a ete examine. La decision est en faveur du passager : aucun credit ne vous a ete attribue pour ce trajet.\n\nEcoRide"
            );
        }

        return ['success' => true, 'message' => 'Incident resolu - decision en faveur du passager.'];
    }
}
