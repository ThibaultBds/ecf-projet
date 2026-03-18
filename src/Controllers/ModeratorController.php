<?php

namespace App\Controllers;

use App\Core\Mailer;
use App\Core\MongoDB;
use App\Repositories\ContactRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\TripRepository;
use App\Repositories\UserRepository;

class ModeratorController extends BaseController
{
    public function index()
    {
        $reviewRepo  = new ReviewRepository();
        $contactRepo = new ContactRepository();

        try {
            $pendingReviews = $reviewRepo->pendingReviews();
        } catch (\Throwable $e) {
            $pendingReviews = [];
        }

        $incidents         = [];
        $resolvedIncidents = [];
        try {
            $tripRepo     = new TripRepository();
            $mongo        = MongoDB::getInstance();
            $allIncidents = array_merge(
                $mongo->find('trip_incidents', ['status' => 'pending']),
                $mongo->find('trip_incidents', ['status' => 'resolved'])
            );

            foreach ($allIncidents as &$incident) {
                $tripInfo = $tripRepo->findIncidentInfo((int) $incident['trip_id'], (int) $incident['reporter_id']);
                if ($tripInfo) {
                    $incident = array_merge($incident, $tripInfo);
                }
            }
            unset($incident);

            $incidents         = array_values(array_filter($allIncidents, fn($i) => ($i['status'] ?? '') === 'pending'));
            $resolvedIncidents = array_values(array_filter($allIncidents, fn($i) => ($i['status'] ?? '') === 'resolved'));
        } catch (\Throwable $e) {
            // MongoDB optionnel
        }

        try {
            $contactMessages = $contactRepo->findAll();
        } catch (\Throwable $e) {
            $contactMessages = [];
        }

        $this->render('moderator/index', [
            'pendingReviews'    => $pendingReviews,
            'incidents'         => $incidents,
            'resolvedIncidents' => $resolvedIncidents,
            'contactMessages'   => $contactMessages,
            'success'           => $_SESSION['flash_success'] ?? '',
            'error'             => $_SESSION['flash_error'] ?? '',
        ]);

        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function markMessageRead()
    {
        $id = (int) ($_POST['message_id'] ?? 0);
        if ($id > 0) {
            (new ContactRepository())->markAsRead($id);
        }
        header('Location: /moderator#messages');
        exit;
    }

    public function approveReview()
    {
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            (new ReviewRepository())->updateStatus($reviewId, 'approved');
            $_SESSION['flash_success'] = 'Avis approuve.';
        }
        header('Location: /moderator');
        exit;
    }

    public function rejectReview()
    {
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            (new ReviewRepository())->updateStatus($reviewId, 'rejected');
            $_SESSION['flash_success'] = 'Avis rejete.';
        }
        header('Location: /moderator');
        exit;
    }

    public function resolveIncident()
    {
        $userRepo = new UserRepository();
        $tripRepo = new TripRepository();
        $mailer   = new Mailer();

        $tripId       = (int) ($_POST['trip_id'] ?? 0);
        $reporterId   = (int) ($_POST['reporter_id'] ?? 0);
        $creditDriver = ($_POST['credit_driver'] ?? '1') === '1';

        if ($tripId > 0 && $reporterId > 0) {
            try {
                $participantStatus = $creditDriver ? 'validated' : 'disputed';

                $mongo = MongoDB::getInstance();
                $mongo->updateWhere(
                    'trip_incidents',
                    ['$or' => [
                        ['trip_id' => $tripId, 'reporter_id' => $reporterId],
                        ['trip_id' => (string) $tripId, 'reporter_id' => (string) $reporterId],
                    ]],
                    [
                        'status'      => 'resolved',
                        'resolved_at' => date('Y-m-d H:i:s'),
                        'decision'    => $creditDriver ? 'favor_driver' : 'favor_passenger',
                    ]
                );

                $pdo  = \App\Core\Database::getInstance()->getConnection();
                $stmt = $pdo->prepare("UPDATE trip_participants SET status = ? WHERE trip_id = ? AND user_id = ?");
                $stmt->execute([$participantStatus, $tripId, $reporterId]);

                $trip = $tripRepo->findById($tripId);

                if ($trip) {
                    $driverId  = $trip->chauffeurId;
                    $tripPrice = (int) $trip->price;
                    $driver    = $userRepo->findById($driverId);

                    if ($creditDriver) {
                        $userRepo->addCredits($driverId, $tripPrice, 'credit', 'Resolution incident - decision en faveur du chauffeur', $tripId);
                        if ($driver) {
                            $mailer->send($driver->email, 'Incident resolu - EcoRide',
                                "Bonjour {$driver->username},\n\nL incident signale sur le trajet #{$tripId} a ete examine. La decision est en votre faveur : vous avez ete credite du montant du trajet.\n\nEcoRide");
                        }
                        $_SESSION['flash_success'] = 'Incident resolu - decision en faveur du chauffeur.';
                    } else {
                        $passenger = $userRepo->findById($reporterId);
                        if ($passenger) {
                            $refund = $tripPrice + 2;
                            $userRepo->addCredits($reporterId, $refund, 'refund', 'Remboursement incident - decision en faveur du passager', $tripId);
                            $mailer->send($passenger->email, 'Incident resolu - EcoRide',
                                "Bonjour {$passenger->username},\n\nL incident sur le trajet #{$tripId} a ete examine. La decision est en votre faveur : vous avez ete rembourse de {$refund} credits.\n\nEcoRide");
                        }
                        if ($driver) {
                            $mailer->send($driver->email, 'Incident resolu - EcoRide',
                                "Bonjour {$driver->username},\n\nL incident signale sur le trajet #{$tripId} a ete examine. La decision est en faveur du passager : aucun credit ne vous a ete attribue pour ce trajet.\n\nEcoRide");
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
