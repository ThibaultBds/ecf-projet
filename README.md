# EcoRide - Plateforme de Covoiturage Écologique

## Présentation

EcoRide est une application web de covoiturage développée dans le cadre du Titre Professionnel Développeur Web et Web Mobile.

Objectif : promouvoir des déplacements responsables en favorisant le partage de trajets et en mettant en avant les véhicules électriques.

Dépôt GitHub : <https://github.com/ThibaultBds/ecf-projet>

Application déployée : <https://ecf-projet-production-5520.up.railway.app/>

## Architecture

L'application repose sur une architecture MVC personnalisée, développée sans framework externe.

### Structure

```text
ecf-projet/
|- public/      -> Point d'entrée
|- routes/
|- src/
|  |- Controllers/
|  |- Models/
|  |- Views/
|  |- Core/
|  |- Middleware/
|  `- Services/
|- docker/
|- composer.json
`- README.md
```

### Justification

Le MVC personnalisé permet :

- Séparation claire des responsabilités
- Implémentation de middlewares
- Compréhension du fonctionnement interne d'un framework

## Stack technique

### Front-end

- HTML5
- CSS3
- JavaScript Vanilla

### Back-end

- PHP 8
- Composer

### Base relationnelle

- MySQL (users, trips, vehicles, credit_logs)

### Base NoSQL

- MongoDB (driver_preferences, trip_incidents)

### Environnement

- Docker (développement)
- Railway (production)

## Gestion des emails

### Développement

Utilisation de MailHog via Docker pour simuler l'envoi d'emails.

Permet :

- Tester sans envoi réel
- Visualiser les emails localement

### Production

Utilisation d'un service SMTP externe (Brevo) :

- Connexion via variables d'environnement
- Adresse professionnelle validée
- Les identifiants SMTP ne sont pas présents dans le dépôt (sécurité)

## Sécurité

- `password_hash` / `password_verify`
- Sessions sécurisées (cookie_secure en production)
- Requêtes préparées PDO
- Protection XSS (htmlspecialchars)
- Middleware Auth
- Middleware Role
- Middleware CSRF

## Système de crédits

- 20 crédits à l'inscription
- 2 crédits prélevés par la plateforme
- Crédit chauffeur après validation
- Historique stocké dans `credit_logs`

## Fonctionnalités

### Visiteur

- Recherche de trajets
- Filtres (écologique, prix, durée, note chauffeur)
- Détail trajet

### Utilisateur

- Participation à un trajet
- Historique des trajets
- Annulation de participation
- Dépôt d'avis (soumis à modération)
- Signalement d'incident

### Chauffeur

- Création de trajet
- Gestion des véhicules
- Démarrer / Clôturer un trajet
- Gestion des préférences conducteur

### Employé

- Validation / refus des avis
- Consultation et traitement des incidents
- Marquage des messages de contact comme traités

### Administrateur

- Statistiques de la plateforme
- Gestion des trajets
- Suspension / réactivation de comptes
- Ajout de crédits
- Création de comptes employés et utilisateurs
- Suivi des messages de contact

## Comptes de démonstration

Mot de passe pour tous : `password`

| Rôle | Email |
| --- | --- |
| Admin | `admin@mail.com` |
| Employé | `employe@mail.com` |
| Chauffeur | `sebastienrolland@mail.com` |
| Chauffeur | `marcus22@mail.com` |
| Passager | `alice@mail.com` |

## Installation locale

```bash
git clone https://github.com/ThibaultBds/ecf-projet.git
cd ecf-projet
composer install
docker-compose up -d --build
```

Accès local :

- Application : <http://localhost:8081>
- phpMyAdmin : <http://localhost:8080>
- MailHog : <http://localhost:8025>

## Configuration des variables d'environnement (local)

Le projet lit les variables suivantes :

```env
DB_HOST=db
DB_PORT=3306
DB_DATABASE=ecoride
DB_USERNAME=ecoride_user
DB_PASSWORD=ecoride_pass

MONGO_URL=mongodb://mongo:27017
MAIL_HOST=mailhog
MAIL_PORT=1025
```

Important : dans cette configuration, les variables sont déjà définies dans `docker-compose.yml`.
Un fichier `.env` est optionnel tant qu'il reprend exactement ces noms de variables.

## Base de données (SQL fourni)

Le fichier `ecoride.sql` contient le schéma MySQL et des données de démonstration (CREATE + INSERT).

En environnement Docker, des scripts SQL sont aussi disponibles dans :

- `docker/mysql-init/init.sql` (schéma)
- `docker/mysql-init/seed.sql` (données de test)

Import local :

- Via phpMyAdmin : choisir la base `ecoride`, puis importer `ecoride.sql`.
- Via Docker CLI : `docker exec -i ecoride-db mysql -u root -proot ecoride < ecoride.sql`

## Livrables (docs/)

| Fichier | Description |
| --- | --- |
| `Annexe_Diagrammes_UML_MCD_EcoRide.pdf` | Diagrammes UML et MCD |
| `Charte_Graphique.pdf` | Charte graphique |
| `Documentation_Technique.pdf` | Documentation technique |
| `Manuel_d_Utilisation.pdf` | Manuel utilisateur |
| `Documentation de Gestion de Projet.docx` | Gestion de projet (Kanban, Agile) |
| `Annexe.docx` | Annexes complémentaires |

## Initialisation MongoDB

MongoDB est utilisé pour les collections `driver_preferences` et `trip_incidents`.

Il n'y a pas de script de seed dédié dans ce dépôt : ces données sont créées via l'application pendant les tests.

Note : les avis utilisateurs sont stockés en MySQL (table `reviews`), pas dans MongoDB.

## Déploiement (Railway)

1. Migrer la base MySQL
2. Configurer les variables d'environnement sur Railway
3. Configurer Brevo (`BREVO_API_KEY`, `MAIL_FROM`)
4. Vérifier l'envoi d'email réel
5. Exécuter un test complet de l'application

## Latence Railway

Railway peut mettre l'application en veille après une période d'inactivité.
Un délai de réveil (~60s) peut apparaître au premier accès.

## Organisation Git

### Branches

- `main` (production)
- `develop` (intégration)
- `refactor-mvc`
- `hotfix/mongo-env`
- `css/cleanup`

### Processus

1. Branche dédiée
2. Développement
3. Merge vers `develop`
4. Merge vers `main`

## Conformité référentiel

- MVC
- Double base (MySQL + MongoDB)
- Sécurité
- Middleware
- Déploiement
- Documentation

## Améliorations futures

- API REST
- JWT
- Tests unitaires
- Optimisation des performances
