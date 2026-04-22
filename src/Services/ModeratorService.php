<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Mailer;
use App\Core\MongoDB;
use App\Repositories\TripRepository;
use App\Repositories\UserRepository;

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
        $mongo->updateWhere(
            'trip_incidents',
            ['$or' => [
                ['trip_id' => $tripId, 'reporter_id' => $reporterId],
                ['trip_id' => (string) $tripId, 'reporter_id' => (string) $reporterId],
            ]],
            [
                'status' => 'resolved',
                'resolved_at' => date('Y-m-d H:i:s'),
                'decision' => $creditDriver ? 'favor_driver' : 'favor_passenger',
            ]
        );

        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("UPDATE trip_participants SET status = ? WHERE trip_id = ? AND user_id = ?");
        $stmt->execute([$participantStatus, $tripId, $reporterId]);

        $driverId = $trip->chauffeurId;
        $tripPrice = (int) $trip->price;
        $driver = $this->userRepository->findById($driverId);

        if ($creditDriver) {
            $this->userService->creditCredits($driverId, $tripPrice, 'credit', 'Resolution incident - decision en faveur du chauffeur', $tripId);

            if ($driver) {
                $this->mailer->send(
                    $driver->email,
                    'Incident resolu - EcoRide',
                    "Bonjour {$driver->username},\n\nL incident signale sur le trajet #{$tripId} a ete examine. La decision est en votre faveur : vous avez ete credite du montant du trajet.\n\nEcoRide"
                );
            }

            return ['success' => true, 'message' => 'Incident resolu - decision en faveur du chauffeur.'];
        }

        $passenger = $this->userRepository->findById($reporterId);
        if ($passenger) {
            $refund = $tripPrice + 2;
            $this->userService->creditCredits($reporterId, $refund, 'refund', 'Remboursement incident - decision en faveur du passager', $tripId);
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
