<?php

declare(strict_types=1);

namespace MAAF\Core\Http;

/**
 * HTTP Request
 * 
 * Stabil HTTP Request osztály.
 * 
 * @version 1.0.0
 */
final class Request
{
    /**
     * @var array<string, mixed>
     */
    public readonly array $server;

    /**
     * @var array<string, mixed>
     */
    public readonly array $get;

    /**
     * @var array<string, mixed>
     */
    public readonly array $post;

    /**
     * @var array<string, mixed>
     */
    public readonly array $files;

    /**
     * @var array<string, mixed>
     */
    public readonly array $cookies;

    /**
     * @var array<string, string>
     */
    private array $headers = [];

    /**
     * @var string|null
     */
    private ?string $body = null;

    /**
     * @var array<string, mixed>
     */
    private array $parsedBody = [];

    public function __construct(
        array $server = [],
        array $get = [],
        array $post = [],
        array $files = [],
        array $cookies = [],
        ?string $body = null
    ) {
        $this->server = $server ?: $_SERVER;
        $this->get = $get ?: $_GET;
        $this->post = $post ?: $_POST;
        $this->files = $files ?: $_FILES;
        $this->cookies = $cookies ?: $_COOKIE;
        $this->body = $body ?? $this->getRawBody();
        $this->parseHeaders();
        $this->parseBody();
    }

    /**
     * Create request from global variables
     * 
     * @return self
     */
    public static function fromGlobals(): self
    {
        return new self();
    }

    /**
     * Get request method
     * 
     * @return string
     */
    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Get request URI
     * 
     * @return string
     */
    public function getUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    /**
     * Get request path (without query string)
     * 
     * @return string
     */
    public function getPath(): string
    {
        $uri = $this->getUri();
        $path = parse_url($uri, PHP_URL_PATH);
        return $path ?: '/';
    }

    /**
     * Get query string
     * 
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->server['QUERY_STRING'] ?? '';
    }

    /**
     * Get a header value
     * 
     * @param string $name Header name (case-insensitive)
     * @return string|null
     */
    public function getHeader(string $name): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? null;
    }

    /**
     * Get all headers
     * 
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get request body
     * 
     * @return string
     */
    public function getBody(): string
    {
        return $this->body ?? '';
    }

    /**
     * Get parsed body (JSON, form data, etc.)
     * 
     * @return array<string, mixed>
     */
    public function getParsedBody(): array
    {
        return $this->parsedBody;
    }

    /**
     * Get a query parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed
     */
    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    /**
     * Get a POST parameter
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed
     */
    public function getPost(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get a request parameter (GET or POST)
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $default;
    }

    /**
     * Check if request is JSON
     * 
     * @return bool
     */
    public function isJson(): bool
    {
        $contentType = $this->getHeader('Content-Type') ?? '';
        return str_contains($contentType, 'application/json');
    }

    /**
     * Check if request is AJAX
     * 
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Get client IP address
     * 
     * @return string
     */
    public function getClientIp(): string
    {
        return $this->server['HTTP_X_FORWARDED_FOR'] 
            ?? $this->server['HTTP_X_REAL_IP'] 
            ?? $this->server['REMOTE_ADDR'] 
            ?? 'unknown';
    }

    private function parseHeaders(): void
    {
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $this->headers[strtolower($headerName)] = (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $this->headers[strtolower(str_replace('_', '-', $key))] = (string) $value;
            }
        }
    }

    private function parseBody(): void
    {
        if ($this->isJson() && $this->body !== null) {
            $decoded = json_decode($this->body, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $this->parsedBody = $decoded;
            }
        } elseif (!empty($this->post)) {
            $this->parsedBody = $this->post;
        }
    }

    private function getRawBody(): string
    {
        return file_get_contents('php://input') ?: '';
    }
}
