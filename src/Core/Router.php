<?php

class Router 
{
    public function dispatch() 
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if ($uri === '/' || $uri === '') {
            require __DIR__ . '/../Controllers/HomeController.php';
            (new HomeController())->index(); 
            return; 
        }
        echo '404 - Page non trouvée';
    } 
}