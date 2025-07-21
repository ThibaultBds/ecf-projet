-- Base de données EcoRide - Structure complète
-- Développé pour l'ECF 2025

CREATE DATABASE IF NOT EXISTS ecoride;
USE ecoride;

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    pseudo VARCHAR(100) NOT NULL,
    role ENUM('Utilisateur', 'Moderateur', 'Administrateur') DEFAULT 'Utilisateur',
    user_type ENUM('passager', 'chauffeur', 'les_deux') DEFAULT 'passager',
    credits INT DEFAULT 20,
    rating DECIMAL(3,2) DEFAULT 0.00,
    status ENUM('actif', 'suspendu', 'supprime') DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des véhicules
CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    marque VARCHAR(100) NOT NULL,
    modele VARCHAR(100) NOT NULL,
    couleur VARCHAR(50) NOT NULL,
    plaque VARCHAR(20) UNIQUE NOT NULL,
    date_immatriculation DATE NOT NULL,
    energie ENUM('essence', 'diesel', 'electrique', 'hybride') NOT NULL,
    places_disponibles INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des préférences utilisateur
CREATE TABLE user_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    fumeur BOOLEAN DEFAULT FALSE,
    animaux BOOLEAN DEFAULT FALSE,
    preference_custom TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des trajets/covoiturages
CREATE TABLE trips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    chauffeur_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    ville_depart VARCHAR(255) NOT NULL,
    ville_arrivee VARCHAR(255) NOT NULL,
    adresse_depart TEXT,
    adresse_arrivee TEXT,
    date_depart DATETIME NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    places_totales INT NOT NULL,
    places_restantes INT NOT NULL,
    status ENUM('planifie', 'en_cours', 'termine', 'annule') DEFAULT 'planifie',
    is_ecological BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (chauffeur_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);

-- Table des participations aux trajets
CREATE TABLE trip_participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    passager_id INT NOT NULL,
    status ENUM('confirme', 'annule', 'termine') DEFAULT 'confirme',
    credits_utilises INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (passager_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participation (trip_id, passager_id)
);

-- Table des avis et notes
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewed_id INT NOT NULL,
    note INT CHECK (note >= 1 AND note <= 5),
    commentaire TEXT,
    status ENUM('en_attente', 'valide', 'refuse') DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    validated_by INT NULL,
    validated_at TIMESTAMP NULL,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des signalements
CREATE TABLE reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    reporter_id INT NOT NULL,
    reported_id INT NOT NULL,
    motif TEXT NOT NULL,
    status ENUM('ouvert', 'en_cours', 'resolu', 'ferme') DEFAULT 'ouvert',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_by INT NULL,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Table des logs d'activité
CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Données de test
INSERT INTO users (email, password, pseudo, role, credits) VALUES
('admin@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'Administrateur', 100),
('modo@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Moderateur', 'Moderateur', 50),
('user@ecoride.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'UserTest', 'Utilisateur', 20);

-- Véhicules de test
INSERT INTO vehicles (user_id, marque, modele, couleur, plaque, date_immatriculation, energie, places_disponibles) VALUES
(4, 'Renault', 'Mégane', 'Bleu', 'AB-123-CD', '2020-03-15', 'essence', 4),
(5, 'Peugeot', '308', 'Rouge', 'EF-456-GH', '2021-07-20', 'electrique', 3),
(4, 'Tesla', 'Model 3', 'Blanc', 'IJ-789-KL', '2022-01-10', 'electrique', 4);

-- Trajets de test
INSERT INTO trips (chauffeur_id, vehicle_id, ville_depart, ville_arrivee, date_depart, prix, places_totales, places_restantes, is_ecological) VALUES
(4, 1, 'Paris', 'Lyon', '2025-01-04 08:00:00', 15.00, 4, 3, FALSE),
(5, 2, 'Marseille', 'Nice', '2025-01-06 14:30:00', 12.00, 3, 2, TRUE),
(4, 3, 'Toulouse', 'Bordeaux', '2025-01-08 16:00:00', 18.00, 4, 1, TRUE);

-- Index pour les performances
CREATE INDEX idx_trips_depart_arrivee ON trips(ville_depart, ville_arrivee);
CREATE INDEX idx_trips_date ON trips(date_depart);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_trip_participants_trip ON trip_participants(trip_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_trip_participants_trip ON trip_participants(trip_id);
