<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\VehicleRepository;

class UserService
{
    private UserRepository $userRepository;
    private VehicleRepository $vehicleRepository;

    public function __construct(
        ?UserRepository $userRepository = null,
        ?VehicleRepository $vehicleRepository = null
    ) {
        $this->userRepository = $userRepository ?? new UserRepository();
        $this->vehicleRepository = $vehicleRepository ?? new VehicleRepository();
    }

    public function emailOrUsernameExists(string $email, string $username): bool
    {
        return $this->userRepository->findByEmail($email) !== null
            || $this->userRepository->findByUsername($username) !== null;
    }

    public function register(array $input): array
    {
        $username        = trim($input['username'] ?? '');
        $email           = strtolower(trim($input['email'] ?? ''));
        $password        = $input['password'] ?? '';
        $passwordConfirm = $input['password_confirm'] ?? '';
        $old             = ['username' => $username, 'email' => $email];

        if ($username === '' || $email === '' || $password === '' || $passwordConfirm === '') {
            return ['success' => false, 'message' => 'Veuillez remplir tous les champs.', 'old' => $old];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Adresse email invalide.', 'old' => $old];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Le mot de passe doit contenir au moins 8 caracteres.', 'old' => $old];
        }

        if ($password !== $passwordConfirm) {
            return ['success' => false, 'message' => 'Les mots de passe ne correspondent pas.', 'old' => $old];
        }

        if ($this->emailOrUsernameExists($email, $username)) {
            return ['success' => false, 'message' => 'Cet email ou ce pseudo est deja utilise.', 'old' => $old];
        }

        $this->userRepository->create([
            'username'     => $username,
            'email'        => $email,
            'password'     => password_hash($password, PASSWORD_DEFAULT),
            'credits'      => 20,
            'role'         => 'user',
            'is_driver'    => 0,
            'is_passenger' => 1,
        ]);

        return ['success' => true, 'old' => $old];
    }

    public function debitCredits(int $userId, int $amount, string $type, ?string $reason = null, ?int $tripId = null): bool
    {
        $success = $this->userRepository->decrementCreditsIfEnough($userId, $amount);
        if ($success) {
            $this->userRepository->logCredit($userId, -$amount, $type, $reason, $tripId);
        }

        return $success;
    }

    public function creditCredits(int $userId, int $amount, string $type, ?string $reason = null, ?int $tripId = null): void
    {
        $this->userRepository->incrementCredits($userId, $amount);
        $this->userRepository->logCredit($userId, $amount, $type, $reason, $tripId);
    }

    public function recentTrips(int $userId, int $limit = 10): array
    {
        return $this->userRepository->recentTrips($userId, $limit);
    }

    public function updateUserType(int $userId, string $type): array
    {
        $isDriver = false;
        $isPassenger = false;

        if ($type === 'chauffeur') {
            $isDriver = true;
        } elseif ($type === 'passager') {
            $isPassenger = true;
        } elseif ($type === 'les_deux') {
            $isDriver = true;
            $isPassenger = true;
        } else {
            return ['success' => false, 'message' => 'Type invalide.'];
        }

        $this->userRepository->update($userId, [
            'is_driver' => $isDriver ? 1 : 0,
            'is_passenger' => $isPassenger ? 1 : 0,
        ]);

        return [
            'success' => true,
            'is_driver' => $isDriver,
            'is_passenger' => $isPassenger,
            'needs_vehicle' => $isDriver && empty($this->vehicleRepository->byUser($userId)),
            'success_message' => 'Profil mis a jour avec succes !',
        ];
    }
}
