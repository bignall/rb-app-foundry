<?php

declare(strict_types=1);

namespace RBCS\AppFoundry\Tests\Unit\Connection;

use PHPUnit\Framework\TestCase;
use RBCS\AppFoundry\Connection\AuthType;

/**
 * Tests for the AuthType backed enum.
 *
 * Pure PHP — no WordPress functions needed.
 */
class AuthTypeTest extends TestCase
{
    /**
     * @dataProvider labelProvider
     */
    public function testLabelReturnsHumanReadableString(AuthType $type, string $expectedLabel): void
    {
        $this->assertSame($expectedLabel, $type->label());
    }

    /**
     * @return array<string, array{AuthType, string}>
     */
    public static function labelProvider(): array
    {
        return [
            'OAuth2'   => [AuthType::OAuth2,  'OAuth 2.0'],
            'APIKey'   => [AuthType::APIKey,  'API Key'],
            'Bearer'   => [AuthType::Bearer,  'Bearer Token'],
            'Basic'    => [AuthType::Basic,   'Basic Auth'],
            'Webhook'  => [AuthType::Webhook, 'Webhook'],
            'Custom'   => [AuthType::Custom,  'Custom'],
        ];
    }

    public function testBackingValuesMatchExpectedStrings(): void
    {
        $this->assertSame('oauth2',    AuthType::OAuth2->value);
        $this->assertSame('api_key',   AuthType::APIKey->value);
        $this->assertSame('bearer',    AuthType::Bearer->value);
        $this->assertSame('basic',     AuthType::Basic->value);
        $this->assertSame('webhook',   AuthType::Webhook->value);
        $this->assertSame('custom',    AuthType::Custom->value);
    }

    public function testFromStringReturnsCorrectCase(): void
    {
        $this->assertSame(AuthType::OAuth2, AuthType::from('oauth2'));
        $this->assertSame(AuthType::APIKey, AuthType::from('api_key'));
    }

    public function testTryFromReturnsNullForUnknownValue(): void
    {
        $this->assertNull(AuthType::tryFrom('unknown_value'));
    }
}
