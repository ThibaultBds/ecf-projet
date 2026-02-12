// === FONCTIONS UTILITAIRES ===

function validerEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function showToast(message, type) {
    type = type || 'success';
    var toast = document.createElement('div');
    toast.className = 'toast ' + type;
    toast.innerHTML =
        '<span class="material-icons" style="vertical-align:middle;margin-right:8px;">' +
        (type === 'success' ? 'check_circle' : 'error') +
        '</span>' + message;

    document.body.appendChild(toast);
    setTimeout(function() { toast.classList.add('show'); }, 100);
    setTimeout(function() {
        toast.classList.remove('show');
        setTimeout(function() { toast.remove(); }, 300);
    }, 3000);
}

// === BASE DE DONNÉES DES DISTANCES ===

var distances = {
    'Paris-Lyon': 465, 'Paris-Marseille': 775, 'Paris-Nice': 930,
    'Paris-Toulouse': 680, 'Paris-Bordeaux': 580, 'Paris-Lille': 225,
    'Paris-Nantes': 380, 'Paris-Strasbourg': 490, 'Lyon-Marseille': 315,
    'Lyon-Nice': 470, 'Lyon-Toulouse': 540, 'Marseille-Nice': 200,
    'Toulouse-Bordeaux': 245, 'Lyon-Dijon': 190, 'Paris-Dijon': 315
};

function calculerDistanceApproximative(villeDepart, villeArrivee) {
    var cle1 = villeDepart + '-' + villeArrivee;
    var cle2 = villeArrivee + '-' + villeDepart;
    return distances[cle1] || distances[cle2] || 0;
}

function calculerTempsTrajet(distanceKm, vitesseMoyenne) {
    vitesseMoyenne = vitesseMoyenne || 90;
    var heures = distanceKm / vitesseMoyenne;
    var heuresEntieres = Math.floor(heures);
    var minutes = Math.round((heures - heuresEntieres) * 60);
    return heuresEntieres + 'h' + (minutes < 10 ? '0' : '') + minutes;
}

function calculerPrixParPersonne(prixEssence, nombrePassagers) {
    if (nombrePassagers <= 0) return 0;
    return Math.round((prixEssence / (nombrePassagers + 1)) * 100) / 100;
}

function calculerEconomieCO2(distanceKm, nombrePassagers) {
    var co2ParKm = 0.12;
    var co2Total = distanceKm * co2ParKm;
    var co2Economise = co2Total * (nombrePassagers / (nombrePassagers + 1));
    return Math.round(co2Economise * 100) / 100;
}

function calculerEconomieTrajet(distanceKm, nombrePassagers, prixEssenceAuLitre) {
    prixEssenceAuLitre = prixEssenceAuLitre || 1.65;
    var consommation = 7;
    var coutEssence = (distanceKm / 100) * consommation * prixEssenceAuLitre;
    return {
        distance: distanceKm,
        prixParPersonne: calculerPrixParPersonne(coutEssence, nombrePassagers),
        co2Economise: calculerEconomieCO2(distanceKm, nombrePassagers),
        tempsTrajet: calculerTempsTrajet(distanceKm)
    };
}

// === CALCULATEUR DE COVOITURAGE (page accueil) ===

function mettreAJourCalculs() {
    var depart = document.getElementById('depart');
    var arrivee = document.getElementById('arrivee');
    var selectPlaces = document.querySelector('.search-bar select[name="places"]');
    var resultDiv = document.getElementById('calculation-results');

    if (!depart || !arrivee || !selectPlaces || !resultDiv) return;

    var villeDepart = depart.value;
    var villeArrivee = arrivee.value;
    var places = selectPlaces.value;

    if (villeDepart && villeArrivee && places && places !== '') {
        var distance = calculerDistanceApproximative(villeDepart, villeArrivee);

        if (distance > 0) {
            var nombrePassagers = places === '4+' ? 4 : parseInt(places);
            var economie = calculerEconomieTrajet(distance, nombrePassagers);

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

// === CALENDRIER PERSONNALISÉ ===

function setupCalendar() {
    var dateInput = document.getElementById('dateInput');
    var calendarPopup = document.getElementById('calendarPopup');
    if (!dateInput || !calendarPopup) return;

    function pad(n) { return n < 10 ? '0' + n : n; }

    function renderCalendar(month, year) {
        var selected = dateInput.value.match(/^(\d{2}) \/ (\d{2}) \/ (\d{4})$/);
        var selectedDay = selected ? parseInt(selected[1], 10) : null;
        var selectedMonth = selected ? parseInt(selected[2], 10) - 1 : null;
        var selectedYear = selected ? parseInt(selected[3], 10) : null;

        var firstDay = new Date(year, month, 1).getDay();
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var html = '<table><thead><tr>' +
            '<th>Lun</th><th>Mar</th><th>Mer</th><th>Jeu</th><th>Ven</th><th>Sam</th><th>Dim</th>' +
            '</tr></thead><tbody><tr>';

        var dayOfWeek = (firstDay + 6) % 7;
        for (var i = 0; i < dayOfWeek; i++) html += '<td></td>';

        for (var d = 1; d <= daysInMonth; d++) {
            var isSelected = (selectedDay === d && selectedMonth === month && selectedYear === year);
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
        html += '<div style="text-align:center;margin-top:6px;">' +
            '<button type="button" id="prevMonth" style="margin-right:10px;">&lt;</button>' +
            '<span style="font-weight:bold;">' + pad(month + 1) + ' / ' + year + '</span>' +
            '<button type="button" id="nextMonth" style="margin-left:10px;">&gt;</button>' +
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

        var cells = calendarPopup.querySelectorAll('td[data-day]');
        for (var c = 0; c < cells.length; c++) {
            (function(td) {
                td.onclick = function(e) {
                    e.stopPropagation();
                    var day = pad(parseInt(td.dataset.day, 10));
                    var m = pad(month + 1);
                    dateInput.value = day + ' / ' + m + ' / ' + year;
                    calendarPopup.style.display = 'none';
                };
            })(cells[c]);
        }
    }

    dateInput.addEventListener('focus', function() {
        var rect = dateInput.getBoundingClientRect();
        calendarPopup.style.left = rect.left + window.scrollX + 'px';
        calendarPopup.style.top = (rect.bottom + window.scrollY + 2) + 'px';
        calendarPopup.style.display = 'block';
        var now = new Date();
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
    // Calendrier personnalisé
    setupCalendar();

    // Calculateur (page accueil)
    var departInput = document.getElementById('depart');
    var arriveeInput = document.getElementById('arrivee');
    var placesSelect = document.querySelector('.search-bar select[name="places"]');

    if (departInput) departInput.addEventListener('input', mettreAJourCalculs);
    if (arriveeInput) arriveeInput.addEventListener('input', mettreAJourCalculs);
    if (placesSelect) placesSelect.addEventListener('change', mettreAJourCalculs);

    // CTA dynamique (page accueil)
    var ctaDynamic = document.getElementById('cta-dynamic');
    if (ctaDynamic && window.ecorideUser) {
        ctaDynamic.textContent = 'Voir mes trajets';
        ctaDynamic.href = '/my-trips';
        ctaDynamic.classList.remove('secondary');
    }
});
