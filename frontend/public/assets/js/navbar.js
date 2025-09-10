// navbar.js
// Ce fichier gère dynamiquement le menu de navigation sur toutes les pages
// Il doit être inclus avec : <script src="/assets/js/navbar.js"></script> avant </body>

document.addEventListener('DOMContentLoaded', function() {
    renderMenu(window.ecorideUser || null);
});

function toggleAdvancedFilters() {
    const filters = document.getElementById('advanced-filters');
    if (filters) {
        filters.style.display = filters.style.display === 'none' ? 'block' : 'none';
    }
}

function renderMenu(user) {
    // Détermine le lien profil selon le rôle
    let profileLink = "/profil.php";
    if (user && user.role === "Administrateur") {
        profileLink = "/admin.php";
    } else if (user && user.role === "Moderateur") {
        profileLink = "/moderateur.php";
    }

    let navHtml = `
      <nav class="navbar">
        <ul>
          <li><a href="/index.php">🏠 Accueil</a></li>
          <li><a href="/covoiturages.php">🚗 Covoiturages</a></li>
          <li><a href="/contact.php">📧 Contact</a></li>
        </ul>
        <div id="user-profile" style="display:${user && user.email ? 'flex' : 'none'};align-items:center;gap:10px;">
          <a href="${profileLink}" style="display:flex;align-items:center;gap:8px;color:white;text-decoration:none;">
            <span class="material-icons" style="font-size:32px;border-radius:50%;background:#e0e0e0;color:#00b894;padding:4px;">account_circle</span>
            <span id="user-name" style="font-weight:600;">${user && user.pseudo ? user.pseudo : 'Profil'}</span>
          </a>
          <a id="logout-link" href="/logout.php" style="color:white;text-decoration:none;margin-left:10px;">Déconnexion</a>
        </div>
        <div id="auth-links" style="display:${user && user.email ? 'none' : 'flex'};gap:10px;">
          <a href="/login_secure.php" style="color:white;text-decoration:none;padding:8px 16px;border:1px solid rgba(255,255,255,0.3);border-radius:4px;">Connexion</a>
          <a href="/register.php" style="color:white;text-decoration:none;padding:8px 16px;background:rgba(255,255,255,0.2);border-radius:4px;">S'inscrire</a>
        </div>
      </nav>
    `;

    // Injection
    const header = document.querySelector('header.container-header');
    if (header) {
      const oldNav = header.querySelector('nav.navbar');
      if (oldNav) oldNav.remove();
      header.insertAdjacentHTML('beforeend', navHtml);
    }

    // Déconnexion
    const logoutLink = document.getElementById('logout-link');
    if (logoutLink) {
      logoutLink.addEventListener('click', function(e) {
        e.preventDefault();
        localStorage.removeItem('user');
        window.location.href = "/logout.php";
      });
    }

    // Gestion modale Mentions légales
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
