<?php
// Tableau de citations
$quotes = [
    "Le succès, c’est tomber sept fois, se relever huit.",
    "Fais de ta vie un rêve, et d’un rêve une réalité.",
    "L’échec est la clé du succès : chaque erreur nous apprend quelque chose.",
    "Ne regarde pas l’horloge, fais comme elle : avance.",
    "Le plus grand risque est de ne pas en prendre."
];

// Choisir une citation au hasard
$randomQuote = $quotes[array_rand($quotes)];

// Affichage stylé
echo "<div style='
    font-family: Arial, sans-serif;
    background: #f0f0f0;
    border-left: 5px solid #00b894;
    padding: 15px;
    margin: 50px auto;
    width: 60%;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
'>
    <h2 style='color:#00b894;'>💬 Citation du jour</h2>
    <p style='font-size:1.2rem;color:#2d3436;'>“{$randomQuote}”</p>
</div>";
?>
