<?php
session_start();

require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

// Guard admin uniquement
require_once __DIR__ . '/../../../backend/config/guard.php';
requireRole(['Administrateur']); // accès réservé admin

// On unifie les infos utilisateur pour la navbar/JS
$auth = $_SESSION['auth'] ?? $_SESSION['user'] ?? $_SESSION['admin'] ?? null;
$user = $auth;

try {
    $pdo = getDatabase();

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'actif'");
    $stats['users'] = (int)$stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM trips WHERE status = 'planifie'");
    $stats['trips'] = (int)$stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reports WHERE status IN ('ouvert','en_cours')");
    $stats['reports'] = (int)$stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT id, email, pseudo, role, status, credits FROM users ORDER BY created_at DESC LIMIT 20");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM trips
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
        LIMIT 30
    ");
    $tripsByDay = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("
        SELECT DATE(tp.created_at) as date, COUNT(*) * 2 as credits
        FROM trip_participants tp
        WHERE tp.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(tp.created_at)
        ORDER BY date DESC
        LIMIT 30
    ");
    $revenuesByDay = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT COUNT(*) * 2 as total_credits FROM trip_participants");
    $totalPlatformCredits = (int)$stmt->fetch()['total_credits'];

} catch (Exception $e) {
    $stats = ['users' => 0, 'trips' => 0, 'reports' => 0];
    $users = [];
    $tripsByDay = [];
    $revenuesByDay = [];
    $totalPlatformCredits = 0;
    $error = "Erreur lors du chargement des données.";
}

// Actions POST (suspendre, activer, créer compte)
if ($_POST && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'suspend_user') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'suspendu' WHERE id = ?");
            $stmt->execute([(int)$_POST['user_id']]);
            $success = "Utilisateur suspendu.";
        } elseif ($_POST['action'] === 'activate_user') {
            $stmt = $pdo->prepare("UPDATE users SET status = 'actif' WHERE id = ?");
            $stmt->execute([(int)$_POST['user_id']]);
            $success = "Utilisateur réactivé.";
        } elseif ($_POST['action'] === 'create_employee') {
            $email = $_POST['email'];
            $pseudo = $_POST['pseudo'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = $_POST['role'];
            if (in_array($role, ['Moderateur', 'Administrateur'])) {
                $stmt = $pdo->prepare("INSERT INTO users (email, pseudo, password, role, status) VALUES (?, ?, ?, ?, 'actif')");
                $stmt->execute([$email, $pseudo, $password, $role]);
                $success = "Compte $role créé avec succès.";
            }
        }
        header('Location: admin.php?msg=' . urlencode($success ?? ''));
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Administration - EcoRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSS absolu -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
<header class="container-header">
    <h1>
        <a href="/index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
            <span class="material-icons">eco</span> EcoRide
        </a>
    </h1>
    <nav id="navbar"></nav>
</header>

<!-- Session unifiée exposée au front -->
<script>
  window.ecorideUser = <?= $auth ? json_encode($auth, JSON_UNESCAPED_UNICODE) : 'null' ?>;
</script>

<main style="max-width:1200px;margin:40px auto;padding:0 20px;">
    <h2>Panel Administrateur</h2>

    <?php if (isset($_GET['msg'])): ?>
        <div class="message-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-cards">
        <div class="stats-card">
            <h3>Utilisateurs actifs</h3>
            <p class="number"><?= (int)$stats['users'] ?></p>
        </div>
        <div class="stats-card">
            <h3>Trajets planifiés</h3>
            <p class="number"><?= (int)$stats['trips'] ?></p>
        </div>
        <div class="stats-card">
            <h3>Signalements ouverts</h3>
            <p class="number"><?= (int)$stats['reports'] ?></p>
        </div>
        <div class="stats-card">
            <h3>Crédits plateforme</h3>
            <p class="number"><?= (int)$totalPlatformCredits ?></p>
        </div>
    </div>

    <h3>Gestion des utilisateurs</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Pseudo</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= (int)$u['id'] ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= htmlspecialchars($u['pseudo']) ?></td>
                    <td><span class="admin-badge <?= strtolower($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                    <td><span class="admin-badge <?= $u['status'] ?>"><?= htmlspecialchars($u['status']) ?></span></td>
                    <td>
                        <?php if ($u['status'] === 'actif'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="suspend_user">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" class="btn-admin">Suspendre</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="activate_user">
                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                <button type="submit" class="btn-primary">Réactiver</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Créer un compte Employé</h3>
    <div class="form-container">
        <form method="POST">
            <input type="hidden" name="action" value="create_employee">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" required>

            <label for="pseudo">Pseudo</label>
            <input type="text" id="pseudo" name="pseudo" placeholder="Pseudo" required>

            <label for="password">Mot de passe temporaire</label>
            <input type="password" id="password" name="password" placeholder="Mot de passe temporaire" required>

            <label for="role">Rôle</label>
            <select id="role" name="role">
                <option value="Moderateur">Modérateur</option>
                <option value="Administrateur">Administrateur</option>
            </select>

            <button type="submit" class="btn-primary">Créer</button>
        </form>
    </div>
</main>

<!-- JS absolu -->
<script src="/assets/js/navbar.js?v=1"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
      if (typeof renderMenu === 'function') renderMenu(window.ecorideUser || null);
  });
</script>
</body>
</html>
