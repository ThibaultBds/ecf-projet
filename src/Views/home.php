<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>EcoRide - Accueil</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/assets/css/style.css?v=2025">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<main>
  <section class="hero">
    <h2>Voyagez vert, partagez vos trajets !</h2>
    <p>
      Faites un geste pour la planète et économisez sur vos trajets quotidiens.<br>
      Rejoignez la communauté <strong>EcoRide</strong> du covoiturage écologique.
    </p>

    <a href="/covoiturages" class="cta-btn">Découvrir les covoiturages</a>
    <a id="cta-dynamic" href="/login" class="cta-btn secondary">Connexion</a>
  </section>

  <!-- Formulaire de recherche -->
  <div style="display:flex;justify-content:center;margin:30px 0;">
    <form class="search-bar"
          style="margin:0;max-width:900px;width:100%;box-sizing:border-box;"
          action="/covoiturages"
          method="get">

      <input type="text" name="depart" placeholder="Départ" list="villes" autocomplete="on">
      <input type="text" name="arrivee" placeholder="Arrivée" list="villes" autocomplete="on">

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

      <input type="text" name="date" placeholder="JJ / MM / AAAA" autocomplete="off" readonly>
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

  <section class="presentation-entreprise">
    <h2>À propos de <span class="green">EcoRide</span></h2>
    <p><strong>EcoRide</strong> a été imaginé pour répondre à la pollution générée par les trajets du quotidien.</p>
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
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const ctaDynamic = document.getElementById('cta-dynamic');
  if (!ctaDynamic) return;

  if (window.ecorideUser) {
    ctaDynamic.textContent = "Voir mes trajets";
    ctaDynamic.href = "/mes-covoiturages";
    ctaDynamic.classList.remove("secondary");
  } else {
    ctaDynamic.textContent = "Connexion";
    ctaDynamic.href = "/login";
  }
});
</script>

</body>
</html>
