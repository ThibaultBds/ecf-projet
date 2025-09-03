<?php
try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;dbname=ecoride;charset=utf8mb4",
        "root",
        "root",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Connexion OK à MySQL";
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
