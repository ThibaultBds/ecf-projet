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
$isDriver = !empty($userData['is_driver']);
$isPassenger = !empty($userData['is_passenger']);
$currentType = ($isDriver && $isPassenger) ? 'les_deux' : ($isDriver ? 'chauffeur' : 'passager');
?>
<main class="page-wrapper">
    <h2 class="profile-hero">
        <span class="material-icons profile-icon">account_circle</span>
        Mon Profil
    </h2>

    <!-- Photo de profil -->
<div class="profile-box">
    <h3 class="profil-titre">Photo de profil</h3>

    <div style="margin-bottom:15px;">
        <?php if (!empty($userData['photo'])): ?>
            <img src="/uploads/<?= htmlspecialchars($userData['photo']) ?>" 
                 alt="Photo de profil"
                 style="width:100px;height:100px;border-radius:50%;object-fit:cover;">
        <?php else: ?>
            <span class="material-icons" style="font-size:100px;color:#00b894;">account_circle</span>
        <?php endif; ?>
    </div>

    <form method="POST" action="/profile/upload-photo" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="file" name="photo" accept="image/jpeg,image/png" required>
        <button type="submit" class="btn-primary">Mettre à jour la photo</button>
    </form>
</div>

<?php if (!empty($userData['photo'])): ?>
    <form method="POST" action="/profile/delete-photo" style="margin-top:10px;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <button type="submit" class="btn-danger">Supprimer la photo</button>
    </form>
<?php endif; ?>


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
                <span class="material-icons profile-icon">badge</span>
                <div>
                    <strong class="profile-strong">Type</strong>
                    <p class="profile-value">
                        <?php if ($isDriver && $isPassenger): ?>
                            <span class="admin-badge" style="background:#00b894;color:white;">Chauffeur &amp; Passager</span>
                        <?php elseif ($isDriver): ?>
                            <span class="admin-badge" style="background:#0984e3;color:white;">Chauffeur</span>
                        <?php else: ?>
                            <span class="admin-badge" style="background:#636e72;color:white;">Passager</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="profile-item">
                <span class="material-icons profile-icon">account_balance_wallet</span>
                <div>
                    <strong class="profile-strong">Cr&eacute;dits</strong>
                    <p class="profile-value profile-credits"><?= (int)($userData['credits'] ?? 0) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modifier le type -->
    <div class="profile-box">
        <h3 class="profil-titre">Modifier mon type de compte</h3>
        <form method="POST" action="/profile/update" class="form-max">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <label for="user_type" class="form-label">Je suis :</label>
            <select name="user_type" id="user_type" required class="select-field">
                <option value="passager" <?= $currentType === 'passager' ? 'selected' : '' ?>>Passager</option>
                <option value="chauffeur" <?= $currentType === 'chauffeur' ? 'selected' : '' ?>>Chauffeur</option>
                <option value="les_deux" <?= $currentType === 'les_deux' ? 'selected' : '' ?>>Chauffeur &amp; Passager</option>
            </select>
            <button type="submit" class="btn-primary">Mettre &agrave; jour</button>
        </form>
    </div>

    <!-- Gestion du compte -->
    <div class="profil-section compte-section">
        <h3 class="profil-titre">Gestion de mon compte</h3>
        <div class="profil-liens-grid">
            <?php if ($isDriver): ?>
                <a href="/driver/vehicles" class="profil-lien lien-vehicules">
                    <span class="material-icons">directions_car</span>
                    <span>G&eacute;rer mes v&eacute;hicules</span>
                </a>
                <a href="/driver/preferences" class="profil-lien lien-preferences">
                    <span class="material-icons">settings</span>
                    <span>G&eacute;rer mes pr&eacute;f&eacute;rences</span>
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

            <?php if (($userData['role'] ?? '') === 'admin'): ?>
                <a href="/admin" class="profil-lien" style="border-color:#e74c3c;">
                    <span class="material-icons">admin_panel_settings</span>
                    <span>Administration</span>
                </a>
            <?php elseif (($userData['role'] ?? '') === 'employe'): ?>
                <a href="/moderator" class="profil-lien" style="border-color:#e17055;">
                    <span class="material-icons">shield</span>
                    <span>Mod&eacute;ration</span>
                </a>
            <?php endif; ?>
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
                                <?= htmlspecialchars($t['ville_depart']) ?> &rarr; <?= htmlspecialchars($t['ville_arrivee']) ?>
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
            <span class="material-icons">arrow_back</span> Retour &agrave; l'accueil
        </a>
    </div>
</main>
