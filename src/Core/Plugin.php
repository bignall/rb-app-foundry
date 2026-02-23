<?php

declare(strict_types=1);

namespace RBCS\PluginForge\Core;

use RBCS\PluginForge\Addon\AddonManager;
use RBCS\PluginForge\Admin\AdminPage;
use RBCS\PluginForge\Admin\RestAPI;
use RBCS\PluginForge\Connection\ConnectionManager;

/**
 * Main plugin orchestrator.
 *
 * Singleton that initializes the framework, loads core components,
 * and delegates to the AddonManager for add-on loading.
 *
 * @package RBCS\PluginForge\Core
 */
final class Plugin
{
    private static ?Plugin $instance = null;

    private bool $booted = false;

    private ?AddonManager $addonManager = null;

    private ?ConnectionManager $connectionManager = null;

    /**
     * Get the singleton instance.
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent direct instantiation.
     */
    private function __construct() {}

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Boot the plugin.
     *
     * This is the main entry point called from the bootstrap file.
     * It initializes all core components and loads active add-ons.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        // Load text domain for i18n.
        $this->loadTextDomain();

        // Initialize core managers.
        $this->connectionManager = new ConnectionManager();
        $this->addonManager = new AddonManager($this);

        // Discover and boot active add-ons.
        $this->addonManager->discover();
        $this->addonManager->bootActive();

        // Initialize admin if in admin context.
        if (is_admin()) {
            $this->initAdmin();
        }

        // Register REST API routes.
        add_action('rest_api_init', [$this, 'registerRestRoutes']);

        // Enqueue front-end assets only when needed.
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);

        /**
         * Fires after PluginForge has fully booted.
         *
         * Use this hook to interact with PluginForge after all core
         * components and active add-ons have been initialized.
         *
         * @param Plugin $plugin The plugin instance.
         */
        do_action('pluginforge_loaded', $this);
    }

    /**
     * Initialize admin components.
     */
    private function initAdmin(): void
    {
        $adminPage = new AdminPage($this);
        $adminPage->register();
    }

    /**
     * Register REST API routes.
     */
    public function registerRestRoutes(): void
    {
        $restAPI = new RestAPI($this);
        $restAPI->registerRoutes();

        // Let add-ons register their routes.
        $this->addonManager->registerRoutes();
    }

    /**
     * Enqueue front-end assets.
     *
     * Only enqueues assets if an active add-on requires them.
     */
    public function enqueueFrontendAssets(): void
    {
        // Core framework has no front-end assets by default.
        // Add-ons handle their own front-end enqueuing.
    }

    /**
     * Load the plugin text domain for translations.
     */
    private function loadTextDomain(): void
    {
        load_plugin_textdomain(
            'pluginforge',
            false,
            dirname(PLUGINFORGE_BASENAME) . '/languages'
        );
    }

    /**
     * Get the AddonManager instance.
     */
    public function getAddonManager(): AddonManager
    {
        return $this->addonManager;
    }

    /**
     * Get the ConnectionManager instance.
     */
    public function getConnectionManager(): ConnectionManager
    {
        return $this->connectionManager;
    }

    /**
     * Get the plugin version.
     */
    public function getVersion(): string
    {
        return PLUGINFORGE_VERSION;
    }

    /**
     * Get the plugin path.
     */
    public function getPath(string $relative = ''): string
    {
        return PLUGINFORGE_PATH . ltrim($relative, '/');
    }

    /**
     * Get the plugin URL.
     */
    public function getUrl(string $relative = ''): string
    {
        return PLUGINFORGE_URL . ltrim($relative, '/');
    }

    /**
     * Get the addons directory path.
     */
    public function getAddonsPath(): string
    {
        return $this->getPath('addons/');
    }
}
