<section class="page-header">
    <h2>Covoiturages disponibles</h2>
    <p>Trouvez le trajet qui vous convient !</p>
</section>

<section>
    <form method="GET" action="/trips" class="search-bar">
        <input type="text" name="depart" placeholder="Ville de départ" value="<?= htmlspecialchars($filters['depart'] ?? '') ?>" list="villes">
        <input type="text" name="arrivee" placeholder="Ville d'arrivée" value="<?= htmlspecialchars($filters['arrivee'] ?? '') ?>" list="villes">
        <input type="date" name="date" value="<?= htmlspecialchars($filters['date'] ?? '') ?>">
        <button type="submit">Rechercher</button>
        <datalist id="villes">
            <option value="Paris"><option value="Lyon"><option value="Marseille">
            <option value="Nice"><option value="Toulouse"><option value="Bordeaux">
            <option value="Lille"><option value="Nantes"><option value="Strasbourg">
            <option value="Dijon">
        </datalist>
    </form>
</section>

<section class="covoiturages-list" style="max-width:1000px;margin:0 auto;padding:20px;">

    <!-- Filtres avancés -->
    <div style="text-align:center;margin-bottom:30px;">
        <button id="toggle-filters" style="background:#636e72;color:white;padding:10px 24px;border:none;border-radius:8px;font-weight:500;cursor:pointer;transition:background 0.2s;">
            <span class="material-icons" style="vertical-align:middle;margin-right:8px;">tune</span> Filtres avancés
        </button>
        <div id="advanced-filters" style="display:none;margin-top:20px;text-align:left;background:#f8f9fa;padding:20px;border-radius:8px;max-width:500px;margin-left:auto;margin-right:auto;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <form method="get" action="/trips">
                <input type="hidden" name="depart" value="<?= htmlspecialchars($filters['depart'] ?? '') ?>">
                <input type="hidden" name="arrivee" value="<?= htmlspecialchars($filters['arrivee'] ?? '') ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($filters['date'] ?? '') ?>">
                <label for="prix_max">Prix max :</label>
                <input type="number" name="prix_max" id="prix_max" min="0" style="margin-right:20px;">
                <label for="note_min">Note min :</label>
                <input type="number" name="note_min" id="note_min" min="1" max="5" style="margin-right:20px;">
                <label for="ecologique">Écologique :</label>
                <select name="ecologique" id="ecologique">
                    <option value="">Tous</option>
                    <option value="1">Oui</option>
                    <option value="0">Non</option>
                </select>
                <button type="submit" style="margin-left:20px;background:#00b894;color:white;padding:8px 18px;border:none;border-radius:6px;font-weight:500;cursor:pointer;">Filtrer</button>
            </form>
        </div>
    </div>

    <?php if (empty($covoiturages)): ?>
        <div style="text-align:center;padding:40px;background:white;border-radius:12px;margin-bottom:20px;">
            <span class="material-icons" style="font-size:64px;color:#ddd;margin-bottom:20px;">search_off</span>
            <h3 style="color:#636e72;margin-bottom:10px;">Aucun covoiturage trouvé</h3>
            <p style="color:#636e72;">Essayez de modifier vos critères de recherche.</p>
        </div>
    <?php else: ?>
        <?php foreach ($covoiturages as $c): ?>
            <div class="ride-card"
                 data-price="<?= $c['prix'] ?>"
                 data-ecological="<?= $c['is_ecological'] ? 'true' : 'false' ?>"
                 data-rating="<?= $c['rating'] ?? 0 ?>">

                <div class="ride-header">
                    <h3><?= htmlspecialchars($c['ville_depart']) ?> → <?= htmlspecialchars($c['ville_arrivee']) ?></h3>
                    <div class="ride-price"><?= number_format($c['prix'], 2) ?>€</div>
                </div>

                <div class="ride-details">
                    <p><span class="material-icons">schedule</span> <?= date('d/m/Y à H:i', strtotime($c['date_depart'])) ?></p>
                    <p>
                        <span class="material-icons">person</span>
                        <img src="<?= htmlspecialchars($c['conducteur_avatar_url'] ?? '/assets/images/default_avatar.png') ?>"
                             alt="Avatar"
                             style="width:30px;height:30px;border-radius:50%;object-fit:cover;margin-right:8px;vertical-align:middle;">
                        <?= htmlspecialchars($c['conducteur']) ?>
                    </p>
                    <p><span class="material-icons">directions_car</span> <?= htmlspecialchars($c['marque']) ?> <?= htmlspecialchars($c['modele']) ?></p>
                    <p><span class="material-icons">people</span> <?= (int)$c['places_restantes'] ?> places restantes</p>
                </div>

                <?php if ($c['is_ecological']): ?>
                    <div class="eco-badge">⚡ Écologique</div>
                <?php endif; ?>

                <div class="ride-actions">
                    <a href="/trip/<?= (int)$c['id'] ?>" class="btn-primary">Voir détails</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggle-filters');
    const filtersBlock = document.getElementById('advanced-filters');
    if (toggleBtn && filtersBlock) {
        toggleBtn.addEventListener('click', function() {
            filtersBlock.style.display = (filtersBlock.style.display === 'none' || filtersBlock.style.display === '') ? 'block' : 'none';
        });
    }
});
</script>
