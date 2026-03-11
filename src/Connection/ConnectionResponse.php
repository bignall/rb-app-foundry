<?php

declare(strict_types=1);

namespace RBCS\AppForge\Connection;

/**
 * Standardized response from platform API calls.
 *
 * Wraps the raw API response into a consistent format that
 * the rest of the framework can work with regardless of platform.
 *
 * @package RBCS\AppForge\Connection
 */
class ConnectionResponse
{
    public function __construct(
        private readonly int $statusCode,
        private readonly mixed $data,
        private readonly array $headers = [],
        private readonly ?string $error = null
    ) {}

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the response data.
     */
    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * Get response headers.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get the error message, if any.
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Check if the request was successful (2xx status).
     */
    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if there was an error.
     */
    public function isError(): bool
    {
        return !$this->isSuccess() || $this->error !== null;
    }

    /**
     * Get data as an array (if JSON response).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if (is_array($this->data)) {
            return $this->data;
        }

        if (is_string($this->data)) {
            $decoded = json_decode($this->data, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Create a success response.
     */
    public static function success(mixed $data, int $statusCode = 200, array $headers = []): self
    {
        return new self($statusCode, $data, $headers);
    }

    /**
     * Create an error response.
     */
    public static function error(string $message, int $statusCode = 500, mixed $data = null): self
    {
        return new self($statusCode, $data, [], $message);
    }
}
