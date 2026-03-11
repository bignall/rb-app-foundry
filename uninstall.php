<?php
/**
 * AppForge Uninstall Handler.
 *
 * Fired when the plugin is uninstalled (deleted) from WordPress.
 * Cleans up all options, custom tables, and transients if the
 * user has opted to delete data on uninstall.
 *
 * @package RBCS\AppForge
 */

// Prevent direct access.
defined('WP_UNINSTALL_PLUGIN') || exit;

// Check if user wants data removed.
$appforge_settings = get_option('appforge_settings', []);
$appforge_delete_data = $appforge_settings['general']['delete_data_on_uninstall'] ?? false;

if (!$appforge_delete_data) {
    return;
}

// Remove all plugin options.
global $wpdb;

// Delete all options starting with appforge_.
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'appforge_%'"
);

// Delete all transients.
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_appforge_%'"
);
$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_appforge_%'"
);

// Delete custom post types and their meta.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$appforge_post_types = $wpdb->get_col(
    "SELECT DISTINCT post_type FROM {$wpdb->posts} WHERE post_type LIKE 'af_%'"
);

if (!empty($appforge_post_types)) {
    foreach ($appforge_post_types as $appforge_post_type) {
        $posts = get_posts([
            'post_type'      => $appforge_post_type,
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ]);

        foreach ($posts as $appforge_post_id) {
            wp_delete_post($appforge_post_id, true);
        }
    }
}

// Clean up any custom tables created by add-ons.
// Add-ons should register their table names in the appforge_custom_tables option.
$appforge_custom_tables = get_option('appforge_custom_tables', []);
foreach ($appforge_custom_tables as $appforge_table) {
    $appforge_table_name = $wpdb->prefix . esc_sql( sanitize_key( $appforge_table ) );
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter
    $wpdb->query("DROP TABLE IF EXISTS {$appforge_table_name}");
}

// Flush rewrite rules.
flush_rewrite_rules();
