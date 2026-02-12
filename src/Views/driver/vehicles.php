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

    <!-- Formulaire d'ajout -->
    <div class="profile-box">
        <h3 class="profil-titre">Ajouter un véhicule</h3>
        <form method="POST" action="/driver/vehicles" class="form-container">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

            <div class="input-group">
                <label for="marque">Marque *</label>
                <input type="text" id="marque" name="marque" required placeholder="Ex: Renault">
            </div>
            <div class="input-group">
                <label for="modele">Modèle *</label>
                <input type="text" id="modele" name="modele" required placeholder="Ex: Clio">
            </div>
            <div class="input-group">
                <label for="couleur">Couleur *</label>
                <input type="text" id="couleur" name="couleur" required placeholder="Ex: Bleu">
            </div>
            <div class="input-group">
                <label for="plaque">Plaque d'immatriculation * (format: AB-123-CD)</label>
                <input type="text" id="plaque" name="plaque" required placeholder="AB-123-CD"
                       pattern="[A-Z]{2}-[0-9]{3}-[A-Z]{2}" style="text-transform:uppercase;">
            </div>
            <div class="input-group">
                <label for="energie">Type d'énergie</label>
                <select id="energie" name="energie" class="select-field">
                    <option value="essence">Essence</option>
                    <option value="diesel">Diesel</option>
                    <option value="electrique">Électrique</option>
                    <option value="hybride">Hybride</option>
                </select>
            </div>
            <div class="input-group">
                <label for="places_disponibles">Places disponibles (1-8)</label>
                <input type="number" id="places_disponibles" name="places_disponibles" min="1" max="8" value="4">
            </div>

            <button type="submit" class="btn-primary">Ajouter le véhicule</button>
        </form>
    </div>

    <!-- Liste des véhicules -->
    <?php if (!empty($vehicles)): ?>
        <h3 class="section-title">Mes véhicules</h3>
        <div class="trips-grid">
            <?php foreach ($vehicles as $v): ?>
                <div class="ride-card-history card-light" style="position:relative;">
                    <div class="ride-content">
                        <p class="ride-title">
                            <span class="material-icons ride-icon">directions_car</span>
                            <?= htmlspecialchars($v['marque']) ?> <?= htmlspecialchars($v['modele']) ?>
                        </p>
                        <p class="small-muted">
                            Couleur : <?= htmlspecialchars($v['couleur']) ?>
                            | Plaque : <?= htmlspecialchars($v['plaque']) ?>
                            | <?= ucfirst(htmlspecialchars($v['energie'])) ?>
                            | <?= $v['places_disponibles'] ?> places
                        </p>
                        <?php if ($v['energie'] === 'electrique'): ?>
                            <span class="eco-badge" style="display:inline-block;margin-top:5px;">⚡ Écologique</span>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="/driver/vehicles/delete" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="vehicle_id" value="<?= $v['id'] ?>">
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
