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
$af_settings = get_option('appforge_settings', []);
$af_delete_data = $af_settings['general']['delete_data_on_uninstall'] ?? false;

if (!$af_delete_data) {
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
$af_post_types = $wpdb->get_col(
    "SELECT DISTINCT post_type FROM {$wpdb->posts} WHERE post_type LIKE 'af_%'"
);

if (!empty($af_post_types)) {
    foreach ($af_post_types as $af_post_type) {
        $posts = get_posts([
            'post_type'      => $af_post_type,
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ]);

        foreach ($posts as $af_post_id) {
            wp_delete_post($af_post_id, true);
        }
    }
}

// Clean up any custom tables created by add-ons.
// Add-ons should register their table names in the appforge_custom_tables option.
$af_custom_tables = get_option('appforge_custom_tables', []);
foreach ($af_custom_tables as $af_table) {
    $af_table_name = $wpdb->prefix . sanitize_key( $af_table );
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query("DROP TABLE IF EXISTS {$af_table_name}"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
}

// Flush rewrite rules.
flush_rewrite_rules();
