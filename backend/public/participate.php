<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    die('{"success":false,"message":"Non connecté"}');
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die('{"success":false,"message":"Méthode non autorisée"}');
}

// Vérification CSRF
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('{"success":false,"message":"Token CSRF invalide"}');
}

$trip_id = (int)($_POST['trip_id'] ?? 0);
$credits_required = (int)($_POST['credits'] ?? 0);
$user_id = (int)($_SESSION['user']['id'] ?? 0);

if (!$trip_id || !$credits_required || !$user_id) {
    die('{"success":false,"message":"Données invalides"}');
}

// Solution simplifiée pour XAMPP local - pas de fichier de config externe
$db_password = getenv('DB_PASSWORD') ?: ''; // Récupère depuis l'environnement ou vide

$pdo = new PDO('mysql:host=localhost;dbname=ecoride', 'root', $db_password);

$pdo->beginTransaction();

$stmt = $pdo->prepare("SELECT credits FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_credits = $stmt->fetchColumn();

if ($user_credits < $credits_required) {
    $pdo->rollBack();
    die('{"success":false,"message":"Crédits insuffisants"}');
}

$stmt = $pdo->prepare("SELECT places_restantes, status, created_by FROM trips WHERE id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip || $trip['status'] != 'planifie' || $trip['places_restantes'] <= 0) {
    $pdo->rollBack();
    die('{"success":false,"message":"Trajet non disponible"}');
}

if ($trip['created_by'] == $user_id) {
    $pdo->rollBack();
    die('{"success":false,"message":"Vous ne pouvez pas participer à votre propre trajet"}');
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM trip_participants WHERE trip_id = ? AND user_id = ?");
$stmt->execute([$trip_id, $user_id]);
if ($stmt->fetchColumn() > 0) {
    $pdo->rollBack();
    die('{"success":false,"message":"Vous participez déjà à ce trajet"}');
}

$pdo->prepare("UPDATE users SET credits = credits - ? WHERE id = ?")->execute([$credits_required, $user_id]);
$pdo->prepare("INSERT INTO trip_participants (trip_id, user_id, created_at) VALUES (?, ?, NOW())")->execute([$trip_id, $user_id]);
$pdo->prepare("UPDATE trips SET places_restantes = places_restantes - 1 WHERE id = ?")->execute([$trip_id]);

$pdo->commit();

$new_credits = $user_credits - $credits_required;
$_SESSION['user']['credits'] = $new_credits;

echo '{"success":true,"message":"Participation confirmée !","new_credits":' . $new_credits . '}';
?>



