<?php

namespace App\Controllers;

use App\Models\Trip;
use App\Models\Review;
use App\Models\User;
use App\Models\TripParticipant;
use App\Models\BaseModel;
use App\Core\Auth\AuthManager;
use App\Core\Mailer;
use App\Controllers\DriverController;
use Exception;

class TripController extends BaseController
{
    public function index()
    {
        // The custom calendar widget sends dates as "DD / MM / YYYY"
        $rawDate = trim($_GET['date'] ?? '');
        if (preg_match('#^(\d{2})\s*/\s*(\d{2})\s*/\s*(\d{4})$#', $rawDate, $m)) {
            $rawDate = "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        $filters = [
            'depart' => $_GET['depart'] ?? '',
            'arrivee' => $_GET['arrivee'] ?? '',
            'date' => $rawDate,
            'prix_max' => $_GET['prix_max'] ?? null,
            'note_min' => $_GET['note_min'] ?? null,
            'ecologique' => $_GET['ecologique'] ?? '',
            'duree_max' => $_GET['duree_max'] ?? null
        ];

        $hasSearched = !empty($filters['depart']) || !empty($filters['arrivee']) || !empty($filters['date']);
        $covoiturages = $hasSearched ? Trip::search($filters) : [];

        $nearestDate = null;
        if ($hasSearched && empty($covoiturages)) {
            $nearestDate = Trip::nearestDate($filters['depart'], $filters['arrivee']);
        }

        $this->render('trips/index', [
            'title' => 'Covoiturages - EcoRide',
            'covoiturages' => $covoiturages,
            'filters' => $filters,
            'hasSearched' => $hasSearched,
            'nearestDate' => $nearestDate,
            'isDriver' => !empty($_SESSION['user']['is_driver']),
            'isLoggedIn' => AuthManager::check(),
        ]);
    }

    public function show($id)
    {
        $covoiturage = Trip::findWithDetails($id);

        if (!$covoiturage) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Trajet non trouvé']);
            return;
        }

        $reviews = Review::byDriver($covoiturage['chauffeur_id']);

        $user_credit = 0;
        $isParticipating = false;
        if (AuthManager::check()) {
            $user = User::find(AuthManager::id());
            $user_credit = (int) ($user['credits'] ?? 0);
            $isParticipating = TripParticipant::isParticipating($id, AuthManager::id());
        }

        $credit_requis = (int) $covoiturage['price'];

        try {
            $driverPrefs = DriverController::getDriverPreferences((int) $covoiturage['chauffeur_id']);
        } catch (\Throwable $e) {
            $driverPrefs = [];
}

        $this->render('trips/show', [
            'title' => $covoiturage['ville_depart'] . ' → ' . $covoiturage['ville_arrivee'] . ' - EcoRide',
            'covoiturage' => $covoiturage,
            'reviews' => $reviews,
            'user_credit' => $user_credit,
            'credit_requis' => $credit_requis,
            'isParticipating' => $isParticipating,
            'driverPrefs' => $driverPrefs
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

            header('Location: /my-trips?success=Action effectuée avec succès');
            exit;
        }

        $isDriver = !empty($_SESSION['user']['is_driver']);
        $trajets_conduits = $isDriver ? Trip::byDriver($userId) : [];
        $participations = Trip::byPassenger($userId);

        $upcomingStatuses = ['scheduled', 'started'];
        $upcoming_conduits      = array_values(array_filter($trajets_conduits, fn($t) => in_array($t['status'], $upcomingStatuses)));
        $past_conduits          = array_values(array_filter($trajets_conduits, fn($t) => !in_array($t['status'], $upcomingStatuses)));
        $upcoming_participations = array_values(array_filter($participations, fn($t) => in_array($t['status'], $upcomingStatuses)));
        $past_participations    = array_values(array_filter($participations, fn($t) => !in_array($t['status'], $upcomingStatuses)));

        $this->render('trips/my-trips', [
            'title' => 'Mes Trajets - EcoRide',
            'upcoming_conduits'       => $upcoming_conduits,
            'past_conduits'           => $past_conduits,
            'upcoming_participations' => $upcoming_participations,
            'past_participations'     => $past_participations,
            'error' => $error
        ]);
    }

