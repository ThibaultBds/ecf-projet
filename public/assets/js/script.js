// === FONCTIONS UTILITAIRES ===

function validerEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showToast(message, type) {
    type = type || 'success';
    const toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerHTML =
        '<span class="material-icons toast-icon">' +
        (type === 'success' ? 'check_circle' : 'error') +
        '</span>' + message;

    document.body.appendChild(toast);
    setTimeout(function() { toast.classList.add('show'); }, 100);
    setTimeout(function() {
        toast.classList.remove('show');
        setTimeout(function() { toast.remove(); }, 300);
    }, 3000);
}

// === BASE DE DONNEES DES DISTANCES ===

const distances = {
    'Paris-Lyon': 465, 'Paris-Marseille': 775, 'Paris-Nice': 930,
    'Paris-Toulouse': 680, 'Paris-Bordeaux': 580, 'Paris-Lille': 225,
    'Paris-Nantes': 380, 'Paris-Strasbourg': 490, 'Lyon-Marseille': 315,
    'Lyon-Nice': 470, 'Lyon-Toulouse': 540, 'Marseille-Nice': 200,
    'Toulouse-Bordeaux': 245, 'Lyon-Dijon': 190, 'Paris-Dijon': 315
};

function calculerDistanceApproximative(villeDepart, villeArrivee) {
    const cle1 = villeDepart + '-' + villeArrivee;
    const cle2 = villeArrivee + '-' + villeDepart;
    return distances[cle1] || distances[cle2] || 0;
}

