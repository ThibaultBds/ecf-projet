<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/ecoride/backend/config/autoload.php';
useClass('Database');

require_once $_SERVER['DOCUMENT_ROOT'] . '/ecoride/backend/config/guard.php';
requireRole(['Moderateur','Administrateur']); // <= accès modo + admin

$user = $_SESSION['user'];

try {
    $pdo = getDatabase();

    // Exemple : derniers reports ouverts/en cours
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
  <link rel="stylesheet" href="/ecoride/frontend/public/assets/css/style.css" />
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
<header class="container-header">
  <h1>
    <a href="index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
      <span class="material-icons">gavel</span> Modération EcoRide
    </a>
  </h1>
</header>

<main style="max-width:1000px;margin:40px auto;padding:0 20px;">
  <div style="background:white;padding:24px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.08);">
    <h2 style="margin:0 0 16px 0;">Signalements à traiter</h2>

    <?php if (!empty($error)): ?>
      <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$reports): ?>
      <p>Aucun signalement en attente 👌</p>
    <?php else: ?>
      <div style="overflow-x:auto;">
        <table class="admin-table">
          <thead>
            <tr>
              <th>#</th><th>Type</th><th>Message</th><th>Par</th><th>Statut</th><th>Créé</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($reports as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td><?= htmlspecialchars($r['type']) ?></td>
              <td><?= htmlspecialchars($r['message']) ?></td>
              <td><?= htmlspecialchars($r['reporter'] ?? '—') ?></td>
              <td><span class="admin-badge"><?= htmlspecialchars($r['status']) ?></span></td>
              <td><?= htmlspecialchars($r['created_at']) ?></td>
              <td style="white-space:nowrap;">
                <form method="POST" action="moderation_actions.php" style="display:inline;">
                  <input type="hidden" name="action" value="take_report">
                  <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="btn-secondary">Prendre</button>
                </form>
                <form method="POST" action="moderation_actions.php" style="display:inline;">
                  <input type="hidden" name="action" value="close_report">
                  <input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="btn-primary">Clore</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div style="margin-top:16px;">
      <a href="profil.php" style="color:#00b894;text-decoration:none;font-weight:600;">← Retour au profil</a>
    </div>
  </div>
</main>
</body>
</html>
