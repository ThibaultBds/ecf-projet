<?php

namespace App\Controllers;

use App\Core\Mailer;

class ContactController extends BaseController
{
    public function show()
    {
        $this->render('pages/contact', [
            'success' => $_SESSION['flash_success'] ?? '',
            'error' => ''
        ]);
        unset($_SESSION['flash_success']);
    }

    public function send()
    {
        $nom     = trim($_POST['nom']     ?? '');
        $email   = trim($_POST['email']   ?? '');
        $sujet   = trim($_POST['sujet']   ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
            return $this->render('pages/contact', [
                'error' => 'Veuillez remplir tous les champs.',
                'success' => '',
                'old' => $_POST
            ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('pages/contact', [
                'error' => 'Format d\'email invalide.',
                'success' => '',
                'old' => $_POST
            ]);
        }

        if (strlen($nom) < 2) {
            return $this->render('pages/contact', [
                'error' => 'Le nom doit contenir au moins 2 caractères.',
                'success' => '',
                'old' => $_POST
            ]);
        }

        if (strlen($message) < 10) {
            return $this->render('pages/contact', [
                'error' => 'Le message doit contenir au moins 10 caractères.',
                'success' => '',
                'old' => $_POST
            ]);
        }

        $adminEmail = "admin@ecoride.fr";

        $emailContent  = "Nouveau message de contact\n\n";
        $emailContent .= "Nom: {$nom}\n";
        $emailContent .= "Email: {$email}\n";
        $emailContent .= "Sujet: {$sujet}\n\n";
        $emailContent .= "Message:\n{$message}";

        Mailer::send(
            $adminEmail,
            "Nouveau message - EcoRide : {$sujet}",
            $emailContent
        );

        $_SESSION['flash_success'] = 'Votre message a été envoyé avec succès ! Nous vous recontacterons bientôt.';
        header('Location: /contact');
        exit;
    }
}
