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
        <a href="/admin/trips" style="background:white;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);text-decoration:none;display:block;">
            <span class="material-icons" style="font-size:36px;color:#0984e3;">map</span>
            <h3 style="color:#2d3436;"><?= $stats['trips'] ?></h3>
            <p style="color:#636e72;">Trajets planifiés</p>
        </a>
        <a href="/moderator" style="background:white;border-radius:12px;padding:20px;text-align:center;box-shadow:0 2px 10px rgba(0,0,0,0.05);text-decoration:none;display:block;">
            <span class="material-icons" style="font-size:36px;color:#e17055;">flag</span>
            <h3 style="color:#2d3436;"><?= $stats['pending_reviews'] ?></h3>
            <p style="color:#636e72;">Avis en attente</p>
            <small style="color:#e17055;">Accéder à la modération →</small>
        </a>
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
        <h3 class="profil-titre">Créer un compte</h3>
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
                    <option value="user">Utilisateur</option>
                    <option value="employe">Employé (Modérateur)</option>
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
                            <td style="padding:12px;display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
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
                                <form method="POST" action="/admin/add-credits" style="display:inline;display:flex;gap:4px;align-items:center;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                    <input type="number" name="credits" min="1" max="9999" placeholder="Crédits" style="width:70px;padding:5px 8px;border:1px solid #ddd;border-radius:6px;font-size:13px;">
                                    <button type="submit" style="background:#fdcb6e;color:#2d3436;border:none;border-radius:6px;padding:6px 10px;cursor:pointer;font-size:13px;font-weight:600;">+</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Messages de contact -->
    <div class="profile-box" id="messages" style="margin-top:30px;">
        <h3 class="profil-titre">
            Messages de contact
            <?php $unread = count(array_filter($contactMessages ?? [], fn($m) => !$m['is_read'])); ?>
            <?php if ($unread > 0): ?>
                <span style="background:#e74c3c;color:white;border-radius:12px;padding:2px 8px;font-size:13px;margin-left:8px;"><?= $unread ?> non lu<?= $unread > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </h3>
        <?php if (empty($contactMessages)): ?>
            <p style="text-align:center;color:#636e72;padding:30px 0;">Aucun message reçu.</p>
        <?php else: ?>
            <?php foreach ($contactMessages as $msg): ?>
                <div style="border:1px solid <?= $msg['is_read'] ? '#f1f2f6' : '#fdcb6e' ?>;border-radius:8px;padding:15px;margin-bottom:12px;background:<?= $msg['is_read'] ? '#fafafa' : '#fffdf0' ?>;">
                    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                        <div>
                            <strong><?= htmlspecialchars($msg['nom']) ?></strong>
                            &lt;<a href="mailto:<?= htmlspecialchars($msg['email']) ?>" style="color:#0984e3;"><?= htmlspecialchars($msg['email']) ?></a>&gt;
                            &mdash; <em><?= htmlspecialchars($msg['sujet']) ?></em>
                        </div>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span style="font-size:12px;color:#b2bec3;"><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></span>
                            <?php if (!$msg['is_read']): ?>
                                <form method="POST" action="/admin/mark-message-read" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                                    <button type="submit" class="btn-primary" style="padding:4px 10px;font-size:12px;">Marquer lu</button>
                                </form>
                            <?php else: ?>
                                <span style="color:#00b894;font-size:12px;">&#10003; Lu</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="margin-top:10px;padding:10px;background:white;border-radius:6px;color:#2d3436;font-size:14px;white-space:pre-wrap;"><?= htmlspecialchars($msg['message']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
