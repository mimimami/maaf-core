<?php

declare(strict_types=1);

namespace MAAF\Core\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * HTTP Response
 * 
 * MAAF Response implementation for handling HTTP responses.
 */
final class Response implements ResponseInterface
{
    private int $statusCode = 200;
    
    /**
     * @var array<string, array<string>>
     */
    private array $headers = [];
    
    private ?string $body = null;

    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        ?string $body = null
    ) {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    /**
     * Create a JSON response.
     *
     * @param array<string, mixed>|object $data
     */
    public static function json(array|object $data, int $statusCode = 200): self
    {
        $headers = [
            'Content-Type' => ['application/json; charset=utf-8']
        ];

        return new self(
            $statusCode,
            $headers,
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)
        );
    }

    /**
     * Create a text response.
     */
    public static function text(string $text, int $statusCode = 200): self
    {
        $headers = [
            'Content-Type' => ['text/plain; charset=utf-8']
        ];

        return new self($statusCode, $headers, $text);
    }

    /**
     * Create an HTML response.
     */
    public static function html(string $html, int $statusCode = 200): self
    {
        $headers = [
            'Content-Type' => ['text/html; charset=utf-8']
        ];

        return new self($statusCode, $headers, $html);
    }

    /**
     * Create an empty response.
     */
    public static function empty(int $statusCode = 204): self
    {
        return new self($statusCode);
    }

    /**
     * Send the response to the client.
     */
    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                header($name . ': ' . $value, false);
            }
        }

        if ($this->body !== null) {
            echo $this->body;
        }
    }

    // PSR-7 ResponseInterface implementation

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion(string $version): ResponseInterface
    {
        return $this;
    }

    /**
     * @return array<string, array<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    /**
     * @return array<string>
     */
    public function getHeader(string $name): array
    {
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine(string $name): string
    {
        $header = $this->getHeader($name);
        return implode(', ', $header);
    }

    public function withHeader(string $name, $value): ResponseInterface
    {
        $new = clone $this;
        $new->headers[$name] = is_array($value) ? $value : [$value];
        return $new;
    }

    public function withAddedHeader(string $name, $value): ResponseInterface
    {
        $new = clone $this;
        if (!isset($new->headers[$name])) {
            $new->headers[$name] = [];
        }
        $values = is_array($value) ? $value : [$value];
        $new->headers[$name] = array_merge($new->headers[$name], $values);
        return $new;
    }

    public function withoutHeader(string $name): ResponseInterface
    {
        $new = clone $this;
        unset($new->headers[$name]);
        return $new;
    }

    public function getBody(): StreamInterface
    {
        return new class($this->body ?? '') implements StreamInterface {
            private string $content;

            public function __construct(string $content)
            {
                $this->content = $content;
            }

            public function __toString(): string
            {
                return $this->content;
            }

            public function close(): void {}
            public function detach() { return null; }
            public function getSize(): ?int { return strlen($this->content); }
            public function tell(): int { return 0; }
            public function eof(): bool { return true; }
            public function isSeekable(): bool { return false; }
            public function seek(int $offset, int $whence = SEEK_SET): void {}
            public function rewind(): void {}
            public function isWritable(): bool { return false; }
            public function write(string $string): int { return 0; }
            public function isReadable(): bool { return true; }
            public function read(int $length): string { return substr($this->content, 0, $length); }
            public function getContents(): string { return $this->content; }
            public function getMetadata(?string $key = null) { return null; }
        };
    }

    public function withBody(StreamInterface $body): ResponseInterface
    {
        $new = clone $this;
        $new->body = (string) $body;
        return $new;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        $new = clone $this;
        $new->statusCode = $code;
        return $new;
    }

    public function getReasonPhrase(): string
    {
        return match ($this->statusCode) {
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Unprocessable Entity',
            500 => 'Internal Server Error',
            default => '',
        };
    }
}

