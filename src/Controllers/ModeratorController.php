<?php

namespace App\Controllers;

use App\Models\BaseModel;
use App\Core\MongoDB;
use App\Core\Mailer;
use App\Models\User;


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
        }

        $incidents = [];
        try {
            $mongo = MongoDB::getInstance();
            $incidents = $mongo->find('trip_incidents', ['status' => 'pending']);

            foreach ($incidents as &$inc) {
                $tripInfo = BaseModel::query(
                    "SELECT t.trip_id, t.departure_datetime, t.arrival_datetime,
                            cd.name AS ville_depart, ca.name AS ville_arrivee,
                            driver.username AS driver_name, driver.email AS driver_email,
                            reporter.username AS reporter_name, reporter.email AS reporter_email
                     FROM trips t
                     JOIN cities cd ON t.city_depart_id = cd.city_id
                     JOIN cities ca ON t.city_arrival_id = ca.city_id
                     JOIN users driver ON t.chauffeur_id = driver.user_id
                     JOIN users reporter ON reporter.user_id = ?
                     WHERE t.trip_id = ?",
                    [$inc['reporter_id'], $inc['trip_id']]
                )->fetch();

                if ($tripInfo) {
                    $inc = array_merge($inc, $tripInfo);
                }
            }
            unset($inc);
        } catch (\Throwable $e) {
            // MongoDB might not be available
        }

        $this->render('moderator/index', [
            'pendingReviews' => $pendingReviews,
            'incidents' => $incidents,
            'success' => $_SESSION['flash_success'] ?? '',
            'error' => $_SESSION['flash_error'] ?? ''
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

    public function resolveIncident()
{
    $tripId = (int) ($_POST['trip_id'] ?? 0);
    $reporterId = (int) ($_POST['reporter_id'] ?? 0);

    if ($tripId > 0 && $reporterId > 0) {
        try {
            $mongo = MongoDB::getInstance();
            $mongo->upsert(
                'trip_incidents',
                ['trip_id' => $tripId, 'reporter_id' => $reporterId],
                ['status' => 'resolved', 'resolved_at' => date('Y-m-d H:i:s')]
            );

            BaseModel::query(
                "UPDATE trip_participants
                 SET status = 'validated'
                 WHERE trip_id = ? AND user_id = ?",
                [$tripId, $reporterId]
            );

            $trip = BaseModel::query(
                "SELECT chauffeur_id, price
                 FROM trips
                 WHERE trip_id = ?
                 LIMIT 1",
                [$tripId]
            )->fetch();

            if ($trip) {

                User::addCredits(
                    $trip['chauffeur_id'],
                    (int)$trip['price'],
                    'credit',
                    'Résolution incident validée',
                    $tripId
                );

                $driver = User::find($trip['chauffeur_id']);

                if ($driver) {
                    Mailer::send(
                        $driver['email'],
                        "Incident résolu - EcoRide",
                        "Bonjour {$driver['username']},\n\nVotre incident pour le trajet #{$tripId} a été résolu. Vous avez été crédité.\n\nEcoRide"
                    );
                }
            }

            $_SESSION['flash_success'] = 'Incident résolu.';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erreur lors de la résolution.';
        }
    }

    header('Location: /moderator');
    exit;
}
}
