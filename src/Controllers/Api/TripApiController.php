<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use App\Models\BaseModel;
use App\Core\Auth\AuthManager;
use Exception;

class TripApiController extends BaseController
{
    /**
     * Participer à un trajet (AJAX)
     */
    public function join($id)
    {
        header('Content-Type: application/json');

        $userId = AuthManager::id();

        try {
            $trip = Trip::find($id);
            $user = User::find($userId);

            if (!$trip) {
                echo json_encode(['success' => false, 'message' => 'Trajet non trouvé.']);
                return;
            }

            if ($trip['chauffeur_id'] == $userId) {
                echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas participer à votre propre trajet.']);
                return;
            }

            if (TripParticipant::isParticipating($id, $userId)) {
                echo json_encode(['success' => false, 'message' => 'Vous participez déjà à ce trajet.']);
                return;
            }

            if ($trip['available_seats'] <= 0) {
                echo json_encode(['success' => false, 'message' => 'Plus de places disponibles.']);
                return;
            }

            $prix = (int) $trip['price'];
            if ($user['credits'] < $prix) {
                echo json_encode(['success' => false, 'message' => 'Crédits insuffisants.']);
                return;
            }

            // Transaction
            BaseModel::beginTransaction();

            User::deductCredits($userId, $prix);

            TripParticipant::create([
                'trip_id' => $id,
                'user_id' => $userId
            ]);

            Trip::query(
                "UPDATE trips SET available_seats = available_seats - 1 WHERE trip_id = ?",
                [$id]
            );

            BaseModel::commit();

            $newCredits = (int) $user['credits'] - $prix;
            $_SESSION['user']['credits'] = $newCredits;

            echo json_encode([
                'success' => true,
                'message' => 'Participation confirmée !',
                'new_credits' => $newCredits
            ]);
        } catch (Exception $e) {
            BaseModel::rollback();
            error_log("Erreur participation : " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur technique, réessayez plus tard.']);
        }
    }

    /**
     * Annuler un trajet (chauffeur)
     */
    public function cancel($id)
    {
        header('Content-Type: application/json');

        $userId = AuthManager::id();

        try {
            $trip = Trip::find($id);

            if (!$trip || $trip['chauffeur_id'] != $userId) {
                echo json_encode(['success' => false, 'message' => 'Trajet non trouvé ou non autorisé.']);
                return;
            }

            if ($trip['status'] !== 'scheduled') {
                echo json_encode(['success' => false, 'message' => 'Ce trajet ne peut plus être annulé.']);
                return;
            }

            BaseModel::beginTransaction();

            Trip::update($id, ['status' => 'cancelled']);

            // Rembourser les passagers
            $participants = TripParticipant::byTrip($id);
            foreach ($participants as $p) {
                User::addCredits($p['user_id'], (int) $trip['price']);
            }

            // Rembourser frais plateforme au chauffeur
            User::addCredits($userId, 2);

            BaseModel::commit();

            echo json_encode([
                'success' => true,
                'message' => 'Trajet annulé. ' . count($participants) . ' passager(s) remboursé(s).'
            ]);
        } catch (Exception $e) {
            BaseModel::rollback();
            echo json_encode(['success' => false, 'message' => 'Erreur technique.']);
        }
    }
}
