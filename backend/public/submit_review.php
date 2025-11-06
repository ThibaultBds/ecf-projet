<?php
session_start();
require_once 'config/autoload.php';
useClass('Database');

// Vérifie que l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: login_secure.php');
    exit();
}

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = getDatabase();

        // Validation sécurisée des champs
        $trip_id = isset($_POST['trip_id']) ? (int)$_POST['trip_id'] : 0;
        $reviewed_id = isset($_POST['reviewed_id']) ? (int)$_POST['reviewed_id'] : 0;
        $note = isset($_POST['note']) ? (int)$_POST['note'] : 0;
        $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';
        $is_problem = !empty($_POST['is_problem']);

        if ($trip_id <= 0 || $reviewed_id <= 0) {
            throw new Exception("Données invalides.");
        }
        if ($note < 1 || $note > 5) {
            throw new Exception("La note est invalide.");
        }

        // Optionnel : limiter la longueur du commentaire
        if (mb_strlen($commentaire) > 1000) {
            throw new Exception("Le commentaire est trop long.");
        }

        // Vérifier que l'utilisateur participe bien à ce trajet
        $stmt = $pdo->prepare("SELECT has_reviewed FROM trip_participants WHERE trip_id = ? AND user_id = ?");
        $stmt->execute([$trip_id, $user['id']]);
        $participant = $stmt->fetch();
        if (!$participant) {
            throw new Exception("Vous ne participez pas à ce trajet.");
        }
        if ($participant['has_reviewed']) {
            throw new Exception("Vous avez déjà laissé un avis pour ce trajet.");
        }

        // Transaction pour garantir la cohérence
        $pdo->beginTransaction();

        // Insérer l'avis
        $stmt = $pdo->prepare("INSERT INTO reviews (trip_id, reviewer_id, reviewed_id, note, commentaire, status) VALUES (?, ?, ?, ?, ?, 'en_attente')");
        $stmt->execute([$trip_id, $user['id'], $reviewed_id, $note, $commentaire]);

        // Si problème, créer un signalement
        if ($is_problem) {
            $details = "Problème signalé par " . htmlspecialchars($user['pseudo']) . " sur le trajet #$trip_id. Commentaire: " . $commentaire;
            $stmt = $pdo->prepare("INSERT INTO reports (trip_id, user_id, details, status) VALUES (?, ?, ?, 'ouvert')");
            $stmt->execute([$trip_id, $user['id'], $details]);
        }
        
        // Marquer la participation comme "avis laissé"
        $stmt = $pdo->prepare("UPDATE trip_participants SET has_reviewed = 1 WHERE trip_id = ? AND user_id = ?");
        $stmt->execute([$trip_id, $user['id']]);

        $pdo->commit();

    header('Location: /pages/mes_trajets.php?success=' . urlencode("Merci pour votre retour !"));
        exit();

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
    header('Location: /pages/mes_trajets.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}
?>


