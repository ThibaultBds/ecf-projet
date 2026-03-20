<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Core\Auth\AuthManager;
use App\Repositories\ReviewRepository;
use App\Repositories\TripParticipantRepository;
use Exception;

class ReviewApiController extends BaseController
{
    public function submit()
    {
        $userId          = AuthManager::id();
        $participantRepo = new TripParticipantRepository();
        $reviewRepo      = new ReviewRepository();

        $tripId  = (int) ($_POST['trip_id'] ?? 0);
        $driverId = (int) ($_POST['driver_id'] ?? 0);
        $rating  = (int) ($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $_SESSION['flash_error'] = 'La note doit etre entre 1 et 5.';
            header('Location: /my-trips');
            exit;
        }

        if (strlen($comment) > 1000) {
            $_SESSION['flash_error'] = 'Le commentaire ne doit pas depasser 1000 caracteres.';
            header('Location: /my-trips');
            exit;
        }

        if (!$participantRepo->isParticipating($tripId, $userId)) {
            $_SESSION['flash_error'] = 'Vous ne participez pas a ce trajet.';
            header('Location: /my-trips');
            exit;
        }

        if ($participantRepo->hasReviewed($tripId, $userId)) {
            $_SESSION['flash_error'] = 'Vous avez deja note ce trajet.';
            header('Location: /my-trips');
            exit;
        }

        try {
            $reviewRepo->create([
                'trip_id'     => $tripId,
                'reviewer_id' => $userId,
                'driver_id'   => $driverId,
                'rating'      => $rating,
                'comment'     => $comment,
                'status'      => 'pending',
            ]);

            header('Location: /my-trips?success=Avis envoyé avec succès !');
            exit;
        } catch (Exception $e) {
            error_log('Erreur soumission avis : ' . $e->getMessage());
            $_SESSION['flash_error'] = "Erreur lors de l'envoi de l'avis.";
            header('Location: /my-trips');
            exit;
        }
    }
}
