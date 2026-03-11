<?php

declare(strict_types=1);

namespace RBCS\AppFoundry\Core;

/**
 * Handles plugin activation tasks.
 *
 * Creates necessary database tables, sets default options,
 * and performs any first-time setup.
 *
 * @package RBCS\AppFoundry\Core
 */
class Activator
{
    /**
     * Run activation tasks.
     */
    public static function activate(): void
    {
        // Check requirements one more time.
        if (version_compare(PHP_VERSION, APPFOUNDRY_MIN_PHP, '<')) {
            wp_die(
                sprintf(
                    'RB App Foundry requires PHP %s or higher.',
                    esc_html( APPFOUNDRY_MIN_PHP )
                ),
                'Plugin Activation Error',
                ['back_link' => true]
            );
        }

        // Set default options.
        self::setDefaults();

        // Create custom tables if needed.
        self::createTables();

        // Store version for upgrade routines.
        update_option('appfoundry_version', APPFOUNDRY_VERSION);

        // Flush rewrite rules for any CPTs.
        flush_rewrite_rules();
    }

    /**
     * Set default plugin options.
     */
    private static function setDefaults(): void
    {
        $defaults = [
            'appfoundry_settings' => [
                'general' => [
                    'delete_data_on_uninstall' => false,
                ],
            ],
            'appfoundry_active_addons' => [],
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Create custom database tables.
     *
     * Override this in child plugins if custom tables are needed.
     */
    private static function createTables(): void
    {
        // Framework doesn't create tables by default.
        // Add-ons can create their own tables in their activate() method.
    }
}
