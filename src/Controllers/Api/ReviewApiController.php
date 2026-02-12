<?php

require_once __DIR__ . '/../BaseController.php';
require_once __DIR__ . '/../../Models/Review.php';
require_once __DIR__ . '/../../Models/TripParticipant.php';
require_once __DIR__ . '/../../Core/Auth/AuthManager.php';

class ReviewApiController extends BaseController
{
    /**
     * Soumettre un avis
     */
    public function submit()
    {
        $userId = AuthManager::id();
        $tripId = (int) ($_POST['trip_id'] ?? 0);
        $reviewedId = (int) ($_POST['reviewed_id'] ?? 0);
        $note = (int) ($_POST['note'] ?? 0);
        $commentaire = trim($_POST['commentaire'] ?? '');
        $isProblem = isset($_POST['is_problem']);

        // Validation
        if ($note < 1 || $note > 5) {
            $_SESSION['flash_error'] = 'La note doit être entre 1 et 5.';
            header('Location: /my-trips');
            exit;
        }

        if (strlen($commentaire) > 1000) {
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
            BaseModel::beginTransaction();

            // Créer l'avis
            Review::create([
                'trip_id' => $tripId,
                'reviewer_id' => $userId,
                'reviewed_id' => $reviewedId,
                'note' => $note,
                'commentaire' => $commentaire,
                'status' => 'en_attente'
            ]);

            // Si problème signalé, créer un signalement
            if ($isProblem) {
                BaseModel::query(
                    "INSERT INTO reports (trip_id, user_id, type, message, status) VALUES (?, ?, 'avis', ?, 'ouvert')",
                    [$tripId, $userId, $commentaire]
                );
            }

            // Marquer comme noté
            TripParticipant::markReviewed($tripId, $userId);

            BaseModel::commit();

            header('Location: /my-trips?success=Avis envoyé avec succès !');
            exit;
        } catch (Exception $e) {
            BaseModel::rollback();
            error_log("Erreur soumission avis : " . $e->getMessage());
            $_SESSION['flash_error'] = 'Erreur lors de l\'envoi de l\'avis.';
            header('Location: /my-trips');
            exit;
        }
    }
}
