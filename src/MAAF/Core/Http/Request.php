<?php

declare(strict_types=1);

namespace MAAF\Core\Http;

/**
 * HTTP Request
 * 
 * MAAF Request implementation for handling HTTP requests.
 */
class Request
{
    /** @var array<string, mixed> */
    public array $query = [];

    /** @var array<string, mixed> */
    public array $post = [];

    /** @var array<string, mixed> */
    public array $server = [];

    /** @var array<string, string> */
    public array $headers = [];

    /** @var array<string, mixed>|null */
    private ?array $body = null;

    public function __construct()
    {
        $this->query = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->headers = $this->parseHeaders();
    }

    /**
     * Create a Request instance from global variables.
     */
    public static function fromGlobals(): self
    {
        return new self();
    }

    /**
     * Get the request path (URI without query string).
     */
    public function getPath(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = (string) strtok($uri, '?');
        return rawurldecode($path);
    }

    /**
     * Get the HTTP method.
     */
    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get request body as array (parsed JSON or form data).
     *
     * @return array<string, mixed>
     */
    public function getBody(): array
    {
        if ($this->body !== null) {
            return $this->body;
        }

        // Try to parse JSON body
        $contentType = $this->getHeader('Content-Type') ?? '';
        
        if (str_contains($contentType, 'application/json')) {
            $json = file_get_contents('php://input');
            if ($json !== false && $json !== '') {
                $decoded = json_decode($json, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->body = $decoded;
                    return $this->body;
                }
            }
        }

        // Fall back to POST data
        $this->body = $this->post;
        return $this->body;
    }

    /**
     * Get a value from the request body.
     *
     * @return mixed
     */
    public function getBodyValue(string $key, mixed $default = null)
    {
        $body = $this->getBody();
        return $body[$key] ?? $default;
    }

    /**
     * Get a query parameter.
     *
     * @return mixed
     */
    public function getQuery(string $key, mixed $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get a header value.
     */
    public function getHeader(string $name): ?string
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? null;
    }

    /**
     * Get all headers.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Parse headers from $_SERVER.
     *
     * @return array<string, string>
     */
    private function parseHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($headerName)] = (string) $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[strtolower(str_replace('_', '-', $key))] = (string) $value;
            }
        }

        return $headers;
    }
}

