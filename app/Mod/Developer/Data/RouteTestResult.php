<?php

declare(strict_types=1);

namespace Mod\Developer\Data;

use Illuminate\Http\Response;
use Throwable;

/**
 * Result of testing a route.
 *
 * Contains the HTTP response details, performance metrics, and any exception
 * that occurred during the request.
 */
readonly class RouteTestResult
{
    /**
     * @param  int  $statusCode  HTTP status code
     * @param  array<string, string>  $headers  Response headers
     * @param  string  $body  Response body
     * @param  float  $responseTime  Time taken in milliseconds
     * @param  int  $memoryUsage  Memory used in bytes
     * @param  Throwable|null  $exception  Exception if request failed
     * @param  string  $method  HTTP method used
     * @param  string  $uri  URI tested
     */
    public function __construct(
        public int $statusCode,
        public array $headers,
        public string $body,
        public float $responseTime,
        public int $memoryUsage,
        public ?Throwable $exception = null,
        public string $method = 'GET',
        public string $uri = '',
    ) {}

    /**
     * Check if the request was successful (2xx status code).
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if the request resulted in a redirect (3xx status code).
     */
    public function isRedirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Check if the request resulted in a client error (4xx status code).
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if the request resulted in a server error (5xx status code).
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }

    /**
     * Check if an exception occurred during the request.
     */
    public function hasException(): bool
    {
        return $this->exception !== null;
    }

    /**
     * Get the status text for the status code.
     */
    public function getStatusText(): string
    {
        return Response::$statusTexts[$this->statusCode] ?? 'Unknown';
    }

    /**
     * Get formatted response time.
     */
    public function getFormattedResponseTime(): string
    {
        if ($this->responseTime < 1) {
            return round($this->responseTime * 1000, 2).'μs';
        }

        if ($this->responseTime < 1000) {
            return round($this->responseTime, 2).'ms';
        }

        return round($this->responseTime / 1000, 2).'s';
    }

    /**
     * Get formatted memory usage.
     */
    public function getFormattedMemoryUsage(): string
    {
        $bytes = $this->memoryUsage;

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 2).' KB';
        }

        return round($bytes / 1048576, 2).' MB';
    }

    /**
     * Get the content type from headers.
     */
    public function getContentType(): string
    {
        $contentType = $this->headers['Content-Type']
            ?? $this->headers['content-type']
            ?? 'text/plain';

        // Extract just the mime type (without charset, etc.)
        return explode(';', $contentType)[0];
    }

    /**
     * Check if response is JSON.
     */
    public function isJson(): bool
    {
        return str_contains($this->getContentType(), 'json');
    }

    /**
     * Check if response is HTML.
     */
    public function isHtml(): bool
    {
        return str_contains($this->getContentType(), 'html');
    }

    /**
     * Get formatted body for display.
     *
     * Attempts to pretty-print JSON responses.
     */
    public function getFormattedBody(): string
    {
        if ($this->isJson()) {
            $decoded = json_decode($this->body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
        }

        return $this->body;
    }

    /**
     * Get body truncated to a maximum length.
     */
    public function getTruncatedBody(int $maxLength = 10000): string
    {
        $body = $this->getFormattedBody();

        if (strlen($body) <= $maxLength) {
            return $body;
        }

        return substr($body, 0, $maxLength)."\n\n... (truncated, total: ".strlen($this->body).' bytes)';
    }

    /**
     * Convert to array for serialisation.
     */
    public function toArray(): array
    {
        return [
            'status_code' => $this->statusCode,
            'status_text' => $this->getStatusText(),
            'method' => $this->method,
            'uri' => $this->uri,
            'headers' => $this->headers,
            'body' => $this->body,
            'body_length' => strlen($this->body),
            'content_type' => $this->getContentType(),
            'response_time' => $this->responseTime,
            'response_time_formatted' => $this->getFormattedResponseTime(),
            'memory_usage' => $this->memoryUsage,
            'memory_usage_formatted' => $this->getFormattedMemoryUsage(),
            'is_successful' => $this->isSuccessful(),
            'is_json' => $this->isJson(),
            'exception' => $this->exception ? [
                'class' => get_class($this->exception),
                'message' => $this->exception->getMessage(),
                'file' => $this->exception->getFile(),
                'line' => $this->exception->getLine(),
            ] : null,
        ];
    }
}
