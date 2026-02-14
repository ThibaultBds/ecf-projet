<?php
$statusLabels = [
    'scheduled' => 'Planifié',
    'started' => 'En cours',
    'completed' => 'Terminé',
    'cancelled' => 'Annulé',
];
$roleLabels = [
    'user' => 'Utilisateur',
    'admin' => 'Administrateur',
    'employe' => 'Employé',
];
?>
<main class="page-wrapper">
    <h2 class="profile-hero">
        <span class="material-icons profile-icon">account_circle</span> Mon Profil
    </h2>

    <?php if (!empty($error)): ?>
        <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="message-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- Informations personnelles -->
    <div class="profile-box">
        <h3 class="profil-titre">Informations personnelles</h3>
        <div class="profile-grid">
            <div class="profile-item">
                <span class="material-icons profile-icon">email</span>
                <div>
                    <strong class="profile-strong">Email</strong>
                    <p class="profile-value"><?= htmlspecialchars($userData['email'] ?? '') ?></p>
                </div>
            </div>
            <div class="profile-item">
                <span class="material-icons profile-icon">person</span>
                <div>
                    <strong class="profile-strong">Pseudo</strong>
                    <p class="profile-value"><?= htmlspecialchars($userData['username'] ?? '') ?></p>
                </div>
            </div>
            <div class="profile-item">
                <span class="material-icons profile-icon">admin_panel_settings</span>
                <div>
                    <strong class="profile-strong">Rôle</strong>
                    <p class="profile-value">
                        <span class="admin-badge <?= strtolower($userData['role'] ?? '') ?>">
                            <?= $roleLabels[$userData['role'] ?? ''] ?? htmlspecialchars($userData['role'] ?? '') ?>
                        </span>
                    </p>
                </div>
            </div>
            <div class="profile-item">
                <span class="material-icons profile-icon">account_balance_wallet</span>
                <div>
                    <strong class="profile-strong">Crédits</strong>
                    <p class="profile-value profile-credits"><?= (int)($userData['credits'] ?? 0) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modifier le rôle -->
    <div class="profile-box">
        <h3 class="profil-titre">Modifier mon rôle</h3>
        <form method="POST" action="/profile/update" class="form-max">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <label for="role" class="form-label">Je suis :</label>
            <select name="role" id="role" required class="select-field">
                <option value="passager" <?= ($userData['role'] ?? 'passager') === 'passager' ? 'selected' : '' ?>>Passager</option>
                <option value="chauffeur" <?= ($userData['role'] ?? 'passager') === 'chauffeur' ? 'selected' : '' ?>>Chauffeur</option>
            </select>
            <button type="submit" class="btn-primary">Mettre à jour</button>
        </form>
    </div>

    <!-- Gestion du compte -->
    <div class="profil-section compte-section">
        <h3 class="profil-titre">Gestion de mon compte</h3>
        <div class="profil-liens-grid">
            <?php
            $role = $userData['role'] ?? 'passager';
            $is_driver = ($role === 'chauffeur');
            ?>

            <?php if ($is_driver): ?>
                <a href="/driver/vehicles" class="profil-lien lien-vehicules">
                    <span class="material-icons">directions_car</span>
                    <span>Gérer mes véhicules</span>
                </a>
                <a href="/driver/preferences" class="profil-lien lien-preferences">
                    <span class="material-icons">settings</span>
                    <span>Gérer mes préférences</span>
                </a>
                <a href="/driver/dashboard" class="profil-lien lien-espace-chauffeur">
                    <span class="material-icons">add_road</span>
                    <span>Espace chauffeur</span>
                </a>
            <?php endif; ?>

            <a href="/my-trips" class="profil-lien lien-trajets">
                <span class="material-icons">list</span>
                <span>Voir mes trajets</span>
            </a>
        </div>
    </div>

    <!-- Derniers trajets -->
    <div class="profil-section trajets-section">
        <h3 class="profil-titre">Vos derniers trajets</h3>
        <?php if (empty($myTrips)): ?>
            <p class="texte-vide">Aucun trajet pour le moment.</p>
        <?php else: ?>
            <div class="trajets-liste">
                <?php foreach ($myTrips as $t): ?>
                    <div class="trajet-item">
                        <div class="trajet-infos">
                            <span class="material-icons">location_on</span>
                            <span class="trajet-ville">
                                <?= htmlspecialchars($t['ville_depart']) ?> → <?= htmlspecialchars($t['ville_arrivee']) ?>
                            </span>
                            <span class="trajet-date"><?= date('d/m/Y', strtotime($t['departure_datetime'])) ?></span>
                        </div>
                        <span class="admin-badge <?= htmlspecialchars($t['status']) ?>">
                            <?= $statusLabels[$t['status']] ?? htmlspecialchars($t['status']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="retour-section">
        <a href="/" class="btn-retour">
            <span class="material-icons">arrow_back</span> Retour à l'accueil
        </a>
    </div>
</main>
