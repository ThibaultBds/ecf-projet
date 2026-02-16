<?php

namespace App\Core;

class Router
{
    private static $routes = [];
    private static $groupMiddleware = [];
    private static $namedRoutes = [];

    public static function get($uri, $action)
    {
        return self::addRoute('GET', $uri, $action);
    }

    public static function post($uri, $action)
    {
        return self::addRoute('POST', $uri, $action);
    }

    public static function put($uri, $action)
    {
        return self::addRoute('PUT', $uri, $action);
    }

    public static function delete($uri, $action)
    {
        return self::addRoute('DELETE', $uri, $action);
    }

    public static function group($attributes, $callback)
    {
        $previousMiddleware = self::$groupMiddleware;

        if (isset($attributes['middleware'])) {
            self::$groupMiddleware = array_merge(
                self::$groupMiddleware,
                is_array($attributes['middleware']) ? $attributes['middleware'] : [$attributes['middleware']]
            );
        }

        $callback();

        self::$groupMiddleware = $previousMiddleware;
    }

    private static function addRoute($method, $uri, $action)
    {
        $uri = '/' . trim($uri, '/');

        $route = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'middleware' => self::$groupMiddleware,
            'pattern' => self::compilePattern($uri)
        ];

        self::$routes[] = $route;

        return new Route($route);
    }

    private static function compilePattern($uri)
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');

        foreach (self::$routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                foreach ($route['middleware'] as $middleware) {
                    $middlewareResult = $this->runMiddleware($middleware);
                    if ($middlewareResult === false) {
                        return;
                    }
                }

                return $this->callAction($route['action'], $params);
            }
        }

        $this->render404();
    }

    private function runMiddleware($middleware)
    {
        // Parse middleware with parameters (e.g. "role:admin")
        if (strpos($middleware, ':') !== false) {
            list($middlewareName, $param) = explode(':', $middleware, 2);
        } else {
            $middlewareName = $middleware;
            $param = null;
        }

        $middlewareClass = $this->getMiddlewareClass($middlewareName);

        if (!$middlewareClass || !class_exists($middlewareClass)) {
            error_log("Middleware non trouvé : $middlewareName");
            return true;
        }

        $instance = new $middlewareClass();

        return $param ? $instance->handle($param) : $instance->handle();
    }

    private function getMiddlewareClass($name)
    {
        $middlewares = [
            'auth'  => \App\Middleware\AuthMiddleware::class,
            'guest' => \App\Middleware\GuestMiddleware::class,
            'role'  => \App\Middleware\RoleMiddleware::class,
            'csrf'  => \App\Middleware\CsrfMiddleware::class,
        ];

        return $middlewares[$name] ?? null;
    }

    private function callAction($action, $params = [])
{
    if (is_callable($action)) {
        return call_user_func_array($action, array_values($params));
    }

    if (is_string($action)) {

        list($controller, $method) = explode('@', $action);

        $controllerClass = "App\\Controllers\\$controller";

        if (!class_exists($controllerClass)) {
            die("Classe de contrôleur non trouvée : $controllerClass");
        }

        $controllerInstance = new $controllerClass();

        if (!method_exists($controllerInstance, $method)) {
            die("Méthode non trouvée : $method dans $controllerClass");
        }

        return call_user_func_array(
            [$controllerInstance, $method],
            array_values($params)
        );
    }
}


    private function render404()
    {
        http_response_code(404);
        self::renderErrorWithLayout(404);
    }

    public static function abort($code = 403, $message = 'Accès interdit')
    {
        http_response_code($code);
        self::renderErrorWithLayout($code);
        exit;
    }

    private static function renderErrorWithLayout($code)
    {
        $errorView = __DIR__ . "/../Views/errors/$code.php";
        $layoutFile = dirname(__DIR__, 2) . '/templates/layout.php';

        if (file_exists($errorView) && file_exists($layoutFile)) {
            ob_start();
            require $errorView;
            $content = ob_get_clean();
            require $layoutFile;
        } elseif (file_exists($errorView)) {
            require $errorView;
        } else {
            echo "<h1>$code - Erreur</h1>";
        }
    }
}

class Route
{
    private $route;

    public function __construct(&$route)
    {
        $this->route = &$route;
    }

    public function middleware(...$middleware)
    {
        $this->route['middleware'] = array_merge(
            $this->route['middleware'],
            $middleware
        );
        return $this;
    }

    public function name($name)
    {
        Router::$namedRoutes[$name] = $this->route;
        return $this;
    }
}
