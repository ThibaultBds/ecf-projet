// === FONCTIONS UTILITAIRES ===
function validerEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <span class="material-icons" style="vertical-align:middle;margin-right:8px;">
            ${type === 'success' ? 'check_circle' : 'error'}
        </span>
        ${message}
    `;
    
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// === BASE DE DONNÉES DES DISTANCES ===
const distances = {
    'Paris-Lyon': 465, 'Paris-Marseille': 775, 'Paris-Nice': 930,
    'Paris-Toulouse': 680, 'Paris-Bordeaux': 580, 'Paris-Lille': 225,
    'Paris-Nantes': 380, 'Paris-Strasbourg': 490, 'Lyon-Marseille': 315,
    'Lyon-Nice': 470, 'Lyon-Toulouse': 540, 'Marseille-Nice': 200,
    'Toulouse-Bordeaux': 245, 'Lyon-Dijon': 190, 'Paris-Dijon': 315
};

function calculerDistanceApproximative(villeDepart, villeArrivee) {
    const cle1 = `${villeDepart}-${villeArrivee}`;
    const cle2 = `${villeArrivee}-${villeDepart}`;
    return distances[cle1] || distances[cle2] || 0;
}

function calculerTempsTrajet(distanceKm, vitesseMoyenne = 90) {
    const heures = distanceKm / vitesseMoyenne;
    const heuresEntieres = Math.floor(heures);
    const minutes = Math.round((heures - heuresEntieres) * 60);
    return `${heuresEntieres}h${minutes.toString().padStart(2, '0')}`;
}

function calculerPrixParPersonne(prixEssence, nombrePassagers) {
    if (nombrePassagers <= 0) return 0;
    return Math.round((prixEssence / (nombrePassagers + 1)) * 100) / 100;
}

function calculerEconomieCO2(distanceKm, nombrePassagers) {
    const co2ParKm = 0.12;
    const co2Total = distanceKm * co2ParKm;
    const co2Economise = co2Total * (nombrePassagers / (nombrePassagers + 1));
    return Math.round(co2Economise * 100) / 100;
}

function calculerEconomieTrajet(distanceKm, nombrePassagers, prixEssenceAuLitre = 1.65) {
    const consommation = 7;
    const coutEssence = (distanceKm / 100) * consommation * prixEssenceAuLitre;
    const prixParPersonne = calculerPrixParPersonne(coutEssence, nombrePassagers);
    const co2Economise = calculerEconomieCO2(distanceKm, nombrePassagers);
    
    return {
        distance: distanceKm,
        prixParPersonne: prixParPersonne,
        co2Economise: co2Economise,
        tempsTrajet: calculerTempsTrajet(distanceKm)
    };
}

// === CALCULATEUR DE COVOITURAGE ===
function mettreAJourCalculs() {
    const depart = document.getElementById('depart')?.value;
    const arrivee = document.getElementById('arrivee')?.value;
    const selectPlaces = document.querySelector('select');
    const places = selectPlaces?.value || '';
    
    const resultDiv = document.getElementById('calculation-results');
    
    if (depart && arrivee && places && places !== '') {
        const distance = calculerDistanceApproximative(depart, arrivee);
        
        if (distance > 0) {
            const nombrePassagers = places === '4+' ? 4 : parseInt(places);
            const economie = calculerEconomieTrajet(distance, nombrePassagers);
            
            document.getElementById('calc-distance').textContent = `${economie.distance} km`;
            document.getElementById('calc-time').textContent = economie.tempsTrajet;
            document.getElementById('calc-price').textContent = `${economie.prixParPersonne}€`;
            document.getElementById('calc-co2').textContent = `${economie.co2Economise} kg`;
            
            const oldBtn = document.getElementById('view-covoiturages-btn');
            if (oldBtn) {
                oldBtn.parentElement.remove();
            }
            
            const btnContainer = document.createElement('div');
            btnContainer.style.textAlign = 'center';
            btnContainer.style.marginTop = '20px';
            btnContainer.innerHTML = `
                <a href="covoiturages.php" id="view-covoiturages-btn"
                   style="display:inline-flex;align-items:center;gap:8px;background:#00b894;color:white;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:500;transition:all 0.3s ease;box-shadow:0 4px 15px rgba(0,184,148,0.3);">
                    <span class="material-icons">search</span>
                    Voir les covoiturages disponibles
                </a>
            `;
            resultDiv.appendChild(btnContainer);
            resultDiv.style.display = 'block';
        } else {
            resultDiv.style.display = 'none';
        }
    } else {
        resultDiv.style.display = 'none';
    }
}

// === CALENDRIER PERSONNALISÉ ===
function setupCalendar() {
    const dateInput = document.getElementById('dateInput');
    const calendarPopup = document.getElementById('calendarPopup');
    if (!dateInput || !calendarPopup) return;

    function pad(n) { return n < 10 ? '0' + n : n; }

    function renderCalendar(month, year) {
        const selected = dateInput.value.match(/^(\d{2}) \/ (\d{2}) \/ (\d{4})$/);
        let selectedDay = selected ? parseInt(selected[1], 10) : null;
        let selectedMonth = selected ? parseInt(selected[2], 10) - 1 : null;
        let selectedYear = selected ? parseInt(selected[3], 10) : null;

        let firstDay = new Date(year, month, 1).getDay();
        let daysInMonth = new Date(year, month + 1, 0).getDate();
        let html = `<table><thead><tr>
            <th>Lun</th><th>Mar</th><th>Mer</th><th>Jeu</th><th>Ven</th><th>Sam</th><th>Dim</th>
        </tr></thead><tbody><tr>`;
        
        let dayOfWeek = (firstDay + 6) % 7;
        for (let i = 0; i < dayOfWeek; i++) html += '<td></td>';
        
        for (let d = 1; d <= daysInMonth; d++) {
            let isSelected = (selectedDay === d && selectedMonth === month && selectedYear === year);
            html += `<td class="${isSelected ? 'selected' : ''}" data-day="${d}">${pad(d)}</td>`;
            dayOfWeek++;
            if (dayOfWeek === 7 && d !== daysInMonth) {
                html += '</tr><tr>';
                dayOfWeek = 0;
            }
        }
        
        while (dayOfWeek > 0 && dayOfWeek < 7) { 
            html += '<td></td>'; 
            dayOfWeek++; 
        }
        
        html += '</tr></tbody></table>';
        html += `<div style="text-align:center;margin-top:6px;">
            <button type="button" id="prevMonth" style="margin-right:10px;">&lt;</button>
            <span style="font-weight:bold;">${pad(month+1)} / ${year}</span>
            <button type="button" id="nextMonth" style="margin-left:10px;">&gt;</button>
        </div>`;
        
        calendarPopup.innerHTML = html;

        document.getElementById('prevMonth').onclick = function(e) {
            e.stopPropagation();
            renderCalendar(month === 0 ? 11 : month - 1, month === 0 ? year - 1 : year);
        };
        
        document.getElementById('nextMonth').onclick = function(e) {
            e.stopPropagation();
            renderCalendar(month === 11 ? 0 : month + 1, month === 11 ? year + 1 : year);
        };
        
        Array.from(calendarPopup.querySelectorAll('td[data-day]')).forEach(td => {
            td.onclick = function(e) {
                e.stopPropagation();
                let day = pad(parseInt(td.dataset.day, 10));
                let m = pad(month + 1);
                dateInput.value = `${day} / ${m} / ${year}`;
                calendarPopup.style.display = 'none';
            };
        });
    }

    dateInput.addEventListener('focus', function() {
        const rect = dateInput.getBoundingClientRect();
        calendarPopup.style.left = rect.left + window.scrollX + 'px';
        calendarPopup.style.top = (rect.bottom + window.scrollY + 2) + 'px';
        calendarPopup.style.display = 'block';
        let now = new Date();
        renderCalendar(now.getMonth(), now.getFullYear());
    });

    document.addEventListener('mousedown', function(e) {
        if (!calendarPopup.contains(e.target) && e.target !== dateInput) {
            calendarPopup.style.display = 'none';
        }
    });
}

// === FILTRES COVOITURAGES ===
function getFilteredTrajets() {
    if (typeof trajets === "undefined") return [];
    let filtered = trajets.slice();

    const elecFilter = document.getElementById('elecFilter');
    if (elecFilter && elecFilter.checked) {
        filtered = filtered.filter(t => t.elec);
    }

    const prixSlider = document.getElementById('prix-max-slider');
    if (prixSlider) {
        const maxPrix = parseInt(prixSlider.value, 10);
        filtered = filtered.filter(t => t.prix <= maxPrix);
    }

    const dureeFilter = document.getElementById('dureeFilter');
    if (dureeFilter && dureeFilter.checked) {
        filtered = filtered.filter(t => t.duree <= 5);
    }

    const placesFilter = document.getElementById('placesFilter');
    if (placesFilter) {
        const minPlaces = parseInt(placesFilter.value, 10);
        filtered = filtered.filter(t => t.places >= minPlaces);
    }

    return filtered;
}

function renderCards() {
    const cardsContainer = document.getElementById('cardsContainer');
    if (!cardsContainer || typeof trajets === "undefined") return;
    
    const filteredTrajets = getFilteredTrajets();
    cardsContainer.innerHTML = '';
    
    if (filteredTrajets.length === 0) {
        cardsContainer.innerHTML = '<p>Aucun covoiturage ne correspond à vos critères.</p>';
        return;
    }
    
    filteredTrajets.forEach(cov => {
        cardsContainer.innerHTML += `
            <div class="card">
                <img src="${cov.img}" alt="${cov.nom} portrait" />
                <h3>${cov.nom}</h3>
                <p>${cov.trajet}</p>
                <p>Note : ${'★'.repeat(cov.note)}</p>
                <p>Places : ${cov.places}</p>
                <p>Date : ${new Date(cov.date).toLocaleDateString('fr-FR')} à ${cov.heure || ''}</p>
                <p>Véhicule : ${cov.vehicule.marque} ${cov.vehicule.modele} (${cov.vehicule.energie})</p>
                <p>${cov.elec ? '<span style="color:#00b894;"><span class="material-icons" style="font-size:1.2em;vertical-align:middle;">electric_car</span> Trajet écologique</span>' : 'Trajet classique'}</p>
                <button class="btn-detail" onclick="window.location.href='details.html?id=${cov.id}'">Détail</button>
            </div>
        `;
    });
}

// === INITIALISATION ===
document.addEventListener('DOMContentLoaded', function() {
    // Modal mentions légales
    const openLegal = document.getElementById('openModalLegal');
    const modalLegal = document.getElementById('modal-legal');
    const closeLegal = document.getElementById('closeModalLegal');
    
    if (openLegal && modalLegal && closeLegal) {
        openLegal.addEventListener('click', function(e){
            e.preventDefault();
            modalLegal.showModal();
        });
        
        closeLegal.addEventListener('click', function(){
            modalLegal.close();
        });
        
        modalLegal.addEventListener('click', function(e){
            if(e.target === modalLegal) modalLegal.close();
        });
    }

    // Calendrier
    setupCalendar();

    // Calculateur
    // Note: Le formulaire de recherche dans index.php doit pouvoir soumettre normalement
    // Le calculateur est mis à jour sur les événements 'input' des champs
    
    const departInput = document.getElementById('depart');
    const arriveeInput = document.getElementById('arrivee');
    const placesSelect = document.querySelector('select');
    
    if (departInput) departInput.addEventListener('input', mettreAJourCalculs);
    if (arriveeInput) arriveeInput.addEventListener('input', mettreAJourCalculs);
    if (placesSelect) placesSelect.addEventListener('change', mettreAJourCalculs);

    // Filtres covoiturages
    if (document.getElementById('cardsContainer') && typeof trajets !== "undefined") {
        const elecFilter = document.getElementById('elecFilter');
        const prixSlider = document.getElementById('prix-max-slider');
        const dureeFilter = document.getElementById('dureeFilter');
        const placesFilter = document.getElementById('placesFilter');
        const resetBtn = document.getElementById('resetFilters');
        const prixValue = document.getElementById('prix-max-value');

        if (elecFilter) elecFilter.addEventListener('change', renderCards);
        if (prixSlider) {
            prixSlider.addEventListener('input', function() {
                if (prixValue) prixValue.textContent = prixSlider.value + "€";
                renderCards();
            });
        }
        if (dureeFilter) dureeFilter.addEventListener('change', renderCards);
        if (placesFilter) placesFilter.addEventListener('input', renderCards);

        if (resetBtn) {
            resetBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (elecFilter) elecFilter.checked = false;
                if (prixSlider) {
                    prixSlider.value = 50;
                    if (prixValue) prixValue.textContent = "50€";
                }
                if (dureeFilter) dureeFilter.checked = false;
                if (placesFilter) placesFilter.value = 1;
                renderCards();
            });
        }
        
        renderCards();
    }
});