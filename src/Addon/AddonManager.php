<?php

declare(strict_types=1);

namespace RBCS\PluginForge\Addon;

use RBCS\PluginForge\Core\Plugin;

/**
 * Manages add-on discovery, activation, and lifecycle.
 *
 * Scans the addons/ directory for available add-ons, tracks which
 * are active, and only boots/loads active ones to keep the footprint minimal.
 *
 * @package RBCS\PluginForge\Addon
 */
class AddonManager
{
    private Plugin $plugin;

    /**
     * All discovered add-ons (both active and inactive).
     *
     * @var array<string, AddonInterface>
     */
    private array $addons = [];

    /**
     * Additional add-on directories registered by dependent plugins.
     *
     * @var string[]
     */
    private array $paths = [];

    /**
     * IDs of currently active add-ons.
     *
     * @var string[]
     */
    private array $activeIds = [];

    /**
     * Whether add-ons have been booted.
     */
    private bool $booted = false;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
        $this->activeIds = get_option('pluginforge_active_addons', []);
    }

    /**
     * Register an additional directory to scan for add-ons.
     *
     * Call this before discover() runs (i.e., on or before plugins_loaded)
     * to include add-ons from dependent plugins such as SocialPillar.
     *
     * @param string $path Absolute path to an addons/ directory.
     */
    public function addPath(string $path): void
    {
        $this->paths[] = trailingslashit($path);
    }

    /**
     * Discover all available add-ons across all registered directories.
     *
     * Scans for directories containing an addon.json and a PHP class
     * that implements AddonInterface.
     */
    public function discover(): void
    {
        // Always include PluginForge's own addons/ directory first.
        $allPaths = array_merge([$this->plugin->getAddonsPath()], $this->paths);

        foreach ($allPaths as $addonsPath) {
            if (!is_dir($addonsPath)) {
                continue;
            }

            $directories = glob($addonsPath . '*', GLOB_ONLYDIR);

            if ($directories === false) {
                continue;
            }

            foreach ($directories as $dir) {
                $this->loadAddon($dir);
            }
        }

        // Handle first-time installation: activate default add-ons.
        if (empty($this->activeIds) && !get_option('pluginforge_addons_initialized')) {
            $this->activateDefaults();
            update_option('pluginforge_addons_initialized', true);
        }
    }

    /**
     * Load a single add-on from its directory.
     */
    private function loadAddon(string $dir): void
    {
        $metaFile = $dir . '/addon.json';

        if (!file_exists($metaFile)) {
            return;
        }

        $metadata = json_decode(file_get_contents($metaFile), true);

        if (!$metadata || empty($metadata['entry_class'])) {
            return;
        }

        $entryClass = $metadata['entry_class'];

        // Only autoload the class file if the add-on is active.
        // This is the key optimization: inactive add-ons don't load PHP.
        $isActive = in_array($metadata['id'] ?? '', $this->activeIds, true);

        if (!$isActive) {
            // Store minimal metadata for inactive add-ons (for the admin UI).
            $this->addons[$metadata['id']] = $this->createInactiveProxy($metadata, $dir);
            return;
        }

        // Register the add-on's autoloader for its src/ directory.
        $this->registerAddonAutoloader($metadata, $dir);

        if (!class_exists($entryClass)) {
            return;
        }

        $addon = new $entryClass($this->plugin, $dir);

        if (!$addon instanceof AddonInterface) {
            return;
        }

        $this->addons[$addon->getId()] = $addon;
    }

    /**
     * Register PSR-4 autoloading for an add-on's classes.
     *
     * @param array<string, mixed> $metadata
     */
    private function registerAddonAutoloader(array $metadata, string $dir): void
    {
        if (empty($metadata['namespace'])) {
            return;
        }

        $namespace = rtrim($metadata['namespace'], '\\') . '\\';
        $srcDir = $dir . '/src/';

        spl_autoload_register(function (string $class) use ($namespace, $srcDir): void {
            if (!str_starts_with($class, $namespace)) {
                return;
            }

            $relativeClass = substr($class, strlen($namespace));
            $file = $srcDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        });
    }

    /**
     * Create a lightweight proxy for inactive add-ons.
     *
     * This allows the admin UI to show all available add-ons
     * without loading their full PHP code.
     *
     * @param array<string, mixed> $metadata
     */
    private function createInactiveProxy(array $metadata, string $dir): AddonInterface
    {
        return new InactiveAddonProxy($metadata, $dir);
    }

    /**
     * Activate add-ons that are marked as default_active.
     */
    private function activateDefaults(): void
    {
        foreach ($this->addons as $addon) {
            if ($addon->isActiveByDefault()) {
                $this->activate($addon->getId());
            }
        }
    }

    /**
     * Boot all active add-ons.
     */
    public function bootActive(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        foreach ($this->activeIds as $id) {
            if (isset($this->addons[$id]) && !($this->addons[$id] instanceof InactiveAddonProxy)) {
                // Check dependencies are met.
                if ($this->dependenciesMet($this->addons[$id])) {
                    $this->addons[$id]->boot();
                }
            }
        }
    }

    /**
     * Check if all dependencies for an add-on are active.
     */
    private function dependenciesMet(AddonInterface $addon): bool
    {
        foreach ($addon->getDependencies() as $depId) {
            if (!in_array($depId, $this->activeIds, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Activate an add-on by ID.
     */
    public function activate(string $id): bool
    {
        if (!isset($this->addons[$id])) {
            return false;
        }

        if (in_array($id, $this->activeIds, true)) {
            return true; // Already active.
        }

        $this->activeIds[] = $id;
        update_option('pluginforge_active_addons', $this->activeIds);

        // Run the add-on's activation routine.
        $addon = $this->addons[$id];
        if (!($addon instanceof InactiveAddonProxy)) {
            $addon->activate();
        }

        /**
         * Fires after an add-on is activated.
         *
         * @param string         $id    The add-on ID.
         * @param AddonInterface $addon The add-on instance.
         */
        do_action('pluginforge_addon_activated', $id, $addon);

        return true;
    }

    /**
     * Deactivate an add-on by ID.
     */
    public function deactivate(string $id): bool
    {
        $key = array_search($id, $this->activeIds, true);

        if ($key === false) {
            return false; // Not active.
        }

        // Check if other active add-ons depend on this one.
        foreach ($this->activeIds as $activeId) {
            if (isset($this->addons[$activeId]) && !($this->addons[$activeId] instanceof InactiveAddonProxy)) {
                if (in_array($id, $this->addons[$activeId]->getDependencies(), true)) {
                    return false; // Can't deactivate, dependency exists.
                }
            }
        }

        // Run the add-on's deactivation routine.
        if (isset($this->addons[$id]) && !($this->addons[$id] instanceof InactiveAddonProxy)) {
            $this->addons[$id]->deactivate();
        }

        unset($this->activeIds[$key]);
        $this->activeIds = array_values($this->activeIds); // Re-index.
        update_option('pluginforge_active_addons', $this->activeIds);

        /**
         * Fires after an add-on is deactivated.
         *
         * @param string $id The add-on ID.
         */
        do_action('pluginforge_addon_deactivated', $id);

        return true;
    }

    /**
     * Register REST routes for all active add-ons.
     */
    public function registerRoutes(): void
    {
        foreach ($this->activeIds as $id) {
            if (isset($this->addons[$id]) && !($this->addons[$id] instanceof InactiveAddonProxy)) {
                $this->addons[$id]->registerRoutes();
            }
        }
    }

    /**
     * Get all discovered add-ons.
     *
     * @return array<string, AddonInterface>
     */
    public function getAll(): array
    {
        return $this->addons;
    }

    /**
     * Get only active add-ons.
     *
     * @return array<string, AddonInterface>
     */
    public function getActive(): array
    {
        return array_filter(
            $this->addons,
            fn(AddonInterface $addon) => in_array($addon->getId(), $this->activeIds, true)
        );
    }

    /**
     * Check if an add-on is active.
     */
    public function isActive(string $id): bool
    {
        return in_array($id, $this->activeIds, true);
    }

    /**
     * Get a specific add-on by ID.
     */
    public function get(string $id): ?AddonInterface
    {
        return $this->addons[$id] ?? null;
    }
}
