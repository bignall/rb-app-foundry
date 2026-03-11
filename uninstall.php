<?php
/**
 * RB App Foundry Uninstall Handler.
 *
 * Fired when the plugin is uninstalled (deleted) from WordPress.
 * Cleans up all options, custom tables, and transients if the
 * user has opted to delete data on uninstall.
 *
 * @package RBCS\AppFoundry
 */

// Prevent direct access.
defined('WP_UNINSTALL_PLUGIN') || exit;

// Check if user wants data removed.
$appfoundry_settings = get_option('appfoundry_settings', []);
$appfoundry_delete_data = $appfoundry_settings['general']['delete_data_on_uninstall'] ?? false;

if (!$appfoundry_delete_data) {
    return;
}

// Remove all plugin options.
global $wpdb;

// Delete all options starting with appfoundry_.
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'appfoundry_%'"
);

// Delete all transients.
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_appfoundry_%'"
);
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_appfoundry_%'"
);

// Delete custom post types and their meta.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$appfoundry_post_types = $wpdb->get_col(
    "SELECT DISTINCT post_type FROM {$wpdb->posts} WHERE post_type LIKE 'af_%'"
);

if (!empty($appfoundry_post_types)) {
    foreach ($appfoundry_post_types as $appfoundry_post_type) {
        $posts = get_posts([
            'post_type'      => $appfoundry_post_type,
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ]);

        foreach ($posts as $appfoundry_post_id) {
            wp_delete_post($appfoundry_post_id, true);
        }
    }
}

// Clean up any custom tables created by add-ons.
// Add-ons should register their table names in the appfoundry_custom_tables option.
$appfoundry_custom_tables = get_option('appfoundry_custom_tables', []);
foreach ($appfoundry_custom_tables as $appfoundry_table) {
    $appfoundry_table_name = $wpdb->prefix . esc_sql( sanitize_key( $appfoundry_table ) );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $wpdb->query("DROP TABLE IF EXISTS {$appfoundry_table_name}");
}

// Flush rewrite rules.
flush_rewrite_rules();
