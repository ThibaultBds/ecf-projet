<?php

namespace App\Core;

use PDO;

class DatabaseSessionHandler implements \SessionHandlerInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT data FROM php_sessions WHERE session_id = ? AND expires_at > NOW() LIMIT 1"
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['data'] : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function write(string $id, string $data): bool
    {
        try {
            $lifetime = (int) ini_get('session.gc_maxlifetime') ?: 1440;
            $this->pdo->prepare(
                "INSERT INTO php_sessions (session_id, data, expires_at)
                 VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
                 ON DUPLICATE KEY UPDATE data = VALUES(data), expires_at = VALUES(expires_at)"
            )->execute([$id, $data, $lifetime]);
        } catch (\Throwable $e) {
            // Ignore: sessions will not persist but app stays functional
        }
        return true;
    }

    public function destroy(string $id): bool
    {
        try {
            $this->pdo->prepare("DELETE FROM php_sessions WHERE session_id = ?")->execute([$id]);
        } catch (\Throwable $e) {
            // Ignore
        }
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE expires_at < NOW()");
            $stmt->execute();
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
