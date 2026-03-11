<?php

declare(strict_types=1);

namespace RBCS\AppFoundry\Core;

/**
 * Handles asset enqueuing with conditional loading.
 *
 * Only loads scripts and styles when they're actually needed,
 * keeping the front-end footprint minimal.
 *
 * @package RBCS\AppFoundry\Core
 */
class Assets
{
    /**
     * Register and enqueue admin assets for the plugin settings page.
     *
     * @param string $hookSuffix The current admin page hook suffix.
     */
    public static function enqueueAdmin(string $hookSuffix): void
    {
        // Only load on our admin pages.
        if (!str_contains($hookSuffix, APPFOUNDRY_SLUG)) {
            return;
        }

        $assetFile = APPFOUNDRY_PATH . 'admin/build/index.asset.php';

        if (!file_exists($assetFile)) {
            return;
        }

        $asset = require $assetFile;

        wp_enqueue_script(
            'appfoundry-admin',
            APPFOUNDRY_URL . 'admin/build/index.js',
            $asset['dependencies'] ?? ['wp-element', 'wp-components', 'wp-api-fetch'],
            $asset['version'] ?? APPFOUNDRY_VERSION,
            true
        );

        wp_enqueue_style(
            'appfoundry-admin',
            APPFOUNDRY_URL . 'admin/build/index.css',
            ['wp-components'],
            $asset['version'] ?? APPFOUNDRY_VERSION
        );

        // Pass data to React app.
        wp_localize_script('appfoundry-admin', 'appFoundryData', [
            'restUrl'   => rest_url('rb-app-foundry/v1/'),
            'nonce'     => wp_create_nonce('wp_rest'),
            'version'   => APPFOUNDRY_VERSION,
            'adminUrl'  => admin_url(),
            'pluginUrl' => APPFOUNDRY_URL,
        ]);
    }
}
