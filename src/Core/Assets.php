<?php

declare(strict_types=1);

namespace RBCS\PluginForge\Core;

/**
 * Handles asset enqueuing with conditional loading.
 *
 * Only loads scripts and styles when they're actually needed,
 * keeping the front-end footprint minimal.
 *
 * @package RBCS\PluginForge\Core
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
        if (!str_contains($hookSuffix, PLUGINFORGE_SLUG)) {
            return;
        }

        $assetFile = PLUGINFORGE_PATH . 'admin/build/index.asset.php';

        if (!file_exists($assetFile)) {
            return;
        }

        $asset = require $assetFile;

        wp_enqueue_script(
            'pluginforge-admin',
            PLUGINFORGE_URL . 'admin/build/index.js',
            $asset['dependencies'] ?? ['wp-element', 'wp-components', 'wp-api-fetch'],
            $asset['version'] ?? PLUGINFORGE_VERSION,
            true
        );

        wp_enqueue_style(
            'pluginforge-admin',
            PLUGINFORGE_URL . 'admin/build/index.css',
            ['wp-components'],
            $asset['version'] ?? PLUGINFORGE_VERSION
        );

        // Pass data to React app.
        wp_localize_script('pluginforge-admin', 'pluginForgeData', [
            'restUrl'   => rest_url('pluginforge/v1/'),
            'nonce'     => wp_create_nonce('wp_rest'),
            'version'   => PLUGINFORGE_VERSION,
            'adminUrl'  => admin_url(),
            'pluginUrl' => PLUGINFORGE_URL,
        ]);
    }
}
