<?php
/**
 * AppForge - WordPress Plugin Starter Framework
 *
 * A modern, lightweight WordPress plugin framework with an add-on architecture.
 * Built for developers who want a solid foundation without the bloat.
 *
 * @package     RBCS\AppForge
 * @author      RB Creative Solutions LLC
 * @copyright   2026 RB Creative Solutions LLC
 * @license     GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: AppForge
 * Plugin URI:  https://github.com/bignall/plugin-forge
 * Description: A modern WordPress plugin starter framework with add-on architecture, PSR-4 autoloading, and React admin panels.
 * Version:     0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author:      RB Creative Solutions LLC
 * Author URI:  https://rbcreativesolutions.net
 * Text Domain: appforge
 * Domain Path: /languages
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

declare(strict_types=1);

// Prevent direct access.
defined('ABSPATH') || exit;

// Plugin constants.
define('APPFORGE_VERSION', '0.1.0');
define('APPFORGE_FILE', __FILE__);
define('APPFORGE_PATH', plugin_dir_path(__FILE__));
define('APPFORGE_URL', plugin_dir_url(__FILE__));
define('APPFORGE_BASENAME', plugin_basename(__FILE__));
define('APPFORGE_SLUG', 'appforge');
define('APPFORGE_MIN_PHP', '8.0');
define('APPFORGE_MIN_WP', '6.4');

/**
 * Check minimum requirements before loading anything.
 */
function appforge_check_requirements(): bool {
    $errors = [];

    if (version_compare(PHP_VERSION, APPFORGE_MIN_PHP, '<')) {
        $errors[] = sprintf(
            /* translators: 1: Required PHP version, 2: Current PHP version */
            __('AppForge requires PHP %1$s or higher. You are running PHP %2$s.', 'appforge'),
            APPFORGE_MIN_PHP,
            PHP_VERSION
        );
    }

    global $wp_version;
    if (version_compare($wp_version, APPFORGE_MIN_WP, '<')) {
        $errors[] = sprintf(
            /* translators: 1: Required WP version, 2: Current WP version */
            __('AppForge requires WordPress %1$s or higher. You are running WordPress %2$s.', 'appforge'),
            APPFORGE_MIN_WP,
            $wp_version
        );
    }

    if (!empty($errors)) {
        add_action('admin_notices', function () use ($errors) {
            foreach ($errors as $error) {
                printf(
                    '<div class="notice notice-error"><p><strong>AppForge:</strong> %s</p></div>',
                    esc_html($error)
                );
            }
        });
        return false;
    }

    return true;
}

/**
 * Load Composer autoloader.
 */
function appforge_load_autoloader(): bool {
    $autoloader = APPFORGE_PATH . 'vendor/autoload.php';

    if (!file_exists($autoloader)) {
        add_action('admin_notices', function () {
            printf(
                '<div class="notice notice-error"><p><strong>AppForge:</strong> %s</p></div>',
                esc_html__('Composer autoloader not found. Please run `composer install` in the plugin directory.', 'appforge')
            );
        });
        return false;
    }

    require_once $autoloader;
    return true;
}

/**
 * Initialize the plugin.
 *
 * This is the single entry point. Everything flows from here.
 */
function appforge_init(): void {
    if (!appforge_check_requirements()) {
        return;
    }

    if (!appforge_load_autoloader()) {
        return;
    }

    // Boot the plugin.
    $plugin = \RBCS\AppForge\Core\Plugin::getInstance();
    $plugin->boot();
}

// Register activation/deactivation hooks (must be in main file).
register_activation_hook(__FILE__, function (): void {
    if (!appforge_load_autoloader()) {
        return;
    }
    \RBCS\AppForge\Core\Activator::activate();
});

register_deactivation_hook(__FILE__, function (): void {
    if (!appforge_load_autoloader()) {
        return;
    }
    \RBCS\AppForge\Core\Deactivator::deactivate();
});

// Go.
add_action('plugins_loaded', 'appforge_init');
