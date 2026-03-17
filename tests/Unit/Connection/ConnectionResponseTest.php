<?php

declare(strict_types=1);

namespace RBCS\AppFoundry\Tests\Unit\Connection;

use PHPUnit\Framework\TestCase;
use RBCS\AppFoundry\Connection\ConnectionResponse;

/**
 * Tests for ConnectionResponse.
 *
 * Pure PHP — no WordPress functions are called, so Brain\Monkey is not needed.
 */
class ConnectionResponseTest extends TestCase
{
    // ── Factories ─────────────────────────────────────────────────────────────

    public function testSuccessFactoryCreatesSuccessResponse(): void
    {
        $response = ConnectionResponse::success(['id' => 1]);

        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->isError());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['id' => 1], $response->getData());
        $this->assertNull($response->getError());
    }

    public function testSuccessFactoryAcceptsCustomStatusCode(): void
    {
        $response = ConnectionResponse::success('created', 201);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertTrue($response->isSuccess());
    }

    public function testSuccessFactoryStoresHeaders(): void
    {
        $headers  = ['X-Rate-Limit' => '100'];
        $response = ConnectionResponse::success([], 200, $headers);

        $this->assertSame($headers, $response->getHeaders());
    }

    public function testErrorFactoryCreatesErrorResponse(): void
    {
        $response = ConnectionResponse::error('Something went wrong');

        $this->assertFalse($response->isSuccess());
        $this->assertTrue($response->isError());
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Something went wrong', $response->getError());
    }

    public function testErrorFactoryAcceptsCustomStatusCode(): void
    {
        $response = ConnectionResponse::error('Not found', 404);

        $this->assertSame(404, $response->getStatusCode());
    }

    // ── isSuccess / isError boundaries ────────────────────────────────────────

    /**
     * @dataProvider successStatusCodes
     */
    public function testIsSuccessForTwoXxCodes(int $code): void
    {
        $response = new ConnectionResponse($code, null);

        $this->assertTrue($response->isSuccess());
        $this->assertFalse($response->isError());
    }

    /**
     * @return array<array{int}>
     */
    public static function successStatusCodes(): array
    {
        return [[200], [201], [204], [299]];
    }

    /**
     * @dataProvider nonSuccessStatusCodes
     */
    public function testIsErrorForNonTwoXxCodes(int $code): void
    {
        $response = new ConnectionResponse($code, null);

        $this->assertFalse($response->isSuccess());
        $this->assertTrue($response->isError());
    }

    /**
     * @return array<array{int}>
     */
    public static function nonSuccessStatusCodes(): array
    {
        return [[199], [300], [400], [401], [403], [404], [500]];
    }

    public function testIsErrorWhenSuccessStatusButErrorMessageSet(): void
    {
        // A 200 response can still be an error if the error field is set.
        $response = new ConnectionResponse(200, null, [], 'Unexpected error');

        $this->assertTrue($response->isSuccess());  // Status is 200.
        $this->assertTrue($response->isError());    // But error is set.
    }

    // ── toArray ───────────────────────────────────────────────────────────────

    public function testToArrayReturnsArrayDataAsIs(): void
    {
        $data     = ['foo' => 'bar', 'count' => 3];
        $response = new ConnectionResponse(200, $data);

        $this->assertSame($data, $response->toArray());
    }

    public function testToArrayDecodesJsonString(): void
    {
        $response = new ConnectionResponse(200, '{"key":"value"}');

        $this->assertSame(['key' => 'value'], $response->toArray());
    }

    public function testToArrayReturnsEmptyArrayForInvalidJson(): void
    {
        $response = new ConnectionResponse(200, 'not-json');

        $this->assertSame([], $response->toArray());
    }

    public function testToArrayReturnsEmptyArrayForNull(): void
    {
        $response = new ConnectionResponse(200, null);

        $this->assertSame([], $response->toArray());
    }

    // ── getters ───────────────────────────────────────────────────────────────

    public function testGetDataReturnsRawData(): void
    {
        $response = new ConnectionResponse(200, 'raw string');

        $this->assertSame('raw string', $response->getData());
    }

    public function testGetHeadersReturnsEmptyArrayByDefault(): void
    {
        $response = new ConnectionResponse(200, null);

        $this->assertSame([], $response->getHeaders());
    }
}