    private function handleCancelParticipation($tripId, $userId)
    {
        try {
            BaseModel::beginTransaction();

            TripParticipant::query(
                "DELETE FROM trip_participants WHERE trip_id = ? AND user_id = ?",
                [$tripId, $userId]
            );

            Trip::query(
                "UPDATE trips SET available_seats = available_seats + 1 WHERE trip_id = ?",
                [$tripId]
            );

            $trip = Trip::find($tripId);
            if ($trip) {
                User::addCredits($userId, (int) $trip['price'] + 2, 'refund', 'Annulation participation', $tripId);
            }

            BaseModel::commit();
        } catch (Exception $e) {
            BaseModel::rollback();
            error_log("Erreur annulation participation : " . $e->getMessage());
        }
    }

    private function handleUpdateTripStatus($tripId, $userId, $newStatus)
    {
        $validStatuses = ['started', 'completed', 'cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            return;
        }

        $trip = Trip::find($tripId);
        if (!$trip || $trip['chauffeur_id'] != $userId) {
            return;
        }

        if ($newStatus === 'started' && $trip['status'] !== 'scheduled') return;
        if ($newStatus === 'completed' && $trip['status'] !== 'started') return;
        if ($newStatus === 'cancelled' && !in_array($trip['status'], ['scheduled', 'started'])) return;

        try {
            BaseModel::beginTransaction();

            Trip::update($tripId, ['status' => $newStatus]);

            $participants = TripParticipant::byTrip($tripId);

            if ($newStatus === 'cancelled') {
                foreach ($participants as $p) {
                    User::addCredits($p['user_id'], (int) $trip['price'], 'refund', 'Remboursement annulation trajet', $tripId);
                    $passenger = User::find($p['user_id']);
                    if ($passenger) {
                        Mailer::send(
                            $passenger['email'],
                            "Trajet annulé - EcoRide",
                            "Bonjour {$passenger['username']},\n\nLe trajet #{$tripId} a été annulé par le chauffeur.\nVous avez été remboursé de {$trip['price']} crédits.\n\nEcoRide"
                        );
                    }
                }
                User::addCredits($userId, 2, 'refund', 'Remboursement frais plateforme', $tripId);
            }

            if ($newStatus === 'completed') {
                foreach ($participants as $p) {
                    $passenger = User::find($p['user_id']);
                    if ($passenger) {
                        Mailer::send(
                            $passenger['email'],
                            "Votre trajet est terminé - EcoRide",
                            "Bonjour {$passenger['username']},\n\nVotre trajet #{$tripId} est terminé.\nRendez-vous dans votre espace pour valider le trajet et laisser un avis.\n\nEcoRide"
                        );
                    }
                }
            }

            BaseModel::commit();
        } catch (Exception $e) {
            BaseModel::rollback();
            error_log("Erreur mise à jour statut : " . $e->getMessage());
        }
    }

    private function handleValidateTrip($tripId, $userId)
    {
        $trip = Trip::find($tripId);
        if (!$trip || $trip['status'] !== 'completed') return;
        if (!TripParticipant::isParticipating($tripId, $userId)) return;

        $participant = TripParticipant::query(
            "SELECT * FROM trip_participants WHERE trip_id = ? AND user_id = ?",
            [$tripId, $userId]
        )->fetch();

        if (!$participant || $participant['status'] === 'validated') return;

        try {
            BaseModel::beginTransaction();

            TripParticipant::query(
                "UPDATE trip_participants SET status = 'validated' WHERE trip_id = ? AND user_id = ?",
                [$tripId, $userId]
            );

            User::addCredits($trip['chauffeur_id'], (int) $trip['price'], 'credit', 'Validation trajet passager', $tripId);

            BaseModel::commit();
        } catch (Exception $e) {
            BaseModel::rollback();
            error_log("Erreur validation trajet : " . $e->getMessage());
        }
    }

    private function handleReportProblem($tripId, $userId)
    {
        $trip = Trip::find($tripId);
        if (!$trip || $trip['status'] !== 'completed') return;
        if (!TripParticipant::isParticipating($tripId, $userId)) return;

        $comment = trim($_POST['problem_comment'] ?? '');

        try {
            $mongo = \App\Core\MongoDB::getInstance();
            $mongo->insertOne('trip_incidents', [
                'trip_id' => (int) $tripId,
                'reporter_id' => (int) $userId,
                'chauffeur_id' => (int) $trip['chauffeur_id'],
                'comment' => $comment,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            TripParticipant::query(
                "UPDATE trip_participants SET status = 'disputed' WHERE trip_id = ? AND user_id = ?",
                [$tripId, $userId]
            );
        } catch (Exception $e) {
            error_log("Erreur signalement : " . $e->getMessage());
        }
    }
}
