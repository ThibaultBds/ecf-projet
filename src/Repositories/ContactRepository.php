<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\ContactMessage;
use PDO;

class ContactRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    public function findAll(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return ContactMessage::hydrateAll($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function markAsRead(int $id): void
    {
        $stmt = $this->pdo->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function save(string $name, string $email, string $subject, string $message): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO contact_messages (nom, email, sujet, message) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$name, $email, $subject, $message]);
    }
}
