<?php

namespace App\Controllers;

class PageController extends BaseController
{
    public function terms()
    {
        $this->render('pages/terms');
    }

    public function privacy()
    {
        $this->render('pages/privacy');
    }

    public function legal()
    {
        $this->render('pages/legal');
    }
}
