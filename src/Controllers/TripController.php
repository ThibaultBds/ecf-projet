<?php

namespace App\Controllers;

use App\Controllers\DriverController;
use App\Core\Auth\AuthManager;
use App\Core\Database;
use App\Core\Mailer;
use App\Models\Review;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use App\Services\TripService;
use Exception;

class TripController extends BaseController
{
    private TripService $tripService;

    public function __construct()
    {
        $this->tripService = new TripService();
    }

    public function index()
    {
        $filters = $this->tripService->buildSearchFiltersFromQuery($_GET);
        $searchData = $this->tripService->searchTrips($filters);

        $this->render('trips/index', [
            'title' => 'Covoiturages - EcoRide',
            'covoiturages' => $searchData['trips'],
            'filters' => $searchData['filters'],
            'hasSearched' => $searchData['hasSearched'],
            'nearestDate' => $searchData['nearestDate'],
            'isDriver' => !empty($_SESSION['user']['is_driver']),
            'isLoggedIn' => AuthManager::check(),
        ]);
    }

    public function search()
    {
        $filters = $this->tripService->buildSearchFiltersFromQuery($_GET);
        $covoiturages = $this->tripService->searchTripsForApi($filters);

        header('Content-Type: application/json');
        echo json_encode($covoiturages);
        exit;
    }

    public function show($id)
    {
        $covoiturage = $this->tripService->findTripWithDetails((int) $id);

        if (!$covoiturage) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Trajet non trouve']);
            return;
        }

        $reviewModel = new Review();
        $reviews = $reviewModel->byDriver($covoiturage['chauffeur_id']);

        $user_credit = 0;
        $isParticipating = false;
        if (AuthManager::check()) {
            $userModel = new User();
            $participantModel = new TripParticipant();
            $user = $userModel->find(AuthManager::id());
            $user_credit = (int) ($user['credits'] ?? 0);
            $isParticipating = $participantModel->isParticipating($id, AuthManager::id());
        }

        $credit_requis = (int) $covoiturage['price'];

        try {
            $driverPrefs = DriverController::getDriverPreferences((int) $covoiturage['chauffeur_id']);
        } catch (\Throwable $e) {
            $driverPrefs = [];
        }

        $this->render('trips/show', [
            'title' => $covoiturage['ville_depart'] . ' -> ' . $covoiturage['ville_arrivee'] . ' - EcoRide',
            'covoiturage' => $covoiturage,
            'reviews' => $reviews,
            'user_credit' => $user_credit,
            'credit_requis' => $credit_requis,
            'isParticipating' => $isParticipating,
            'driverPrefs' => $driverPrefs,
        ]);
    }

    public function myTrips()
    {
        $userId = AuthManager::id();
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $tripId = (int) ($_POST['trip_id'] ?? 0);

            if ($action === 'cancel_participation') {
                $this->handleCancelParticipation($tripId, $userId);
            } elseif ($action === 'update_trip_status') {
                $this->handleUpdateTripStatus($tripId, $userId, $_POST['status'] ?? '');
            } elseif ($action === 'validate_trip') {
                $this->handleValidateTrip($tripId, $userId);
            } elseif ($action === 'report_problem') {
                $this->handleReportProblem($tripId, $userId);
            }

            header('Location: /my-trips?success=Action effectuee avec succes');
            exit;
        }

        $isDriver = !empty($_SESSION['user']['is_driver']);
        $tripsData = $this->tripService->getMyTripsData($userId, $isDriver);

        // Recupere les incidents resolus pour ce passager (map trip_id => decision)
        $resolvedIncidents = [];
        try {
            $mongo = \App\Core\MongoDB::getInstance();
            $incidents = $mongo->find('trip_incidents', [
                'reporter_id' => $userId,
                'status' => 'resolved',
            ]);
            foreach ($incidents as $inc) {
                $resolvedIncidents[(int) $inc['trip_id']] = $inc['decision'] ?? '';
            }
        } catch (\Throwable $e) {
            // MongoDB indisponible: on affiche juste "Litige" sans detail.
        }

        $this->render('trips/my-trips', [
            'title' => 'Mes Trajets - EcoRide',
            'upcoming_conduits' => $tripsData['upcoming_conduits'],
            'past_conduits' => $tripsData['past_conduits'],
            'upcoming_participations' => $tripsData['upcoming_participations'],
            'past_participations' => $tripsData['past_participations'],
            'resolvedIncidents' => $resolvedIncidents,
            'error' => $error,
        ]);
    }

    private function handleCancelParticipation($tripId, $userId)
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

    private function handleUpdateTripStatus($tripId, $userId, $newStatus)
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
                        Mailer::send(
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
                        Mailer::send(
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

    private function handleValidateTrip($tripId, $userId)
    {
        $tripModel = new Trip();
        $participantModel = new TripParticipant();
        $userModel = new User();
        $pdo = Database::getInstance()->getConnection();

        $trip = $tripModel->find((int) $tripId);
        if (!$trip || $trip['status'] !== 'completed') return;
        if (!$participantModel->isParticipating((int) $tripId, (int) $userId)) return;

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
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Erreur validation trajet : " . $e->getMessage());
        }
    }

    private function handleReportProblem($tripId, $userId)
    {
        $tripModel = new Trip();
        $participantModel = new TripParticipant();
        $trip = $tripModel->find((int) $tripId);
        if (!$trip || $trip['status'] !== 'completed') return;
        if (!$participantModel->isParticipating((int) $tripId, (int) $userId)) return;

        $comment = trim($_POST['problem_comment'] ?? '');

        try {
            $mongo = \App\Core\MongoDB::getInstance();
            $mongo->insertOne('trip_incidents', [
                'trip_id' => (int) $tripId,
                'reporter_id' => (int) $userId,
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
}
