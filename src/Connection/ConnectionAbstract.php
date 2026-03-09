<?php

declare(strict_types=1);

namespace RBCS\PluginForge\Connection;

/**
 * Abstract base class for platform connections.
 *
 * Connections can operate in two modes:
 *
 * 1. Self-managed credentials: The connection stores its own credentials
 *    (useful for single-account platforms or AI APIs).
 *
 * 2. External credentials: Credentials are provided per-request, typically
 *    from a Social Account CPT. This supports multiple accounts per platform.
 *
 * @package RBCS\PluginForge\Connection
 */
abstract class ConnectionAbstract implements ConnectionInterface
{
    private const OPTION_PREFIX = 'pluginforge_connection_';

    /**
     * Cached self-managed credentials.
     *
     * @var array<string, mixed>|null
     */
    private ?array $cachedCredentials = null;

    /**
     * Last error message from authenticate(), for surfacing to the REST API.
     */
    protected ?string $lastError = null;

    /**
     * Get the last authentication error message, if any.
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected(): bool
    {
        $credentials = $this->getStoredCredentials();

        if (empty($credentials)) {
            return false;
        }

        if ($this->isTokenExpired($credentials)) {
            return $this->refreshToken();
        }

        return true;
    }

    /**
     * Check if a specific set of external credentials is valid/connected.
     *
     * Used when credentials are stored externally (e.g., in a Social Account CPT)
     * rather than in the connection's own option.
     *
     * @param array<string, mixed> $credentials Decrypted credentials.
     */
    public function isConnectedWith(array $credentials): bool
    {
        if (empty($credentials)) {
            return false;
        }

        if ($this->isTokenExpired($credentials)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        delete_option(self::OPTION_PREFIX . $this->getId());
        $this->cachedCredentials = null;

        do_action('pluginforge_connection_disconnected', $this->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsSchema(): array
    {
        return [];
    }

    /**
     * Make an API request using external credentials.
     *
     * This is the preferred method when working with multiple accounts.
     * Child classes should implement this to add the appropriate auth headers.
     *
     * @param string               $method      HTTP method.
     * @param string               $endpoint    API endpoint.
     * @param array<string, mixed> $data        Request data.
     * @param array<string, mixed> $credentials Decrypted credentials for this request.
     * @return ConnectionResponse
     */
    public function requestWith(
        string $method,
        string $endpoint,
        array $data = [],
        array $credentials = []
    ): ConnectionResponse {
        // Default implementation falls back to regular request.
        // Child classes should override this to use the provided credentials.
        return $this->request($method, $endpoint, $data);
    }

    /**
     * Refresh a token for externally-stored credentials.
     *
     * @param array<string, mixed> $credentials Current credentials with refresh_token.
     * @return array<string, mixed>|null Updated credentials, or null on failure.
     */
    public function refreshTokenWith(array $credentials): ?array
    {
        // Override in child classes that support external credential refresh.
        return null;
    }

    /**
     * Store credentials in the connection's own WP option.
     *
     * @param array<string, mixed> $credentials
     */
    protected function storeCredentials(array $credentials): void
    {
        $encrypted = $this->encryptSensitiveFields($credentials);
        update_option(self::OPTION_PREFIX . $this->getId(), $encrypted);
        $this->cachedCredentials = $credentials;
    }

    /**
     * Retrieve self-managed stored credentials.
     *
     * @return array<string, mixed>
     */
    protected function getStoredCredentials(): array
    {
        if ($this->cachedCredentials !== null) {
            return $this->cachedCredentials;
        }

        $stored = get_option(self::OPTION_PREFIX . $this->getId(), []);

        if (empty($stored)) {
            return [];
        }

        $this->cachedCredentials = $this->decryptSensitiveFields($stored);
        return $this->cachedCredentials;
    }

    /**
     * Check if a token is expired.
     *
     * @param array<string, mixed> $credentials
     */
    protected function isTokenExpired(array $credentials): bool
    {
        if (!isset($credentials['expires_at'])) {
            return false;
        }

        return time() >= ($credentials['expires_at'] - 300);
    }

    /**
     * Make an HTTP request using WordPress HTTP API.
     *
     * @param string               $method  HTTP method.
     * @param string               $url     Full URL.
     * @param array<string, mixed> $options Request options.
     */
    protected function httpRequest(
        string $method,
        string $url,
        array $options = []
    ): ConnectionResponse {
        $defaults = [
            'method'  => strtoupper($method),
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        ];

        $args = wp_parse_args($options, $defaults);

        if (isset($options['headers'])) {
            $args['headers'] = array_merge($defaults['headers'], $options['headers']);
        }

        if (isset($args['body']) && is_array($args['body'])) {
            $args['body'] = wp_json_encode($args['body']);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return ConnectionResponse::error(
                $response->get_error_message(),
                0
            );
        }

        $statusCode = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response)->getAll();

        $decoded = json_decode($body, true);
        $data = (json_last_error() === JSON_ERROR_NONE) ? $decoded : $body;

        $connectionResponse = new ConnectionResponse(
            (int) $statusCode,
            $data,
            $headers
        );

        if ($connectionResponse->isError()) {
            $this->logError($method, $url, $statusCode, $data);
        }

        return $connectionResponse;
    }

    /**
     * Encrypt sensitive credential fields.
     *
     * Made public so external credential managers (like SocialAccountCPT)
     * can encrypt credentials before storage.
     *
     * @param array<string, mixed> $credentials
     * @return array<string, mixed>
     */
    public function encryptSensitiveFields(array $credentials): array
    {
        $sensitiveKeys = ['access_token', 'refresh_token', 'api_key', 'client_secret'];
        $encryptionKey = $this->getEncryptionKey();

        foreach ($sensitiveKeys as $key) {
            if (isset($credentials[$key]) && is_string($credentials[$key])) {
                $credentials[$key] = $this->encrypt($credentials[$key], $encryptionKey);
                $credentials[$key . '_encrypted'] = true;
            }
        }

        return $credentials;
    }

    /**
     * Decrypt sensitive credential fields.
     *
     * Made public so external credential managers can decrypt credentials.
     *
     * @param array<string, mixed> $credentials
     * @return array<string, mixed>
     */
    public function decryptSensitiveFields(array $credentials): array
    {
        $sensitiveKeys = ['access_token', 'refresh_token', 'api_key', 'client_secret'];
        $encryptionKey = $this->getEncryptionKey();

        foreach ($sensitiveKeys as $key) {
            if (isset($credentials[$key . '_encrypted']) && $credentials[$key . '_encrypted'] === true) {
                $credentials[$key] = $this->decrypt($credentials[$key], $encryptionKey);
                unset($credentials[$key . '_encrypted']);
            }
        }

        return $credentials;
    }

    private function getEncryptionKey(): string
    {
        return hash('sha256', AUTH_KEY . SECURE_AUTH_KEY . 'pluginforge');
    }

    private function encrypt(string $data, string $key): string
    {
        if (!function_exists('openssl_encrypt')) {
            return base64_encode($data);
        }

        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . '::' . $encrypted);
    }

    private function decrypt(string $data, string $key): string
    {
        if (!function_exists('openssl_encrypt')) {
            return base64_decode($data);
        }

        $decoded = base64_decode($data);
        $parts = explode('::', $decoded, 2);

        if (count($parts) !== 2) {
            return $data;
        }

        [$iv, $encrypted] = $parts;
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv) ?: $data;
    }

    private function logError(string $method, string $url, int $statusCode, mixed $data): void
    {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        error_log(sprintf(
            '[PluginForge] Connection "%s" API error: %s %s returned %d - %s',
            $this->getId(),
            $method,
            $url,
            $statusCode,
            is_string($data) ? $data : wp_json_encode($data)
        ));
    }
}
