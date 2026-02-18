<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    public static function send(string $to, string $subject, string $message): bool
    {
        $host = getenv('MAIL_HOST') ?: null;

        // Si MAIL_HOST est défini et différent de mailhog → Brevo SMTP
        if ($host && $host !== 'mailhog') {
            return self::sendSmtp($to, $subject, $message);
        }

        // Sinon fallback sur mail() (local Docker + Mailhog)
        $from = "noreply@ecoride.fr";
        $headers = "From: {$from}\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        return mail($to, $subject, $message, $headers);
    }

    private static function sendSmtp(string $to, string $subject, string $message): bool
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = getenv('MAIL_HOST');
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('MAIL_USERNAME');
            $mail->Password   = getenv('MAIL_PASSWORD');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) (getenv('MAIL_PORT') ?: 587);
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom('noreply@ecoride.fr', 'EcoRide');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body    = nl2br(htmlspecialchars($message));
            $mail->AltBody = $message;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erreur envoi mail : " . $e->getMessage());
            return false;
        }
    }
}
