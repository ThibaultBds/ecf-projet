<?php

namespace App\Controllers;

class BaseController 
{
    protected function render(string $view, array $data = [])
    {
        extract($data, EXTR_SKIP);

        $viewPath = dirname(__DIR__) . '/Views/' . $view . '.php';
               if (!file_exists($viewPath)) {
            die("Vue introuvable : " . $viewPath);
        }

        ob_start();
        require $viewPath;
        $content = ltrim(ob_get_clean(), "\xEF\xBB\xBF");

        require dirname(__DIR__, 2) . '/templates/layout.php';

    }
}
