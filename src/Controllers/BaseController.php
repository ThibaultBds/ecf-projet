<?php

class BaseController 
{
    protected function render(string $view, array $data = [])
    {
        extract($data);

        $viewPath = dirname(__DIR__) . '/Views/' . $view . '.php';
               if (!file_exists($viewPath)) {
            die("Vue introuvable : " . $viewPath);
        }

        ob_start();
        require $viewPath; 
        $content = ob_get_clean();

        require dirname(__DIR__, 2) . '/templates/layout.php';

    }
}
