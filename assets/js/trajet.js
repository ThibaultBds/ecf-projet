// À FAIRE : Ce fichier sera remplacé par des appels à la base de données via une API serveur (PHP, Node.js, etc.)

var trajets = [
  {
    id: 1,
    nom: 'Adelyne',
    trajet: 'Paris - Lyon',
    note: 5,
    places: 3,
    date: '2025-06-21',
    heure: '08:30',
    prix: 27,
    elec: true,
    duree: 4,
    img: 'images/adelyne.jpg',
    avis: [
      "Ponctuelle et très sympa !",
      "Voiture propre, je recommande."
    ],
    vehicule: {
      marque: "Renault",
      modele: "Zoé",
      energie: "Électrique"
    },
    preferences: [
      "Pas d’animaux",
      "Musique douce",
      "Petite pause café"
    ]
  },
  {
    id: 2,
    nom: 'Lucie',
    trajet: 'Paris - Dijon',
    note: 4,
    places: 2,
    date: '2025-06-22',
    heure: '13:00',
    prix: 19,
    elec: false,
    duree: 5,
    img: 'images/lucie.jpg',
    avis: [
      "Très agréable, bonne conduite.",
      "Discussion facile."
    ],
    vehicule: {
      marque: "Peugeot",
      modele: "208",
      energie: "Essence"
    },
    preferences: [
      "Animaux acceptés",
      "Musique au choix",
      "Pause toutes les 2h"
    ]
  },
  {
    id: 3,
    nom: 'Sébastien',
    trajet: 'Lyon - Nice',
    note: 4,
    places: 1,
    date: '2025-06-25',
    heure: '17:45',
    prix: 35,
    elec: true,
    duree: 6,
    img: 'images/sebastien.jpg',
    avis: [
      "Super trajet, conducteur prudent.",
      "Bonne ambiance."
    ],
    vehicule: {
      marque: "Tesla",
      modele: "Model 3",
      energie: "Électrique"
    },
    preferences: [
      "Pas de musique forte",
      "Pas de fumeur",
      "Pause repas"
    ]
  }
];
