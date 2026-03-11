<?php

namespace App\Controllers;

use App\Core\Mailer;
use App\Core\MongoDB;
use App\Models\BaseModel;
use App\Models\User;

class ModeratorController extends BaseController
{
    public function index()
    {
        try {
            $pendingReviews = BaseModel::query(
                "SELECT r.id, r.rating, r.comment, r.status, r.created_at,
                        reviewer.username AS reviewer_name, reviewer.email AS reviewer_email,
                        driver.username AS driver_name
                 FROM reviews r
                 JOIN users reviewer ON reviewer.user_id = r.reviewer_id
                 JOIN users driver ON driver.user_id = r.driver_id
                 WHERE r.status = 'pending'
                 ORDER BY r.created_at DESC
                 LIMIT 50"
            )->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $pendingReviews = [];
        }

        $incidents = [];
        $resolvedIncidents = [];
        try {
            $mongo = MongoDB::getInstance();
            $allIncidents = array_merge(
                $mongo->find('trip_incidents', ['status' => 'pending']),
                $mongo->find('trip_incidents', ['status' => 'resolved'])
            );

            foreach ($allIncidents as &$incident) {
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
                    [$incident['reporter_id'], $incident['trip_id']]
                )->fetch();

                if ($tripInfo) {
                    $incident = array_merge($incident, $tripInfo);
                }
            }
            unset($incident);

            $incidents = array_values(array_filter($allIncidents, fn($i) => ($i['status'] ?? '') === 'pending'));
            $resolvedIncidents = array_values(array_filter($allIncidents, fn($i) => ($i['status'] ?? '') === 'resolved'));
        } catch (\Throwable $e) {
            // MongoDB optionnel
        }

        try {
            $contactMessages = BaseModel::query(
                "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 50"
            )->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $contactMessages = [];
        }

        $this->render('moderator/index', [
            'pendingReviews' => $pendingReviews,
            'incidents' => $incidents,
            'resolvedIncidents' => $resolvedIncidents,
            'contactMessages' => $contactMessages,
            'success' => $_SESSION['flash_success'] ?? '',
            'error' => $_SESSION['flash_error'] ?? '',
        ]);

        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function markMessageRead()
    {
        $id = (int) ($_POST['message_id'] ?? 0);
        if ($id > 0) {
            BaseModel::query("UPDATE contact_messages SET is_read = 1 WHERE id = ?", [$id]);
        }
        header('Location: /moderator#messages');
        exit;
    }

    public function approveReview()
    {
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            BaseModel::query("UPDATE reviews SET status = 'approved' WHERE id = ?", [$reviewId]);
            $_SESSION['flash_success'] = 'Avis approuve.';
        }
        header('Location: /moderator');
        exit;
    }

    public function rejectReview()
    {
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            BaseModel::query("UPDATE reviews SET status = 'rejected' WHERE id = ?", [$reviewId]);
            $_SESSION['flash_success'] = 'Avis rejete.';
        }
        header('Location: /moderator');
        exit;
    }

    public function resolveIncident()
    {
        $userModel = new User();
        $mailer = new Mailer();

        $tripId = (int) ($_POST['trip_id'] ?? 0);
        $reporterId = (int) ($_POST['reporter_id'] ?? 0);
        $creditDriver = ($_POST['credit_driver'] ?? '1') === '1';

        if ($tripId > 0 && $reporterId > 0) {
            try {
                $participantStatus = $creditDriver ? 'validated' : 'disputed';
                if (!in_array($participantStatus, ['validated', 'disputed'], true)) {
                    throw new \RuntimeException('Invalid participant status');
                }

                $mongo = MongoDB::getInstance();
                $mongo->updateWhere(
                    'trip_incidents',
                    ['$or' => [
                        ['trip_id' => $tripId, 'reporter_id' => $reporterId],
                        ['trip_id' => (string) $tripId, 'reporter_id' => (string) $reporterId],
                    ]],
                    [
                        'status' => 'resolved',
                        'resolved_at' => date('Y-m-d H:i:s'),
                        'decision' => $creditDriver ? 'favor_driver' : 'favor_passenger',
                    ]
                );

                BaseModel::query(
                    "UPDATE trip_participants
                     SET status = ?
                     WHERE trip_id = ? AND user_id = ?",
                    [$participantStatus, $tripId, $reporterId]
                );

                $trip = BaseModel::query(
                    "SELECT chauffeur_id, price
                     FROM trips
                     WHERE trip_id = ?
                     LIMIT 1",
                    [$tripId]
                )->fetch();

                if ($trip) {
                    $driverId = (int) $trip['chauffeur_id'];
                    $tripPrice = (int) $trip['price'];
                    $driver = $userModel->find($driverId);

                    if ($creditDriver) {
                        $userModel->addCredits(
                            $driverId,
                            $tripPrice,
                            'credit',
                            'Resolution incident - decision en faveur du chauffeur',
                            $tripId
                        );

                        if ($driver) {
                            $mailer->send(
                                $driver['email'],
                                'Incident resolu - EcoRide',
                                "Bonjour {$driver['username']},\n\nL incident signale sur le trajet #{$tripId} a ete examine. La decision est en votre faveur : vous avez ete credite du montant du trajet.\n\nEcoRide"
                            );
                        }

                        $_SESSION['flash_success'] = 'Incident resolu - decision en faveur du chauffeur.';
                    } else {
                        $passenger = $userModel->find($reporterId);
                        if ($passenger) {
                            $refund = $tripPrice + 2;
                            $userModel->addCredits(
                                $reporterId,
                                $refund,
                                'refund',
                                'Remboursement incident - decision en faveur du passager',
                                $tripId
                            );

                            $mailer->send(
                                $passenger['email'],
                                'Incident resolu - EcoRide',
                                "Bonjour {$passenger['username']},\n\nL incident sur le trajet #{$tripId} a ete examine. La decision est en votre faveur : vous avez ete rembourse de {$refund} credits.\n\nEcoRide"
                            );
                        }

                        if ($driver) {
                            $mailer->send(
                                $driver['email'],
                                'Incident resolu - EcoRide',
                                "Bonjour {$driver['username']},\n\nL incident signale sur le trajet #{$tripId} a ete examine. La decision est en faveur du passager : aucun credit ne vous a ete attribue pour ce trajet.\n\nEcoRide"
                            );
                        }

                        $_SESSION['flash_success'] = 'Incident resolu - decision en faveur du passager.';
                    }
                }
            } catch (\Throwable $e) {
                error_log('resolveIncident error: ' . $e->getMessage());
                $_SESSION['flash_error'] = 'Erreur: ' . $e->getMessage();
            }
        }

        header('Location: /moderator');
        exit;
    }
}
