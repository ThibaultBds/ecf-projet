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
    $creditDriver = ($_POST['credit_driver'] ?? '1') === '1';

    if ($tripId > 0 && $reporterId > 0) {
        try {
            $validStatuses = ['validated', 'disputed'];
            $participantStatus = $creditDriver ? 'validated' : 'disputed';
            
            if (!in_array($participantStatus, $validStatuses, true)) {
                throw new \Exception("Invalid participant status: " . $participantStatus);
            }

            $mongo = MongoDB::getInstance();
            $mongo->upsert(
                'trip_incidents',
                ['trip_id' => $tripId, 'reporter_id' => $reporterId],
                [
                    'status' => 'resolved',
                    'resolved_at' => date('Y-m-d H:i:s'),
                    'decision' => $creditDriver ? 'favor_driver' : 'favor_passenger',
                ]
            );

            $stmt = BaseModel::query(
                "UPDATE trip_participants
                 SET status = ?
                 WHERE trip_id = ? AND user_id = ?",
                [$participantStatus, $tripId, $reporterId]
            );
            
            if ($stmt && $stmt->rowCount() === 0) {
                error_log("Warning: No participant found for trip_id=$tripId, user_id=$reporterId when resolving incident");
            }

            $trip = BaseModel::query(
                "SELECT chauffeur_id, price
                 FROM trips
                 WHERE trip_id = ?
                 LIMIT 1",
                [$tripId]
            )->fetch();

            if ($trip) {
                $driver = User::find($trip['chauffeur_id']);

                if ($creditDriver) {
                    User::addCredits(
                        $trip['chauffeur_id'],
                        (int)$trip['price'],
                        'credit',
                        'Résolution incident — décision en faveur du chauffeur',
                        $tripId
                    );

                    if ($driver) {
                        try {
                            Mailer::send(
                                $driver['email'],
                                "Incident résolu - EcoRide",
                                "Bonjour {$driver['username']},\n\nL'incident signalé sur le trajet #{$tripId} a été examiné. La décision est en votre faveur : vous avez été crédité du montant du trajet.\n\nEcoRide"
                            );
                        } catch (\Throwable $mailErr) {
                            error_log("Mailer error resolveIncident (favor driver): " . $mailErr->getMessage());
                        }
                    }

                    $_SESSION['flash_success'] = 'Incident résolu — décision en faveur du chauffeur (crédité).';
                } else {
                    if ($driver) {
                        try {
                            Mailer::send(
                                $driver['email'],
                                "Incident résolu - EcoRide",
                                "Bonjour {$driver['username']},\n\nL'incident signalé sur le trajet #{$tripId} a été examiné. La décision est en faveur du passager : aucun crédit ne vous a été attribué pour ce trajet.\n\nEcoRide"
                            );
                        } catch (\Throwable $mailErr) {
                            error_log("Mailer error resolveIncident (favor passenger): " . $mailErr->getMessage());
                        }
                    }

                    $_SESSION['flash_success'] = 'Incident résolu — décision en faveur du passager (chauffeur non crédité).';
                }
            }
        } catch (\Throwable $e) {
            error_log("resolveIncident error: " . $e->getMessage());
            $_SESSION['flash_error'] = 'Erreur : ' . $e->getMessage();
        }
    }

    header('Location: /moderator');
    exit;
}
}
