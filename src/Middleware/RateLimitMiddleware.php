<?php

namespace App\Middleware;

use App\Core\Database;

class RateLimitMiddleware
{
    public function handle($config = null)
    {
        $key = 'general';
        $max = 60;
        $window = 60;
        $ip = $this->getClientIp();
        $now = time();
        $windowStart = intdiv($now, $window) * $window;

        try {
            $pdo = Database::getInstance()->getConnection();

            $cleanupBefore = $windowStart - ($window * 2);
            $cleanup = $pdo->prepare(
                "DELETE FROM rate_limits WHERE rate_key = :rate_key AND ip_address = :ip AND window_start < :cutoff"
            );
            $cleanup->execute([
                ':rate_key' => $key,
                ':ip' => $ip,
                ':cutoff' => $cleanupBefore,
            ]);

            $upsert = $pdo->prepare(
                "INSERT INTO rate_limits (rate_key, ip_address, window_start, hits)"
                    . " VALUES (:rate_key, :ip, :window_start, 1)"
                    . " ON DUPLICATE KEY UPDATE hits = hits + 1"
            );
            $upsert->execute([
                ':rate_key' => $key,
                ':ip' => $ip,
                ':window_start' => $windowStart,
            ]);

            $select = $pdo->prepare(
                "SELECT hits FROM rate_limits WHERE rate_key = :rate_key AND ip_address = :ip AND window_start = :window_start"
            );
            $select->execute([
                ':rate_key' => $key,
                ':ip' => $ip,
                ':window_start' => $windowStart,
            ]);
            $hits = (int) $select->fetchColumn();
        } catch (\Throwable $e) {
            error_log('Rate limit indisponible : ' . $e->getMessage());
            return true;
        }

        if ($hits > $max) {
            $retryAfter = max(1, ($windowStart + $window) - $now);
            header('Retry-After: ' . $retryAfter);
            http_response_code(429);

            if ($this->isApiRequest()) {
                header('Content-Type: application/json');
                echo json_encode([
                    'error' => 'rate_limit',
                    'message' => 'Trop de requetes, reessayez plus tard.',
                    'retry_after' => $retryAfter,
                ]);
            } else {
                echo '<h1>429 - Trop de requetes</h1>';
                echo '<p>Merci de reessayer dans quelques instants.</p>';
            }

            exit;
        }

        return true;
    }

    private function getClientIp()
    {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded) {
            $parts = array_map('trim', explode(',', $forwarded));
            if (!empty($parts[0])) {
                return $parts[0];
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    private function isApiRequest()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/api') === 0) {
            return true;
        }

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        return strpos($accept, 'application/json') !== false;
    }
}
