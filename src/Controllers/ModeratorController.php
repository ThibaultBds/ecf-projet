<?php

require_once __DIR__ . '/../Models/BaseModel.php';

class ModeratorController extends BaseController
{
    public function index()
    {
        $pdo = Database::getInstance()->getConnection();

        try {
            $stmt = $pdo->query("
                SELECT r.id, r.type, r.message, r.status, r.created_at, u.email AS reporter
                FROM reports r
                LEFT JOIN users u ON u.id = r.user_id
                WHERE r.status IN ('ouvert','en_cours')
                ORDER BY r.created_at DESC
                LIMIT 50
            ");
            $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $reports = [];
            $error = "Erreur lors du chargement des signalements.";
        }

        $this->render('moderator/index', [
            'reports' => $reports,
            'error' => $error ?? ''
        ]);
    }
}
