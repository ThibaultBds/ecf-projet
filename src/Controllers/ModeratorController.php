<?php

namespace App\Controllers;

use App\Models\BaseModel;

class ModeratorController extends BaseController
{
    public function index()
    {
        try {
            $pendingReviews = BaseModel::query("
                SELECT r.id, r.rating, r.comment, r.status, r.created_at,
                       reviewer.username AS reviewer_name, reviewer.email AS reviewer_email,
                       driver.username AS driver_name
                FROM reviews r
                JOIN users reviewer ON reviewer.user_id = r.reviewer_id
                JOIN users driver ON driver.user_id = r.driver_id
                WHERE r.status = 'pending'
                ORDER BY r.created_at DESC
                LIMIT 50
            ")->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $pendingReviews = [];
            $error = "Erreur lors du chargement des avis.";
        }

        $this->render('moderator/index', [
            'pendingReviews' => $pendingReviews,
            'success' => $_SESSION['flash_success'] ?? '',
            'error' => $error ?? ($_SESSION['flash_error'] ?? '')
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function approveReview()
    {
        $reviewId = (int) ($_POST['review_id'] ?? 0);

        if ($reviewId > 0) {
            BaseModel::query(
                "UPDATE reviews SET status = 'approved' WHERE id = ?",
                [$reviewId]
            );
            $_SESSION['flash_success'] = 'Avis approuvé.';
        }

        header('Location: /moderator');
        exit;
    }

    public function rejectReview()
    {
        $reviewId = (int) ($_POST['review_id'] ?? 0);

        if ($reviewId > 0) {
            BaseModel::query(
                "UPDATE reviews SET status = 'rejected' WHERE id = ?",
                [$reviewId]
            );
            $_SESSION['flash_success'] = 'Avis rejeté.';
        }

        header('Location: /moderator');
        exit;
    }
}
