<main class="member-container">
    <h2 class="page-title-hero">
        <span class="material-icons page-icon-large">directions_car</span> Mes Véhicules
    </h2>

    <?php if (!empty($error)): ?>
        <div class="message-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="message-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="profile-box">
        <h3 class="profil-titre">Ajouter un véhicule</h3>
        <form method="POST" action="/driver/vehicles" class="form-container">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <div class="input-group">
                <label for="brand">Marque *</label>
                <input type="text" id="brand" name="brand" required placeholder="Ex: Renault">
            </div>
            <div class="input-group">
                <label for="model">Modèle *</label>
                <input type="text" id="model" name="model" required placeholder="Ex: Clio">
            </div>
            <div class="input-group">
                <label for="color">Couleur *</label>
                <input type="text" id="color" name="color" required placeholder="Ex: Bleu">
            </div>
            <div class="input-group">
                <label for="license_plate">Plaque d'immatriculation * (format: AB-123-CD)</label>
                <input type="text" id="license_plate" name="license_plate" required placeholder="AB-123-CD"
                       pattern="[A-Z]{2}-[0-9]{3}-[A-Z]{2}" class="vehicle-plate-input">
            </div>
            <div class="input-group">
                <label for="energy_type">Type d'énergie</label>
                <select id="energy_type" name="energy_type" class="select-field">
                    <option value="essence">Essence</option>
                    <option value="diesel">Diesel</option>
                    <option value="electrique">Électrique</option>
                </select>
            </div>
            <div class="input-group">
                <label for="seats_available">Places disponibles (1-8)</label>
                <input type="number" id="seats_available" name="seats_available" min="1" max="8" value="4">
            </div>
            <div class="input-group">
                <label for="registration_date">Date de première immatriculation *</label>
                <input type="date" id="registration_date" name="registration_date" required>
            </div>

            <button type="submit" class="btn-primary">Ajouter le véhicule</button>
        </form>
    </div>

    <?php if (!empty($vehicles)): ?>
        <h3 class="section-title">Mes véhicules</h3>
        <div class="trips-grid">
            <?php foreach ($vehicles as $v): ?>
                <div class="ride-card-history card-light vehicle-card-wrap">
                    <div class="ride-content">
                        <p class="ride-title">
                            <span class="material-icons ride-icon">directions_car</span>
                            <?= htmlspecialchars($v->brand) ?> <?= htmlspecialchars($v->model) ?>
                        </p>
                        <p class="small-muted">
                            Couleur : <?= htmlspecialchars($v->color) ?>
                            | Plaque : <?= htmlspecialchars($v->licensePlate) ?>
                            | <?= ucfirst(htmlspecialchars($v->energyType)) ?>
                            | <?= $v->seatsAvailable ?> places
                        </p>
                        <?php if ($v->energyType === 'electrique'): ?>
                            <span class="eco-badge eco-badge-inline">⚡ Écologique</span>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="/driver/vehicles/delete" class="inline-form">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="vehicle_id" value="<?= $v->vehicleId ?>">
                        <button type="submit" class="btn-danger" onclick="return confirm('Supprimer ce véhicule ?');">Supprimer</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="retour-section">
        <a href="/profile" class="btn-retour">
            <span class="material-icons">arrow_back</span> Retour au profil
        </a>
    </div>
</main>
