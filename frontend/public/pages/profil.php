<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/ecoride/backend/config/autoload.php';
useClass('Database');

require_once $_SERVER['DOCUMENT_ROOT'] . '/ecoride/backend/config/guard.php';
requireLogin(); // accès réservé aux utilisateurs connectés

$user = $_SESSION['user'];

try {
    $pdo = getDatabase();

    // Récupérer les infos de l’utilisateur connecté
    $stmt = $pdo->prepare("SELECT id, email, pseudo, role, status, credits FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Récupérer les trajets créés par l’utilisateur
    $stmt = $pdo->prepare("
        SELECT id, ville_depart, ville_arrivee, date_depart, status
        FROM trips
        WHERE chauffeur_id = ?
        ORDER BY date_depart DESC
        LIMIT 10
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
    <title>Profil - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="container-header">
    <h1>
        <a href="index.php" style="color:inherit;text-decoration:none;">
            Mon Profil
        </a>
    </h1>
</header>

<main class="login-container">
    <h2 class="title-login">Bienvenue, <?= htmlspecialchars($userData['pseudo'] ?? $user['email']) ?></h2>

    <?php if (!empty($error)): ?>
        <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="profile-box">
        <p><strong>Email :</strong> <?= htmlspecialchars($userData['email']) ?></p>
        <p><strong>Pseudo :</strong> <?= htmlspecialchars($userData['pseudo']) ?></p>
        <p><strong>Rôle :</strong> <?= htmlspecialchars($userData['role']) ?></p>
        <p><strong>Statut :</strong> <?= htmlspecialchars($userData['status']) ?></p>
        <p><strong>Crédits :</strong> <?= (int)$userData['credits'] ?></p>
    </div>

    <h3>Vos derniers trajets</h3>
    <?php if (!$myTrips): ?>
        <p>Vous n’avez pas encore créé de trajets.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($myTrips as $trip): ?>
                <li>
                    <?= htmlspecialchars($trip['ville_depart']) ?> →
                    <?= htmlspecialchars($trip['ville_arrivee']) ?>
                    le <?= htmlspecialchars($trip['date_depart']) ?>
                    (<?= htmlspecialchars($trip['status']) ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div style="margin-top:20px;">
        <a href="index.php">← Retour à l’accueil</a>
    </div>
</main>
</body>
</html>
