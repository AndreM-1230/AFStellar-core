<?php

namespace App\Core;

use Closure;
use Exception;

class Route
{
    private static $routes = [];
    private static $path;
    private static $middlewares = [];

    public static function init ($path = '')
    {
        self::$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        self::$path = str_replace($path, '', self::$path);
        self::$path = rtrim(self::$path, '/') ?: '/';
    }

    public static function get($uri, $action, $middleware = [])
    {
        self::addRoute('GET', $uri, $action, $middleware);
    }

    public static function post($uri, $action, $middleware = [])
    {
        self::addRoute('POST', $uri, $action, $middleware);
    }

    public static function put($uri, $action, $middleware = [])
    {
        self::addRoute('PUT', $uri, $action, $middleware);
    }

    public static function patch($uri, $action, $middleware = [])
    {
        self::addRoute('PATCH', $uri, $action, $middleware);
    }

    public static function delete($uri, $action, $middleware = [])
    {
        self::addRoute('DELETE', $uri, $action, $middleware);
    }

    private static function addRoute($methods, $uri, $action, $middleware)
    {
        $methods = (array)$methods;
        $uri = '/' . trim($uri, '/');
        $uri = rtrim($uri, '/') ?: '/';

        //$allMiddleware = array_merge(self::$middlewares, (array)$middleware);

        foreach ($methods as $method) {
            self::$routes[] = [
                'method' => strtoupper($method),
                'uri' => $uri,
                'action' => $action,
                'middleware' => (array)$middleware
            ];
        }
    }

    public static function dispatch()
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        if ($requestMethod === 'POST' && isset($_POST['_method'])) {
            $requestMethod = strtoupper($_POST['_method']);
        }
        foreach (self::$routes as $route) {
            if ($route['method'] === $requestMethod) {
                $pattern = self::convertToRegex($route['uri']);
                if (preg_match($pattern, self::$path, $matches)) {
                    array_shift($matches);
                    if (!self::executeMiddleware($route['middleware'])) {
                        return;
                    }
                    self::executeAction($route['action'], $matches);
                    return;
                }
            }
        }
    }

    private static function convertToRegex($uri)
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    private static function executeAction($action, $params = [])
    {
        if (is_callable($action)) {
            call_user_func_array($action, $params);
        } elseif (is_string($action) && strpos($action, '@') !== false) {
            list($controller, $method) = explode('@', $action);
            $controller = 'App\Controllers\\' . $controller;
            $controllerInstance = new $controller();
            $request = Request::createFromGlobals();
            call_user_func_array([$controllerInstance, $method], $params);
        } else {
            throw new Exception('');
        }
    }

    private static function executeMiddleware($middlewares)
    {
        if ($middlewares) {
            foreach($middlewares as $middleware) {
                if (is_string($middleware) && class_exists($middleware)) {
                    $middlewareInstance = new $middleware();
                    if ($middlewareInstance instanceof Middleware) {
                        return $middlewareInstance->handle();
                    }
                } elseif ($middleware instanceof Middleware) {
                    return $middleware->handle();
                }
            }
        } else {
            return true;
        }
        
    }
}