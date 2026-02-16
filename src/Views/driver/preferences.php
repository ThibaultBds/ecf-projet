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

            <!-- Fumeur -->
            <div class="pref-card" style="background:#f8f9fa;border-radius:12px;padding:20px;margin-bottom:15px;">
                <h4><span class="material-icons" style="vertical-align:middle;color:#00b894;">smoking_rooms</span> Cigarette</h4>
                <label style="margin-right:20px;">
                    <input type="radio" name="fumeur" value="oui" <?= ($prefs['fumeur'] ?? '') === 'oui' ? 'checked' : '' ?>>
                    Fumeur accepté
                </label>
                <label>
                    <input type="radio" name="fumeur" value="non" <?= ($prefs['fumeur'] ?? 'non') === 'non' ? 'checked' : '' ?>>
                    Non-fumeur
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

            <!-- Préférences personnalisées -->
            <div class="pref-card" style="background:#f8f9fa;border-radius:12px;padding:20px;margin-bottom:15px;">
                <h4><span class="material-icons" style="vertical-align:middle;color:#00b894;">edit_note</span> Préférences personnalisées</h4>
                <p style="font-size:14px;color:#636e72;margin-bottom:10px;">Ajoutez vos propres préférences (une par ligne)</p>
                <textarea name="custom_preferences" rows="4" style="width:100%;border:1px solid #ddd;border-radius:8px;padding:12px;font-size:14px;resize:vertical;" placeholder="Ex: Pas de nourriture dans le véhicule&#10;Bagages légers uniquement&#10;Ponctualité exigée"><?php
                    $custom = $prefs['custom_preferences'] ?? [];
                    if (is_array($custom)) {
                        echo htmlspecialchars(implode("\n", $custom));
                    } else {
                        echo htmlspecialchars((string) $custom);
                    }
                ?></textarea>
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
