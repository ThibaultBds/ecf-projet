-- Ajouter des avis de test pour la modération
USE ecoride;

-- Insérer quelques avis en attente de modération
INSERT INTO reviews (trip_id, reviewer_id, reviewed_id, note, commentaire, status) VALUES
(1, 5, 4, 5, 'Excellent conducteur, très ponctuel et sympathique ! Je recommande vivement.', 'en_attente'),
(2, 4, 5, 2, 'Conduite un peu rapide à mon goût, mais trajet dans les temps.', 'en_attente'),
(3, 5, 4, 4, 'Très bon trajet, véhicule propre et confortable. Conducteur agréable.', 'en_attente'),
(1, 3, 4, 3, 'Trajet correct, mais le chauffeur était en retard de 15 minutes.', 'en_attente');

-- Insérer quelques avis déjà validés pour exemple
INSERT INTO reviews (trip_id, reviewer_id, reviewed_id, note, commentaire, status, validated_by, validated_at) VALUES
(1, 4, 5, 5, 'Parfait passager, très respectueux et ponctuel !', 'valide', 2, NOW()),
(2, 5, 4, 4, 'Bon conducteur, je recommande pour vos trajets.', 'valide', 2, NOW());
