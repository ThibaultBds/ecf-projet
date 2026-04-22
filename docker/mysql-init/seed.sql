SET NAMES utf8mb4;

-- users de test (mdp : password)

INSERT INTO users
(username, email, password, telephone, credits, role, is_driver, is_passenger)
VALUES
('sebastien', 'sebastienrolland@mail.com', '$2y$12$BJbdbzVi2Mngb6gpcAPhpO30A76SVa8Nu4lvhKk7dY32.xYci4cNy', '0600000001', 150, 'user', TRUE, TRUE),
('marcus', 'marcus22@mail.com', '$2y$12$BJbdbzVi2Mngb6gpcAPhpO30A76SVa8Nu4lvhKk7dY32.xYci4cNy', '0600000002', 100, 'user', TRUE, TRUE),
('alice', 'alice@mail.com', '$2y$12$BJbdbzVi2Mngb6gpcAPhpO30A76SVa8Nu4lvhKk7dY32.xYci4cNy', '0600000003', 100, 'user', FALSE, TRUE),
('admin1', 'admin@mail.com', '$2y$12$BJbdbzVi2Mngb6gpcAPhpO30A76SVa8Nu4lvhKk7dY32.xYci4cNy', NULL, 0, 'admin', FALSE, FALSE),
('employe1', 'employe@mail.com', '$2y$12$BJbdbzVi2Mngb6gpcAPhpO30A76SVa8Nu4lvhKk7dY32.xYci4cNy', NULL, 0, 'employe', FALSE, FALSE);

-- villes

INSERT INTO cities (name, postal_code, country) VALUES
('Paris', '75000', 'France'),
('Lyon', '69000', 'France'),
('Marseille', '13000', 'France'),
('Bordeaux', '33000', 'France'),
('Toulouse', '31000', 'France'),
('Nice', '06000', 'France');

-- véhicules

INSERT INTO vehicles (user_id, brand, model, color, license_plate, energy_type, seats_available, registration_date) VALUES
(1, 'Tesla', 'Model 3', 'Noir', 'AA-123-BB', 'electrique', 4, '2022-01-15'),
(1, 'Renault', 'Clio V', 'Blanc', 'CC-456-DD', 'essence', 4, '2020-06-10'),
(2, 'Peugeot', '308', 'Gris', 'EE-789-FF', 'diesel', 4, '2021-03-20');

-- trajets à venir

INSERT INTO trips (chauffeur_id, vehicle_id, city_depart_id, city_arrival_id, departure_datetime, arrival_datetime, price, available_seats, status) VALUES
(1, 1, 1, 2, '2026-04-18 08:00:00', '2026-04-18 12:00:00', 15.00, 3, 'scheduled'),
(1, 1, 2, 3, '2026-04-22 09:00:00', '2026-04-22 12:30:00', 20.00, 3, 'scheduled'),
(2, 3, 1, 4, '2026-04-25 07:30:00', '2026-04-25 13:00:00', 25.00, 3, 'scheduled'),
(1, 2, 3, 5, '2026-04-29 14:00:00', '2026-04-29 18:00:00', 18.00, 3, 'scheduled');

-- trajets à venir (juin)

INSERT INTO trips (chauffeur_id, vehicle_id, city_depart_id, city_arrival_id, departure_datetime, arrival_datetime, price, available_seats, status) VALUES
(2, 3, 2, 1, '2026-06-04 08:15:00', '2026-06-04 12:05:00', 17.00, 3, 'scheduled'),
(1, 1, 4, 2, '2026-06-12 07:45:00', '2026-06-12 11:50:00', 22.00, 2, 'scheduled'),
(1, 2, 5, 3, '2026-06-21 15:30:00', '2026-06-21 19:20:00', 19.00, 3, 'scheduled');

-- Trajet terminé (pour tester validation/avis)
INSERT INTO trips (chauffeur_id, vehicle_id, city_depart_id, city_arrival_id, departure_datetime, arrival_datetime, price, available_seats, status) VALUES
(1, 1, 1, 2, '2026-02-10 08:00:00', '2026-02-10 12:00:00', 15.00, 2, 'completed');

-- participants du trajet terminé

INSERT INTO trip_participants (trip_id, user_id, status) VALUES
(5, 2, 'confirmed'),
(5, 3, 'confirmed');

-- participants de trajets de juin

INSERT INTO trip_participants (trip_id, user_id, status) VALUES
(6, 1, 'confirmed'),
(6, 3, 'confirmed'),
(7, 2, 'confirmed'),
(7, 3, 'confirmed'),
(8, 2, 'confirmed');

-- avis (à modérer)

INSERT INTO reviews (trip_id, reviewer_id, driver_id, rating, comment, status) VALUES
(5, 2, 1, 5, 'Excellent conducteur, voiture propre et ponctuel !', 'pending'),
(5, 3, 1, 4, 'Trajet agreable, bonne conduite. Merci !', 'pending');

-- logs de crédits
-- négatif = débit, positif = crédit
-- à la création d'un trajet le chauffeur avance price + 2 crédits (frais plateforme)
-- quand un passager rejoint, il paie price + 2, le chauffeur reçoit price direct

INSERT INTO credit_logs (user_id, trip_id, amount, type, reason, created_at) VALUES
-- Sebastien crée trip 1 (Paris→Lyon, price=15)
(1, 1, -15, 'debit', 'Création trajet', '2026-02-01 10:00:00'),
(1, 1, -2,  'platform_fee', 'Frais plateforme création', '2026-02-01 10:00:00'),
-- Sebastien crée trip 2 (Lyon→Marseille, price=20)
(1, 2, -20, 'debit', 'Création trajet', '2026-02-02 09:00:00'),
(1, 2, -2,  'platform_fee', 'Frais plateforme création', '2026-02-02 09:00:00'),
-- Marcus crée trip 3 (Paris→Bordeaux, price=25)
(2, 3, -25, 'debit', 'Création trajet', '2026-02-03 10:00:00'),
(2, 3, -2,  'platform_fee', 'Frais plateforme création', '2026-02-03 10:00:00'),
-- Sebastien crée trip 4 (Marseille→Toulouse, price=18)
(1, 4, -18, 'debit', 'Création trajet', '2026-02-04 14:00:00'),
(1, 4, -2,  'platform_fee', 'Frais plateforme création', '2026-02-04 14:00:00'),
-- Sebastien crée trip 5 (Paris→Lyon terminé, price=15)
(1, 5, -15, 'debit', 'Création trajet', '2026-02-05 07:00:00'),
(1, 5, -2,  'platform_fee', 'Frais plateforme création', '2026-02-05 07:00:00'),
-- Marcus participe au trip 5 : -17 marcus, +15 sebastien
(2, 5, -15, 'debit', 'Participation au trajet', '2026-02-09 10:00:00'),
(2, 5, -2,  'platform_fee', 'Frais plateforme', '2026-02-09 10:00:00'),
(1, 5,  15, 'credit', 'Revenu trajet', '2026-02-09 10:00:00'),
-- Alice participe au trip 5 : -17 alice, +15 sebastien
(3, 5, -15, 'debit', 'Participation au trajet', '2026-02-09 11:00:00'),
(3, 5, -2,  'platform_fee', 'Frais plateforme', '2026-02-09 11:00:00'),
(1, 5,  15, 'credit', 'Revenu trajet', '2026-02-09 11:00:00');
