<section class="hero">
  <h2>Voyagez vert, partagez vos trajets !</h2>

  <p>
    Faites un geste pour la planète et économisez sur vos trajets quotidiens.<br>
    Rejoignez la communauté <strong>EcoRide</strong> du covoiturage écologique.
  </p>

  <a href="/covoiturages" class="cta-btn">Découvrir les covoiturages</a>
  <a id="cta-dynamic" href="/login" class="cta-btn secondary">Connexion</a>
</section>

<div class="home-search-wrap">
  <form class="search-bar home-search-bar"
        action="/covoiturages"
        method="get">

    <input type="text" id="depart" name="depart" placeholder="Départ" list="villes" autocomplete="on">
    <input type="text" id="arrivee" name="arrivee" placeholder="Arrivée" list="villes" autocomplete="on">

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

    <input type="text" id="dateInput" name="date" placeholder="JJ / MM / AAAA" autocomplete="off" readonly>
    <div id="calendarPopup" class="home-calendar-popup"></div>

    <select name="places">
      <option value="">Places</option>
      <option value="1">1</option>
      <option value="2">2</option>
      <option value="3">3</option>
      <option value="4+">4+</option>
    </select>

    <button type="submit">Rechercher</button>
  </form>
</div>

<div id="calculation-results" class="home-calc-results">
  <h3 class="home-calc-title">
    <span class="material-icons home-calc-title-icon">calculate</span>
    Estimation du trajet
  </h3>
  <div class="home-calc-grid">
    <div>
      <span class="material-icons home-calc-icon">straighten</span>
      <p class="home-calc-value home-calc-dark" id="calc-distance">-</p>
      <p class="home-calc-label">Distance</p>
    </div>
    <div>
      <span class="material-icons home-calc-icon">schedule</span>
      <p class="home-calc-value home-calc-dark" id="calc-time">-</p>
      <p class="home-calc-label">Durée estimée</p>
    </div>
    <div>
      <span class="material-icons home-calc-icon">euro</span>
      <p class="home-calc-value home-calc-green" id="calc-price">-</p>
      <p class="home-calc-label">Prix / personne</p>
    </div>
    <div>
      <span class="material-icons home-calc-icon">eco</span>
      <p class="home-calc-value home-calc-green" id="calc-co2">-</p>
      <p class="home-calc-label">CO2 économisé</p>
    </div>
  </div>
</div>

<section class="presentation-entreprise">
  <h2>À propos de <span class="green">EcoRide</span></h2>
  <p><strong>EcoRide</strong> a été imaginé pour répondre à la pollution générée par les trajets du quotidien. Portée par une vraie ambition écologique, la plateforme propose des solutions de covoiturage accessibles à tous, que votre voiture soit écolo ou non.</p>
  <p>Notre objectif : devenir la référence du covoiturage éco-responsable partout en France.<br> Ensemble, réduisons notre empreinte carbone, économisons, et voyageons plus intelligemment !</p>
</section>

<section class="benefits">
  <h3>Pourquoi choisir EcoRide ?</h3>
  <div class="benefit-list">
    <div><span class="material-icons" aria-hidden="true">eco</span><p>Réduisez votre empreinte carbone</p></div>
    <div><span class="material-icons" aria-hidden="true">savings</span><p>Faites des économies</p></div>
    <div><span class="material-icons" aria-hidden="true">groups</span><p>Rencontrez des gens sympas</p></div>
    <div><span class="material-icons" aria-hidden="true">star</span><p>Trajets fiables & sécurisés</p></div>
  </div>
</section>

<section class="story-cards">
  <div class="alt-card">
    <img src="/assets/images/smartcar.jpeg" alt="Covoiturage écologique" class="alt-img">
    <div class="alt-desc">
      <h4>Voyagez écologique</h4>
      <p>Réduisez votre empreinte carbone en partageant vos trajets. Chaque voyage en covoiturage permet d'économiser en moyenne 2,3 kg de CO2.</p>
    </div>
  </div>

  <div class="alt-card-reverse">
    <img src="/assets/images/voitureencharge.jpeg" alt="Économies covoiturage" class="alt-img">
    <div class="alt-desc">
      <h4>Économisez sur vos trajets</h4>
      <p>Divisez vos frais de transport par le nombre de passagers. En moyenne, nos utilisateurs économisent 60% sur leurs frais de déplacement.</p>
    </div>
  </div>

  <div class="alt-card">
    <img src="/assets/images/pub.jpg" alt="Communauté EcoRide" class="alt-img">
    <div class="alt-desc">
      <h4>Rejoignez notre communauté</h4>
      <p>Plus de 10 000 utilisateurs actifs partagent leurs trajets quotidiennement. Créez des liens et voyagez en toute confiance.</p>
    </div>
  </div>
</section>
