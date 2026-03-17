<?php

declare(strict_types=1);

namespace RBCS\AppFoundry\Tests\Unit\Admin;

use Brain\Monkey\Functions;
use RBCS\AppFoundry\Admin\SettingsManager;
use RBCS\AppFoundry\Tests\WPTestCase;

/**
 * Tests for SettingsManager.
 *
 * SettingsManager uses get_option / update_option and has a static cache.
 * Each test clears the cache first so tests are isolated.
 */
class SettingsManagerTest extends WPTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        SettingsManager::clearCache();
    }

    // ── get ───────────────────────────────────────────────────────────────────

    public function testGetReturnsDefaultWhenKeyMissing(): void
    {
        Functions\when('get_option')->justReturn([]);

        $result = SettingsManager::get('missing.key', 'default_val');

        $this->assertSame('default_val', $result);
    }

    public function testGetReturnsValueForSimpleKey(): void
    {
        Functions\when('get_option')->justReturn(['plugin_name' => 'My Plugin']);

        $this->assertSame('My Plugin', SettingsManager::get('plugin_name'));
    }

    public function testGetResolvesNestedDotNotation(): void
    {
        Functions\when('get_option')->justReturn([
            'general' => ['delete_data_on_uninstall' => true],
        ]);

        $this->assertTrue(SettingsManager::get('general.delete_data_on_uninstall'));
    }

    public function testGetReturnsDefaultWhenIntermediateKeyMissing(): void
    {
        Functions\when('get_option')->justReturn(['general' => 'not-an-array']);

        $result = SettingsManager::get('general.nested', 'fallback');

        $this->assertSame('fallback', $result);
    }

    // ── set ───────────────────────────────────────────────────────────────────

    public function testSetStoresValueAndUpdatesCache(): void
    {
        Functions\when('get_option')->justReturn([]);
        Functions\expect('update_option')
            ->once()
            ->andReturn(true);

        SettingsManager::set('api_key', 'abc123');

        $this->assertSame('abc123', SettingsManager::get('api_key'));
    }

    public function testSetCreatesIntermediateKeysForDotNotation(): void
    {
        Functions\when('get_option')->justReturn([]);
        Functions\when('update_option')->justReturn(true);

        SettingsManager::set('section.sub.value', 42);

        $this->assertSame(42, SettingsManager::get('section.sub.value'));
    }

    public function testSetUpdatesExistingValue(): void
    {
        Functions\when('get_option')->justReturn(['status' => 'draft']);
        Functions\when('update_option')->justReturn(true);

        SettingsManager::set('status', 'published');

        $this->assertSame('published', SettingsManager::get('status'));
    }

    // ── delete ────────────────────────────────────────────────────────────────

    public function testDeleteSetsKeyToNull(): void
    {
        Functions\when('get_option')->justReturn(['to_delete' => 'value']);
        Functions\when('update_option')->justReturn(true);

        SettingsManager::delete('to_delete');

        $this->assertNull(SettingsManager::get('to_delete'));
    }

    // ── getAll / clearCache ───────────────────────────────────────────────────

    public function testGetAllCachesAfterFirstRead(): void
    {
        // Brain\Monkey's expect()->once() will fail the test if get_option
        // is called more than once — proving the cache is used on the second call.
        Functions\expect('get_option')
            ->once()
            ->andReturn(['cached' => true]);

        SettingsManager::getAll();
        $result = SettingsManager::getAll(); // Must use cache.

        $this->assertSame(['cached' => true], $result);
    }

    public function testClearCacheForcesReread(): void
    {
        Functions\expect('get_option')
            ->twice()
            ->andReturn(['value' => 1]);

        SettingsManager::getAll();
        SettingsManager::clearCache();
        SettingsManager::getAll(); // Must re-read.

        $this->assertTrue(true); // Assertion is implicit in expect()->twice().
    }
}
