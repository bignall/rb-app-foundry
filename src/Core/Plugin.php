<?php

declare(strict_types=1);


namespace RBCS\AppForge\Core;

defined( 'ABSPATH' ) || exit;

use RBCS\AppForge\Addon\AddonManager;
use RBCS\AppForge\Admin\AdminPage;
use RBCS\AppForge\Admin\RestAPI;
use RBCS\AppForge\Connection\ConnectionManager;

/**
 * Main plugin orchestrator.
 *
 * Singleton that initializes the framework, loads core components,
 * and delegates to the AddonManager for add-on loading.
 *
 * @package RBCS\AppForge\Core
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

        // Initialize core managers.
        $this->connectionManager = new ConnectionManager();
        $this->addonManager = new AddonManager($this);

        // Defer add-on discovery and booting to init so dependent plugins
        // (e.g. SocialPillar) can register their add-on paths first during
        // their own plugins_loaded callback (which runs after ours at priority 20).
        add_action('init', [$this->addonManager, 'discover'], 0);
        add_action('init', [$this->addonManager, 'bootActive'], 1);

        // Initialize admin if in admin context.
        if (is_admin()) {
            $this->initAdmin();
        }

        // Register REST API routes.
        add_action('rest_api_init', [$this, 'registerRestRoutes']);

        // Enqueue front-end assets only when needed.
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);

        /**
         * Fires after AppForge has fully booted.
         *
         * Use this hook to interact with AppForge after all core
         * components and active add-ons have been initialized.
         *
         * @param Plugin $plugin The plugin instance.
         */
        do_action('appforge_loaded', $this);
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
        return APPFORGE_VERSION;
    }

    /**
     * Get the plugin path.
     */
    public function getPath(string $relative = ''): string
    {
        return APPFORGE_PATH . ltrim($relative, '/');
    }

    /**
     * Get the plugin URL.
     */
    public function getUrl(string $relative = ''): string
    {
        return APPFORGE_URL . ltrim($relative, '/');
    }

    /**
     * Get the addons directory path.
     */
    public function getAddonsPath(): string
    {
        return $this->getPath('addons/');
    }
}
