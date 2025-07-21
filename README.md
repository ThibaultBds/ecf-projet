# 🌿 EcoRide

**EcoRide** est une plateforme web de covoiturage écologique développée en PHP, JavaScript et CSS, avec une architecture MVC légère.  
Ce projet est réalisé dans le cadre du Titre Professionnel Développeur Web & Web Mobile (TP DWWM).

---

## 🗂️ Sommaire

1. Présentation  
2. Technologies utilisées  
3. Installation locale  
4. Configuration  
5. Structure du projet  
6. Fonctionnalités principales  
7. Base de données  
8. Documentation  
9. Auteur

---

## 1. 🌱 Présentation

EcoRide facilite le partage de trajets entre particuliers pour encourager une mobilité plus durable.  
Les utilisateurs peuvent rechercher, proposer et réserver des trajets selon des critères écologiques et sociaux (animaux, fumeur, type de véhicule, etc.).

---

## 2. 💻 Technologies utilisées

- PHP 8.x (PDO)
- HTML5, CSS3 (Flexbox, Bootstrap)
- JavaScript (vanilla)
- MySQL / MariaDB
- Chart.js (statistiques)
- Google Material Icons
- Serveur local : XAMPP (Windows)

---

## 3. 🛠️ Installation locale

### Prérequis

- XAMPP (Apache, PHP, MySQL)
- Git

### Étapes

1. **Cloner le dépôt**
   ```
   git clone https://github.com/[TON-USERNAME]/ecoride.git
   ```
2. **Placer le dossier dans XAMPP**
   - Copier le dossier dans `c:\xampp\htdocs\ecoride`
3. **Créer la base de données**
   - Ouvrir phpMyAdmin
   - Importer `SQL/schema.sql` puis `SQL/fixtures.sql`
4. **Configurer la connexion**
   - Modifier `backend/config/database.php` avec vos identifiants MySQL
5. **Lancer le serveur**
   - Démarrer Apache et MySQL via XAMPP
   - Accéder à l’application via [http://localhost/ecoride/frontend/](http://localhost/ecoride/frontend/)

---

## 4. ⚙️ Configuration

- Adapter les paramètres de connexion dans `backend/config/database.php`
- Vérifier l’inclusion de l’autoloader et des fichiers d’authentification dans `backend/config/autoload.php`
- Personnaliser les variables d’environnement si besoin

---

## 5. 🧱 Structure du projet

```
ecoride/
├── backend/      # Code PHP (logique serveur, API)
├── frontend/     # HTML, CSS, JS, images
├── SQL/          # Fichiers SQL (base de données)
├── docs/         # Documentation PDF (technique, charte, manuel, gestion projet)
├── script/       # Scripts divers
├── docker/       # Fichiers Docker (optionnel)
└── README.md     # Ce fichier
```

---

## 6. ✨ Fonctionnalités principales

- Authentification sécurisée (inscription, connexion, gestion des sessions)
- Recherche de trajets avec filtres (prix, durée, note, impact écologique)
- Gestion des trajets (proposer, démarrer, annuler, historique)
- Gestion des véhicules et préférences (fumeur, animaux, bagages)
- Avis et notation (notation des trajets, commentaires)
- Modération (validation des avis, gestion des signalements)
- Administration (gestion des utilisateurs, statistiques dynamiques)

---

## 7. 🗃️ Base de données

- Tables principales : `users`, `trips`, `trip_participants`, `vehicles`, `reviews`, `reports`
- Scripts SQL :
  - `SQL/schema.sql` : structure de la base
  - `SQL/fixtures.sql` : données de démonstration

---

## 8. 📄 Documentation

- Manuel d’utilisation : `docs/manuel_utilisation.pdf`
- Charte graphique : `docs/charte_graphique.pdf`
- Documentation technique : `docs/documentation_technique.pdf`
- Gestion de projet : `docs/gestion_projet.pdf`

---

## 9. 👤 Auteur

Développé par Brandès Thibault
Date : 20 juillet 2025  
Formation : TP Développeur Web & Web Mobile

---
