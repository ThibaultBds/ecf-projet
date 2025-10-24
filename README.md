🌿 EcoRide – Plateforme de covoiturage écologique

EcoRide est une plateforme web de covoiturage écologique permettant de partager des trajets facilement tout en favorisant une mobilité durable.
Projet réalisé par Thibault dans le cadre du Titre Professionnel Développeur Web & Web Mobile (TP DWWM).

🗂️ Sommaire

Présentation

Démonstration & Accès

Fonctionnalités principales

Environnement Docker

Installation locale

Accès local

Comptes de test

Sauvegarde & Base de données

Problèmes connus / Limitations

Structure du projet

Documentation

Contact

1. 🌍 Présentation

EcoRide facilite le partage de trajets entre particuliers pour encourager une mobilité plus durable.
Les utilisateurs peuvent rechercher, proposer et réserver des trajets selon divers critères (fumeur, animaux, type de véhicule, etc.).
L’objectif principal est de promouvoir un covoiturage écoresponsable, simple d’utilisation et accessible à tous.

2. 🚀 Démonstration & Accès

En ligne (démo Heroku) :
https://ecoride-heroku-b5554d0a41a1.herokuapp.com/pages/index.php

En local (Docker) :
http://localhost:8081

L’adresse http://localhost/ecoride/frontend/public/pages/index.php
 n’est utilisée que pour XAMPP.
En Docker, l’accès se fait directement via le port 8081.

3. ⚡ Fonctionnalités principales

Authentification (utilisateur, modérateur, administrateur)

Publication et recherche de trajets

Gestion des préférences (animaux, fumeur, confort, écologie)

Visualisation de statistiques (Chart.js)

Formulaires dynamiques connectés à la BDD

Administration / modération (validation, suppression de trajets)

4. 🐳 Environnement Docker

Le projet fonctionne entièrement dans un environnement Dockerisé.

Conteneurs :

ecoride_web → Apache + PHP (port 8081)

ecoride_db → MySQL 8 (port 3306)

Les deux conteneurs communiquent via un réseau interne Docker.
La base de données est automatiquement importée au premier lancement depuis docker/mysql-init/ecoride.sql.

5. 🛠️ Installation locale (version Docker)

Prérequis :

Docker Desktop

Git

Étapes :

Cloner le dépôt :
git clone https://github.com/ThibaultBds/ecf-projet.git

cd ecoride

Démarrer les conteneurs :
docker compose up --build

Accéder au site :
http://localhost:8081

(Optionnel) Recréer la base :
docker compose down -v
docker compose up --build

6. 🌐 Accès local

Accès principal : http://localhost:8081

Si ton docker-compose.yml ne pointe pas sur frontend/public, utiliser :
http://localhost:8081/frontend/public/pages/index.php

7. 👥 Comptes de test

Utilisateur :

user@ecoride.fr
 / password

Modérateur :

modo@ecoride.fr
 / password

Administrateur :

admin@ecoride.fr
 / password

Ces comptes sont inclus dans la base importée automatiquement.

8. 💾 Base de données & Sauvegarde

Import automatique :

Fichier ecoride.sql dans docker/mysql-init/

Import automatique au démarrage de ecoride_db

Sauvegardes :

Stockées dans le dossier backup/

Commande manuelle :
docker exec ecoride_db mysqldump -u root -proot ecoride > backup/backup-ecoride_$(date +%F_%H-%M).sql

Connexion PDO (backend/config/database.php) :

host : ecoride-db

username : root

password : root

dbName : ecoride

port : 3306

9. ⚠️ Problèmes connus / Limitations

Import SQL impossible sur certains hébergeurs (Heroku/JawsDB).

Le dossier vendor/ est ignoré → exécuter composer install si besoin.

Quelques ajustements possibles selon la configuration (ports, environnements).

Certaines fonctions d’administration sont locales uniquement.

10. 🧱 Structure du projet

ecoride/
├── backend/ → logique serveur (PHP, sécurité, API)
│ ├── config/ → fichiers de configuration
│ └── public/ → pages et traitements PHP
├── frontend/ → interface utilisateur (HTML, CSS, JS)
│ ├── assets/ → ressources (CSS, JS, images, HTML)
│ └── pages/ → pages principales
├── docker/ → configuration Docker
│ ├── mysql-init/ → fichier ecoride.sql (import auto)
│ └── Dockerfile
├── backup/ → sauvegardes SQL
├── docs/ → documentation PDF (technique, utilisateur, preuves)
├── docker-compose.yml → configuration principale
├── .env → variables d’environnement
├── composer.json / lock → dépendances PHP
├── Procfile → déploiement Heroku
├── TODO.md → tâches à venir
└── README.md → ce fichier

11. 📚 Documentation

Manuel d’utilisation : docs/Manuel Utilisateur.pdf

Documentation technique : docs/Documentation Technique.pdf

Charte graphique : docs/Charte Graphique.pdf

Preuves de fonctionnement : docs/PREUVES_FONCTIONNEMENT.pdf

Gestion de projet : docs/project_management.md

12. 📞 Contact

Thibault
Formation : Titre Professionnel – Développeur Web & Web Mobile (DWWM)
