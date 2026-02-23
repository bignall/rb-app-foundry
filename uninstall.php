<?php
/**
 * PluginForge Uninstall Handler.
 *
 * Fired when the plugin is uninstalled (deleted) from WordPress.
 * Cleans up all options, custom tables, and transients if the
 * user has opted to delete data on uninstall.
 *
 * @package RBCS\PluginForge
 */

// Prevent direct access.
defined('WP_UNINSTALL_PLUGIN') || exit;

// Check if user wants data removed.
$settings = get_option('pluginforge_settings', []);
$deleteData = $settings['general']['delete_data_on_uninstall'] ?? false;

if (!$deleteData) {
    return;
}

// Remove all plugin options.
global $wpdb;

// Delete all options starting with pluginforge_.
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'pluginforge_%'"
);

// Delete all transients.
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pluginforge_%'"
);
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_pluginforge_%'"
);

// Delete custom post types and their meta.
$postTypes = $wpdb->get_col(
    "SELECT DISTINCT post_type FROM {$wpdb->posts} WHERE post_type LIKE 'pf_%'"
);

if (!empty($postTypes)) {
    foreach ($postTypes as $postType) {
        $posts = get_posts([
            'post_type'      => $postType,
            'posts_per_page' => -1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ]);

        foreach ($posts as $postId) {
            wp_delete_post($postId, true);
        }
    }
}

// Clean up any custom tables created by add-ons.
// Add-ons should register their table names in the pluginforge_custom_tables option.
$customTables = get_option('pluginforge_custom_tables', []);
foreach ($customTables as $table) {
    $tableName = $wpdb->prefix . $table;
    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query("DROP TABLE IF EXISTS {$tableName}");
}

// Flush rewrite rules.
flush_rewrite_rules();
