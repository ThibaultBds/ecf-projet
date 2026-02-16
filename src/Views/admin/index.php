<?php
$roleLabels = [
    'user' => 'Utilisateur',
    'admin' => 'Administrateur',
    'employe' => 'Employé',
];
?>
<main class="member-container">
    <h2 class="page-title-hero">
        <span class="material-icons page-icon-large">admin_panel_settings</span> Administration
    </h2>

    <?php if (!empty($success)): ?>
        <div class="message-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-cards" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px;">
        <div style="background:white;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <span class="material-icons" style="font-size:36px;color:#00b894;">people</span>
            <h3><?= $stats['users'] ?></h3>
            <p style="color:#636e72;">Utilisateurs actifs</p>
        </div>
        <div style="background:white;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <span class="material-icons" style="font-size:36px;color:#0984e3;">map</span>
            <h3><?= $stats['trips'] ?></h3>
            <p style="color:#636e72;">Trajets planifiés</p>
        </div>
        <div style="background:white;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <span class="material-icons" style="font-size:36px;color:#e17055;">flag</span>
            <h3><?= $stats['pending_reviews'] ?></h3>
            <p style="color:#636e72;">Avis en attente</p>
        </div>
        <div style="background:white;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <span class="material-icons" style="font-size:36px;color:#fdcb6e;">account_balance_wallet</span>
            <h3><?= $stats['platform_credits'] ?></h3>
            <p style="color:#636e72;">Crédits plateforme</p>
        </div>
    </div>

    <!-- Graphiques -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(400px,1fr));gap:20px;margin-bottom:30px;">
        <div style="background:white;border-radius:12px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <h3 style="margin:0 0 15px 0;color:#2d3436;font-size:16px;">
                <span class="material-icons" style="vertical-align:middle;color:#0984e3;">bar_chart</span>
                Covoiturages par jour
            </h3>
            <canvas id="chart-trips" height="200"></canvas>
        </div>
        <div style="background:white;border-radius:12px;padding:20px;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <h3 style="margin:0 0 15px 0;color:#2d3436;font-size:16px;">
                <span class="material-icons" style="vertical-align:middle;color:#fdcb6e;">monetization_on</span>
                Cr&eacute;dits plateforme par jour
            </h3>
            <canvas id="chart-credits" height="200"></canvas>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var tripsData = <?= json_encode($tripsPerDay ?? []) ?>;
        var creditsData = <?= json_encode($creditsPerDay ?? []) ?>;

        new Chart(document.getElementById('chart-trips'), {
            type: 'bar',
            data: {
                labels: tripsData.map(function(r) { return r.jour; }),
                datasets: [{
                    label: 'Covoiturages',
                    data: tripsData.map(function(r) { return parseInt(r.total); }),
                    backgroundColor: 'rgba(9, 132, 227, 0.6)',
                    borderColor: '#0984e3',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        new Chart(document.getElementById('chart-credits'), {
            type: 'line',
            data: {
                labels: creditsData.map(function(r) { return r.jour; }),
                datasets: [{
                    label: 'Cr\u00e9dits',
                    data: creditsData.map(function(r) { return parseInt(r.total); }),
                    borderColor: '#00b894',
                    backgroundColor: 'rgba(0, 184, 148, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    });
    </script>

    <!-- Créer un employé -->
    <div class="profile-box">
        <h3 class="profil-titre">Créer un compte Employé</h3>
        <form method="POST" action="/admin/create-employee" class="form-container">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="input-group">
                <label for="username">Pseudo</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Mot de passe temporaire</label>
                <input type="password" id="password" name="password" required minlength="8">
            </div>
            <div class="input-group">
                <label for="role">Rôle</label>
                <select id="role" name="role" class="select-field">
                    <option value="employe">Employé (Modérateur)</option>
                    <option value="admin">Administrateur</option>
                </select>
            </div>
            <button type="submit" class="btn-primary">Créer</button>
        </form>
    </div>

    <!-- Gestion des utilisateurs -->
    <div class="profile-box">
        <h3 class="profil-titre">Gestion des utilisateurs</h3>
        <div style="overflow-x:auto;">
            <table class="admin-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8f9fa;">
                        <th style="padding:12px;text-align:left;">ID</th>
                        <th style="padding:12px;text-align:left;">Pseudo</th>
                        <th style="padding:12px;text-align:left;">Email</th>
                        <th style="padding:12px;text-align:left;">Rôle</th>
                        <th style="padding:12px;text-align:left;">Crédits</th>
                        <th style="padding:12px;text-align:left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr style="border-bottom:1px solid #f1f2f6;">
                            <td style="padding:12px;"><?= $u['user_id'] ?></td>
                            <td style="padding:12px;"><?= htmlspecialchars($u['username'] ?? '') ?></td>
                            <td style="padding:12px;"><?= htmlspecialchars($u['email']) ?></td>
                            <td style="padding:12px;">
                                <span class="admin-badge <?= strtolower($u['role'] ?? '') ?>"><?= $roleLabels[$u['role'] ?? ''] ?? ($u['role'] ?? '') ?></span>
                            </td>
                            <td style="padding:12px;"><?= (int)($u['credits'] ?? 0) ?></td>
                            <td style="padding:12px;">
                                <?php if (empty($u['suspended'])): ?>
                                    <form method="POST" action="/admin/suspend-user" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <button type="submit" class="btn-danger" style="padding:6px 12px;font-size:13px;">Suspendre</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="/admin/activate-user" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <button type="submit" class="btn-primary" style="padding:6px 12px;font-size:13px;">Réactiver</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
