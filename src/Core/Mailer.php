<?php

namespace App\Core;

class Mailer
{
    public static function send(string $to, string $subject, string $message): bool
    {
        $from = "noreply@ecoride.fr";

        $headers = "From: {$from}\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        return mail($to, $subject, $message, $headers);
    }
}