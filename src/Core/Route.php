<?php

namespace App\Core;

use Closure;
use Exception;

class Route
{
    private static array $routes = [];
    private static string $path;
    private static array $middlewares = [];

    public static function init ($path = ''): void
    {
        self::$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        self::$path = str_replace($path, '', self::$path);
        self::$path = rtrim(self::$path, '/') ?: '/';
    }

    public static function get($uri, $action, $middleware = []): void
    {
        self::addRoute('GET', $uri, $action, $middleware);
    }

    public static function post($uri, $action, $middleware = []): void
    {
        self::addRoute('POST', $uri, $action, $middleware);
    }

    public static function put($uri, $action, $middleware = []): void
    {
        self::addRoute('PUT', $uri, $action, $middleware);
    }

    public static function patch($uri, $action, $middleware = []): void
    {
        self::addRoute('PATCH', $uri, $action, $middleware);
    }

    public static function delete($uri, $action, $middleware = []): void
    {
        self::addRoute('DELETE', $uri, $action, $middleware);
    }

    private static function addRoute($methods, $uri, $action, $middleware): void
    {
        $methods = (array)$methods;
        $uri = '/' . trim($uri, '/');
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($methods as $method) {
            self::$routes[] = [
                'method' => strtoupper($method),
                'uri' => $uri,
                'action' => $action,
                'middleware' => (array)$middleware
            ];
        }
    }

    public static function dispatch(): void
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        if ($requestMethod === 'POST' && isset($_POST['_method'])) {
            $requestMethod = strtoupper($_POST['_method']);
        }
        foreach (self::$routes as $route) {
            if ($route['method'] === $requestMethod) {
                $pattern = self::convertToRegex($route['uri']);
                if (preg_match($pattern, self::$path, $matches)) {
                    $params = self::getParams($route['uri'], $matches);
                    array_shift($matches);
                    if (!self::executeMiddleware($route['middleware'])) {
                        return;
                    }
                    self::executeAction($route['action'], $params);
                    return;
                }
            }
        }
    }

    private static function getParams(string $uri, $matches): array
    {
        array_shift($matches);
        if (str_contains($uri, '{')) {
            return self::parseParams($uri, $matches);
        }
        return array_values($matches);
    }

    private static function parseParams(string $uri, $matches): array
    {
        $params = [];
        $paramsNames = [];
        $matchIndex = 0;
        preg_match_all('/\{(\??)([a-zA-Z_][a-zA-Z0-9_]*)\}/i', $uri, $paramsNames, PREG_SET_ORDER);
        //$pattern = '#^' . $pattern . '$#';
        //preg_match_all($pattern, $uri, $paramsNames);
        foreach ($paramsNames as $key => $item) {
            $isOptional = !empty($item[1]);
            $paramName = $item[2];
            if ($isOptional) {
                if (isset($matches[$matchIndex]) && $matches[$matchIndex] != '') {
                    $params[$paramName] = $matches[$matchIndex];
                    $matchIndex++;
                } else {
                    $params[$paramName] = null;
                }
            } else {
                if (isset($matches[$matchIndex])) {
                    $params[$paramName] = $matches[$matchIndex];
                    $matchIndex++;
                }
            }
        }
        return $params;
    }

    private static function convertToRegex($uri): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^/]+)', $uri);
        $pattern = preg_replace('/\{(\?[a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?:/([^/]+))?', $pattern);
        $pattern = str_replace('//', '/', $pattern);
        if (str_contains($pattern, '(?:/([^/]+))?')) {
            $pattern = str_replace('/(?:/([^/]+))?', '(?:/([^/]+))?', $pattern);
            $pattern = rtrim($pattern, '/');
            $pattern .= '/?';
        }
        return '#^' . $pattern . '$#';
    }

    private static function executeAction($action, $params = []): void
    {
        $request = Request::createFromGlobals();
        if (is_callable($action)) {
            call_user_func_array($action, [$request, $params]);
        } elseif (is_string($action) && str_contains($action, '@')) {
            list($controller, $method) = explode('@', $action);
            $controller = 'App\Controllers\\' . $controller;
            $controllerInstance = new $controller();
            $request->setRouteParams($params);
            call_user_func_array([$controllerInstance, $method], [$request, $params]);
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