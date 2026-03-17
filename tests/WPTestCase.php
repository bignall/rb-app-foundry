<?php

declare(strict_types=1);

namespace RBCS\AppFoundry\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;

/**
 * Base test case for classes that interact with WordPress functions.
 *
 * Sets up Brain\Monkey before each test and tears it down after,
 * ensuring WP function stubs are isolated between tests.
 */
class WPTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Stub functions called unconditionally by framework code.
        Functions\when('wp_json_encode')->alias(fn(mixed $v) => json_encode($v));
        Functions\when('trailingslashit')->alias(fn(string $s) => rtrim($s, '/') . '/');
        Functions\when('wp_parse_args')->alias(function (array $args, array $defaults): array {
            return array_merge($defaults, $args);
        });
        // Note: do_action is NOT stubbed here because individual tests need to
        // use either when() or expect() depending on whether they verify the call.
        // Tests that don't care about do_action should add their own when() stub.
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}
