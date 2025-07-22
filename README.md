# 🌿 EcoRide – Plateforme de covoiturage écologique

**EcoRide** est une plateforme web de covoiturage écologique permettant de partager des trajets facilement tout en favorisant une mobilité durable.  
Projet réalisé par Brandès Thibault dans le cadre du Titre Professionnel Développeur Web & Web Mobile (TP DWWM).

---

## 🗂️ Sommaire

1. Présentation  
2. Accès à l’application  
3. Preuves de fonctionnement  
4. Difficultés rencontrées  
5. Technologies utilisées  
6. Installation locale  
7. Accès local  
8. Comptes de test  
9. Problèmes connus / Limitations  
10. Structure du projet  
11. Documentation  
12. Contact

---

## 1. Présentation

EcoRide facilite le partage de trajets entre particuliers pour encourager une mobilité plus durable.  
Les utilisateurs peuvent rechercher, proposer et réserver des trajets selon des critères écologiques et sociaux (animaux, fumeur, type de véhicule, etc.).

---

## 2. Accès à l’application

- **Démo en ligne (Heroku)** :  
  [https://ecoride-heroku-b5554d0a41a1.herokuapp.com/pages/index.php](https://ecoride-heroku-b5554d0a41a1.herokuapp.com/pages/index.php)

- **Accès local après installation** :  
  [http://localhost/ecoride/frontend/public/pages/index.php](http://localhost/ecoride/frontend/public/pages/index.php)

> Utilisez le lien qui correspond à votre environnement de test ou de démonstration.

---

## 3. Preuves de fonctionnement

Toutes les fonctionnalités principales sont démontrées par des captures d’écran, disponibles dans le PDF “preuves de fonctionnement” (`/docs/PREUVES_FONCTIONNEMENT.pdf`).  
Ce document fait foi en cas d’impossibilité de test direct.

---

## 4. Difficultés rencontrées (base de données)

- **Import de la base de données** : certaines contraintes techniques sur les hébergeurs (Heroku/JawsDB, etc.) empêchent l’import sans erreurs.
- **Adaptation du script SQL** : Le script fonctionne en local (phpMyAdmin/XAMPP) mais peut nécessiter des adaptations ailleurs.
- **Conséquence** : Les preuves de fonctionnement sont apportées via screenshots.

---

## 5. 💻 Technologies utilisées

- PHP 8.x (PDO orienté objet)
- HTML5, CSS3 (Flexbox, Bootstrap)
- JavaScript (vanilla JS)
- MySQL / MariaDB
- Chart.js (statistiques)
- Google Material Icons
- Serveur local : XAMPP (Windows)

---

## 6. 🛠️ Installation locale

### Prérequis

- XAMPP (Apache, PHP, MySQL)
- Git

### Étapes

1. **Cloner le dépôt**
   ```
   git clone https://github.com/ThibaultBds/ecf-projet.git
   ```
2. **Placer le dossier dans XAMPP**
   - Copier le dossier dans `C:\xampp\htdocs\ecoride`
3. **Créer la base de données**
   - Ouvrir phpMyAdmin
   - Importer **`SQL/ecoride.sql`**
4. **Configurer la connexion**
   - Modifier `config.php` (ou `backend/config/database.php`) avec vos identifiants MySQL locaux :  
     ```
     $host = 'localhost';
     $dbname = 'ecoride';
     $username = 'root';
     $password = '';
     ```
5. **Lancer le serveur**
   - Démarrer Apache et MySQL via XAMPP
   - Accéder à l’application via [http://localhost/ecoride/frontend/public/pages/index.php](http://localhost/ecoride/frontend/public/pages/index.php)

---

## 7. Accès local

Une fois installé, l’application est accessible à l’adresse suivante :  
[http://localhost/ecoride/frontend/public/pages/index.php](http://localhost/ecoride/frontend/public/pages/index.php)

---

## 8. Comptes de test

Utilisez les identifiants suivants pour tester les différents rôles dans l’application :

- **Utilisateur Standard**
  - Email : user@ecoride.fr
  - Mot de passe : password
  - Rôle : Utilisateur

- **Administrateur**
  - Email : admin@ecoride.fr
  - Mot de passe : password
  - Rôle : Administrateur

- **Modérateur**
  - Email : modo@ecoride.fr
  - Mot de passe : password
  - Rôle : Modérateur

> Ces identifiants sont ceux de la base locale de développement et des captures d’écran.

---

## 9. Problèmes connus / Limitations

- Import SQL impossible sur certains hébergeurs (Heroku/JawsDB).
- Les preuves de fonctionnement sont fournies par captures d’écran.
- Le dossier `vendor/` n’est pas inclus : lancer `composer install` après clonage si besoin.
- L’application peut nécessiter des adaptations selon l’hébergeur.

---

## 10. Structure du projet

```
ecoride/
├── backend/      # Code PHP (logique serveur, API)
├── frontend/     # HTML, CSS, JS, images
├── SQL/          # Fichiers SQL (base de données)
│   └── ecoride.sql
├── docs/         # Documentation PDF (technique, manuel, preuves)
├── script/       # Scripts divers
├── docker/       # Fichiers Docker (optionnel)
└── README.md     # Ce fichier
```

---

## 11. Documentation

- Manuel d’utilisation : `docs/Manuel Utilisateur.pdf`
- Documentation technique : `docs/Documentation_Technique.pdf`
- Preuves de fonctionnement : `docs/PREUVES_FONCTIONNEMENT.pdf`
- Charte graphique : `docs/Charte_Graphique.pdf`
- Remarques sur la base de données : `docs/Remarque sur la base de données (1).pdf`

---

## 12. Contact

Auteur : Brandès Thibault  
Date : 22 juillet 2025  
Formation : TP Développeur Web & Web Mobile

---
