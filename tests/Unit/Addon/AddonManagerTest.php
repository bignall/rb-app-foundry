<?php

declare(strict_types=1);

namespace RBCS\AppFoundry\Tests\Unit\Addon;

use Brain\Monkey\Functions;
use PHPUnit\Framework\MockObject\MockObject;
use RBCS\AppFoundry\Addon\AddonInterface;
use RBCS\AppFoundry\Addon\AddonManager;
use RBCS\AppFoundry\Core\Plugin;
use RBCS\AppFoundry\Tests\WPTestCase;

/**
 * Tests for AddonManager.
 *
 * The constructor calls get_option(), so all tests extend WPTestCase.
 * Because Plugin is a final singleton with an empty constructor, we use
 * Plugin::getInstance() directly rather than a mock.
 *
 * The 'addons' and 'activeIds' properties are private, so tests that need
 * to pre-populate state use reflection helpers.
 */
class AddonManagerTest extends WPTestCase
{
    private Plugin $plugin;
    private AddonManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        Functions\when('get_option')->justReturn([]);
        Functions\when('update_option')->justReturn(true);

        $this->plugin  = Plugin::getInstance();
        $this->manager = new AddonManager($this->plugin);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeAddon(
        string $id,
        bool $activeByDefault = false,
        array $dependencies = []
    ): AddonInterface&MockObject {
        $addon = $this->createMock(AddonInterface::class);
        $addon->method('getId')->willReturn($id);
        $addon->method('getName')->willReturn(ucfirst($id));
        $addon->method('isActiveByDefault')->willReturn($activeByDefault);
        $addon->method('getDependencies')->willReturn($dependencies);
        return $addon;
    }

    /**
     * Inject an addon into the private $addons array via reflection.
     */
    private function injectAddon(AddonInterface $addon): void
    {
        $ref  = new \ReflectionClass($this->manager);
        $prop = $ref->getProperty('addons');
        // setAccessible() is a no-op since PHP 8.1.
        $current         = $prop->getValue($this->manager);
        $current[$addon->getId()] = $addon;
        $prop->setValue($this->manager, $current);
    }

    /**
     * Set the private $activeIds array via reflection.
     *
     * @param string[] $ids
     */
    private function setActiveIds(array $ids): void
    {
        $ref  = new \ReflectionClass($this->manager);
        $prop = $ref->getProperty('activeIds');
        // setAccessible() is a no-op since PHP 8.1.
        $prop->setValue($this->manager, $ids);
    }

    // ── isActive ──────────────────────────────────────────────────────────────

    public function testIsActiveReturnsFalseForUnknownId(): void
    {
        $this->assertFalse($this->manager->isActive('unknown-addon'));
    }

    public function testIsActiveReturnsTrueAfterActivation(): void
    {
        $addon = $this->makeAddon('my-addon');
        $this->injectAddon($addon);

        $addon->expects($this->once())->method('activate');
        Functions\expect('do_action')->once()->with('appfoundry_addon_activated', 'my-addon', $addon);

        $this->manager->activate('my-addon');

        $this->assertTrue($this->manager->isActive('my-addon'));
    }

    // ── activate ──────────────────────────────────────────────────────────────

    public function testActivateReturnsFalseForUnknownAddon(): void
    {
        $this->assertFalse($this->manager->activate('does-not-exist'));
    }

    public function testActivateReturnsTrueWhenAlreadyActive(): void
    {
        $addon = $this->makeAddon('my-addon');
        $this->injectAddon($addon);
        $this->setActiveIds(['my-addon']);

        // activate() should short-circuit without calling activate() on the addon.
        $addon->expects($this->never())->method('activate');

        $this->assertTrue($this->manager->activate('my-addon'));
    }

    public function testActivateCallsAddonActivateAndFiresAction(): void
    {
        $addon = $this->makeAddon('new-addon');
        $this->injectAddon($addon);

        $addon->expects($this->once())->method('activate');
        Functions\expect('do_action')
            ->once()
            ->with('appfoundry_addon_activated', 'new-addon', $addon);

        $result = $this->manager->activate('new-addon');

        $this->assertTrue($result);
    }

    // ── deactivate ────────────────────────────────────────────────────────────

    public function testDeactivateReturnsFalseWhenNotActive(): void
    {
        $addon = $this->makeAddon('inactive-addon');
        $this->injectAddon($addon);

        $this->assertFalse($this->manager->deactivate('inactive-addon'));
    }

    public function testDeactivatePreventedWhenAnotherAddonDependsOnIt(): void
    {
        $base      = $this->makeAddon('base-addon');
        $dependent = $this->makeAddon('dependent-addon', false, ['base-addon']);

        $this->injectAddon($base);
        $this->injectAddon($dependent);
        $this->setActiveIds(['base-addon', 'dependent-addon']);

        // base-addon cannot be deactivated while dependent-addon is active.
        $result = $this->manager->deactivate('base-addon');

        $this->assertFalse($result);
        $this->assertTrue($this->manager->isActive('base-addon'));
    }

    public function testDeactivateCallsDeactivateAndFiresAction(): void
    {
        $addon = $this->makeAddon('removable-addon');
        $this->injectAddon($addon);
        $this->setActiveIds(['removable-addon']);

        $addon->expects($this->once())->method('deactivate');
        Functions\expect('do_action')
            ->once()
            ->with('appfoundry_addon_deactivated', 'removable-addon');

        $result = $this->manager->deactivate('removable-addon');

        $this->assertTrue($result);
        $this->assertFalse($this->manager->isActive('removable-addon'));
    }

    // ── bootActive ────────────────────────────────────────────────────────────

    public function testBootActiveIsIdempotent(): void
    {
        $addon = $this->makeAddon('boot-addon');
        $this->injectAddon($addon);
        $this->setActiveIds(['boot-addon']);

        // boot() must be called exactly once even if bootActive() is called twice.
        $addon->expects($this->once())->method('boot');

        $this->manager->bootActive();
        $this->manager->bootActive();
    }

    public function testBootActiveSkipsAddonWithUnmetDependency(): void
    {
        $addon = $this->makeAddon('depends-addon', false, ['missing-dep']);
        $this->injectAddon($addon);
        $this->setActiveIds(['depends-addon']);

        // boot() must NOT be called because 'missing-dep' is not active.
        $addon->expects($this->never())->method('boot');

        $this->manager->bootActive();
    }

    // ── getActive ─────────────────────────────────────────────────────────────

    public function testGetActiveReturnsOnlyActiveAddons(): void
    {
        $active   = $this->makeAddon('active-addon');
        $inactive = $this->makeAddon('inactive-addon');

        $this->injectAddon($active);
        $this->injectAddon($inactive);
        $this->setActiveIds(['active-addon']);

        $result = $this->manager->getActive();

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('active-addon', $result);
        $this->assertArrayNotHasKey('inactive-addon', $result);
    }

    // ── get ───────────────────────────────────────────────────────────────────

    public function testGetReturnsAddonByIdOrNull(): void
    {
        $addon = $this->makeAddon('known-addon');
        $this->injectAddon($addon);

        $this->assertSame($addon, $this->manager->get('known-addon'));
        $this->assertNull($this->manager->get('unknown-addon'));
    }
}
