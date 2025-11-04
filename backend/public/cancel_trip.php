<?php
session_start();
header('Content-Type: application/json');

require_once '../config/autoload.php';
useClass('Database');

// Vérifier l'authentification
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

$user_id = $_SESSION['user']['id'];
$trip_id = isset($_POST['trip_id']) ? intval($_POST['trip_id']) : 0;

if ($trip_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de trajet invalide']);
    exit();
}

try {
    $pdo = getDatabase();
    
    // Vérifier que le trajet appartient bien au chauffeur
    $stmt = $pdo->prepare("
        SELECT id, chauffeur_id, status, prix, places_totales, places_restantes
        FROM trips
        WHERE id = ? AND chauffeur_id = ?
        LIMIT 1
    ");
    $stmt->execute([$trip_id, $user_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$trip) {
        echo json_encode(['success' => false, 'message' => 'Trajet introuvable ou vous n\'êtes pas le chauffeur']);
        exit();
    }
    
    if ($trip['status'] !== 'planifie') {
        echo json_encode(['success' => false, 'message' => 'Ce trajet ne peut plus être annulé (statut: ' . $trip['status'] . ')']);
        exit();
    }
    
    // Commencer une transaction
    $pdo->beginTransaction();
    
    try {
        // 1. Changer le statut du trajet à 'annule'
        $stmt = $pdo->prepare("UPDATE trips SET status = 'annule' WHERE id = ?");
        $stmt->execute([$trip_id]);
        
        // 2. Rembourser les 2 crédits de frais plateforme au chauffeur
        $stmt = $pdo->prepare("UPDATE users SET credits = credits + 2 WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // 3. Récupérer les passagers qui ont réservé
        $stmt = $pdo->prepare("
            SELECT passager_id, credits_utilises
            FROM trip_participants
            WHERE trip_id = ?
        ");
        $stmt->execute([$trip_id]);
        $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 4. Rembourser chaque passager
        $total_refunded = 0;
        foreach ($participants as $participant) {
            $refund_amount = $participant['credits_utilises'];
            $stmt = $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
            $stmt->execute([$refund_amount, $participant['passager_id']]);
            $total_refunded += $refund_amount;
        }
        
        // 5. Logger l'action
        try {
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, details, ip_address)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                'Annulation trajet',
                "Trajet #$trip_id annulé - " . count($participants) . " passagers remboursés",
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        } catch (Exception $e) {
            // Log non critique, on continue
        }
        
        // Valider la transaction
        $pdo->commit();
        
        $message = "Trajet annulé avec succès ! Vous avez été remboursé de 2 crédits.";
        if (count($participants) > 0) {
            $message .= " " . count($participants) . " passager(s) ont été remboursés.";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'refunded_passengers' => count($participants),
            'total_refunded' => $total_refunded
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('[CANCEL_TRIP][ERR] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'annulation du trajet. Veuillez réessayer.'
    ]);
}
?>
