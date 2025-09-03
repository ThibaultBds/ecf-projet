<?php
session_start();

require_once __DIR__ . '/../../../backend/config/autoload.php';
useClass('Database');

// Charger le guard
require_once __DIR__ . '/../../../backend/config/guard.php';
requireRole(['Administrateur']); // accès réservé admin

$user = $_SESSION['user'];

// Récupérer les statistiques
try {
    $pdo = getDatabase();
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'actif'");
    $stats['users'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM trips WHERE status = 'planifie'");
    $stats['trips'] = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reports WHERE status IN ('ouvert','en_cours')");
    $stats['reports'] = $stmt->fetch()['count'];

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
    $totalPlatformCredits = $stmt->fetch()['total_credits'];

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
<link rel="stylesheet" href="../assets/css/style.css">
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

<script>
    window.ecorideUser = <?php echo isset($_SESSION['user']) ? json_encode($_SESSION['user'], JSON_UNESCAPED_UNICODE) : 'null'; ?>;
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
    <div style="display:flex;gap:20px;flex-wrap:wrap;">
        <div>Utilisateurs actifs: <?= $stats['users'] ?></div>
        <div>Trajets planifiés: <?= $stats['trips'] ?></div>
        <div>Signalements ouverts: <?= $stats['reports'] ?></div>
        <div>Crédits plateforme: <?= $totalPlatformCredits ?></div>
    </div>

    <h3>Gestion des utilisateurs</h3>
    <table border="1" cellpadding="5">
        <tr><th>ID</th><th>Email</th><th>Pseudo</th><th>Rôle</th><th>Statut</th><th>Actions</th></tr>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['pseudo']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td><?= htmlspecialchars($u['status']) ?></td>
                <td>
                    <?php if ($u['status'] === 'actif'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="suspend_user">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit">Suspendre</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="activate_user">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="submit">Réactiver</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h3>Créer un compte Employé</h3>
    <form method="POST">
        <input type="hidden" name="action" value="create_employee">
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="pseudo" placeholder="Pseudo" required>
        <input type="password" name="password" placeholder="Mot de passe temporaire" required>
        <select name="role">
            <option value="Moderateur">Modérateur</option>
            <option value="Administrateur">Administrateur</option>
        </select>
        <button type="submit">Créer</button>
    </form>
</main>

<script src="/ecoride/frontend/public/assets/js/navbar.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.ecorideUser) renderMenu(window.ecorideUser);
        else renderMenu();
    });
</script>
</body>
</html>
