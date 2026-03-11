<?php

namespace App\Controllers;

use App\Models\BaseModel;

class ContactController extends BaseController
{
    public function show()
    {
        $this->render('pages/contact', [
            'success' => $_SESSION['flash_success'] ?? '',
            'error' => '',
        ]);
        unset($_SESSION['flash_success']);
    }

    public function send()
    {
        $name = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['sujet'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            return $this->render('pages/contact', [
                'error' => 'Veuillez remplir tous les champs.',
                'success' => '',
                'old' => $_POST,
            ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->render('pages/contact', [
                'error' => 'Format d email invalide.',
                'success' => '',
                'old' => $_POST,
            ]);
        }

        if (strlen($name) < 2) {
            return $this->render('pages/contact', [
                'error' => 'Le nom doit contenir au moins 2 caracteres.',
                'success' => '',
                'old' => $_POST,
            ]);
        }

        if (strlen($message) < 10) {
            return $this->render('pages/contact', [
                'error' => 'Le message doit contenir au moins 10 caracteres.',
                'success' => '',
                'old' => $_POST,
            ]);
        }

        BaseModel::query(
            "INSERT INTO contact_messages (nom, email, sujet, message) VALUES (?, ?, ?, ?)",
            [$name, $email, $subject, $message]
        );

        $_SESSION['flash_success'] = 'Votre message a ete envoye avec succes.';
        header('Location: /contact');
        exit;
    }
}
