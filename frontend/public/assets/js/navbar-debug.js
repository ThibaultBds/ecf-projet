// Navbar simple et robuste pour EcoRide
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔧 Chargement du navbar...');
    
    // Créer la navbar dynamiquement
    const header = document.querySelector('.container-header');
    if (!header) {
        console.error('❌ Header .container-header non trouvé');
        return;
    }
    
    console.log('✅ Header trouvé, création du menu...');
    
    const nav = document.createElement('nav');
    nav.className = 'header-nav';
    nav.innerHTML = `
        <ul class="nav-links">
            <li><a href="/Ecoridegit/">Accueil</a></li>
            <li><a href="/Ecoridegit/frontend/src/pages/covoiturages.php">Covoiturages</a></li>
            <li><a href="/Ecoridegit/frontend/src/pages/contact.php">Contact</a></li>
        </ul>
        <ul class="auth-links" id="auth-links">
            <li><a href="/Ecoridegit/frontend/src/pages/login_secure.php">Connexion</a></li>
            <li><a href="/Ecoridegit/frontend/src/pages/register.php">Inscription</a></li>
        </ul>
    `;
    header.appendChild(nav);
    
    console.log('✅ Menu créé !');
    
    // Charger l'état de connexion (optionnel)
    loadAuthState();
});

function loadAuthState() {
    console.log('🔍 Vérification de l\'état de connexion...');
    
    fetch('/Ecoridegit/backend/public/check_auth.php')
        .then(response => {
            console.log('📡 Réponse reçue:', response.status);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('📋 Données:', data);
            const authLinks = document.getElementById('auth-links');
            if (authLinks) {
                if (data.isLoggedIn) {
                    authLinks.innerHTML = `
                        <li><a href="/Ecoridegit/backend/public/profil.php">Mon Profil</a></li>
                        <li><a href="/Ecoridegit/backend/public/logout.php">Déconnexion</a></li>
                    `;
                    console.log('✅ Utilisateur connecté');
                } else {
                    console.log('ℹ️ Utilisateur non connecté');
                }
            }
        })
        .catch(error => {
            console.warn('⚠️ Erreur auth (normal si pas connecté):', error);
        });
}

// Fonctions utilitaires (conservées de l'ancien fichier)
function toggleAdvancedFilters() {
    const filters = document.getElementById('advanced-filters');
    if (filters) {
        filters.style.display = filters.style.display === 'none' ? 'block' : 'none';
    }
}
