<?php

namespace App\Core;

class Mailer
{
    public static function send(string $to, string $subject, string $message): bool
    {
        $apiKey = getenv('BREVO_API_KEY');

        if ($apiKey) {
            return self::sendBrevoApi($to, $subject, $message, $apiKey);
        }

        // Fallback local : mail() via Mailhog
        $from = "noreply@ecoride.fr";
        $headers  = "From: {$from}\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        return mail($to, $subject, $message, $headers);
    }

    private static function sendBrevoApi(string $to, string $subject, string $message, string $apiKey): bool
    {
        $payload = json_encode([
            'sender'      => ['name' => 'EcoRide', 'email' => 'noreply@ecoride.fr'],
            'to'          => [['email' => $to]],
            'subject'     => $subject,
            'textContent' => $message,
            'htmlContent' => '<html><body>' . nl2br(htmlspecialchars($message)) . '</body></html>',
        ]);

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'accept: application/json',
                'api-key: ' . $apiKey,
                'content-type: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Brevo curl error: $error");
            return false;
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Brevo API error ($httpCode): $response");
            return false;
        }

        return true;
    }
}
