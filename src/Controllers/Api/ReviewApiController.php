<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\Review;
use App\Models\TripParticipant;
use App\Core\Auth\AuthManager;
use Exception;

class ReviewApiController extends BaseController
{
    /**
     * Soumettre un avis
     */
    public function submit()
    {
        $userId = AuthManager::id();
        $tripId = (int) ($_POST['trip_id'] ?? 0);
        $driverId = (int) ($_POST['driver_id'] ?? 0);
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        // Validation
        if ($rating < 1 || $rating > 5) {
            $_SESSION['flash_error'] = 'La note doit être entre 1 et 5.';
            header('Location: /my-trips');
            exit;
        }

        if (strlen($comment) > 1000) {
            $_SESSION['flash_error'] = 'Le commentaire ne doit pas dépasser 1000 caractères.';
            header('Location: /my-trips');
            exit;
        }

        // Vérifier que l'utilisateur participe et n'a pas déjà noté
        if (!TripParticipant::isParticipating($tripId, $userId)) {
            $_SESSION['flash_error'] = 'Vous ne participez pas à ce trajet.';
            header('Location: /my-trips');
            exit;
        }

        if (TripParticipant::hasReviewed($tripId, $userId)) {
            $_SESSION['flash_error'] = 'Vous avez déjà noté ce trajet.';
            header('Location: /my-trips');
            exit;
        }

        try {
            // Créer l'avis
            Review::create([
                'trip_id' => $tripId,
                'reviewer_id' => $userId,
                'driver_id' => $driverId,
                'rating' => $rating,
                'comment' => $comment,
                'status' => 'pending'
            ]);

            header('Location: /my-trips?success=Avis envoyé avec succès !');
            exit;
        } catch (Exception $e) {
            error_log("Erreur soumission avis : " . $e->getMessage());
            $_SESSION['flash_error'] = 'Erreur lors de l\'envoi de l\'avis.';
            header('Location: /my-trips');
            exit;
        }
    }
}
