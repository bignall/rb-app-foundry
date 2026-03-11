<?php

declare(strict_types=1);

namespace RBCS\AppForge\Connection;

/**
 * Contract for all platform connections.
 *
 * Every connection (Facebook, Claude, Instagram, etc.) implements
 * this interface to provide a consistent API for authentication,
 * token management, and making requests.
 *
 * @package RBCS\AppForge\Connection
 */
interface ConnectionInterface
{
    /**
     * Get the unique connection identifier.
     *
     * Must be a lowercase slug (e.g., 'facebook', 'claude', 'instagram').
     */
    public function getId(): string;

    /**
     * Get the human-readable connection name.
     */
    public function getName(): string;

    /**
     * Get the authentication type for this connection.
     */
    public function getAuthType(): AuthType;

    /**
     * Get the settings/fields schema required for authentication.
     *
     * Returns an array of field definitions for the admin UI.
     *
     * @return array<string, mixed>
     */
    public function getAuthFields(): array;

    /**
     * Authenticate with the platform.
     *
     * @param array<string, mixed> $credentials The credentials/tokens to use.
     * @return bool True if authentication was successful.
     */
    public function authenticate(array $credentials): bool;

    /**
     * Refresh the authentication token.
     *
     * @return bool True if token refresh was successful.
     */
    public function refreshToken(): bool;

    /**
     * Check if the connection is currently authenticated and valid.
     */
    public function isConnected(): bool;

    /**
     * Disconnect/revoke the connection.
     */
    public function disconnect(): void;

    /**
     * Make an API request to the platform.
     *
     * @param string               $method   HTTP method (GET, POST, PUT, DELETE).
     * @param string               $endpoint API endpoint path.
     * @param array<string, mixed> $data     Request data/parameters.
     * @return ConnectionResponse The response from the platform.
     */
    public function request(
        string $method,
        string $endpoint,
        array $data = []
    ): ConnectionResponse;

    /**
     * Get the connection's settings schema for the admin UI.
     *
     * @return array<string, mixed>
     */
    public function getSettingsSchema(): array;
}
