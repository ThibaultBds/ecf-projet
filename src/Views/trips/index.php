<section class="page-header">
    <h2>Covoiturages disponibles</h2>
    <p>Trouvez le trajet qui vous convient !</p>
</section>

<section>
    <form method="GET" action="/trips" class="search-bar">
        <input type="text" name="depart" placeholder="Ville de d&eacute;part" value="<?= htmlspecialchars($filters['depart'] ?? '') ?>" list="villes" required>
        <input type="text" name="arrivee" placeholder="Ville d'arriv&eacute;e" value="<?= htmlspecialchars($filters['arrivee'] ?? '') ?>" list="villes" required>
        <input type="date" name="date" value="<?= htmlspecialchars($filters['date'] ?? '') ?>" required>
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

    <?php if (!empty($hasSearched)): ?>
    <!-- Filtres avancés -->
    <div style="text-align:center;margin-bottom:30px;">
        <button type="button" id="toggle-filters" style="background:#636e72;color:white;padding:10px 24px;border:none;border-radius:8px;font-weight:500;cursor:pointer;transition:background 0.2s;">
            <span class="material-icons" style="vertical-align:middle;margin-right:8px;">tune</span> Filtres avanc&eacute;s
        </button>
        <div id="advanced-filters" style="display:none;margin-top:20px;text-align:left;background:#f8f9fa;padding:20px;border-radius:8px;max-width:600px;margin-left:auto;margin-right:auto;box-shadow:0 2px 10px rgba(0,0,0,0.05);">
            <form method="get" action="/trips">
                <input type="hidden" name="depart" value="<?= htmlspecialchars($filters['depart'] ?? '') ?>">
                <input type="hidden" name="arrivee" value="<?= htmlspecialchars($filters['arrivee'] ?? '') ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($filters['date'] ?? '') ?>">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px;">
                    <div>
                        <label for="prix_max" style="display:block;font-weight:500;margin-bottom:5px;">
                            <span class="material-icons" style="vertical-align:middle;font-size:18px;color:#00b894;">euro</span> Prix maximum
                        </label>
                        <input type="number" name="prix_max" id="prix_max" min="1" max="100" step="1"
                               value="<?= htmlspecialchars($filters['prix_max'] ?? '') ?>"
                               placeholder="Ex: 30"
                               style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;">
                    </div>
                    <div>
                        <label for="duree_max" style="display:block;font-weight:500;margin-bottom:5px;">
                            <span class="material-icons" style="vertical-align:middle;font-size:18px;color:#00b894;">schedule</span> Dur&eacute;e max (heures)
                        </label>
                        <input type="number" name="duree_max" id="duree_max" min="1" max="24" step="1"
                               value="<?= htmlspecialchars($filters['duree_max'] ?? '') ?>"
                               placeholder="Ex: 4"
                               style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;">
                    </div>
                    <div>
                        <label for="note_min" style="display:block;font-weight:500;margin-bottom:5px;">
                            <span class="material-icons" style="vertical-align:middle;font-size:18px;color:#ffd700;">star</span> Note minimum
                        </label>
                        <select name="note_min" id="note_min" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;">
                            <option value="">Toutes</option>
                            <option value="1" <?= ($filters['note_min'] ?? '') == '1' ? 'selected' : '' ?>>1+</option>
                            <option value="2" <?= ($filters['note_min'] ?? '') == '2' ? 'selected' : '' ?>>2+</option>
                            <option value="3" <?= ($filters['note_min'] ?? '') == '3' ? 'selected' : '' ?>>3+</option>
                            <option value="4" <?= ($filters['note_min'] ?? '') == '4' ? 'selected' : '' ?>>4+</option>
                            <option value="5" <?= ($filters['note_min'] ?? '') == '5' ? 'selected' : '' ?>>5</option>
                        </select>
                    </div>
                    <div>
                        <label for="ecologique" style="display:block;font-weight:500;margin-bottom:5px;">
                            <span style="vertical-align:middle;font-size:16px;">⚡</span> &Eacute;cologique
                        </label>
                        <select name="ecologique" id="ecologique" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:6px;">
                            <option value="">Tous</option>
                            <option value="1" <?= ($filters['ecologique'] ?? '') == '1' ? 'selected' : '' ?>>Oui uniquement</option>
                        </select>
                    </div>
                </div>
                <button type="submit" style="width:100%;background:#00b894;color:white;padding:10px;border:none;border-radius:6px;font-weight:500;cursor:pointer;">Appliquer les filtres</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($hasSearched)): ?>
        <!-- État initial : pas de recherche -->
        <div style="text-align:center;padding:60px 20px;background:white;border-radius:12px;">
            <span class="material-icons" style="font-size:80px;color:#00b894;opacity:0.6;margin-bottom:20px;display:block;">travel_explore</span>
            <h3 style="color:#2d3436;margin-bottom:10px;">Recherchez un covoiturage</h3>
            <p style="color:#636e72;">Renseignez une ville de d&eacute;part, d'arriv&eacute;e et une date pour d&eacute;couvrir les trajets disponibles.</p>
        </div>

    <?php elseif (empty($covoiturages)): ?>
        <div style="text-align:center;padding:40px;background:white;border-radius:12px;margin-bottom:20px;">
            <span class="material-icons" style="font-size:64px;color:#ddd;margin-bottom:20px;">search_off</span>
            <h3 style="color:#636e72;margin-bottom:10px;">Aucun covoiturage trouv&eacute;</h3>
            <p style="color:#636e72;">Aucun trajet ne correspond &agrave; vos crit&egrave;res pour cette date.</p>

            <?php if (!empty($nearestDate)): ?>
                <div style="margin-top:20px;padding:15px;background:#e8f8f5;border-radius:8px;display:inline-block;">
                    <p style="margin:0 0 10px 0;color:#00b894;font-weight:600;">
                        <span class="material-icons" style="vertical-align:middle;">event</span>
                        Un trajet est disponible le <?= date('d/m/Y', strtotime($nearestDate)) ?>
                    </p>
                    <a href="/trips?depart=<?= urlencode($filters['depart']) ?>&arrivee=<?= urlencode($filters['arrivee']) ?>&date=<?= $nearestDate ?>"
                       style="display:inline-block;background:#00b894;color:white;padding:8px 20px;border-radius:6px;text-decoration:none;font-weight:500;">
                        Voir ce trajet
                    </a>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <p style="text-align:center;color:#636e72;margin-bottom:20px;">
            <?= count($covoiturages) ?> trajet<?= count($covoiturages) > 1 ? 's' : '' ?> trouv&eacute;<?= count($covoiturages) > 1 ? 's' : '' ?>
        </p>

        <?php foreach ($covoiturages as $c): ?>
            <div class="ride-card"
                 data-price="<?= $c['price'] ?>"
                 data-ecological="<?= $c['energy_type'] === 'electrique' ? 'true' : 'false' ?>"
                 data-rating="<?= $c['note_conducteur'] ?? 0 ?>">

                <div class="ride-header">
                    <h3><?= htmlspecialchars($c['ville_depart']) ?> &rarr; <?= htmlspecialchars($c['ville_arrivee']) ?></h3>
                    <div class="ride-price"><?= number_format($c['price'], 2) ?>&euro;</div>
                </div>

                <div class="ride-details">
                    <p>
                        <span class="material-icons">schedule</span>
                        <?= date('d/m/Y', strtotime($c['departure_datetime'])) ?> &agrave; <?= date('H:i', strtotime($c['departure_datetime'])) ?>
                        <?php if (!empty($c['arrival_datetime'])): ?>
                            &rarr; <?= date('H:i', strtotime($c['arrival_datetime'])) ?>
                        <?php endif; ?>
                    </p>
                    <p>
                        <span class="material-icons" style="vertical-align:middle;">person</span>
                        <?= htmlspecialchars($c['conducteur']) ?>
                        <?php if (round($c['note_conducteur'], 1) > 0): ?>
                            <span style="color:#ffd700;margin-left:8px;">&#9733; <?= round($c['note_conducteur'], 1) ?>/5</span>
                        <?php endif; ?>
                    </p>
                    <p><span class="material-icons">directions_car</span> <?= htmlspecialchars($c['brand']) ?> <?= htmlspecialchars($c['model']) ?></p>
                    <p><span class="material-icons">people</span> <?= (int)$c['available_seats'] ?> place<?= (int)$c['available_seats'] > 1 ? 's' : '' ?> restante<?= (int)$c['available_seats'] > 1 ? 's' : '' ?></p>
                </div>

                <?php if ($c['energy_type'] === 'electrique'): ?>
                    <div class="eco-badge">⚡ &Eacute;cologique</div>
                <?php endif; ?>

                <div class="ride-actions">
                    <a href="/trip/<?= (int)$c['trip_id'] ?>" class="btn-primary">Voir d&eacute;tails</a>
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
