<main class="member-container">
    <h2 class="page-title-hero">
        <span class="material-icons page-icon-large">add_road</span> Créer un trajet
    </h2>

    <?php if (!empty($error)): ?>
        <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="profile-box">
        <p style="text-align:center;color:#636e72;margin-bottom:20px;">
            <span class="material-icons" style="vertical-align:middle;">account_balance_wallet</span>
            Vos crédits : <strong><?= (int)($user['credits'] ?? 0) ?></strong>
            | Frais plateforme : <strong>2 crédits</strong>
        </p>

        <form method="POST" action="/driver/create-trip" class="form-container">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <div class="input-group">
                <label for="ville_depart">Ville de départ *</label>
                <input type="text" id="ville_depart" name="ville_depart" required list="villes"
                       value="<?= htmlspecialchars($_POST['ville_depart'] ?? '') ?>">
            </div>

            <div class="input-group">
                <label for="ville_arrivee">Ville d'arrivée *</label>
                <input type="text" id="ville_arrivee" name="ville_arrivee" required list="villes"
                       value="<?= htmlspecialchars($_POST['ville_arrivee'] ?? '') ?>">
            </div>

            <datalist id="villes">
                <option value="Paris"><option value="Lyon"><option value="Marseille">
                <option value="Nice"><option value="Toulouse"><option value="Bordeaux">
                <option value="Lille"><option value="Nantes"><option value="Strasbourg">
                <option value="Dijon">
            </datalist>

            <div class="input-group">
                <label for="date_depart">Date de départ *</label>
                <input type="date" id="date_depart" name="date_depart" required min="<?= date('Y-m-d') ?>"
                       value="<?= htmlspecialchars($_POST['date_depart'] ?? '') ?>">
            </div>

            <div class="input-group">
                <label for="heure_depart">Heure de départ *</label>
                <input type="time" id="heure_depart" name="heure_depart" required
                       value="<?= htmlspecialchars($_POST['heure_depart'] ?? '') ?>">
            </div>

            <div class="input-group">
                <label for="places">Nombre de places (1-4) *</label>
                <input type="number" id="places" name="places" min="1" max="4" required
                       value="<?= htmlspecialchars($_POST['places'] ?? '3') ?>">
            </div>

            <div class="input-group">
                <label for="prix">Prix par personne (1-100€) *</label>
                <input type="number" id="prix" name="prix" min="1" max="100" step="0.5" required
                       value="<?= htmlspecialchars($_POST['prix'] ?? '') ?>">
            </div>

            <div class="input-group">
                <label for="description">Description (optionnel, max 500 caractères)</label>
                <textarea id="description" name="description" maxlength="500" rows="3"
                          placeholder="Détails du trajet, point de rendez-vous..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-primary" style="width:100%;padding:15px;">
                <span class="material-icons" style="vertical-align:middle;">add_circle</span> Créer le trajet
            </button>
        </form>
    </div>

    <div class="retour-section">
        <a href="/driver/dashboard" class="btn-retour">
            <span class="material-icons">arrow_back</span> Retour au dashboard
        </a>
    </div>
</main>
