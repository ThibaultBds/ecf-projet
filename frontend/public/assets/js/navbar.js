// navbar.js
// Ce fichier gère dynamiquement le menu de navigation sur toutes les pages
// Il doit être inclus avec : <script src="/navbar.js"></script> avant </body>

document.addEventListener('DOMContentLoaded', function() {
    // Utiliser window.ecorideUser s'il existe, sinon null
    renderMenu(window.ecorideUser || null);
});

function toggleAdvancedFilters() {
    const filters = document.getElementById('advanced-filters');
    if (filters) {
        filters.style.display = filters.style.display === 'none' ? 'block' : 'none';
    }
}

function renderMenu(user) {
    // Debug
    console.log('renderMenu called with user:', user);
    
    // Build inner content for the navbar. We will prefer filling the
    // placeholder <nav id="navbar"> if present to avoid duplicating nav nodes.
    const navInner = `
        <ul>
          <li><a class="nav-link" href="/pages/index.php">🏠 Accueil</a></li>
          <li><a class="nav-link" href="/pages/covoiturages.php">🚗 Covoiturages</a></li>
          <li><a class="nav-link" href="/pages/contact.php">📧 Contact</a></li>
        </ul>
        <div id="user-profile" class="nav-user">
          <a href="/pages/profil.php" class="nav-profile-link">
            <span class="material-icons nav-avatar">account_circle</span>
            <span id="user-name" class="nav-username">${user && user.pseudo ? user.pseudo : 'Profil'}</span>
          </a>
          <a id="logout-link" href="/pages/logout.php" class="nav-logout">Déconnexion</a>
        </div>
        <div id="auth-links" class="nav-auth">
          <a class="nav-auth-link" href="/pages/login_secure.php">Connexion</a>
          <a class="nav-auth-cta" href="/pages/register.php">S'inscrire</a>
        </div>
    `;

    // Injection du menu — prefer existing placeholder <nav id="navbar"> if present
    const header = document.querySelector('header.container-header');
    if (header) {
      // Reuse any existing nav: prefer the explicit placeholder by id,
      // otherwise reuse a nav with class .navbar if it already exists.
      const existingNav = header.querySelector('nav#navbar') || header.querySelector('nav.navbar');
      if (existingNav) {
        existingNav.classList.add('navbar');
        // ensure a stable id for future runs
        if (!existingNav.id) existingNav.id = 'navbar';
        existingNav.innerHTML = navInner;
      } else {
        // fallback: insert a new nav element with an id so subsequent calls reuse it
        header.insertAdjacentHTML('beforeend', `<nav id="navbar" class="navbar">${navInner}</nav>`);
      }

      // Toggle visibility classes based on auth state (no inline styles)
      const userProfileEl = header.querySelector('#user-profile');
      const authLinksEl = header.querySelector('#auth-links');
      if (user && user.email) {
        if (userProfileEl) userProfileEl.classList.add('visible');
        if (authLinksEl) authLinksEl.classList.remove('visible');
      } else {
        if (userProfileEl) userProfileEl.classList.remove('visible');
        if (authLinksEl) authLinksEl.classList.add('visible');
      }
    }

    // Déconnexion
    // Déconnexion — remove previous listeners by replacing the node, then attach
    let logoutLink = document.getElementById('logout-link');
    if (logoutLink) {
      // clone the node to remove prior event listeners (safe idempotent approach)
      const cloned = logoutLink.cloneNode(true);
      logoutLink.parentNode.replaceChild(cloned, logoutLink);
      cloned.addEventListener('click', function(e) {
        e.preventDefault();
        localStorage.removeItem('user');
        window.location.href = "/pages/logout.php";
      });
    }

    // Gestion modales mentions légales
    const openModalLegal = document.getElementById('openModalLegal');
    const closeModalLegal = document.getElementById('closeModalLegal');
    const modalLegal = document.getElementById('modal-legal');

    if (openModalLegal && modalLegal) {
        openModalLegal.addEventListener('click', function(e) {
            e.preventDefault();
            modalLegal.showModal();
        });
    }

    if (closeModalLegal && modalLegal) {
        closeModalLegal.addEventListener('click', function() {
            modalLegal.close();
        });
    }

    if (modalLegal) {
        modalLegal.addEventListener('click', function(e) {
            if (e.target === modalLegal) {
                modalLegal.close();
            }
        });
    }
}
