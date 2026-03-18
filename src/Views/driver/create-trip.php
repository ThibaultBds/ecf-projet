<main class="member-container">
    <h2 class="page-title-hero">
        <span class="material-icons page-icon-large">add_road</span> Créer un trajet
    </h2>

    <?php if (!empty($error)): ?>
        <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="profile-box">
        <p class="trip-create-credit-info">
            <span class="material-icons icon-middle">account_balance_wallet</span>
            Vos crédits : <strong><?= (int) $user->credits ?></strong>
            | Frais plateforme : <strong>2 crédits</strong>
        </p>

        <form method="POST" action="/driver/create-trip" class="form-container">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

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
                <label for="heure_arrivee">Heure d'arrivée *</label>
                <input type="time" id="heure_arrivee" name="heure_arrivee" required
                       value="<?= htmlspecialchars($_POST['heure_arrivee'] ?? '') ?>">
            </div>

            <div class="input-group">
                <label for="vehicle_id">Véhicule *</label>
                <?php if (!empty($vehicles)): ?>
                    <select id="vehicle_id" name="vehicle_id" required class="select-field">
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?= $v->vehicleId ?>">
                                <?= htmlspecialchars($v->brand) ?> <?= htmlspecialchars($v->model) ?>
                                (<?= htmlspecialchars($v->licensePlate) ?> - <?= ucfirst($v->energyType) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <p class="trip-create-error-text">
                        Aucun véhicule enregistré.
                        <a href="/driver/vehicles" class="trip-create-inline-link">Ajouter un véhicule</a>
                    </p>
                <?php endif; ?>
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

            <button type="submit" class="btn-primary trip-create-submit">
                <span class="material-icons icon-middle">add_circle</span> Créer le trajet
            </button>
        </form>
    </div>

    <div class="retour-section">
        <a href="/driver/dashboard" class="btn-retour">
            <span class="material-icons">arrow_back</span> Retour au dashboard
        </a>
    </div>
</main>
