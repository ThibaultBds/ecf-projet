<?php

namespace App\Middleware;

class GuestMiddleware
{
    public function handle()
    {
        if (isset($_SESSION['user']) && isset($_SESSION['user']['id'])) {
            header('Location: /profile');
            exit;
        }

        return true;
    }
}
