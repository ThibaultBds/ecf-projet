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
    <title>Profil - EcoRide</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header class="container-header">
    <h1><a href="index.php">Mon Profil</a></h1>
</header>

<main class="login-container">
    <h2>Bienvenue, <?= htmlspecialchars($userData['pseudo'] ?? $user['email']) ?></h2>
    <?php if (!empty($error)): ?><div class="message-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="profile-box">
        <p><strong>Email :</strong> <?= htmlspecialchars($userData['email']) ?></p>
        <p><strong>Pseudo :</strong> <?= htmlspecialchars($userData['pseudo']) ?></p>
        <p><strong>Rôle :</strong> <?= htmlspecialchars($userData['role']) ?></p>
        <p><strong>Statut :</strong> <?= htmlspecialchars($userData['status']) ?></p>
        <p><strong>Crédits :</strong> <?= (int)$userData['credits'] ?></p>
    </div>

    <h3>Vos derniers trajets</h3>
    <?php if (!$myTrips): ?>
        <p>Aucun trajet.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($myTrips as $t): ?>
                <li><?= $t['ville_depart'] ?> → <?= $t['ville_arrivee'] ?> (<?= $t['status'] ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <a href="index.php">← Retour à l’accueil</a>
</main>

<!-- Nav dynamique -->
<script>
window.ecorideUser = <?php
    echo !empty($_SESSION['user'])
        ? json_encode($_SESSION['user'], JSON_UNESCAPED_UNICODE)
        : 'null';
?>;
</script>
<script src="../assets/js/navbar.js"></script>
<script>
if (typeof renderMenu === 'function') renderMenu(window.ecorideUser);
</script>
</body>
</html>
