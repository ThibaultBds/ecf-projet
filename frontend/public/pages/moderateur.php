<?php
session_start();

require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

require_once __DIR__ . '/../../../backend/config/guard.php';
requireRole(['Moderateur','Administrateur']); 

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
  <meta charset="UTF-8">
  <title>Modération - EcoRide</title>
  <link rel="stylesheet" href="/assets/css/style.css">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
<header class="container-header">
  <h1>
    <a href="/index.php"><span class="material-icons">gavel</span> Modération EcoRide</a>
  </h1>
  <nav id="navbar"></nav>
</header>

<script>
  window.ecorideUser = <?= isset($_SESSION['user']) ? json_encode($_SESSION['user'], JSON_UNESCAPED_UNICODE) : 'null'; ?>;
</script>

<main>
  <h2>Signalements à traiter</h2>
  <?php if (!empty($error)): ?><div class="message-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if (!$reports): ?>
    <p>Aucun signalement en attente 👌</p>
  <?php else: ?>
    <table border="1">
      <tr><th>#</th><th>Type</th><th>Message</th><th>Par</th><th>Statut</th><th>Créé</th></tr>
      <?php foreach ($reports as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= htmlspecialchars($r['type']) ?></td>
        <td><?= htmlspecialchars($r['message']) ?></td>
        <td><?= htmlspecialchars($r['reporter'] ?? '—') ?></td>
        <td><?= htmlspecialchars($r['status']) ?></td>
        <td><?= htmlspecialchars($r['created_at']) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</main>

<script src="/assets/js/navbar.js"></script>
</body>
</html>
