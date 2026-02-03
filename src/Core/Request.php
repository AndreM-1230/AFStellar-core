<?php

namespace App\Core;

class Request
{
    protected array $query;
    protected array $request;
    protected array $files;
    protected array $server;
    protected array $cookies;
    protected array $headers;
    protected $content;
    protected array $attributes;
    
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
    
    public function url(): string
    {
        $scheme = $this->isSecure() ? 'https' : 'http';
        $host = $this->getHost();
        $port = $this->getPort();
        
        $url = "{$scheme}://{$host}";
        if (($scheme === 'http' && $port != 80) || ($scheme === 'https' && $port != 443)) {
            $url .= ":{$port}";
        }
        
        return $url . $this->getRequestUri();
    }
    
    public function fullUrl(): string
    {
        $query = $this->getQueryString();
        return $this->url() . ($query ? "?{$query}" : '');
    }

    public function header(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->headers;
        }
        
        $key = strtoupper(str_replace('-', '_', $key));
        return $this->headers[$key] ?? $default;
    }
    
    public function bearerToken(): ?string
    {
        $header = $this->header('Authorization');
        if (str_starts_with($header ?? '', 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    public function isJson(): bool
    {
        $contentType = $this->header('Content-Type');
        return str_contains($contentType ?? '', '/json');
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

    public function validate(array $rules): array
    {
        $data = $this->all();
        $validated = [];
        $errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            $value = $data[$field] ?? null;
            
            foreach ($rulesArray as $rule) {
                if (!$this->checkRule($field, $value, $rule, $data)) {
                    $errors[$field][] = "Поле {$field} не прошло правило: {$rule}";
                }
            }
            
            if (!isset($errors[$field])) {
                $validated[$field] = $value;
            }
        }
        
        if (!empty($errors)) {
            throw new \Exception('Validation failed: ' . json_encode($errors));
        }
        
        return $validated;
    }
    
    protected function checkRule(string $field, $value, string $rule, array $data): bool
    {
        switch ($rule) {
            case 'required':
                return !empty($value) || $value === '0';
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'numeric':
                return is_numeric($value);
            case 'min:6':
                return strlen($value) >= 6;
            case 'confirmed':
                return isset($data[$field . '_confirmation']) && $value === $data[$field . '_confirmation'];
            default:
                return true;
        }
    }

    protected function initHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
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
    
    protected function getPort(): int
    {
        $host = $this->server['HTTP_HOST'] ?? 'localhost';
        if (str_contains($host, ':')) {
            return (int) explode(':', $host)[1];
        }
        return $this->isSecure() ? 443 : 80;
    }
    
    protected function getRequestUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }
    
    protected function getQueryString(): string
    {
        return $this->server['QUERY_STRING'] ?? '';
    }
    
    public function isSecure(): bool
    {
        $https = $this->server['HTTPS'] ?? '';
        return !empty($https) && $https !== 'off';
    }

    public function __get(string $name)
    {
        return $this->input($name);
    }
    
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }
}