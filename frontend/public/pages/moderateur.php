<?php
session_start();

require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

require_once __DIR__ . '/../../../backend/config/guard.php';
requireRole(['Moderateur','Administrateur']); // accès modo + admin

$user = $_SESSION['user'];

try {
    $pdo = getDatabase();
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>Modération - EcoRide</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- CSS absolu -->
  <link rel="stylesheet" href="/assets/css/style.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
<header class="container-header">
  <h1>
    <a href="/index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
      <span class="material-icons">gavel</span> Modération EcoRide
    </a>
  </h1>
  <nav id="navbar"></nav>
</header>

<script>
    window.ecorideUser = <?php echo isset($_SESSION['user']) ? json_encode($_SESSION['user'], JSON_UNESCAPED_UNICODE) : 'null'; ?>;
</script>

<main style="max-width:1000px;margin:40px auto;padding:0 20px;">
  <h2>Signalements à traiter</h2>

  <?php if (!empty($error)): ?>
    <div class="message-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if (!$reports): ?>
    <p>Aucun signalement en attente 👌</p>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Type</th>
          <th>Message</th>
          <th>Par</th>
          <th>Statut</th>
          <th>Créé</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reports as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><span class="admin-badge <?= htmlspecialchars($r['type']) ?>"><?= htmlspecialchars($r['type']) ?></span></td>
            <td><?= htmlspecialchars($r['message']) ?></td>
            <td><?= htmlspecialchars($r['reporter'] ?? '—') ?></td>
            <td><span class="admin-badge <?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
            <td><?= htmlspecialchars($r['created_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <div style="margin-top:16px;">
    <a href="/profil.php">← Retour au profil</a>
  </div>
</main>

<!-- JS absolu -->
<script src="/assets/js/navbar.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof renderMenu === 'function') {
            if (window.ecorideUser) renderMenu(window.ecorideUser);
            else renderMenu();
        }
    });
</script>
</body>
</html>
