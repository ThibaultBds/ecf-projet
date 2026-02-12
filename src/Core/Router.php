<?php

class Router
{
    private static $routes = [];
    private static $groupMiddleware = [];
    private static $namedRoutes = [];

    /**
     * Enregistrer une route GET
     */
    public static function get($uri, $action)
    {
        return self::addRoute('GET', $uri, $action);
    }

    /**
     * Enregistrer une route POST
     */
    public static function post($uri, $action)
    {
        return self::addRoute('POST', $uri, $action);
    }

    /**
     * Enregistrer une route PUT
     */
    public static function put($uri, $action)
    {
        return self::addRoute('PUT', $uri, $action);
    }

    /**
     * Enregistrer une route DELETE
     */
    public static function delete($uri, $action)
    {
        return self::addRoute('DELETE', $uri, $action);
    }

    /**
     * Groupe de routes avec middleware partagé
     */
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

    /**
     * Ajouter une route
     */
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

    /**
     * Compiler le pattern d'URI pour la regex
     */
    private static function compilePattern($uri)
    {
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    /**
     * Dispatcher la requête
     */
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');

        foreach (self::$routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                // Extraire les paramètres
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                // Exécuter les middlewares
                foreach ($route['middleware'] as $middleware) {
                    $middlewareResult = $this->runMiddleware($middleware);
                    if ($middlewareResult === false) {
                        return; // Middleware a bloqué la requête
                    }
                }

                // Dispatcher vers le contrôleur
                return $this->callAction($route['action'], $params);
            }
        }

        // 404 - Route non trouvée
        $this->render404();
    }

    /**
     * Exécuter un middleware
     */
    private function runMiddleware($middleware)
    {
        // Parse middleware avec paramètres (ex: "role:Administrateur")
        if (strpos($middleware, ':') !== false) {
            list($middlewareName, $param) = explode(':', $middleware, 2);
        } else {
            $middlewareName = $middleware;
            $param = null;
        }

        $middlewareClass = $this->getMiddlewareClass($middlewareName);

        if (!$middlewareClass || !file_exists($middlewareClass['file'])) {
            error_log("Middleware non trouvé : $middlewareName");
            return true;
        }

        require_once $middlewareClass['file'];
        $instance = new $middlewareClass['class']();

        return $param ? $instance->handle($param) : $instance->handle();
    }

    /**
     * Obtenir la classe middleware
     */
    private function getMiddlewareClass($name)
    {
        $middlewares = [
            'auth' => [
                'file' => __DIR__ . '/../Middleware/AuthMiddleware.php',
                'class' => 'AuthMiddleware'
            ],
            'guest' => [
                'file' => __DIR__ . '/../Middleware/GuestMiddleware.php',
                'class' => 'GuestMiddleware'
            ],
            'role' => [
                'file' => __DIR__ . '/../Middleware/RoleMiddleware.php',
                'class' => 'RoleMiddleware'
            ],
            'csrf' => [
                'file' => __DIR__ . '/../Middleware/CsrfMiddleware.php',
                'class' => 'CsrfMiddleware'
            ]
        ];

        return $middlewares[$name] ?? null;
    }

    /**
     * Appeler l'action du contrôleur
     */
    private function callAction($action, $params = [])
    {
        if (is_callable($action)) {
            return call_user_func_array($action, array_values($params));
        }

        if (is_string($action)) {
            list($controller, $method) = explode('@', $action);

            // Gérer les namespaces (ex: Api\TripController)
            $controllerPath = str_replace('\\', '/', $controller);
            $controllerFile = __DIR__ . '/../Controllers/' . $controllerPath . '.php';

            if (!file_exists($controllerFile)) {
                die("Contrôleur non trouvé : $controllerFile");
            }

            require_once $controllerFile;

            // Extraire le nom de classe sans namespace pour l'instanciation
            $controllerClass = basename($controller);

            if (!class_exists($controllerClass)) {
                die("Classe de contrôleur non trouvée : $controllerClass");
            }

            $controllerInstance = new $controllerClass();

            if (!method_exists($controllerInstance, $method)) {
                die("Méthode non trouvée : $method dans $controllerClass");
            }

            return call_user_func_array([$controllerInstance, $method], array_values($params));
        }
    }

    /**
     * Afficher la page 404
     */
    private function render404()
    {
        http_response_code(404);
        self::renderErrorWithLayout(404);
    }

    /**
     * Afficher la page 403
     */
    public static function abort($code = 403, $message = 'Accès interdit')
    {
        http_response_code($code);
        self::renderErrorWithLayout($code);
        exit;
    }

    /**
     * Rendre une page d'erreur dans le layout
     */
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

/**
 * Classe Route pour le chaînage fluide
 */
class Route
{
    private $route;

    public function __construct(&$route)
    {
        $this->route = &$route;
    }

    /**
     * Ajouter un middleware à la route
     */
    public function middleware(...$middleware)
    {
        $this->route['middleware'] = array_merge(
            $this->route['middleware'],
            $middleware
        );
        return $this;
    }

    /**
     * Nommer la route
     */
    public function name($name)
    {
        Router::$namedRoutes[$name] = $this->route;
        return $this;
    }
}
