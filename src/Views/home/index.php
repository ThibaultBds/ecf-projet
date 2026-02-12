<section class="hero">
  <h2>Voyagez vert, partagez vos trajets !</h2>

  <p>
    Faites un geste pour la planète et économisez sur vos trajets quotidiens.<br>
    Rejoignez la communauté <strong>EcoRide</strong> du covoiturage écologique.
  </p>

  <a href="/covoiturages" class="cta-btn">Découvrir les covoiturages</a>
  <a id="cta-dynamic" href="/login" class="cta-btn secondary">Connexion</a>
</section>

<div style="display:flex;justify-content:center;margin:30px 0;">
  <form class="search-bar"
        style="margin:0;max-width:900px;width:100%;box-sizing:border-box;"
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
    <div id="calendarPopup" style="display:none;position:absolute;z-index:1000;background:white;border:1px solid #ddd;border-radius:8px;padding:10px;box-shadow:0 4px 12px rgba(0,0,0,0.15);"></div>

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

<!-- Résultats du calculateur -->
<div id="calculation-results" style="display:none;max-width:900px;margin:0 auto 30px auto;padding:20px;background:white;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);">
  <h3 style="text-align:center;color:#2d3436;margin-bottom:20px;">
    <span class="material-icons" style="vertical-align:middle;color:#00b894;">calculate</span>
    Estimation du trajet
  </h3>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:20px;text-align:center;">
    <div>
      <span class="material-icons" style="font-size:32px;color:#00b894;">straighten</span>
      <p style="font-size:24px;font-weight:bold;color:#2d3436;" id="calc-distance">-</p>
      <p style="color:#636e72;font-size:14px;">Distance</p>
    </div>
    <div>
      <span class="material-icons" style="font-size:32px;color:#00b894;">schedule</span>
      <p style="font-size:24px;font-weight:bold;color:#2d3436;" id="calc-time">-</p>
      <p style="color:#636e72;font-size:14px;">Durée estimée</p>
    </div>
    <div>
      <span class="material-icons" style="font-size:32px;color:#00b894;">euro</span>
      <p style="font-size:24px;font-weight:bold;color:#00b894;" id="calc-price">-</p>
      <p style="color:#636e72;font-size:14px;">Prix / personne</p>
    </div>
    <div>
      <span class="material-icons" style="font-size:32px;color:#00b894;">eco</span>
      <p style="font-size:24px;font-weight:bold;color:#00b894;" id="calc-co2">-</p>
      <p style="color:#636e72;font-size:14px;">CO2 économisé</p>
    </div>
  </div>
</div>

<section class="presentation-entreprise">
  <h2>À propos de <span class="green">EcoRide</span></h2>
  <p>
    <strong>EcoRide</strong> a été imaginé pour répondre à la pollution générée par les trajets du quotidien.
  </p>
</section>

<section class="story-cards">
  <div class="alt-card">
    <img src="/assets/images/smartcar.jpeg" alt="Covoiturage écologique" class="alt-img">
    <div class="alt-desc">
      <h4>Voyagez écologique</h4>
      <p>Réduisez votre empreinte carbone en partageant vos trajets.</p>
    </div>
  </div>

  <div class="alt-card-reverse">
    <img src="/assets/images/voitureencharge.jpeg" alt="Économies covoiturage" class="alt-img">
    <div class="alt-desc">
      <h4>Économisez sur vos trajets</h4>
      <p>Divisez vos frais de transport par le nombre de passagers.</p>
    </div>
  </div>

  <div class="alt-card">
    <img src="/assets/images/pub.jpg" alt="Communauté EcoRide" class="alt-img">
    <div class="alt-desc">
      <h4>Rejoignez notre communauté</h4>
      <p>Créez des liens et voyagez en toute confiance.</p>
    </div>
  </div>
</section>
