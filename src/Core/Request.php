<?php

namespace App\Core;

class Request
{
    protected $query;
    protected $request;
    protected $files;
    protected $server;
    protected $cookies;
    protected $headers;
    protected $content;
    protected $attributes;
    protected $routeParams;
    
    public function __construct(
        array $query = [],
        array $request = [],
        array $files = [],
        array $server = [],
        array $cookies = [],
        $content = null
    ) {
        $this->query = $query;
        $this->request = $request;
        $this->files = $files;
        $this->server = $server;
        $this->cookies = $cookies;
        $this->content = $content;
        $this->headers = $this->initHeaders();
        $this->attributes = [];
    }
    
    public static function createFromGlobals(): self
    {
        $request = new static(
            $_GET,
            $_POST,
            $_FILES,
            $_SERVER,
            $_COOKIE,
            file_get_contents('php://input')
        );
        
        if ($request->isJson()) {
            $data = json_decode($request->getContent(), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->request = $data;
            }
        }
        
        return $request;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->request);
    }

    public function input(string $key, $default = null)
    {
        if (isset($this->request[$key])) {
            return $this->request[$key];
        }
        
        if (isset($this->query[$key])) {
            return $this->query[$key];
        }
        
        return $default;
    }

    public function query(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        
        return $this->query[$key] ?? $default;
    }

    public function post(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->request;
        }
        
        return $this->request[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->request[$key]) || isset($this->query[$key]);
    }

    public function filled(string $key): bool
    {
        $value = $this->input($key);
        return !empty($value) || $value === '0';
    }

    public function only(array $keys): array
    {
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = $this->input($key);
        }
        return $results;
    }

    public function except(array $keys): array
    {
        $all = $this->all();
        foreach ($keys as $key) {
            unset($all[$key]);
        }
        return $all;
    }

    public function setRouteParams($params): self
    {
        $this->routeParams = $params;
        foreach ($params as $key => $item) {
            $this->setAttribute($key, $item);
        }
        return $this;
    }

    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }
    
    public function hasFile(string $key): bool
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }
    
    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function path(): string
    {
        $path = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return trim($path, '/') ?: '/';
    }

    public function header(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }
        
        $key = strtoupper(str_replace('-', '_', $key));
        return $this->headers[$key] ?? $default;
    }

    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type');
        return strpos($contentType ?? '', '/json');
    }
    
    public function json(string $key = null, $default = null)
    {
        if (!$this->isJson()) {
            return $default;
        }
        
        $data = json_decode($this->getContent(), true);
        if ($key === null) {
            return $data;
        }
        
        return $data[$key] ?? $default;
    }

    public function getContent(): string
    {
        if ($this->content === null) {
            $this->content = file_get_contents('php://input');
        }
        return $this->content;
    }

    public function cookie(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }
        
        return $this->cookies[$key] ?? $default;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }
    
    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }
    
    public function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    protected function initHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headers[substr($key, 5)] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'])) {
                $headers[$key] = $value;
            }
        }
        return $headers;
    }
    
    protected function getHost(): string
    {
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        return explode(':', $host)[0];
    }
    
    protected function getRequestUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }
    
    protected function getQueryString(): string
    {
        return $this->server['QUERY_STRING'] ?? '';
    }

    public function __get(string $name)
    {
        if ($this->input($name) !== null) {
            return $this->input($name);
        } elseif ($this->getAttribute($name) !== null) {
            return $this->getAttribute($name);
        }
    }
    
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }
}