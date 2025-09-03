<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/ecoride/backend/config/autoload.php';
useClass('Database');

require_once $_SERVER['DOCUMENT_ROOT'] . '/ecoride/backend/config/guard.php';
requireLogin();

$type = $_SESSION['user']['type'] ?? 'utilisateur';
if ($type === 'admin') {
    header('Location: admin.php'); exit;
}
if ($type === 'moderateur') {
    header('Location: modo.php'); exit;
}

$user = $_SESSION['user'];

try {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT id, email, pseudo, role, status, credits FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("
        SELECT id, ville_depart, ville_arrivee, date_depart, status
        FROM trips WHERE chauffeur_id = ?
        ORDER BY date_depart DESC LIMIT 10
    ");
    $stmt->execute([$user['id']]);
    $myTrips = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    $userData = $user;
    $myTrips = [];
    $error = "Erreur lors du chargement du profil.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Pr
