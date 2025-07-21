<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
  <title>EcoRide - Accueil</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/style.css?v=2025">
   <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
  <header class="container-header">
    <h1>
      <a href="index.php" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:10px;">
        <span class="material-icons">eco</span> EcoRide
      </a>
    </h1>
  </header>
  <script>
  window.ecorideUser = <?php
    if (isset($_SESSION['user'])) {
      $u = $_SESSION['user'];
      echo json_encode([
        'email' => $u['email'],
        'pseudo' => $u['pseudo'],
        'type' => $u['type']
      ]);
    } else {
      echo 'null';
    }
  ?>;
  </script>
  <main>
    <section class="hero">
      <h2>Voyagez vert, partagez vos trajets !</h2>
      <p>
        Faites un geste pour la planète et économisez sur vos trajets quotidiens.<br>
        Rejoignez la communauté <strong>EcoRide</strong> du covoiturage écologique.
      </p>
      <a href="covoiturages.php" class="cta-btn">Découvrir les covoiturages</a>
      <a href="login_secure.php" class="cta-btn secondary">Connexion</a>
    </section>

    <!-- Formulaire de recherche placé sous le hero, centré et espacé -->
    <div style="display:flex;justify-content:center;margin:30px 0;">
      <form class="search-bar" style="margin:0;max-width:900px;width:100%;box-sizing:border-box;">
        <label for="depart" style="display:none;">Départ</label>
        <input type="text" id="depart" placeholder="Départ" list="villes" name="depart" autocomplete="on">
        <label for="arrivee" style="display:none;">Arrivée</label>
        <input type="text" id="arrivee" placeholder="Arrivée" list="villes" name="arrivee" autocomplete="on">
        <datalist id="villes">
          <option value="Paris">
          <option value="Lyon">
          <option value="Marseille">
          <option value="Nice">
          <option value="Dijon">
          <option value="Bordeaux">
          <option value="Toulouse">
          <option value="Lille">
          <option value="Nantes">
          <option value="Strasbourg">
        </datalist>
        <input type="text" id="dateInput" name="date" placeholder="JJ / MM / AAAA" autocomplete="off" readonly style="background:#222;color:#fff;font-weight:bold;letter-spacing:2px;text-align:center;cursor:pointer;">
        <div id="calendarPopup" style="display:none;position:absolute;z-index:10;"></div>
        <label for="places-select" style="display:none;">Places</label>
        <select id="places-select" name="places">
          <option value="">Places</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4+">4+</option>
        </select>
        <button type="submit">Rechercher</button>
      </form>
    </div>

    <!-- Calculateur d'économies -->
    <section class="calculator-section" style="background:#f8f9fa;padding:30px 20px;margin:30px 0;border-radius:10px;">
      <div style="max-width:900px;margin:0 auto;">
        <h3 style="text-align:center;color:#2d3436;margin-bottom:20px;">
          <span class="material-icons" style="vertical-align:middle;color:#00b894;">calculate</span>
          Calculateur d'économies
        </h3>
        <div id="calculation-results" style="display:none;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);margin-top:20px;">
          <div class="calc-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;">
            <div class="calc-item">
              <span class="material-icons" style="color:#e17055;">route</span>
              <strong>Distance:</strong> <span id="calc-distance">-</span>
            </div>
            <div class="calc-item">
              <span class="material-icons" style="color:#0984e3;">schedule</span>
              <strong>Durée:</strong> <span id="calc-time">-</span>
            </div>
            <div class="calc-item">
              <span class="material-icons" style="color:#00b894;">euro</span>
              <strong>Prix/pers:</strong> <span id="calc-price">-</span>
            </div>
            <div class="calc-item">
              <span class="material-icons" style="color:#00cec9;">eco</span>
              <strong>CO2 économisé:</strong> <span id="calc-co2">-</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="presentation-entreprise">
      <h2>À propos de <span class="green">EcoRide</span></h2>
      <strong>EcoRide</strong> a été imaginé pour répondre à la pollution générée par les trajets du quotidien. Portée par une vraie ambition écologique, la plateforme propose des solutions de covoiturage accessibles à tous, que votre voiture soit écolo ou non.
      </p>
      <p>
        Notre objectif : devenir la référence du covoiturage éco-responsable partout en France.<br>
        Ensemble, réduisons notre empreinte carbone, économisons, et voyageons plus intelligemment !
      </p>
    </section>

    <section class="benefits">
      <h3>Pourquoi choisir EcoRide ?</h3>
      <div class="benefit-list">
        <div>
          <span class="material-icons" aria-hidden="true">eco</span>
          <p>Réduisez votre empreinte carbone</p>
        </div>
        <div>
          <span class="material-icons" aria-hidden="true">savings</span>
          <p>Faites des économies</p>
        </div>
        <div>
          <span class="material-icons" aria-hidden="true">groups</span>
          <p>Rencontrez des gens sympas</p>
        </div>
        <div>
          <span class="material-icons" aria-hidden="true">star</span>
          <p>Trajets fiables & sécurisés</p>
        </div>
      </div>
    </section>

    <section class="story-cards">
      <div class="alt-card">
