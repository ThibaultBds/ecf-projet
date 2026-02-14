<?php

namespace App\Controllers;


class HomeController extends BaseController
{
    public function index()
    {
        $this->render('home/index', [
            'title' => 'Accueil - EcoRide'
        ]);
    }
}
