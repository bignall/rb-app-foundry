<?php

declare(strict_types=1);

namespace RBCS\AppFoundry\Tests\Unit\Connection;

use Brain\Monkey\Functions;
use RBCS\AppFoundry\Connection\AuthType;
use RBCS\AppFoundry\Connection\ConnectionAbstract;
use RBCS\AppFoundry\Connection\ConnectionResponse;
use RBCS\AppFoundry\Tests\WPTestCase;

// ── Testable subclass ──────────────────────────────────────────────────────────
// Overrides protected/private methods to avoid real HTTP calls and DB access.

class TestableConnection extends ConnectionAbstract
{
    /** Credentials that getStoredCredentials() returns. */
    private array $fakeCredentials = [];

    /** When non-null, getStoredCredentials() returns this instead. */
    private ?array $storedOverride = null;

    /** Track what storeCredentials() was last called with. */
    public ?array $lastStored = null;

    public function getId(): string
    {
        return 'testable';
    }

    public function getName(): string
    {
        return 'Testable Connection';
    }

    public function getAuthType(): AuthType
    {
        return AuthType::APIKey;
    }

    public function getAuthFields(): array
    {
        return [];
    }

    public function authenticate(array $credentials): bool
    {
        return true;
    }

    public function refreshToken(): bool
    {
        return false;
    }

    public function request(string $method, string $endpoint, array $data = []): ConnectionResponse
    {
        return ConnectionResponse::success([]);
    }

    // -- Override protected methods for tests ----------------------------------

    protected function storeCredentials(array $credentials): void
    {
        $this->lastStored = $credentials;
        // Call parent so encrypt/decrypt round-trip is tested, but skip update_option.
    }

    protected function getStoredCredentials(): array
    {
        return $this->storedOverride ?? $this->fakeCredentials;
    }

    // -- Helpers to set up test state -----------------------------------------

    public function setFakeCredentials(array $credentials): void
    {
        $this->fakeCredentials = $credentials;
    }

    // -- Expose protected methods as public for testing ------------------------

    public function publicEncryptSensitiveFields(array $credentials): array
    {
        return $this->encryptSensitiveFields($credentials);
    }

    public function publicDecryptSensitiveFields(array $credentials): array
    {
        return $this->decryptSensitiveFields($credentials);
    }

    public function publicIsTokenExpired(array $credentials): bool
    {
        return $this->isTokenExpired($credentials);
    }
}

// ── Tests ─────────────────────────────────────────────────────────────────────

