<?php

namespace App\Controllers;

use App\Services\ContactService;

class ContactController extends BaseController
{
    public function show()
    {
        $this->render('pages/contact', [
            'success' => $_SESSION['flash_success'] ?? '',
            'error'   => '',
        ]);
        unset($_SESSION['flash_success']);
    }

    public function send()
    {
        $service = new ContactService();
        $payload = $service->buildPayload($_POST);
        $error = $service->validatePayload($payload);

        if ($error !== null) {
            return $this->render('pages/contact', ['error' => $error, 'success' => '', 'old' => $_POST]);
        }

        $mailSent = $service->save($payload);

        if ($mailSent) {
            $_SESSION['flash_success'] = 'Votre message a ete envoye avec succes.';
        } else {
            $_SESSION['flash_success'] = 'Votre message a bien ete enregistre, mais l email n a pas pu etre envoye.';
        }

        header('Location: /contact');
        exit;
    }
}
