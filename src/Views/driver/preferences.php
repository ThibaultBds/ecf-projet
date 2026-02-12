<main class="member-container">
    <h2 class="page-title-hero">
        <span class="material-icons page-icon-large">settings</span> Mes Préférences
    </h2>

    <?php if (!empty($success)): ?>
        <div class="message-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="profile-box">
        <form method="POST" action="/driver/preferences" class="form-container">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <!-- Musique -->
            <div class="pref-card" style="background:#f8f9fa;border-radius:12px;padding:20px;margin-bottom:15px;">
                <h4><span class="material-icons" style="vertical-align:middle;color:#00b894;">music_note</span> Musique</h4>
                <label style="margin-right:20px;">
                    <input type="radio" name="musique" value="oui" <?= ($prefs['musique'] ?? '') === 'oui' ? 'checked' : '' ?>>
                    Avec plaisir
                </label>
                <label>
                    <input type="radio" name="musique" value="non" <?= ($prefs['musique'] ?? 'non') === 'non' ? 'checked' : '' ?>>
                    Silence préféré
                </label>
            </div>

            <!-- Animaux -->
            <div class="pref-card" style="background:#f8f9fa;border-radius:12px;padding:20px;margin-bottom:15px;">
                <h4><span class="material-icons" style="vertical-align:middle;color:#00b894;">pets</span> Animaux</h4>
                <label style="margin-right:20px;">
                    <input type="radio" name="animaux" value="oui" <?= ($prefs['animaux'] ?? '') === 'oui' ? 'checked' : '' ?>>
                    Acceptés
                </label>
                <label>
                    <input type="radio" name="animaux" value="non" <?= ($prefs['animaux'] ?? 'non') === 'non' ? 'checked' : '' ?>>
                    Non acceptés
                </label>
            </div>

            <!-- Discussion -->
            <div class="pref-card" style="background:#f8f9fa;border-radius:12px;padding:20px;margin-bottom:15px;">
                <h4><span class="material-icons" style="vertical-align:middle;color:#00b894;">chat</span> Discussion</h4>
                <label style="margin-right:15px;">
                    <input type="radio" name="discussion" value="plaisir" <?= ($prefs['discussion'] ?? '') === 'plaisir' ? 'checked' : '' ?>>
                    Avec plaisir
                </label>
                <label style="margin-right:15px;">
                    <input type="radio" name="discussion" value="un_peu" <?= ($prefs['discussion'] ?? 'un_peu') === 'un_peu' ? 'checked' : '' ?>>
                    Un peu
                </label>
                <label>
                    <input type="radio" name="discussion" value="silence" <?= ($prefs['discussion'] ?? '') === 'silence' ? 'checked' : '' ?>>
                    Silence préféré
                </label>
            </div>

            <!-- Fumeur -->
            <div class="pref-card" style="background:#f8f9fa;border-radius:12px;padding:20px;margin-bottom:15px;">
                <h4><span class="material-icons" style="vertical-align:middle;color:#00b894;">smoking_rooms</span> Cigarette</h4>
                <label style="margin-right:20px;">
                    <input type="radio" name="fumeur" value="oui" <?= ($prefs['fumeur'] ?? '') === 'oui' ? 'checked' : '' ?>>
                    Fumeur
                </label>
                <label>
                    <input type="radio" name="fumeur" value="non" <?= ($prefs['fumeur'] ?? 'non') === 'non' ? 'checked' : '' ?>>
                    Non-fumeur
                </label>
            </div>

            <button type="submit" class="btn-primary" style="width:100%;padding:15px;">Sauvegarder mes préférences</button>
        </form>
    </div>

    <div class="retour-section">
        <a href="/profile" class="btn-retour">
            <span class="material-icons">arrow_back</span> Retour au profil
        </a>
    </div>
</main>
