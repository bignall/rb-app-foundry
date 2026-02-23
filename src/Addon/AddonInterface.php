<?php

declare(strict_types=1);

namespace RBCS\PluginForge\Addon;

/**
 * Contract that all add-ons must implement.
 *
 * This interface defines the minimum methods required for an add-on
 * to be discovered, managed, and integrated by the AddonManager.
 *
 * @package RBCS\PluginForge\Addon
 */
interface AddonInterface
{
    /**
     * Get the unique add-on identifier.
     *
     * Must be a lowercase slug (e.g., 'social-posting', 'feed-display').
     */
    public function getId(): string;

    /**
     * Get the human-readable add-on name.
     */
    public function getName(): string;

    /**
     * Get the add-on description.
     */
    public function getDescription(): string;

    /**
     * Get the add-on version.
     */
    public function getVersion(): string;

    /**
     * Whether this add-on should be active by default on first install.
     */
    public function isActiveByDefault(): bool;

    /**
     * Get add-on dependencies (IDs of other required add-ons).
     *
     * @return string[] Array of add-on IDs this add-on depends on.
     */
    public function getDependencies(): array;

    /**
     * Boot the add-on.
     *
     * Called only when the add-on is active. Register hooks, filters,
     * CPTs, shortcodes, blocks, etc. here.
     */
    public function boot(): void;

    /**
     * Run first-time activation tasks.
     *
     * Called when the add-on is activated for the first time.
     * Create database tables, set default options, etc.
     */
    public function activate(): void;

    /**
     * Run deactivation cleanup.
     *
     * Called when the add-on is deactivated. Clean up transients,
     * cron events, etc. but preserve data for reactivation.
     */
    public function deactivate(): void;

    /**
     * Get the settings schema for the admin UI.
     *
     * Returns an array describing the settings fields for this add-on.
     * Used by the React admin to dynamically render settings tabs.
     *
     * @return array Settings schema definition.
     */
    public function getSettingsSchema(): array;

    /**
     * Register REST API routes for this add-on.
     *
     * Called during rest_api_init. Register any add-on-specific
     * REST endpoints here.
     */
    public function registerRoutes(): void;
}
