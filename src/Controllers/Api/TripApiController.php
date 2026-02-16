<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use App\Models\BaseModel;
use App\Core\Auth\AuthManager;
use Exception;
use App\Core\Mailer;


class TripApiController extends BaseController
{
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
            $fraisPlateforme = 2;
            $total = $prix + $fraisPlateforme;

            if ($user['credits'] < $total) {
                echo json_encode(['success' => false, 'message' => 'Crédits insuffisants.']);
                return;
            }

            BaseModel::beginTransaction();

            try {

                if (!User::deductCredits(
                    $userId,
                    $prix,
                    'debit',
                    'Participation au trajet',
                    $id
                )) {
                    throw new Exception("Erreur débit prix");
                }

                if (!User::deductCredits(
                    $userId,
                    $fraisPlateforme,
                    'platform_fee',
                    'Frais plateforme',
                    $id
                )) {
                    throw new Exception("Erreur frais plateforme");
                }

                if (!User::addCredits(
                    $trip['chauffeur_id'],
                    $prix,
                    'trip_income',
                    'Revenu trajet',
                    $id
                )) {
                    throw new Exception("Erreur crédit chauffeur");
                }

                TripParticipant::create([
                    'trip_id' => $id,
                    'user_id' => $userId
                ]);

                Trip::query(
                    "UPDATE trips SET available_seats = available_seats - 1 WHERE trip_id = ?",
                    [$id]
                );

                BaseModel::commit();

                // Reload credits from DB instead of doing arithmetic in session
                $updatedUser = User::find($userId);
                $newCredits = (int) $updatedUser['credits'];
                $_SESSION['user']['credits'] = $newCredits;

                echo json_encode([
                    'success' => true,
                    'message' => 'Participation confirmée !',
                    'new_credits' => $newCredits
                ]);

            } catch (Exception $e) {
                BaseModel::rollback();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Erreur participation : " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erreur technique, réessayez plus tard.'
            ]);
        }
    }

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

            try {

                Trip::update($id, ['status' => 'cancelled']);

                $participants = TripParticipant::byTrip($id);

                foreach ($participants as $p) {

                User::addCredits(
                $p['user_id'],
                (int) $trip['price'],
                'refund',
                'Remboursement annulation',
                $id
                );

                $passenger = User::find($p['user_id']);

                if ($passenger) {
                Mailer::send(
                $passenger['email'],
                "Trajet annulé - EcoRide",
                "Bonjour {$passenger['username']},\n\nLe trajet #{$id} a été annulé par le chauffeur.\nVous avez été remboursé de {$trip['price']} crédits.\n\nEcoRide"
            );
        }
    }


                User::addCredits(
                    $userId,
                    2,
                    'platform_refund',
                    'Remboursement frais plateforme',
                    $id
                );

                BaseModel::commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Trajet annulé. ' . count($participants) . ' passager(s) remboursé(s).'
                ]);

            } catch (Exception $e) {
                BaseModel::rollback();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Erreur annulation : " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erreur technique.']);
        }
    }
}
