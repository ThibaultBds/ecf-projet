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

    <div class="stats-cards admin-stats-grid">
        <div class="admin-stat-card">
            <span class="material-icons admin-stat-icon admin-stat-green">people</span>
            <h3><?= $stats['users'] ?></h3>
            <p class="admin-stat-text">Utilisateurs actifs</p>
        </div>
        <a href="/admin/trips" class="admin-stat-card admin-stat-link">
            <span class="material-icons admin-stat-icon admin-stat-blue">map</span>
            <h3 class="admin-stat-title"><?= $stats['trips'] ?></h3>
            <p class="admin-stat-text">Trajets planifiés</p>
        </a>
        <a href="/moderator" class="admin-stat-card admin-stat-link">
            <span class="material-icons admin-stat-icon admin-stat-orange">flag</span>
            <h3 class="admin-stat-title"><?= $stats['pending_reviews'] ?></h3>
            <p class="admin-stat-text">Avis en attente</p>
            <small class="admin-stat-note">Accéder à la modération →</small>
        </a>
        <div class="admin-stat-card">
            <span class="material-icons admin-stat-icon admin-stat-yellow">account_balance_wallet</span>
            <h3><?= $stats['platform_credits'] ?></h3>
            <p class="admin-stat-text">Crédits plateforme</p>
        </div>
    </div>

    <div class="admin-chart-grid">
        <div class="admin-chart-card">
            <h3 class="admin-chart-title">
                <span class="material-icons admin-chart-title-icon admin-stat-blue">bar_chart</span>
                Covoiturages par jour
            </h3>
            <canvas id="chart-trips" height="200"></canvas>
        </div>
        <div class="admin-chart-card">
            <h3 class="admin-chart-title">
                <span class="material-icons admin-chart-title-icon admin-stat-yellow">monetization_on</span>
                Crédits plateforme par jour
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
                    label: 'Crédits',
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

    <div class="profile-box">
        <h3 class="profil-titre">Gestion des utilisateurs</h3>
        <div class="table-wrap-x">
            <table class="admin-table admin-users-table">
                <thead>
                    <tr class="admin-row-head">
                        <th>ID</th>
                        <th>Pseudo</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Crédits</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr class="admin-row">
                            <td><?= $u['user_id'] ?></td>
                            <td><?= htmlspecialchars($u['username'] ?? '') ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <span class="admin-badge <?= strtolower($u['role'] ?? '') ?>"><?= $roleLabels[$u['role'] ?? ''] ?? ($u['role'] ?? '') ?></span>
                            </td>
                            <td><?= (int)($u['credits'] ?? 0) ?></td>
                            <td class="admin-user-actions-cell">
                                <?php if (empty($u['suspended'])): ?>
                                    <form method="POST" action="/admin/suspend-user" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <button type="submit" class="btn-danger admin-btn-sm">Suspendre</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="/admin/activate-user" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                        <button type="submit" class="btn-primary admin-btn-sm">Réactiver</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="/admin/add-credits" class="admin-credit-form">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                    <input type="number" name="credits" min="1" max="9999" placeholder="Crédits" class="admin-credit-input">
                                    <button type="submit" class="admin-credit-plus-btn">+</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="profile-box admin-messages-box" id="messages">
        <h3 class="profil-titre">
            Messages de contact
            <?php $unread = count(array_filter($contactMessages ?? [], fn($m) => !$m['is_read'])); ?>
            <?php if ($unread > 0): ?>
                <span class="admin-unread-badge"><?= $unread ?> non lu<?= $unread > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </h3>
        <?php if (empty($contactMessages)): ?>
            <p class="admin-empty-text">Aucun message reçu.</p>
        <?php else: ?>
            <?php foreach ($contactMessages as $msg): ?>
                <div class="admin-message-card <?= $msg['is_read'] ? 'is-read' : 'is-unread' ?>">
                    <div class="admin-message-head">
                        <div>
                            <strong><?= htmlspecialchars($msg['nom']) ?></strong>
                            &lt;<a href="mailto:<?= htmlspecialchars($msg['email']) ?>" class="admin-message-mail-link"><?= htmlspecialchars($msg['email']) ?></a>&gt;
                            &mdash; <em><?= htmlspecialchars($msg['sujet']) ?></em>
                        </div>
                        <div class="admin-message-meta">
                            <span class="admin-message-date"><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></span>
                            <?php if (!$msg['is_read']): ?>
                                <form method="POST" action="/admin/mark-message-read" class="inline-form">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="message_id" value="<?= (int)$msg['id'] ?>">
                                    <button type="submit" class="btn-primary admin-btn-xs">Marquer lu</button>
                                </form>
                            <?php else: ?>
                                <span class="admin-read-check">&#10003; Lu</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="admin-message-body"><?= htmlspecialchars($msg['message']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
