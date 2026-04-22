<?php

namespace App\Services;

use App\Core\Mailer;
use App\Repositories\ContactRepository;

class ContactService
{
    private ContactRepository $contactRepository;
    private Mailer $mailer;

    public function __construct(?ContactRepository $contactRepository = null, ?Mailer $mailer = null)
    {
        $this->contactRepository = $contactRepository ?? new ContactRepository();
        $this->mailer = $mailer ?? new Mailer();
    }

    public function buildPayload(array $input): array
    {
        return [
            'name'    => trim($input['nom'] ?? ''),
            'email'   => trim($input['email'] ?? ''),
            'subject' => trim($input['sujet'] ?? ''),
            'message' => trim($input['message'] ?? ''),
        ];
    }

    public function validatePayload(array $payload): ?string
    {
        if (empty($payload['name']) || empty($payload['email']) || empty($payload['subject']) || empty($payload['message'])) {
            return 'Veuillez remplir tous les champs.';
        }

        if (!filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Format d email invalide.';
        }

        if (strlen($payload['name']) < 2) {
            return 'Le nom doit contenir au moins 2 caracteres.';
        }

        if (strlen($payload['message']) < 10) {
            return 'Le message doit contenir au moins 10 caracteres.';
        }

        return null;
    }

    public function save(array $payload): bool
    {
        $this->contactRepository->save(
            $payload['name'],
            $payload['email'],
            $payload['subject'],
            $payload['message']
        );

        $recipient = getenv('CONTACT_EMAIL') ?: 'contact@ecoride.fr';
        $subject = '[Contact EcoRide] ' . $payload['subject'];
        $message = "Nouveau message depuis le formulaire de contact.\n\n"
            . "Nom : {$payload['name']}\n"
            . "Email : {$payload['email']}\n"
            . "Sujet : {$payload['subject']}\n\n"
            . "Message :\n{$payload['message']}\n";

        return $this->mailer->send($recipient, $subject, $message);
    }
}
