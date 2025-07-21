<?php
session_start();

// Log de la déconnexion
try {
    if (isset($_SESSION['user'])) {
        require_once __DIR__ . '/../../../backend/config/autoload.php';
        useClass('Database');
        
        $pdo = getDatabase();
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user']['id'],
            'Déconnexion',
            'Utilisateur déconnecté',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
    }
} catch (Exception $e) {
    // Log silencieux, ne pas bloquer la déconnexion
}

session_unset();
session_destroy();
header('Location: index.php?msg=logout');
exit();
?>
header('Location: index.php?msg=logout');
exit();
?>



