<?php

declare(strict_types=1);

namespace RBCS\AppFoundry\Tests\Unit\Connection;

use Brain\Monkey\Functions;
use PHPUnit\Framework\MockObject\MockObject;
use RBCS\AppFoundry\Connection\AuthType;
use RBCS\AppFoundry\Connection\ConnectionInterface;
use RBCS\AppFoundry\Connection\ConnectionManager;
use RBCS\AppFoundry\Tests\WPTestCase;

class ConnectionManagerTest extends WPTestCase
{
    private ConnectionManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ConnectionManager();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeConnection(
        string $id,
        string $name = 'Test',
        bool $connected = false,
        AuthType $authType = AuthType::APIKey
    ): ConnectionInterface&MockObject {
        $conn = $this->createMock(ConnectionInterface::class);
        $conn->method('getId')->willReturn($id);
        $conn->method('getName')->willReturn($name);
        $conn->method('isConnected')->willReturn($connected);
        $conn->method('getAuthType')->willReturn($authType);
        $conn->method('getAuthFields')->willReturn([]);
        return $conn;
    }

    // ── register / get ────────────────────────────────────────────────────────

    public function testRegisterAddsConnectionAndFiresAction(): void
    {
        $conn = $this->makeConnection('facebook');

        Functions\expect('do_action')
            ->once()
            ->with('appfoundry_connection_registered', 'facebook', $conn);

        $this->manager->register($conn);

        $this->assertSame($conn, $this->manager->get('facebook'));
    }

    public function testGetReturnsNullForUnknownId(): void
    {
        $this->assertNull($this->manager->get('unknown'));
    }

    public function testHasReturnsTrueForRegisteredConnection(): void
    {
        $conn = $this->makeConnection('claude');
        Functions\when('do_action')->justReturn(null);
        $this->manager->register($conn);

        $this->assertTrue($this->manager->has('claude'));
        $this->assertFalse($this->manager->has('unknown'));
    }

    // ── getAll ────────────────────────────────────────────────────────────────

    public function testGetAllReturnsAllRegisteredConnections(): void
    {
        Functions\when('do_action')->justReturn(null);

        $fb  = $this->makeConnection('facebook');
        $gpt = $this->makeConnection('openai');

        $this->manager->register($fb);
        $this->manager->register($gpt);

        $all = $this->manager->getAll();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey('facebook', $all);
        $this->assertArrayHasKey('openai', $all);
    }

    // ── getConnected ──────────────────────────────────────────────────────────

    public function testGetConnectedFiltersToConnectedOnly(): void
    {
        Functions\when('do_action')->justReturn(null);

        $connected    = $this->makeConnection('facebook', 'Facebook', true);
        $disconnected = $this->makeConnection('instagram', 'Instagram', false);

        $this->manager->register($connected);
        $this->manager->register($disconnected);

        $result = $this->manager->getConnected();

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('facebook', $result);
    }

    // ── isConnected ───────────────────────────────────────────────────────────

    public function testIsConnectedDelegatesToConnection(): void
    {
        Functions\when('do_action')->justReturn(null);

        $conn = $this->makeConnection('claude', 'Claude', true);
        $this->manager->register($conn);

        $this->assertTrue($this->manager->isConnected('claude'));
        $this->assertFalse($this->manager->isConnected('unknown'));
    }

    // ── getByAuthType ─────────────────────────────────────────────────────────

    public function testGetByAuthTypeGroupsConnections(): void
    {
        Functions\when('do_action')->justReturn(null);

        $oauth  = $this->makeConnection('facebook', 'Facebook', false, AuthType::OAuth2);
        $apikey = $this->makeConnection('claude', 'Claude', false, AuthType::APIKey);

        $this->manager->register($oauth);
        $this->manager->register($apikey);

        $grouped = $this->manager->getByAuthType();

        $this->assertArrayHasKey('oauth2', $grouped);
        $this->assertArrayHasKey('api_key', $grouped);
        $this->assertCount(1, $grouped['oauth2']);
        $this->assertCount(1, $grouped['api_key']);
    }

    // ── getSummary ────────────────────────────────────────────────────────────

    public function testGetSummaryIncludesExpectedFields(): void
    {
        Functions\when('do_action')->justReturn(null);

        $conn = $this->makeConnection('twitter', 'Twitter', false, AuthType::OAuth2);
        $this->manager->register($conn);

        $summary = $this->manager->getSummary();

        $this->assertArrayHasKey('twitter', $summary);
        $this->assertSame('twitter', $summary['twitter']['id']);
        $this->assertSame('Twitter', $summary['twitter']['name']);
        $this->assertFalse($summary['twitter']['connected']);
        $this->assertSame('oauth2', $summary['twitter']['auth_type']);
    }
}
