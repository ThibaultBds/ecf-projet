<?php
session_start();

require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

require_once __DIR__ . '/../../../backend/config/guard.php';
requireLogin();

// Type/role de l'utilisateur
$type = $_SESSION['user']['type'] ?? ($_SESSION['user']['role'] ?? 'utilisateur');
// Redirections éventuelles si tu sépares les dashboards
if ($type === 'admin' || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Administrateur')) {
    header('Location: admin.php'); exit;
}
if ($type === 'moderateur' || (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'Moderateur')) {
    header('Location: modo.php'); exit;
}

// Unification pour le front
$auth = $_SESSION['auth'] ?? $_SESSION['user'] ?? $_SESSION['admin'] ?? null;
$user = $_SESSION['user'];

try {
    $pdo = getDatabase();

    $stmt = $pdo->prepare("SELECT id, email, pseudo, role, status, credits FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC) ?: $user;

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
    <!-- Chemin vers les assets publics depuis /frontend/private/user/ -->
    <link rel="stylesheet" href="../../public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
<header class="container-header">
    <h1>
        <a href="index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
            <span class="material-icons">eco</span> EcoRide
        </a>
    </h1>
    <nav id="navbar"></nav>
</header>

<!-- Session unifiée exposée au front -->
<script>
  window.ecorideUser = <?= $auth ? json_encode($auth, JSON_UNESCAPED_UNICODE) : 'null' ?>;
</script>

<main style="max-width:1000px;margin:40px auto;padding:0 20px;">
    <h2>Mon Profil</h2>

    <?php if (!empty($error)): ?>
      <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="profile-box">
        <p><strong>Email :</strong> <?= htmlspecialchars($userData['email'] ?? '') ?></p>
        <p><strong>Pseudo :</strong> <?= htmlspecialchars($userData['pseudo'] ?? '') ?></p>
        <p><strong>Rôle :</strong> <?= htmlspecialchars($userData['role'] ?? '') ?></p>
        <p><strong>Statut :</strong> <?= htmlspecialchars($userData['status'] ?? '') ?></p>
        <p><strong>Crédits :</strong> <?= (int)($userData['credits'] ?? 0) ?></p>
    </div>

    <h3>Vos derniers trajets</h3>
    <?php if (!$myTrips): ?>
        <p>Aucun trajet.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($myTrips as $t): ?>
                <li><?= htmlspecialchars($t['ville_depart']) ?> → <?= htmlspecialchars($t['ville_arrivee']) ?>
                    (<?= htmlspecialchars($t['status']) ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p><a href="index.php">← Retour à l’accueil</a></p>
</main>

<!-- Chemin JS public depuis /frontend/private/user/ -->
<script src="../../public/assets/js/navbar.js?v=1"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
      if (typeof renderMenu === 'function') renderMenu(window.ecorideUser || null);
  });
</script>
</body>
</html>
 