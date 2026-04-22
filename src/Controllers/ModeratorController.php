<?php

namespace App\Controllers;

use App\Core\MongoDB;
use App\Repositories\ContactRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\TripRepository;
use App\Services\ModeratorService;

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
        $tripId       = (int) ($_POST['trip_id'] ?? 0);
        $reporterId   = (int) ($_POST['reporter_id'] ?? 0);
        $creditDriver = ($_POST['credit_driver'] ?? '1') === '1';

        try {
            $result = (new ModeratorService())->resolveIncident($tripId, $reporterId, $creditDriver);

            if ($result['success'] ?? false) {
                $_SESSION['flash_success'] = $result['message'] ?? 'Incident resolu.';
            } else {
                $_SESSION['flash_error'] = $result['message'] ?? 'Erreur lors de la resolution.';
            }
        } catch (\Throwable $e) {
            error_log('resolveIncident error: ' . $e->getMessage());
            $_SESSION['flash_error'] = 'Erreur: ' . $e->getMessage();
        }

        header('Location: /moderator');
        exit;
    }
}
