<section class="page-header">
    <h2>Covoiturages disponibles</h2>
    <p>Trouvez le trajet qui vous convient !</p>
</section>

<section>
    <form method="GET" action="/trips" class="search-bar">
        <input type="text" name="depart" placeholder="Ville de départ" value="<?= htmlspecialchars($filters['depart'] ?? '') ?>" list="villes" required>
        <input type="text" name="arrivee" placeholder="Ville d'arrivée" value="<?= htmlspecialchars($filters['arrivee'] ?? '') ?>" list="villes" required>
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

<section class="covoiturages-list trips-list-wrap">

    <div class="trips-advanced-wrap" id="filters-wrap" <?= empty($hasSearched) ? 'style="display:none"' : '' ?>>
        <button type="button" id="toggle-filters" class="trips-filter-toggle-btn">
            <span class="material-icons trips-filter-toggle-icon">tune</span> Filtres avancés
        </button>
        <div id="advanced-filters" class="trips-advanced-filters">
            <form method="get" action="/trips">
                <input type="hidden" name="depart" value="<?= htmlspecialchars($filters['depart'] ?? '') ?>">
                <input type="hidden" name="arrivee" value="<?= htmlspecialchars($filters['arrivee'] ?? '') ?>">
                <input type="hidden" name="date" value="<?= htmlspecialchars($filters['date'] ?? '') ?>">

                <div class="trips-advanced-grid">
                    <div>
                        <label for="prix_max" class="trips-advanced-label">
                            <span class="material-icons trips-advanced-icon-green">euro</span> Prix maximum
                        </label>
                        <input type="number" name="prix_max" id="prix_max" min="1" max="100" step="1"
                               value="<?= htmlspecialchars($filters['prix_max'] ?? '') ?>"
                               placeholder="Ex: 30"
                               class="trips-advanced-input">
                    </div>
                    <div>
                        <label for="duree_max" class="trips-advanced-label">
                            <span class="material-icons trips-advanced-icon-green">schedule</span> Durée max (heures)
                        </label>
                        <input type="number" name="duree_max" id="duree_max" min="1" max="24" step="1"
                               value="<?= htmlspecialchars($filters['duree_max'] ?? '') ?>"
                               placeholder="Ex: 4"
                               class="trips-advanced-input">
                    </div>
                    <div>
                        <label for="note_min" class="trips-advanced-label">
                            <span class="material-icons trips-advanced-icon-star">star</span> Note minimum
                        </label>
                        <select name="note_min" id="note_min" class="trips-advanced-input">
                            <option value="">Toutes</option>
                            <option value="1" <?= ($filters['note_min'] ?? '') == '1' ? 'selected' : '' ?>>1+</option>
                            <option value="2" <?= ($filters['note_min'] ?? '') == '2' ? 'selected' : '' ?>>2+</option>
                            <option value="3" <?= ($filters['note_min'] ?? '') == '3' ? 'selected' : '' ?>>3+</option>
                            <option value="4" <?= ($filters['note_min'] ?? '') == '4' ? 'selected' : '' ?>>4+</option>
                            <option value="5" <?= ($filters['note_min'] ?? '') == '5' ? 'selected' : '' ?>>5</option>
                        </select>
                    </div>
                    <div>
                        <label for="ecologique" class="trips-advanced-label">
                            <span class="trips-advanced-eco">⚡</span> Écologique
                        </label>
                        <select name="ecologique" id="ecologique" class="trips-advanced-input">
                            <option value="">Tous</option>
                            <option value="1" <?= ($filters['ecologique'] ?? '') == '1' ? 'selected' : '' ?>>Oui uniquement</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="trips-advanced-submit">Appliquer les filtres</button>
            </form>
        </div>
    </div>

    <?php if (!empty($isDriver)): ?>
        <div class="trips-driver-cta">
            <div class="trips-driver-cta-left">
                <span class="material-icons trips-driver-cta-icon">add_road</span>
                <div>
                    <strong class="trips-driver-cta-title">Vous êtes chauffeur</strong>
                    <p class="trips-driver-cta-text">Proposez un trajet et gagnez des crédits.</p>
                </div>
            </div>
            <a href="/driver/create-trip" class="btn-primary trips-driver-cta-btn">
                <span class="material-icons trips-driver-cta-btn-icon">add</span>
                Créer un trajet
            </a>
        </div>
    <?php elseif (!empty($isLoggedIn)): ?>
        <div class="trips-info-box">
            <span class="material-icons trips-info-icon">info</span>
            <p class="trips-info-text">Vous souhaitez proposer des trajets ?
                <a href="/profile" class="trips-info-link">Activez le mode chauffeur</a> depuis votre profil.
            </p>
        </div>
    <?php endif; ?>

    <div id="trips-results">
    <?php if (empty($hasSearched)): ?>
        <div class="trips-empty-state">
            <span class="material-icons trips-empty-icon">travel_explore</span>
            <h3 class="trips-empty-title">Recherchez un covoiturage</h3>
            <p class="trips-empty-text">Renseignez une ville de départ, d'arrivée et une date pour découvrir les trajets disponibles.</p>
        </div>

    <?php elseif (empty($covoiturages)): ?>
        <div class="trips-none-found">
            <span class="material-icons trips-none-icon">search_off</span>
            <h3 class="trips-none-title">Aucun covoiturage trouvé</h3>
            <p class="trips-none-text">Aucun trajet ne correspond à vos critères pour cette date.</p>

            <?php if (!empty($nearestDate)): ?>
                <div class="trips-nearest-box">
                    <p class="trips-nearest-text">
                        <span class="material-icons trips-nearest-icon">event</span>
                        Un trajet est disponible le <?= date('d/m/Y', strtotime($nearestDate)) ?>
                    </p>
                    <a href="/trips?depart=<?= urlencode($filters['depart']) ?>&arrivee=<?= urlencode($filters['arrivee']) ?>&date=<?= $nearestDate ?>"
                       class="trips-nearest-link">
                        Voir ce trajet
                    </a>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <p class="trips-count-text">
            <?= count($covoiturages) ?> trajet<?= count($covoiturages) > 1 ? 's' : '' ?> trouvé<?= count($covoiturages) > 1 ? 's' : '' ?>
        </p>

        <?php foreach ($covoiturages as $c): ?>
            <div class="ride-card"
                 data-price="<?= $c->price ?>"
                 data-ecological="<?= $c->energyType === 'electrique' ? 'true' : 'false' ?>"
                 data-rating="<?= $c->noteConducteur ?? 0 ?>">

                <div class="ride-header">
                    <h3><?= htmlspecialchars($c->villeDepart) ?> &rarr; <?= htmlspecialchars($c->villeArrivee) ?></h3>
                    <div class="ride-price"><?= number_format($c->price, 2) ?>€</div>
                </div>

                <div class="ride-details">
                    <p>
                        <span class="material-icons">schedule</span>
                        <?= date('d/m/Y', strtotime($c->departureDatetime)) ?> à <?= date('H:i', strtotime($c->departureDatetime)) ?>
                        <?php if (!empty($c->arrivalDatetime)): ?>
                            &rarr; <?= date('H:i', strtotime($c->arrivalDatetime)) ?>
                        <?php endif; ?>
                    </p>
                    <p class="trips-driver-line">
                        <?php if (!empty($c->conducteurPhoto)): ?>
                            <img src="/uploads/<?= htmlspecialchars($c->conducteurPhoto) ?>" alt="Photo" class="trips-driver-photo">
                        <?php else: ?>
                            <span class="material-icons trips-driver-fallback">account_circle</span>
                        <?php endif; ?>
                        <?= htmlspecialchars($c->conducteur) ?>
                        <?php if (round($c->noteConducteur, 1) > 0): ?>
                            <span class="trips-driver-rating">&#9733; <?= round($c->noteConducteur, 1) ?>/5</span>
                        <?php endif; ?>
                    </p>
                    <p><span class="material-icons">directions_car</span> <?= htmlspecialchars($c->brand) ?> <?= htmlspecialchars($c->model) ?></p>
                    <p><span class="material-icons">people</span> <?= (int) $c->availableSeats ?> place<?= (int) $c->availableSeats > 1 ? 's' : '' ?> restante<?= (int) $c->availableSeats > 1 ? 's' : '' ?></p>
                </div>

                <?php if ($c->energyType === 'electrique'): ?>
                    <div class="eco-badge">⚡ Écologique</div>
                <?php else: ?>
                    <div class="eco-badge trips-non-eco-badge">🚗 Non écologique</div>
                <?php endif; ?>

                <div class="ride-actions">
                    <a href="/trip/<?= $c->tripId ?>" class="btn-primary">Voir détails</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div><!-- #trips-results -->
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle filtres avancés
    const toggleBtn = document.getElementById('toggle-filters');
    const filtersBlock = document.getElementById('advanced-filters');
    if (toggleBtn && filtersBlock) {
        toggleBtn.addEventListener('click', function() {
            filtersBlock.classList.toggle('is-open');
        });
    }

    // Fetch search
    const form = document.querySelector('form.search-bar');
    const resultsContainer = document.getElementById('trips-results');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const params = new URLSearchParams({
            depart: form.querySelector('[name="depart"]').value,
            arrivee: form.querySelector('[name="arrivee"]').value,
            date: form.querySelector('[name="date"]').value,
        });

        resultsContainer.innerHTML = '<p>Chargement...</p>';

        fetch('/trips/search?' + params)
            .then(res => res.json())
            .then(function(data) {
                document.getElementById('filters-wrap').style.display = '';
                const trajets = data.trips;
                const nearestDate = data.nearestDate;

                if (trajets.length === 0) {
                    let html = '<div class="trips-none-found"><span class="material-icons trips-none-icon">search_off</span><h3 class="trips-none-title">Aucun covoiturage trouvé</h3><p class="trips-none-text">Aucun trajet ne correspond à vos critères pour cette date.</p>';
                    if (nearestDate) {
                        const d = new Date(nearestDate);
                        const formatted = d.toLocaleDateString('fr-FR');
                        html += `<div class="trips-nearest-box"><p class="trips-nearest-text"><span class="material-icons trips-nearest-icon">event</span> Un trajet est disponible le ${formatted}</p><a href="/trips?depart=${encodeURIComponent(params.get('depart'))}&arrivee=${encodeURIComponent(params.get('arrivee'))}&date=${nearestDate}" class="trips-nearest-link">Voir ce trajet</a></div>`;
                    }
                    html += '</div>';
                    resultsContainer.innerHTML = html;
                    return;
                }

                let html = '<p>' + trajets.length + ' trajet(s) trouvé(s)</p>';
                trajets.forEach(function(c) {
                    html += `
                        <div class="ride-card">
                            <div class="ride-header">
                                <h3>${c.villeDepart} &rarr; ${c.villeArrivee}</h3>
                                <div class="ride-price">${parseFloat(c.price).toFixed(2)}€</div>
                            </div>
                            <div class="ride-details">
                                <p>${c.conducteur} &bull; ${c.brand} ${c.model}</p>
                                <p>${c.availableSeats} place(s) &bull; ${c.departureDatetime}</p>
                            </div>
                            <div class="ride-actions">
                                <a href="/trip/${c.tripId}" class="btn-primary">Voir détails</a>
                            </div>
                        </div>`;
                });
                resultsContainer.innerHTML = html;
            })
            .catch(function() {
                resultsContainer.innerHTML = '<p>Erreur lors de la recherche.</p>';
            });
    });
});
</script>
