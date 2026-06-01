<?php

namespace App\Middleware;

use App\Core\Database;

class RateLimitMiddleware
{
    public function handle($config = null)
    {
        [$key, $max, $window] = $this->resolveLimit($config);
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
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    private function resolveLimit($config): array
    {
        if (is_string($config) && preg_match('/^([a-z0-9_-]+):(\d+):(\d+)$/i', $config, $matches)) {
            return [$matches[1], max(1, (int) $matches[2]), max(1, (int) $matches[3])];
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        if ($path === '/login' && $method === 'POST') {
            return ['login', 10, 900];
        }

        if ($path === '/contact' && $method === 'POST') {
            return ['contact', 5, 300];
        }

        if (strpos($path, '/api/') === 0) {
            return ['api:' . $path, 60, 60];
        }

        return ['page:' . $method . ':' . $path, 120, 60];
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
