-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: ecoride
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','127.0.0.1','2025-07-16 09:47:43'),(2,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','127.0.0.1','2025-07-16 09:47:55'),(3,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','::1','2025-07-16 09:58:59'),(4,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','::1','2025-07-16 09:59:20'),(5,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','::1','2025-07-17 14:34:26'),(6,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','::1','2025-07-17 15:41:16'),(7,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','127.0.0.1','2025-07-17 15:53:40'),(8,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','127.0.0.1','2025-07-17 16:11:45'),(9,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','::1','2025-07-18 07:55:26'),(10,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','::1','2025-07-18 07:57:11'),(11,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','127.0.0.1','2025-07-18 07:57:36'),(12,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','::1','2025-07-18 08:00:38'),(13,1,'DÃ©connexion','Utilisateur dÃ©connectÃ©','::1','2025-07-18 08:00:50'),(14,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','::1','2025-07-18 08:14:28'),(15,3,'DÃ©connexion','Utilisateur dÃ©connectÃ©','::1','2025-07-18 14:30:41'),(16,3,'Déconnexion','Utilisateur déconnecté','172.20.0.1','2025-10-24 12:45:23'),(17,1,'Déconnexion','Utilisateur déconnecté','172.20.0.1','2025-10-24 12:45:39'),(18,3,'Déconnexion','Utilisateur déconnecté','172.20.0.1','2025-10-24 13:06:47'),(19,1,'Déconnexion','Utilisateur déconnecté','172.20.0.1','2025-10-24 13:10:39'),(20,1,'Déconnexion','Utilisateur déconnecté','172.20.0.1','2025-10-24 13:12:07');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reports`
--

DROP TABLE IF EXISTS `reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `trip_id` int NOT NULL,
  `reporter_id` int NOT NULL,
  `reported_id` int NOT NULL,
  `motif` text COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('ouvert','en_cours','resolu','ferme') COLLATE utf8mb4_general_ci DEFAULT 'ouvert',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_by` int DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  KEY `reporter_id` (`reporter_id`),
  KEY `reported_id` (`reported_id`),
  KEY `resolved_by` (`resolved_by`),
  CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reports_ibfk_3` FOREIGN KEY (`reported_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reports_ibfk_4` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reports`
--

LOCK TABLES `reports` WRITE;
/*!40000 ALTER TABLE `reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` int NOT NULL AUTO_INCREMENT,
  `trip_id` int NOT NULL,
  `reviewer_id` int NOT NULL,
  `reviewed_id` int NOT NULL,
  `note` int DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_general_ci,
  `status` enum('en_attente','valide','refuse') COLLATE utf8mb4_general_ci DEFAULT 'en_attente',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `validated_by` int DEFAULT NULL,
  `validated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `trip_id` (`trip_id`),
  KEY `reviewer_id` (`reviewer_id`),
  KEY `reviewed_id` (`reviewed_id`),
  KEY `validated_by` (`validated_by`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`reviewed_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_4` FOREIGN KEY (`validated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,1,3,4,5,'Excellent chauffeur, trÃ¨s ponctuel !','en_attente','2025-07-11 09:35:18',NULL,NULL),(2,1,4,3,4,'Passager agrÃ©able et respectueux.','en_attente','2025-07-11 09:35:18',NULL,NULL),(3,2,1,5,5,'Trajet Ã©cologique parfait, voiture trÃ¨s confortable.','en_attente','2025-07-11 09:35:18',NULL,NULL);
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trip_participants`
--

DROP TABLE IF EXISTS `trip_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trip_participants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `trip_id` int NOT NULL,
  `passager_id` int NOT NULL,
  `status` enum('confirme','annule','termine') COLLATE utf8mb4_general_ci DEFAULT 'confirme',
  `credits_utilises` int NOT NULL,
  `has_reviewed` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participation` (`trip_id`,`passager_id`),
  KEY `passager_id` (`passager_id`),
  KEY `idx_trip_participants_trip` (`trip_id`),
  CONSTRAINT `trip_participants_ibfk_1` FOREIGN KEY (`trip_id`) REFERENCES `trips` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trip_participants_ibfk_2` FOREIGN KEY (`passager_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trip_participants`
--

LOCK TABLES `trip_participants` WRITE;
/*!40000 ALTER TABLE `trip_participants` DISABLE KEYS */;
INSERT INTO `trip_participants` VALUES (1,1,3,'confirme',15,0,'2025-07-11 09:35:18'),(2,2,1,'confirme',12,0,'2025-07-11 09:35:18'),(3,3,5,'confirme',18,0,'2025-07-11 09:35:18'),(4,5,2,'confirme',14,0,'2025-07-11 09:35:18');
/*!40000 ALTER TABLE `trip_participants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trips`
--

DROP TABLE IF EXISTS `trips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trips` (
  `id` int NOT NULL AUTO_INCREMENT,
  `chauffeur_id` int NOT NULL,
  `vehicle_id` int NOT NULL,
  `ville_depart` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `ville_arrivee` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `adresse_depart` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `adresse_arrivee` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_depart` datetime NOT NULL,
  `prix` decimal(6,2) NOT NULL,
  `places_totales` int NOT NULL,
  `places_restantes` int NOT NULL,
  `status` enum('planifie','termine','annule') COLLATE utf8mb4_general_ci DEFAULT 'planifie',
  `description` text COLLATE utf8mb4_general_ci,
  `preferences` text COLLATE utf8mb4_general_ci,
  `is_ecological` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `chauffeur_id` (`chauffeur_id`),
  KEY `vehicle_id` (`vehicle_id`),
  CONSTRAINT `trips_ibfk_1` FOREIGN KEY (`chauffeur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trips_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trips`
--

LOCK TABLES `trips` WRITE;
/*!40000 ALTER TABLE `trips` DISABLE KEYS */;
INSERT INTO `trips` VALUES (1,4,1,'Paris','Lyon',NULL,NULL,'2025-01-04 08:00:00',15.00,4,3,'planifie',NULL,NULL,0,'2025-07-11 09:35:18','2025-07-11 09:35:18'),(2,5,2,'Marseille','Nice',NULL,NULL,'2025-01-06 14:30:00',12.00,3,2,'planifie',NULL,NULL,1,'2025-07-11 09:35:18','2025-07-11 09:35:18'),(3,4,3,'Toulouse','Bordeaux',NULL,NULL,'2025-01-08 16:00:00',18.00,4,1,'planifie',NULL,NULL,1,'2025-07-11 09:35:18','2025-07-11 09:35:18'),(4,5,2,'Lyon','Paris',NULL,NULL,'2025-01-10 15:00:00',16.00,3,3,'planifie',NULL,NULL,1,'2025-07-11 09:35:18','2025-07-11 09:35:18'),(5,4,1,'Nice','Marseille',NULL,NULL,'2025-01-12 09:30:00',14.00,4,2,'planifie',NULL,NULL,0,'2025-07-11 09:35:18','2025-07-11 09:35:18');
/*!40000 ALTER TABLE `trips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_preferences`
--

DROP TABLE IF EXISTS `user_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_preferences` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `fumeur` tinyint(1) DEFAULT '0',
  `animaux` tinyint(1) DEFAULT '0',
  `preference_custom` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_preferences`
--

LOCK TABLES `user_preferences` WRITE;
/*!40000 ALTER TABLE `user_preferences` DISABLE KEYS */;
INSERT INTO `user_preferences` VALUES (1,1,1,0,'Aime la musique','2025-10-24 12:30:40'),(2,2,0,1,'PrÃ©fÃ©rence calme','2025-10-24 12:30:40');
/*!40000 ALTER TABLE `user_preferences` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `pseudo` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('Utilisateur','Moderateur','Administrateur') COLLATE utf8mb4_general_ci DEFAULT 'Utilisateur',
  `user_type` enum('passager','chauffeur','les_deux') COLLATE utf8mb4_general_ci DEFAULT 'passager',
  `user_type_preference` enum('passager','chauffeur','les_deux') COLLATE utf8mb4_general_ci DEFAULT 'passager',
  `credits` int DEFAULT '20',
  `rating` decimal(3,2) DEFAULT '0.00',
  `status` enum('actif','suspendu','supprime') COLLATE utf8mb4_general_ci DEFAULT 'actif',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin@ecoride.fr','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Admin','Administrateur','passager','passager',100,0.00,'actif','2025-07-11 09:35:18','2025-10-24 12:45:37'),(2,'modo@ecoride.fr','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Moderateur','Moderateur','passager','passager',50,0.00,'actif','2025-07-11 09:35:18','2025-07-11 09:35:18'),(3,'user@ecoride.fr','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','UserTest','Utilisateur','chauffeur','passager',20,0.00,'actif','2025-07-11 09:35:18','2025-10-24 12:45:21'),(4,'marc.d@ecoride.fr','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Marc D.','Utilisateur','passager','passager',25,0.00,'actif','2025-07-11 09:35:18','2025-07-11 09:35:18'),(5,'sophie.l@ecoride.fr','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Sophie L.','Utilisateur','passager','passager',30,0.00,'actif','2025-07-11 09:35:18','2025-07-11 09:35:18');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `marque` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `modele` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `couleur` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `plaque` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `date_immatriculation` date NOT NULL,
  `energie` enum('essence','diesel','electrique','hybride') COLLATE utf8mb4_general_ci NOT NULL,
  `places_disponibles` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plaque` (`plaque`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES (1,4,'Renault','Megane','Bleu','AB-123-CD','2020-03-15','essence',4,'2025-07-11 09:35:18'),(2,5,'Peugeot','308','Rouge','EF-456-GH','2021-07-20','electrique',3,'2025-07-11 09:35:18'),(3,4,'Tesla','Model 3','Blanc','IJ-789-KL','2022-01-10','electrique',4,'2025-07-11 09:35:18');
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-24 14:13:57
