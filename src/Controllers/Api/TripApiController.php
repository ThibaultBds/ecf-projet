<?php

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../Models/Trip.php';
require_once __DIR__ . '/../../Models/TripParticipant.php';
require_once __DIR__ . '/../../Models/User.php';
require_once __DIR__ . '/../../Core/Auth/AuthManager.php';

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

            if ($trip['places_restantes'] <= 0) {
                echo json_encode(['success' => false, 'message' => 'Plus de places disponibles.']);
                return;
            }

            $prix = (int) $trip['prix'];
            if ($user['credits'] < $prix) {
                echo json_encode(['success' => false, 'message' => 'Crédits insuffisants.']);
                return;
            }

            // Transaction
            BaseModel::beginTransaction();

            User::deductCredits($userId, $prix);

            TripParticipant::create([
                'trip_id' => $id,
                'passager_id' => $userId,
                'credits_utilises' => $prix
            ]);

            Trip::query(
                "UPDATE trips SET places_restantes = places_restantes - 1 WHERE id = ?",
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

            if ($trip['status'] !== 'planifie') {
                echo json_encode(['success' => false, 'message' => 'Ce trajet ne peut plus être annulé.']);
                return;
            }

            BaseModel::beginTransaction();

            Trip::update($id, ['status' => 'annule']);

            // Rembourser les passagers
            $participants = TripParticipant::byTrip($id);
            foreach ($participants as $p) {
                User::addCredits($p['passager_id'], (int) $trip['prix']);
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