class ConnectionAbstractTest extends WPTestCase
{
    private TestableConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = new TestableConnection();
    }

    // ── encryptSensitiveFields / decryptSensitiveFields ───────────────────────

    public function testEncryptDecryptRoundTripForAccessToken(): void
    {
        $original = ['access_token' => 'super-secret'];

        $encrypted = $this->connection->publicEncryptSensitiveFields($original);

        $this->assertNotSame('super-secret', $encrypted['access_token']);
        $this->assertTrue($encrypted['access_token_encrypted']);

        $decrypted = $this->connection->publicDecryptSensitiveFields($encrypted);

        $this->assertSame('super-secret', $decrypted['access_token']);
        $this->assertArrayNotHasKey('access_token_encrypted', $decrypted);
    }

    public function testEncryptDecryptRoundTripForAllSensitiveKeys(): void
    {
        $originals = [
            'access_token'  => 'tok_access',
            'refresh_token' => 'tok_refresh',
            'api_key'       => 'key_abc',
            'client_secret' => 'secret_xyz',
        ];

        $encrypted = $this->connection->publicEncryptSensitiveFields($originals);
        $decrypted = $this->connection->publicDecryptSensitiveFields($encrypted);

        foreach ($originals as $key => $value) {
            $this->assertSame($value, $decrypted[$key], "Round-trip failed for {$key}");
            $this->assertArrayNotHasKey($key . '_encrypted', $decrypted);
        }
    }

    public function testEncryptDoesNotTouchNonSensitiveFields(): void
    {
        $credentials = [
            'user_id'      => 42,
            'access_token' => 'secret',
        ];

        $encrypted = $this->connection->publicEncryptSensitiveFields($credentials);

        $this->assertSame(42, $encrypted['user_id']);
    }

    public function testDecryptDoesNotTouchUnflaggedFields(): void
    {
        // If the _encrypted flag is missing, the value should pass through unchanged.
        $credentials = ['access_token' => 'plain_text'];

        $result = $this->connection->publicDecryptSensitiveFields($credentials);

        $this->assertSame('plain_text', $result['access_token']);
    }

    // ── isTokenExpired ────────────────────────────────────────────────────────

    public function testIsTokenExpiredReturnsFalseWhenNoExpiresAt(): void
    {
        $this->assertFalse($this->connection->publicIsTokenExpired([]));
    }

    public function testIsTokenExpiredReturnsTrueWhenInPast(): void
    {
        // Expired 10 seconds ago.
        $credentials = ['expires_at' => time() - 10];

        $this->assertTrue($this->connection->publicIsTokenExpired($credentials));
    }

    public function testIsTokenExpiredReturnsTrueWithin5MinuteBuffer(): void
    {
        // Expires in 2 minutes — within the 5-minute buffer, so treated as expired.
        $credentials = ['expires_at' => time() + 120];

        $this->assertTrue($this->connection->publicIsTokenExpired($credentials));
    }

    public function testIsTokenExpiredReturnsFalseWhenMoreThan5MinutesRemaining(): void
    {
        // Expires in 1 hour — well outside the buffer.
        $credentials = ['expires_at' => time() + 3600];

        $this->assertFalse($this->connection->publicIsTokenExpired($credentials));
    }

    // ── isConnected ───────────────────────────────────────────────────────────

    public function testIsConnectedReturnsFalseWhenNoCredentials(): void
    {
        $this->connection->setFakeCredentials([]);

        $this->assertFalse($this->connection->isConnected());
    }

    public function testIsConnectedReturnsTrueWhenCredentialsAreValid(): void
    {
        // Token expires in 1 hour — not expired.
        $this->connection->setFakeCredentials(['api_key' => 'key', 'expires_at' => time() + 3600]);

        $this->assertTrue($this->connection->isConnected());
    }

    // ── isConnectedWith ───────────────────────────────────────────────────────

    public function testIsConnectedWithReturnsFalseForEmptyCredentials(): void
    {
        $this->assertFalse($this->connection->isConnectedWith([]));
    }

    public function testIsConnectedWithReturnsFalseForExpiredToken(): void
    {
        $credentials = ['access_token' => 'tok', 'expires_at' => time() - 60];

        $this->assertFalse($this->connection->isConnectedWith($credentials));
    }

    public function testIsConnectedWithReturnsTrueForValidCredentials(): void
    {
        $credentials = ['access_token' => 'tok', 'expires_at' => time() + 3600];

        $this->assertTrue($this->connection->isConnectedWith($credentials));
    }

    // ── disconnect ────────────────────────────────────────────────────────────

    public function testDisconnectDeletesOptionAndFiresAction(): void
    {
        Functions\expect('delete_option')
            ->once()
            ->with('appfoundry_connection_testable')
            ->andReturn(true);

        Functions\expect('do_action')
            ->once()
            ->with('appfoundry_connection_disconnected', 'testable');

        $this->connection->disconnect();

        // Brain\Monkey expect() verifications count as assertions but PHPUnit
        // doesn't track them natively. This explicit count silences the risky warning.
        $this->addToAssertionCount(2);
    }

    // ── getLastError ──────────────────────────────────────────────────────────

    public function testGetLastErrorReturnsNullByDefault(): void
    {
        $this->assertNull($this->connection->getLastError());
    }
}
