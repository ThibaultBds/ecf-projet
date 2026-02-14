<?php

namespace App\Controllers;

use App\Models\Trip;
use App\Models\Review;
use App\Models\User;
use App\Models\TripParticipant;
use App\Models\BaseModel;
use App\Core\Auth\AuthManager;
use Exception;

class TripController extends BaseController
{
    /**
     * Liste des trajets avec recherche et filtres
     */
    public function index()
    {
        $filters = [
            'depart' => $_GET['depart'] ?? '',
            'arrivee' => $_GET['arrivee'] ?? '',
            'date' => $_GET['date'] ?? '',
            'prix_max' => $_GET['prix_max'] ?? null,
            'note_min' => $_GET['note_min'] ?? null,
            'ecologique' => $_GET['ecologique'] ?? ''
        ];

        $covoiturages = Trip::search($filters);

        $this->render('trips/index', [
            'title' => 'Covoiturages - EcoRide',
            'covoiturages' => $covoiturages,
            'filters' => $filters
        ]);
    }

    /**
     * Détails d'un trajet
     */
    public function show($id)
    {
        $covoiturage = Trip::findWithDetails($id);

        if (!$covoiturage) {
            http_response_code(404);
            $this->render('errors/404', ['title' => 'Trajet non trouvé']);
            return;
        }

        // Récupérer les avis du chauffeur
        $reviews = Review::byDriver($covoiturage['chauffeur_id']);

        // Crédits de l'utilisateur connecté
        $user_credit = 0;
        $isParticipating = false;
        if (AuthManager::check()) {
            $user = User::find(AuthManager::id());
            $user_credit = (int) ($user['credits'] ?? 0);
            $isParticipating = TripParticipant::isParticipating($id, AuthManager::id());
        }

        $credit_requis = (int) $covoiturage['price'];

        $this->render('trips/show', [
            'title' => $covoiturage['ville_depart'] . ' → ' . $covoiturage['ville_arrivee'] . ' - EcoRide',
            'covoiturage' => $covoiturage,
            'reviews' => $reviews,
            'user_credit' => $user_credit,
            'credit_requis' => $credit_requis,
            'isParticipating' => $isParticipating
        ]);
    }

    /**
     * Mes trajets (chauffeur + passager)
     */
    public function myTrips()
    {
        $userId = AuthManager::id();
        $error = '';

        // Traiter les actions POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $tripId = (int) ($_POST['trip_id'] ?? 0);

            if ($action === 'cancel_participation') {
                $this->handleCancelParticipation($tripId, $userId);
            } elseif ($action === 'update_trip_status') {
                $this->handleUpdateTripStatus($tripId, $userId, $_POST['status'] ?? '');
            }

            header('Location: /my-trips?success=Action effectuée avec succès');
            exit;
        }

        $trajets_conduits = Trip::byDriver($userId);
        $participations = Trip::byPassenger($userId);

        $this->render('trips/my-trips', [
            'title' => 'Mes Trajets - EcoRide',
            'trajets_conduits' => $trajets_conduits,
            'participations' => $participations,
            'error' => $error
        ]);
    }

    /**
     * Annuler la participation d'un passager
     */
    private function handleCancelParticipation($tripId, $userId)
    {
        try {
            BaseModel::beginTransaction();

            // Supprimer la participation
            TripParticipant::query(
                "DELETE FROM trip_participants WHERE trip_id = ? AND user_id = ?",
                [$tripId, $userId]
            );

            // Remettre une place
            Trip::query(
                "UPDATE trips SET available_seats = available_seats + 1 WHERE trip_id = ?",
                [$tripId]
            );

            // Rembourser les crédits
            $trip = Trip::find($tripId);
            if ($trip) {
                User::addCredits($userId, (int) $trip['price']);
            }

            BaseModel::commit();
        } catch (Exception $e) {
            BaseModel::rollback();
            error_log("Erreur annulation participation : " . $e->getMessage());
        }
    }

    /**
     * Mettre à jour le statut d'un trajet
     */
    private function handleUpdateTripStatus($tripId, $userId, $newStatus)
    {
        $validStatuses = ['completed', 'cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            return;
        }

        $trip = Trip::find($tripId);
        if (!$trip || $trip['chauffeur_id'] != $userId) {
            return;
        }

        try {
            BaseModel::beginTransaction();

            Trip::update($tripId, ['status' => $newStatus]);

            // Si annulation, rembourser les passagers
            if ($newStatus === 'cancelled') {
                $participants = TripParticipant::byTrip($tripId);
                foreach ($participants as $p) {
                    User::addCredits($p['user_id'], (int) $trip['price']);
                }
                // Rembourser les frais plateforme au chauffeur
                User::addCredits($userId, 2);
            }

            BaseModel::commit();
        } catch (Exception $e) {
            BaseModel::rollback();
            error_log("Erreur mise à jour statut : " . $e->getMessage());
        }
    }
}
