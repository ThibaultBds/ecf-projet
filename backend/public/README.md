# EcoRide - Plateforme de Covoiturage Écologique

![EcoRide Logo](https://img.shields.io/badge/EcoRide-2025-00b894?style=for-the-badge&logo=leaf&logoColor=white)

## 🚗 Description
EcoRide est une plateforme moderne de covoiturage axée sur l'écologie et le développement durable. L'application permet aux utilisateurs de proposer et rechercher des trajets partagés.

## 🔧 Installation

### Prérequis
- XAMPP 8.2+ (Apache, PHP, MySQL)
- Navigateur moderne

### Étapes d'installation
1. Cloner le projet dans `c:\xampp\htdocs\Ecoride`
2. Démarrer Apache et MySQL dans XAMPP
3. Créer la base de données `ecoride` dans phpMyAdmin
4. Importer le fichier SQL (voir documentation technique)
5. Accéder à `http://localhost/Ecoride/`

## 👥 Comptes de test
- **Administrateur:** admin@ecoride.fr / password
- **Modérateur:** modo@ecoride.fr / password  
- **Utilisateur:** user@ecoride.fr / password

## 📁 Structure du projet
```
Ecoride/
├── config/          # Configuration et autoloader
├── documentation/   # Documentation technique
├── images/         # Assets visuels
├── logs/           # Logs de sécurité
├── *.php          # Pages de l'application
├── style.css      # Styles CSS
├── script.js      # JavaScript principal
└── navbar.js      # Navigation dynamique
```

## 🚀 Fonctionnalités principales
- ✅ Système d'authentification sécurisé
- ✅ Recherche et filtrage de trajets
- ✅ Gestion des participations avec crédits
- ✅ Interface d'administration complète
- ✅ Système de modération des avis
- ✅ Protection CSRF et rate limiting
- ✅ Journalisation des événements

## 🔒 Sécurité
- Protection CSRF sur tous les formulaires
- Hachage sécurisé des mots de passe (Argon2ID)
- Limitation des tentatives de connexion
- Headers de sécurité HTTP
- Validation stricte des entrées utilisateur

## 📊 Technologies utilisées
- **Frontend:** HTML5, CSS3, JavaScript ES6
- **Backend:** PHP 8.2 avec sessions sécurisées
- **Base de données:** MySQL avec PDO
- **Sécurité:** Protection CSRF, validation côté serveur

## 🎯 Statut du projet
✅ **Terminé et prêt pour la production**

Toutes les fonctionnalités sont implémentées et testées. Le code est sécurisé et optimisé pour un environnement de production.

---

**🌱 EcoRide - Voyagez vert, partagez vos trajets !**

Développé avec ❤️ pour un avenir plus durable.

---

**Développé dans le cadre d'un projet étudiant - 2025**
├── login_secure.php         # Authentification
├── profil.php              # Profil utilisateur
├── register.php            # Inscription
├── details.php             # Détails covoiturage
├── admin.php               # Panel administrateur
├── contact.html            # Formulaire contact
├── conditions.html         # CGU
├── confidentialite.html    # Politique confidentialité
├── style.css              # Styles principaux
├── script.js              # JavaScript principal
├── navbar.js              # Navigation dynamique
└── README.md              # Cette documentation
```

## 🎯 Fonctionnalités clés

### Calculateur d'économies
- Calcul automatique des distances
- Estimation du coût par personne
- Calcul du CO2 économisé
- Interface interactive en temps réel

### Système de participation (US6)
- Vérification des places disponibles
- Contrôle du crédit utilisateur
- Double confirmation obligatoire
- Redirection vers connexion si non connecté

### Administration
- Gestion des utilisateurs
- Statistiques de la plateforme
- Modération des contenus
- Logs de sécurité

## 🔧 Utilisation

### Navigation principale
1. **Accueil** → Présentation et calculateur
2. **Covoiturages** → Liste des trajets disponibles
3. **Détails** → Information complète d'un trajet
4. **Connexion** → Authentification utilisateur
5. **Profil** → Espace personnel (après connexion)

### Parcours utilisateur
1. Rechercher un trajet via le calculateur
2. Consulter la liste des covoiturages
3. Voir les détails d'un trajet
4. Se connecter ou s'inscrire
5. Participer au covoiturage

## 🛡️ Sécurité

- Sessions PHP sécurisées
- Protection CSRF
- Validation côté serveur
- Échappement des données (XSS)
- Contrôle d'accès par rôles
- Limitation des tentatives de connexion

## 📱 Responsive Design

- Interface adaptative mobile/tablette/desktop
- Navigation optimisée tactile
- Formulaires responsive
- Grilles CSS flexibles

## 🎨 Charte graphique

- **Couleur principale:** #00b894 (Vert EcoRide)
- **Couleur secondaire:** #00cec9 (Turquoise)
- **Couleur d'accent:** #0984e3 (Bleu)
- **Police:** Inter, Segoe UI
- **Icônes:** Material Icons

## 📊 Statistiques du projet

- **Fichiers:** ~15 fichiers principaux
- **Lignes de code:** ~2000+ lignes
- **Pages fonctionnelles:** 8 pages principales
- **Temps de développement:** 2 semaines

## 🚀 Déploiement

### Local (développement)
```bash
# Démarrer XAMPP
# Accéder à http://localhost/Ecoride/
```

### Production (optionnel)
- Compatible avec tout hébergeur PHP
- Adaptation des chemins requis
- Configuration HTTPS recommandée

## 📈 Évolutions possibles

- [ ] Base de données MySQL
- [ ] API REST
- [ ] Notifications en temps réel
- [ ] Intégration cartographique
- [ ] Application mobile
- [ ] Système de paiement

## 🤝 Contribution

Projet étudiant ECF - Développement individuel avec assistance IA pour l'apprentissage.

## 📄 Licence

Projet éducatif - Tous droits réservés dans le cadre de l'ECF 2025.

---

**🌱 EcoRide - Voyagez vert, partagez vos trajets !**

Développé avec ❤️ pour un avenir plus durable.