function calculerTempsTrajet(distanceKm, vitesseMoyenne) {
    vitesseMoyenne = vitesseMoyenne || 90;
    const heures = distanceKm / vitesseMoyenne;
    const heuresEntieres = Math.floor(heures);
    const minutes = Math.round((heures - heuresEntieres) * 60);
    return heuresEntieres + 'h' + (minutes < 10 ? '0' : '') + minutes;
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

function calculerEconomieTrajet(distanceKm, nombrePassagers, prixEssenceAuLitre) {
    prixEssenceAuLitre = prixEssenceAuLitre || 1.65;
    const consommation = 7;
    const coutEssence = (distanceKm / 100) * consommation * prixEssenceAuLitre;
    return {
        distance: distanceKm,
        prixParPersonne: calculerPrixParPersonne(coutEssence, nombrePassagers),
        co2Economise: calculerEconomieCO2(distanceKm, nombrePassagers),
        tempsTrajet: calculerTempsTrajet(distanceKm)
    };
}

// === CALCULATEUR DE COVOITURAGE (page accueil) ===

function mettreAJourCalculs() {
    const depart = document.getElementById('depart');
    const arrivee = document.getElementById('arrivee');
    const selectPlaces = document.querySelector('.search-bar select[name="places"]');
    const resultDiv = document.getElementById('calculation-results');

    if (!depart || !arrivee || !selectPlaces || !resultDiv) return;

    const villeDepart = depart.value;
    const villeArrivee = arrivee.value;
    const places = selectPlaces.value;

    if (villeDepart && villeArrivee && places && places !== '') {
        const distance = calculerDistanceApproximative(villeDepart, villeArrivee);

        if (distance > 0) {
            const nombrePassagers = places === '4+' ? 4 : parseInt(places, 10);
            const economie = calculerEconomieTrajet(distance, nombrePassagers);

            document.getElementById('calc-distance').textContent = economie.distance + ' km';
            document.getElementById('calc-time').textContent = economie.tempsTrajet;
            document.getElementById('calc-price').textContent = economie.prixParPersonne + '\u20AC';
            document.getElementById('calc-co2').textContent = economie.co2Economise + ' kg';

            resultDiv.style.display = 'block';
        } else {
            resultDiv.style.display = 'none';
        }
    } else {
        resultDiv.style.display = 'none';
    }
}

// === CALENDRIER PERSONNALISE ===

function setupCalendar() {
    const dateInput = document.getElementById('dateInput');
    const calendarPopup = document.getElementById('calendarPopup');
    if (!dateInput || !calendarPopup) return;

    function pad(n) { return n < 10 ? '0' + n : n; }

    function renderCalendar(month, year) {
        const selected = dateInput.value.match(/^(\d{2}) \/ (\d{2}) \/ (\d{4})$/);
        const selectedDay = selected ? parseInt(selected[1], 10) : null;
        const selectedMonth = selected ? parseInt(selected[2], 10) - 1 : null;
        const selectedYear = selected ? parseInt(selected[3], 10) : null;

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        let html = '<table><thead><tr>' +
            '<th>Lun</th><th>Mar</th><th>Mer</th><th>Jeu</th><th>Ven</th><th>Sam</th><th>Dim</th>' +
            '</tr></thead><tbody><tr>';

        let dayOfWeek = (firstDay + 6) % 7;
        for (let i = 0; i < dayOfWeek; i++) html += '<td></td>';

        for (let d = 1; d <= daysInMonth; d++) {
            const isSelected = (selectedDay === d && selectedMonth === month && selectedYear === year);
            html += '<td class="' + (isSelected ? 'selected' : '') + '" data-day="' + d + '">' + pad(d) + '</td>';
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
        html += '<div class="calendar-controls">' +
            '<button type="button" id="prevMonth" class="calendar-nav-btn prev">&lt;</button>' +
            '<span class="calendar-current">' + pad(month + 1) + ' / ' + year + '</span>' +
            '<button type="button" id="nextMonth" class="calendar-nav-btn next">&gt;</button>' +
            '</div>';

        calendarPopup.innerHTML = html;

        document.getElementById('prevMonth').onclick = function(e) {
            e.stopPropagation();
            renderCalendar(month === 0 ? 11 : month - 1, month === 0 ? year - 1 : year);
        };
        document.getElementById('nextMonth').onclick = function(e) {
            e.stopPropagation();
            renderCalendar(month === 11 ? 0 : month + 1, month === 11 ? year + 1 : year);
        };

        const cells = calendarPopup.querySelectorAll('td[data-day]');
        for (let c = 0; c < cells.length; c++) {
            (function(td) {
                td.onclick = function(e) {
                    e.stopPropagation();
                    const day = pad(parseInt(td.dataset.day, 10));
                    const m = pad(month + 1);
                    dateInput.value = day + ' / ' + m + ' / ' + year;
                    calendarPopup.style.display = 'none';
                };
            })(cells[c]);
        }
    }

    dateInput.addEventListener('focus', function() {
        const rect = dateInput.getBoundingClientRect();
        calendarPopup.style.left = rect.left + window.scrollX + 'px';
        calendarPopup.style.top = (rect.bottom + window.scrollY + 2) + 'px';
        calendarPopup.style.display = 'block';
        const now = new Date();
        renderCalendar(now.getMonth(), now.getFullYear());
    });

    document.addEventListener('mousedown', function(e) {
        if (!calendarPopup.contains(e.target) && e.target !== dateInput) {
            calendarPopup.style.display = 'none';
        }
    });
}

// === INITIALISATION ===

document.addEventListener('DOMContentLoaded', function() {
    // Calendrier personnalise
    setupCalendar();

    // Calculateur (page accueil)
    const departInput = document.getElementById('depart');
    const arriveeInput = document.getElementById('arrivee');
    const placesSelect = document.querySelector('.search-bar select[name="places"]');

    if (departInput) departInput.addEventListener('input', mettreAJourCalculs);
    if (arriveeInput) arriveeInput.addEventListener('input', mettreAJourCalculs);
    if (placesSelect) placesSelect.addEventListener('change', mettreAJourCalculs);

    // CTA dynamique (page accueil)
    const ctaDynamic = document.getElementById('cta-dynamic');
    if (ctaDynamic && window.ecorideUser) {
        ctaDynamic.textContent = 'Voir mes trajets';
        ctaDynamic.href = '/my-trips';
        ctaDynamic.classList.remove('secondary');
    }
});