<img src="../assets/images/smartcar.jpeg" alt="Covoiturage écologique" class="alt-img">
        <div class="alt-desc">
          <h4>Voyagez écologique</h4>
          <p>Réduisez votre empreinte carbone en partageant vos trajets. Chaque voyage en covoiturage permet d'économiser en moyenne 2,3 kg de CO₂.</p>
        </div>
      </div>

      <div class="alt-card-reverse">
        <img src="../assets/images/voitureencharge.jpeg" alt="Économies covoiturage" class="alt-img">
        <div class="alt-desc">
          <h4>Économisez sur vos trajets</h4>
          <p>Divisez vos frais de transport par le nombre de passagers. En moyenne, nos utilisateurs économisent 60% sur leurs frais de déplacement.</p>
        </div>
      </div>

      <div class="alt-card">
        <img src="../assets/images/pub.jpg" alt="Communauté EcoRide" class="alt-img">
        <div class="alt-desc">
          <h4>Rejoignez notre communauté</h4>
          <p>Plus de 10 000 utilisateurs actifs partagent leurs trajets quotidiennement. Créez des liens et voyagez en toute confiance.</p>
        </div>
      </div>
    </section>
  </main>
  <footer>
    <p>&copy; 2025 EcoRide - Tous droits réservés</p>
    <div>
      <a href="#" id="openModalLegal">Mentions légales</a>
    </div>
  </footer>
  <!-- Modal Mentions Légales -->
  <dialog id="modal-legal" class="modal-legal-dialog">
    <form method="dialog" class="modal-legal-content">
      <button class="modal-legal-close" id="closeModalLegal" aria-label="Fermer la fenêtre" type="button">×</button>
      <h2>Mentions légales</h2>
      <div class="modal-legal-body">
        <p>
          <strong>Nom de l'entreprise</strong> : EcoRide<br>
          <strong>Statut</strong> : Société fictive dans le cadre d'un projet étudiant<br>
          <strong>Adresse</strong> : 123 rue de la Planète Verte, 75000 Paris<br>
          <strong>SIREN</strong> : 000 000 000<br>
          <strong>Responsable de publication</strong> : Jules Fictif<br>
          <strong>Email</strong> : contact@ecoride.fr<br>
          <strong>Hébergeur</strong> : OVH, 2 rue Kellermann, 59100 Roubaix, France<br>
        </p>
        <p>
          Ce site a été réalisé dans le cadre d'un projet étudiant et n'a pas vocation commerciale.<br>
          Pour toute question, contactez-nous via le formulaire de contact.
        </p>
      </div>
    </form>
  </dialog>
  <script src="../assets/js/script.js"></script>
  <script src="../assets/js/navbar.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    if (window.ecorideUser) {
      renderMenu(window.ecorideUser);
    } else {
      renderMenu();
    }
  });
  </script>
</body>
</html>