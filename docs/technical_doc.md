# Documentation Technique - EcoRide

## 🔧 Configuration de l'environnement

### Prérequis
- **XAMPP** 8.2+ (Apache, PHP, MySQL)
- **MongoDB** 6.0+ (pour les données NoSQL)
- **Navigateur moderne** (Chrome, Firefox, Safari)

### Installation locale
1. Installer XAMPP
2. Démarrer Apache et MySQL
3. Importer `database/ecoride.sql` dans phpMyAdmin
4. Configurer MongoDB avec `database/mongo_setup.js`
5. Accéder à `http://localhost/Ecoride/`

## 🏗️ Architecture technique

### Stack technologique
- **Frontend:** HTML5, CSS3, JavaScript ES6
- **Backend:** PHP 8.2 avec sessions sécurisées
- **Base de données:** MySQL (relationnel) + MongoDB (NoSQL)
- **Serveur:** Apache 2.4
- **Sécurité:** Protection CSRF, validation côté serveur

### Structure des fichiers
```
Ecoride/
├── config/
│   └── security.php          # Sécurité et authentification
├── database/
│   ├── ecoride.sql          # Structure MySQL
│   └── mongo_setup.js       # Configuration MongoDB
├── documentation/
│   └── technical_doc.md     # Cette documentation
├── images/                  # Assets visuels
├── *.php                   # Pages dynamiques
├── *.html                  # Pages statiques
├── style.css              # Styles CSS
├── script.js              # JavaScript principal
└── README.md              # Documentation utilisateur
```

## 🗄️ Modèle de données

### Base de données relationnelle (MySQL)

#### Entités principales
- **users:** Comptes utilisateurs avec rôles
- **vehicles:** Véhicules des chauffeurs
- **trips:** Trajets de covoiturage
- **trip_participants:** Participations aux trajets
- **reviews:** Avis et notes
- **reports:** Signalements

#### Relations clés
- Un utilisateur peut avoir plusieurs véhicules (1:N)
- Un trajet appartient à un chauffeur et un véhicule (N:1)
- Un trajet peut avoir plusieurs participants (N:M)
- Les avis sont liés aux trajets et utilisateurs

### Base de données NoSQL (MongoDB)

#### Collections
- **daily_stats:** Statistiques quotidiennes
- **application_logs:** Logs applicatifs détaillés
- **performance_metrics:** Métriques de performance

## 🔐 Sécurité

### Authentification
```php
// Sessions sécurisées
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true
]);
```

### Protection CSRF
```php
function generateCsrfToken() {
    return bin2hex(random_bytes(32));
}
```

### Validation des données
- Échappement HTML avec `htmlspecialchars()`
- Validation email avec `filter_var()`
- Hachage des mots de passe avec `password_hash()`

## 🚀 Déploiement

### Checklist pré-déploiement
- [ ] Tests de sécurité effectués
- [ ] Base de données optimisée
- [ ] Configuration HTTPS
- [ ] Variables d'environnement sécurisées
- [ ] Logs de monitoring activés

### Plateformes supportées
- **Heroku** (avec ClearDB MySQL)
- **DigitalOcean** (Droplet + base managée)
- **AWS** (EC2 + RDS)
- **Vercel** (frontend) + **PlanetScale** (base)

## 📊 Diagrammes

### Diagramme d'utilisation
```
Visiteur → [Rechercher trajets] → [Voir détails] → [S'inscrire/Connecter]
Utilisateur → [Participer trajet] → [Gérer profil] → [Historique]
Chauffeur → [Créer trajet] → [Gérer véhicules] → [Démarrer/Arrêter]
Moderateur → [Valider avis] → [Gérer signalements]
Admin → [Gérer utilisateurs] → [Voir statistiques]
```

### Diagramme de séquence (Participation)
```
Utilisateur → Page détail : Clic "Participer"
Page détail → Système : Vérification crédit
Système → Page détail : Confirmation requise
Page détail → Utilisateur : Modal confirmation
Utilisateur → Système : Validation
Système → Base : Mise à jour credits/places
Base → Système : Confirmation
Système → Utilisateur : Succès
```

## 🔧 Configuration serveur

### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Sécurité headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

### PHP (php.ini)
```ini
; Sécurité
expose_php = Off
session.cookie_httponly = 1
session.cookie_secure = 1

; Performance
memory_limit = 256M
max_execution_time = 60
upload_max_filesize = 10M
```

## 📈 Monitoring

### Métriques surveillées
- Temps de réponse des pages
- Taux d'erreurs 4xx/5xx
- Utilisation mémoire PHP
- Connexions base de données
- Tentatives de connexion échouées

### Logs importantes
- Connexions/déconnexions utilisateurs
- Créations/annulations de trajets
- Erreurs applicatives
- Tentatives de sécurité

## 🧪 Tests

### Tests fonctionnels
- [ ] Inscription/connexion utilisateur
- [ ] Recherche et filtrage trajets
- [ ] Participation à un covoiturage
- [ ] Gestion des rôles admin/moderateur

### Tests de sécurité
- [ ] Protection CSRF
- [ ] Validation XSS
- [ ] Contrôle d'accès
- [ ] Limitation tentatives connexion

## 📞 Support

### Logs de debug
```bash
# Activer les logs PHP
tail -f /var/log/apache2/error.log

# Logs applicatifs
tail -f logs/security.log
```

### Commandes utiles
```sql
-- Statistiques utilisateurs
SELECT role, COUNT(*) FROM users GROUP BY role;

-- Trajets par jour
SELECT DATE(date_depart), COUNT(*) FROM trips GROUP BY DATE(date_depart);
```

---

**Documentation mise à jour :** Janvier 2025  
**Version application :** 1.0.0  
**Environnement :** Production
