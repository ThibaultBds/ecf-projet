-- Ecoride - SQL CLEAN pour import direct (db-fiddle, JawsDB, phpMyAdmin)
-- Version compatible MySQL (sans SET, TRANSACTION ni CHECK, tout en un seul script)

-- D'abord : suppression si existant (ordre contraintes OK)
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS trip_participants;
DROP TABLE IF EXISTS trips;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- Table users
CREATE TABLE users (
  id int(11) NOT NULL AUTO_INCREMENT,
  email varchar(255) NOT NULL,
  password varchar(255) NOT NULL,
  pseudo varchar(100) NOT NULL,
  role enum('Utilisateur','Moderateur','Administrateur') DEFAULT 'Utilisateur',
  user_type enum('passager','chauffeur','les_deux') DEFAULT 'passager',
  user_type_preference enum('passager','chauffeur','les_deux') DEFAULT 'passager',
  credits int(11) DEFAULT 20,
  rating decimal(3,2) DEFAULT 0.00,
  status enum('actif','suspendu','supprime') DEFAULT 'actif',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY email (email),
  KEY idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table vehicles
CREATE TABLE vehicles (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  marque varchar(100) NOT NULL,
  modele varchar(100) NOT NULL,
  couleur varchar(50) NOT NULL,
  plaque varchar(20) NOT NULL,
  date_immatriculation date NOT NULL,
  energie enum('essence','diesel','electrique','hybride') NOT NULL,
  places_disponibles int(11) NOT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY plaque (plaque),
  KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table trips
CREATE TABLE trips (
  id int(11) NOT NULL AUTO_INCREMENT,
  chauffeur_id int(11) NOT NULL,
  vehicle_id int(11) NOT NULL,
  ville_depart varchar(100) NOT NULL,
  ville_arrivee varchar(100) NOT NULL,
  adresse_depart varchar(255) DEFAULT NULL,
  adresse_arrivee varchar(255) DEFAULT NULL,
  date_depart datetime NOT NULL,
  prix decimal(6,2) NOT NULL,
  places_totales int(11) NOT NULL,
  places_restantes int(11) NOT NULL,
  status enum('planifie','termine','annule') DEFAULT 'planifie',
  description text DEFAULT NULL,
  preferences text DEFAULT NULL,
  is_ecological tinyint(1) DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  updated_at timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (id),
  KEY chauffeur_id (chauffeur_id),
  KEY vehicle_id (vehicle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table activity_logs
CREATE TABLE activity_logs (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) DEFAULT NULL,
  action varchar(255) NOT NULL,
  details text DEFAULT NULL,
  ip_address varchar(45) DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table reports
CREATE TABLE reports (
  id int(11) NOT NULL AUTO_INCREMENT,
  trip_id int(11) NOT NULL,
  reporter_id int(11) NOT NULL,
  reported_id int(11) NOT NULL,
  motif text NOT NULL,
  status enum('ouvert','en_cours','resolu','ferme') DEFAULT 'ouvert',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  resolved_by int(11) DEFAULT NULL,
  resolved_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY trip_id (trip_id),
  KEY reporter_id (reporter_id),
  KEY reported_id (reported_id),
  KEY resolved_by (resolved_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table reviews
CREATE TABLE reviews (
  id int(11) NOT NULL AUTO_INCREMENT,
  trip_id int(11) NOT NULL,
  reviewer_id int(11) NOT NULL,
  reviewed_id int(11) NOT NULL,
  note int(11) DEFAULT NULL,
  commentaire text DEFAULT NULL,
  status enum('en_attente','valide','refuse') DEFAULT 'en_attente',
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  validated_by int(11) DEFAULT NULL,
  validated_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY trip_id (trip_id),
  KEY reviewer_id (reviewer_id),
  KEY reviewed_id (reviewed_id),
  KEY validated_by (validated_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table trip_participants
CREATE TABLE trip_participants (
  id int(11) NOT NULL AUTO_INCREMENT,
  trip_id int(11) NOT NULL,
  passager_id int(11) NOT NULL,
  status enum('confirme','annule','termine') DEFAULT 'confirme',
  credits_utilises int(11) NOT NULL,
  has_reviewed tinyint(1) DEFAULT 0,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY unique_participation (trip_id,passager_id),
  KEY passager_id (passager_id),
  KEY idx_trip_participants_trip (trip_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table user_preferences (clé étrangère ajoutée SEULEMENT via ALTER TABLE plus bas)
CREATE TABLE user_preferences (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  fumeur tinyint(1) DEFAULT 0,
  animaux tinyint(1) DEFAULT 0,
  preference_custom text DEFAULT NULL,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ajout des contraintes étrangères (à la fin !)
ALTER TABLE vehicles
  ADD CONSTRAINT vehicles_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE trips
  ADD CONSTRAINT trips_ibfk_1 FOREIGN KEY (chauffeur_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT trips_ibfk_2 FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE;

ALTER TABLE activity_logs
  ADD CONSTRAINT activity_logs_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE reports
  ADD CONSTRAINT reports_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
  ADD CONSTRAINT reports_ibfk_2 FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT reports_ibfk_3 FOREIGN KEY (reported_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT reports_ibfk_4 FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE reviews
  ADD CONSTRAINT reviews_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
  ADD CONSTRAINT reviews_ibfk_2 FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT reviews_ibfk_3 FOREIGN KEY (reviewed_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT reviews_ibfk_4 FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE trip_participants
  ADD CONSTRAINT trip_participants_ibfk_1 FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
  ADD CONSTRAINT trip_participants_ibfk_2 FOREIGN KEY (passager_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE user_preferences
  ADD CONSTRAINT user_preferences_ibfk_1 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Données de démonstration
INSERT INTO users (id, email, password, pseudo, role, user_type, user_type_preference, credits, rating, status, created_at, updated_at) VALUES
(1, 'admin@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Administrateur', 'passager', 'passager', 100, 0.00, 'actif', '2025-07-11 09:35:18', '2025-07-11 09:35:18'),
(2, 'modo@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Moderateur', 'Moderateur', 'passager', 'passager', 50, 0.00, 'actif', '2025-07-11 09:35:18', '2025-07-11 09:35:18'),
(3, 'user@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'UserTest', 'Utilisateur', 'passager', 'passager', 20, 0.00, 'actif', '2025-07-11 09:35:18', '2025-07-11 09:35:18'),
(4, 'marc.d@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Marc D.', 'Utilisateur', 'passager', 'passager', 25, 0.00, 'actif', '2025-07-11 09:35:18', '2025-07-11 09:35:18'),
(5, 'sophie.l@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sophie L.', 'Utilisateur', 'passager', 'passager', 30, 0.00, 'actif', '2025-07-11 09:35:18', '2025-07-11 09:35:18');

INSERT INTO vehicles (id, user_id, marque, modele, couleur, plaque, date_immatriculation, energie, places_disponibles, created_at) VALUES
(1, 4, 'Renault', 'Megane', 'Bleu', 'AB-123-CD', '2020-03-15', 'essence', 4, '2025-07-11 09:35:18'),
(2, 5, 'Peugeot', '308', 'Rouge', 'EF-456-GH', '2021-07-20', 'electrique', 3, '2025-07-11 09:35:18'),
(3, 4, 'Tesla', 'Model 3', 'Blanc', 'IJ-789-KL', '2022-01-10', 'electrique', 4, '2025-07-11 09:35:18');

INSERT INTO trips (id, chauffeur_id, vehicle_id, ville_depart, ville_arrivee, adresse_depart, adresse_arrivee, date_depart, prix, places_totales, places_restantes, status, description, preferences, is_ecological, created_at, updated_at) VALUES
(1, 4, 1, 'Paris', 'Lyon', NULL, NULL, '2025-01-04 08:00:00', 15.00, 4, 3, 'planifie', NULL, NULL, 0, '2025-07-11 09:35:18', '2025-07-11 09:35:18'),
(2, 5, 2, 'Marseille', 'Nice', NULL, NULL, '2025-01-06 14:30:00', 12.00, 3, 2, 'planifie', NULL, NULL, 1, '2025-07-11 09:35:18', '2025-07-11 09:35:18'),
(3, 4, 3, 'Toulouse', 'Bordeaux', NULL, NULL, '2025-01-08 16:00:00', 18.00, 4, 1, 'planifie', NULL, NULL, 1, '2025-07-11 09:35:18', '2025-07-11 09:35:18'),
(4, 5, 2, 'Lyon', 'Paris', NULL, NULL, '2025-01-10 15:00:00', 16.00, 3, 3, 'planifie', NULL, NULL, 1, '2025-07-11 09:35:18', '2025-07-11 09:35:18'),
(5, 4, 1, 'Nice', 'Marseille', NULL, NULL, '2025-01-12 09:30:00', 14.00, 4, 2, 'planifie', NULL, NULL, 0, '2025-07-11 09:35:18', '2025-07-11 09:35:18');

INSERT INTO activity_logs (id, user_id, action, details, ip_address, created_at) VALUES
(1, 3, 'Déconnexion', 'Utilisateur déconnecté', '127.0.0.1', '2025-07-16 09:47:43'),
(2, 3, 'Déconnexion', 'Utilisateur déconnecté', '127.0.0.1', '2025-07-16 09:47:55'),
(3, 3, 'Déconnexion', 'Utilisateur déconnecté', '::1', '2025-07-16 09:58:59'),
(4, 3, 'Déconnexion', 'Utilisateur déconnecté', '::1', '2025-07-16 09:59:20'),
(5, 3, 'Déconnexion', 'Utilisateur déconnecté', '::1', '2025-07-17 14:34:26'),
(6, 3, 'Déconnexion', 'Utilisateur déconnecté', '::1', '2025-07-17 15:41:16'),
(7, 3, 'Déconnexion', 'Utilisateur déconnecté', '127.0.0.1', '2025-07-17 15:53:40'),
(8, 3, 'Déconnexion', 'Utilisateur déconnecté', '127.0.0.1', '2025-07-17 16:11:45'),
(9, 3, 'Déconnexion', 'Utilisateur déconnecté', '::1', '2025-07-18 07:55:26'),
(10, 3, 'Déconnexion', 'Utilisateur déconnecté', '::1', '2025-07-18 07:57:11'),
(11, 3, 'Déconnexion', 'Utilisateur déconnecté', '127.0.0.1', '2025-07-18 07:57:36'),
(12, 3, 'Déconnexion', 'Utilisateur déconnecté', '::1', '2025-07-18 08:00:38'),
(13, 1, 'Déconnexion', 'Utilisateur déconnecté', '::1', '2025-07-18 08:00:50'),
(14, 3, 'Déconnexion', 'Utilisateur déconnecté', '::1', '2025-07-18 08:14:28'),
(15, 3, 'Déconnexion', 'Utilisateur déconnecté', '::1', '2025-07-18 14:30:41');

INSERT INTO reviews (id, trip_id, reviewer_id, reviewed_id, note, commentaire, status, created_at, validated_by, validated_at) VALUES
(1, 1, 3, 4, 5, 'Excellent chauffeur, très ponctuel !', 'en_attente', '2025-07-11 09:35:18', NULL, NULL),
(2, 1, 4, 3, 4, 'Passager agréable et respectueux.', 'en_attente', '2025-07-11 09:35:18', NULL, NULL),
(3, 2, 1, 5, 5, 'Trajet écologique parfait, voiture très confortable.', 'en_attente', '2025-07-11 09:35:18', NULL, NULL);

INSERT INTO trip_participants (id, trip_id, passager_id, status, credits_utilises, has_reviewed, created_at) VALUES
(1, 1, 3, 'confirme', 15, 0, '2025-07-11 09:35:18'),
(2, 2, 1, 'confirme', 12, 0, '2025-07-11 09:35:18'),
(3, 3, 5, 'confirme', 18, 0, '2025-07-11 09:35:18'),
(4, 5, 2, 'confirme', 14, 0, '2025-07-11 09:35:18');

INSERT INTO user_preferences (user_id, fumeur, animaux, preference_custom, created_at)
VALUES
(1, 1, 0, 'Aime la musique', NOW()),
(2, 0, 1, 'Préférence calme', NOW());

-- Réglage des auto-incréments
ALTER TABLE activity_logs AUTO_INCREMENT = 16;
ALTER TABLE reports AUTO_INCREMENT = 1;
ALTER TABLE reviews AUTO_INCREMENT = 4;
ALTER TABLE trips AUTO_INCREMENT = 6;
ALTER TABLE trip_participants AUTO_INCREMENT = 5;
ALTER TABLE users AUTO_INCREMENT = 6;
ALTER TABLE user_preferences AUTO_INCREMENT = 3;
ALTER TABLE vehicles AUTO_INCREMENT = 4;