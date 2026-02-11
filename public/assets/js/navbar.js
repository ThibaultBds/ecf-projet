// navbar.js — version adaptée MVC EcoRide

document.addEventListener('DOMContentLoaded', function () {
    renderMenu(window.ecorideUser || null);
});

function renderMenu(user) {

    const navInner = `
        <ul class="nav-links">
            <li><a class="nav-link" href="/">🏠 Accueil</a></li>
            <li><a class="nav-link" href="/covoiturages">🚗 Covoiturages</a></li>
            <li><a class="nav-link" href="/contact">📧 Contact</a></li>
        </ul>

        <div id="user-profile" class="nav-user">
            <a href="/profil" class="nav-profile-link">
                <span class="material-icons nav-avatar">account_circle</span>
                <span class="nav-username">
                    ${user && user.pseudo ? user.pseudo : 'Profil'}
                </span>
            </a>
            <a id="logout-link" href="/logout" class="nav-logout">Déconnexion</a>
        </div>

        <div id="auth-links" class="nav-auth">
            <a class="nav-auth-link" href="/login">Connexion</a>
            <a class="nav-auth-cta" href="/register">S'inscrire</a>
        </div>
    `;

    const header = document.querySelector('header.container-header');
    if (!header) return;

    let existingNav = header.querySelector('#navbar');

    if (existingNav) {
        existingNav.innerHTML = navInner;
    } else {
        header.insertAdjacentHTML('beforeend', `
            <nav id="navbar" class="navbar">
                ${navInner}
            </nav>
        `);
    }

    // Gestion affichage connecté / non connecté
    const userProfileEl = document.getElementById('user-profile');
    const authLinksEl = document.getElementById('auth-links');

    if (user && user.email) {
        if (userProfileEl) userProfileEl.classList.add('visible');
        if (authLinksEl) authLinksEl.classList.remove('visible');
    } else {
        if (userProfileEl) userProfileEl.classList.remove('visible');
        if (authLinksEl) authLinksEl.classList.add('visible');
    }

    // Déconnexion
    const logoutLink = document.getElementById('logout-link');
    if (logoutLink) {
        logoutLink.addEventListener('click', function (e) {
            e.preventDefault();
            window.location.href = "/logout";
        });
    }
}
