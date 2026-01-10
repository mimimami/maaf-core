<?php

declare(strict_types=1);

namespace MAAF\Core\Http;

/**
 * HTTP Response
 * 
 * Stabil HTTP Response osztály.
 * 
 * @version 1.0.0
 */
final class Response
{
    private int $statusCode = 200;
    
    /**
     * @var array<string, string>
     */
    private array $headers = [];
    
    private string $body = '';

    public function __construct(int $statusCode = 200, array $headers = [], string $body = '')
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * Create JSON response
     * 
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     * @return self
     */
    public static function json(mixed $data, int $statusCode = 200): self
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        
        return new self(
            $statusCode,
            ['Content-Type' => 'application/json; charset=utf-8'],
            $json
        );
    }

    /**
     * Create HTML response
     * 
     * @param string $html HTML content
     * @param int $statusCode HTTP status code
     * @return self
     */
    public static function html(string $html, int $statusCode = 200): self
    {
        return new self(
            $statusCode,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $html
        );
    }

    /**
     * Create text response
     * 
     * @param string $text Text content
     * @param int $statusCode HTTP status code
     * @return self
     */
    public static function text(string $text, int $statusCode = 200): self
    {
        return new self(
            $statusCode,
            ['Content-Type' => 'text/plain; charset=utf-8'],
            $text
        );
    }

    /**
     * Create redirect response
     * 
     * @param string $url Redirect URL
     * @param int $statusCode HTTP status code (301 or 302)
     * @return self
     */
    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self(
            $statusCode,
            ['Location' => $url],
            ''
        );
    }

    /**
     * Get status code
     * 
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get headers
     * 
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get body
     * 
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set status code
     * 
     * @param int $statusCode HTTP status code
     * @return self
     */
    public function withStatus(int $statusCode): self
    {
        $new = clone $this;
        $new->statusCode = $statusCode;
        return $new;
    }

    /**
     * Add header
     * 
     * @param string $name Header name
     * @param string $value Header value
     * @return self
     */
    public function withHeader(string $name, string $value): self
    {
        $new = clone $this;
        $new->headers[$name] = $value;
        return $new;
    }

    /**
     * Set body
     * 
     * @param string $body Response body
     * @return self
     */
    public function withBody(string $body): self
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    /**
     * Send response to client
     * 
     * @return void
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        echo $this->body;
    }
}
