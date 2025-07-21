// Configuration MongoDB pour EcoRide
// Base de données NoSQL pour les statistiques et logs

use('ecoride_nosql');

// Collection des statistiques journalières
db.daily_stats.insertMany([
    {
        date: new Date("2025-01-01"),
        trips_count: 15,
        new_users: 5,
        credits_earned: 30,
        co2_saved: 125.5
    },
    {
        date: new Date("2025-01-02"),
        trips_count: 22,
        new_users: 8,
        credits_earned: 44,
        co2_saved: 198.2
    },
    {
        date: new Date("2025-01-03"),
        trips_count: 18,
        new_users: 3,
        credits_earned: 36,
        co2_saved: 156.8
    }
]);

// Collection des logs détaillés
db.application_logs.insertMany([
    {
        timestamp: new Date(),
        level: "INFO",
        module: "auth",
        action: "user_login",
        user_id: "user@ecoride.fr",
        ip: "192.168.1.100",
        details: {
            browser: "Chrome",
            success: true
        }
    },
    {
        timestamp: new Date(),
        level: "WARNING",
        module: "trip",
        action: "trip_creation_failed",
        user_id: "marc.d@ecoride.fr",
        details: {
            reason: "insufficient_credits",
            credits_available: 2,
            credits_required: 5
        }
    }
]);

// Collection des métriques de performance
db.performance_metrics.insertMany([
    {
        date: new Date("2025-01-03"),
        page_views: 1250,
        unique_visitors: 456,
        avg_response_time: 180,
        search_queries: 89,
        successful_bookings: 34
    }
]);

// Index pour optimiser les requêtes
db.daily_stats.createIndex({ "date": 1 });
db.application_logs.createIndex({ "timestamp": -1 });
db.application_logs.createIndex({ "user_id": 1 });
db.performance_metrics.createIndex({ "date": 1 });

print("Base de données MongoDB configurée avec succès !");
