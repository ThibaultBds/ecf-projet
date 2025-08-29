<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/ecoride/backend/config/autoload.php';
useClass('Database');

// Charger le guard
require_once $_SERVER['DOCUMENT_ROOT'] . '/ecoride/backend/config/guard.php';

// Vérifie que seul un administrateur peut accéder
requireRole(['Administrateur']);

$user = $_SESSION['user'];

// Récupérer les statistiques
try {
    $pdo = getDatabase();
    
    // Nombre d'utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'actif'");
    $stats['users'] = $stmt->fetch()['count'];
    
    // Nombre de trajets actifs
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM trips WHERE status = 'planifie'");
    $stats['trips'] = $stmt->fetch()['count'];
    
    // Nombre de signalements
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM reports WHERE status IN ('ouvert', 'en_cours')");
    $stats['reports'] = $stmt->fetch()['count'];
    
    // Liste des utilisateurs
    $stmt = $pdo->query("SELECT id, email, pseudo, role, status, credits FROM users ORDER BY created_at DESC LIMIT 20");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Données pour graphiques
    $stmt = $pdo->query("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM trips
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
        LIMIT 30
    ");
    $tripsByDay = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Revenus par jour (2 crédits par trajet)
    $stmt = $pdo->query("
        SELECT DATE(tp.created_at) as date, COUNT(*) * 2 as credits
        FROM trip_participants tp
        WHERE tp.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(tp.created_at)
        ORDER BY date DESC
        LIMIT 30
    ");
    $revenuesByDay = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Total crédits gagnés par la plateforme
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

// Traitement des actions
if ($_POST && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'suspend_user') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE users SET status = 'suspendu' WHERE id = ?");
            $stmt->execute([$userId]);
            $success = "Utilisateur suspendu.";
        } elseif ($_POST['action'] === 'activate_user') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE users SET status = 'actif' WHERE id = ?");
            $stmt->execute([$userId]);
            $success = "Utilisateur réactivé.";
        }
        elseif ($_POST['action'] === 'create_employee') {
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
        
        // Recharger la page pour voir les changements
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
    <link rel="stylesheet" href="/ecoride/frontend/public/assets/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <header class="container-header">
        <h1>
            <a href="index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
                <span class="material-icons">eco</span> EcoRide
            </a>
        </h1>
    </header>

    <main style="max-width:1200px;margin:40px auto;padding:0 20px;">
        <div style="background:white;padding:30px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
            <h2 style="color:#2d3436;margin-bottom:30px;">
                <span class="material-icons" style="vertical-align:middle;color:#e74c3c;margin-right:10px;">admin_panel_settings</span>
                Panel Administrateur
            </h2>
            
            <div style="background:#fff5f5;border:1px solid #e74c3c;border-radius:8px;padding:15px;margin-bottom:30px;">
                <p style="margin:0;color:#e74c3c;">
                    <span class="material-icons" style="vertical-align:middle;margin-right:8px;">warning</span>
                    <strong>Connecté en tant qu'administrateur :</strong> <?= htmlspecialchars($user['email']) ?>
                </p>
            </div>

            <?php if (isset($_GET['msg'])): ?>
                <div class="message-success"><?= htmlspecialchars($_GET['msg']) ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="message-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="admin-stats" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:40px;">
                <div style="background:#e6f9ed;padding:20px;border-radius:8px;text-align:center;">
                    <span class="material-icons" style="font-size:48px;color:#00b894;">people</span>
                    <h3 style="margin:10px 0 5px 0;color:#00b894;"><?= $stats['users'] ?></h3>
                    <p style="margin:0;color:#636e72;">Utilisateurs actifs</p>
                </div>
                <div style="background:#e3f2fd;padding:20px;border-radius:8px;text-align:center;">
                    <span class="material-icons" style="font-size:48px;color:#0984e3;">directions_car</span>
                    <h3 style="margin:10px 0 5px 0;color:#0984e3;"><?= $stats['trips'] ?></h3>
                    <p style="margin:0;color:#636e72;">Trajets planifiés</p>
                </div>
                <div style="background:#fff3e0;padding:20px;border-radius:8px;text-align:center;">
                    <span class="material-icons" style="font-size:48px;color:#ff9800;">report</span>
                    <h3 style="margin:10px 0 5px 0;color:#ff9800;"><?= $stats['reports'] ?></h3>
                    <p style="margin:0;color:#636e72;">Signalements ouverts</p>
                </div>
                <div style="background:#f3e5f5;padding:20px;border-radius:8px;text-align:center;">
                    <span class="material-icons" style="font-size:48px;color:#9c27b0;">account_balance_wallet</span>
                    <h3 style="margin:10px 0 5px 0;color:#9c27b0;"><?= $totalPlatformCredits ?></h3>
                    <p style="margin:0;color:#636e72;">Crédits gagnés total</p>
                </div>
            </div>

            <!-- Graphiques -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:30px;margin-bottom:40px;">
                <div style="background:#f8f9fa;padding:20px;border-radius:8px;">
                    <h4 style="margin:0 0 15px 0;color:#2d3436;">Covoiturages par jour (30 derniers jours)</h4>
                    <canvas id="tripsChart" width="400" height="200" style="max-width:100%;"></canvas>
                </div>
                <div style="background:#f8f9fa;padding:20px;border-radius:8px;">
                    <h4 style="margin:0 0 15px 0;color:#2d3436;">Revenus par jour (crédits)</h4>
                    <canvas id="revenueChart" width="400" height="200" style="max-width:100%;"></canvas>
                </div>
            </div>

            <h3>Gestion des utilisateurs</h3>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Pseudo</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Crédits</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars($u['pseudo']) ?></td>
                            <td>
                                <span class="admin-badge <?= strtolower($u['role']) ?>">
                                    <?= $u['role'] ?>
                                </span>
                            </td>
                            <td>
                                <span class="admin-badge <?= strtolower($u['status']) ?>">
                                    <?= ucfirst($u['status']) ?>
                                </span>
                            </td>
                            <td><?= $u['credits'] ?></td>
                            <td>
                                <?php if ($u['status'] === 'actif'): ?>
                                    <button onclick="suspendUser(<?= $u['id'] ?>)" class="admin-table button">
                                        Suspendre
                                    </button>
                                <?php else: ?>
                                    <button onclick="activateUser(<?= $u['id'] ?>)" class="admin-table button">
                                        Réactiver
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h3 style="margin-top:40px;">Créer un compte Employé</h3>
            <form method="POST" class="form-container">
                <input type="hidden" name="action" value="create_employee">
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="pseudo" placeholder="Pseudo" required>
                <input type="password" name="password" placeholder="Mot de passe temporaire" required>
                <select name="role">
                    <option value="Moderateur">Modérateur</option>
                    <option value="Administrateur">Administrateur</option>
                </select>
                <button type="submit" class="btn-primary">Créer le compte</button>
            </form>

            <div style="text-align:center;margin-top:30px;">
                <a href="profil.php" style="color:#00b894;text-decoration:none;font-weight:600;">
                    ← Retour au profil
                </a>
            </div>
        </div>
    </main>

    <!-- Formulaires cachés pour les actions -->
    <form id="suspend-form" method="POST" style="display:none;">
        <input type="hidden" name="action" value="suspend_user">
        <input type="hidden" id="suspend-user-id" name="user_id" value="">
    </form>
    
    <form id="activate-form" method="POST" style="display:none;">
        <input type="hidden" name="action" value="activate_user">
        <input type="hidden" id="activate-user-id" name="user_id" value="">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="/ecoride/frontend/public/assets/js/navbar.js"></script>
    <script>
        function suspendUser(id) {
            if (confirm('Êtes-vous sûr de vouloir suspendre cet utilisateur ?')) {
                document.getElementById('suspend-user-id').value = id;
                document.getElementById('suspend-form').submit();
            }
        }
        
        function activateUser(id) {
            if (confirm('Êtes-vous sûr de vouloir réactiver cet utilisateur ?')) {
                document.getElementById('activate-user-id').value = id;
                document.getElementById('activate-form').submit();
            }
        }

        // Données pour les graphiques
        const tripsData = <?= json_encode(array_reverse($tripsByDay)) ?>;
        const revenueData = <?= json_encode(array_reverse($revenuesByDay)) ?>;
        
        // Graphique trajets
        const tripsCtx = document.getElementById('tripsChart').getContext('2d');
        new Chart(tripsCtx, {
            type: 'line',
            data: {
                labels: tripsData.map(d => new Date(d.date).toLocaleDateString('fr-FR')),
                datasets: [{
                    label: 'Nouveaux trajets',
                    data: tripsData.map(d => d.count),
                    borderColor: '#00b894',
                    backgroundColor: 'rgba(0, 184, 148, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        
        // Graphique revenus
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: revenueData.map(d => new Date(d.date).toLocaleDateString('fr-FR')),
                datasets: [{
                    label: 'Crédits gagnés',
                    data: revenueData.map(d => d.credits),
                    backgroundColor: '#e74c3c',
                    borderColor: '#c0392b',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>