<main class="member-container">
    <h2 class="page-title-hero">
        <span class="material-icons page-icon-large">settings</span> Mes Préférences
    </h2>

    <?php if (!empty($error)): ?>
        <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="message-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="profile-box">
        <form method="POST" action="/driver/preferences" class="form-container">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <div class="pref-card pref-card-box">
                <h4><span class="material-icons pref-card-icon">smoking_rooms</span> Cigarette</h4>
                <div class="pref-options-col">
                    <label><input type="radio" name="fumeur" value="oui" <?= ($prefs['fumeur'] ?? '') === 'oui' ? 'checked' : '' ?>> Fumeur accepté</label>
                    <label><input type="radio" name="fumeur" value="non" <?= ($prefs['fumeur'] ?? 'non') === 'non' ? 'checked' : '' ?>> Non-fumeur</label>
                </div>
            </div>

            <div class="pref-card pref-card-box">
                <h4><span class="material-icons pref-card-icon">pets</span> Animaux</h4>
                <div class="pref-options-col">
                    <label><input type="radio" name="animaux" value="oui" <?= ($prefs['animaux'] ?? '') === 'oui' ? 'checked' : '' ?>> Acceptés</label>
                    <label><input type="radio" name="animaux" value="non" <?= ($prefs['animaux'] ?? 'non') === 'non' ? 'checked' : '' ?>> Non acceptés</label>
                </div>
            </div>

            <div class="pref-card pref-card-box">
                <h4><span class="material-icons pref-card-icon">music_note</span> Musique</h4>
                <div class="pref-options-col">
                    <label><input type="radio" name="musique" value="oui" <?= ($prefs['musique'] ?? '') === 'oui' ? 'checked' : '' ?>> Avec plaisir</label>
                    <label><input type="radio" name="musique" value="non" <?= ($prefs['musique'] ?? 'non') === 'non' ? 'checked' : '' ?>> Silence préféré</label>
                </div>
            </div>

            <div class="pref-card pref-card-box">
                <h4><span class="material-icons pref-card-icon">chat</span> Discussion</h4>
                <div class="pref-options-col">
                    <label><input type="radio" name="discussion" value="plaisir" <?= ($prefs['discussion'] ?? '') === 'plaisir' ? 'checked' : '' ?>> Avec plaisir</label>
                    <label><input type="radio" name="discussion" value="un_peu" <?= ($prefs['discussion'] ?? 'un_peu') === 'un_peu' ? 'checked' : '' ?>> Un peu</label>
                    <label><input type="radio" name="discussion" value="silence" <?= ($prefs['discussion'] ?? '') === 'silence' ? 'checked' : '' ?>> Silence préféré</label>
                </div>
            </div>

            <div class="pref-card pref-card-box">
                <h4><span class="material-icons pref-card-icon">edit_note</span> Préférences personnalisées</h4>
                <p class="pref-help-text">Ajoutez vos propres préférences (une par ligne)</p>
                <textarea name="custom_preferences" rows="4" class="pref-custom-textarea" placeholder="Ex: Pas de nourriture dans le véhicule&#10;Bagages légers uniquement&#10;Ponctualité exigée"><?php
                    $custom = $prefs['custom_preferences'] ?? [];
                    if (is_array($custom)) {
                        echo htmlspecialchars(implode("\n", $custom));
                    } else {
                        echo htmlspecialchars((string) $custom);
                    }
                ?></textarea>
            </div>

            <button type="submit" class="btn-primary pref-submit-btn">Sauvegarder mes préférences</button>
        </form>
    </div>

    <div class="retour-section">
        <a href="/profile" class="btn-retour">
            <span class="material-icons">arrow_back</span> Retour au profil
        </a>
    </div>
</main>
