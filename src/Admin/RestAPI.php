<?php

declare(strict_types=1);

namespace RBCS\PluginForge\Admin;

use RBCS\PluginForge\Core\Plugin;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST API endpoints for the admin React app.
 *
 * Provides endpoints for managing settings, add-ons, and connections.
 *
 * @package RBCS\PluginForge\Admin
 */
class RestAPI
{
    private const NAMESPACE = 'pluginforge/v1';

    public function __construct(
        private readonly Plugin $plugin
    ) {}

    /**
     * Register all REST API routes.
     */
    public function registerRoutes(): void
    {
        // Settings endpoints.
        register_rest_route(self::NAMESPACE, '/settings', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'getSettings'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'updateSettings'],
                'permission_callback' => [$this, 'checkAdminPermission'],
            ],
        ]);

        // Add-ons endpoints.
        register_rest_route(self::NAMESPACE, '/addons', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'getAddons'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);

        register_rest_route(self::NAMESPACE, '/addons/(?P<id>[a-z0-9-]+)/activate', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'activateAddon'],
            'permission_callback' => [$this, 'checkAdminPermission'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => fn($param) => is_string($param) && !empty($param),
                ],
            ],
        ]);

        register_rest_route(self::NAMESPACE, '/addons/(?P<id>[a-z0-9-]+)/deactivate', [
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'deactivateAddon'],
            'permission_callback' => [$this, 'checkAdminPermission'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => fn($param) => is_string($param) && !empty($param),
                ],
            ],
        ]);

        // Connections endpoints.
        register_rest_route(self::NAMESPACE, '/connections', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'getConnections'],
            'permission_callback' => [$this, 'checkAdminPermission'],
        ]);

        register_rest_route(self::NAMESPACE, '/connections/(?P<id>[a-z0-9_-]+)/credentials', [
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'saveCredentials'],
                'permission_callback' => [$this, 'checkAdminPermission'],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'validate_callback' => fn($param) => is_string($param) && !empty($param),
                    ],
                ],
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'deleteCredentials'],
                'permission_callback' => [$this, 'checkAdminPermission'],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'validate_callback' => fn($param) => is_string($param) && !empty($param),
                    ],
                ],
            ],
        ]);

        // Health check.
        register_rest_route(self::NAMESPACE, '/health', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'healthCheck'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Check if the current user has admin permissions.
     */
    public function checkAdminPermission(): bool
    {
        return current_user_can('manage_options');
    }

    /**
     * Get plugin settings.
     */
    public function getSettings(WP_REST_Request $request): WP_REST_Response
    {
        $settings = get_option('pluginforge_settings', []);

        // Include add-on settings schemas for active add-ons.
        $addonSchemas = [];
        foreach ($this->plugin->getAddonManager()->getActive() as $addon) {
            $schema = $addon->getSettingsSchema();
            if (!empty($schema)) {
                $addonSchemas[$addon->getId()] = [
                    'name'     => $addon->getName(),
                    'schema'   => $schema,
                    'settings' => $addon->getAllSettings(),
                ];
            }
        }

        return new WP_REST_Response([
            'general'  => $settings,
            'addons'   => $addonSchemas,
        ]);
    }

    /**
     * Update plugin settings.
     */
    public function updateSettings(WP_REST_Request $request): WP_REST_Response
    {
        $params = $request->get_json_params();

        // Update general settings.
        if (isset($params['general'])) {
            $current = get_option('pluginforge_settings', []);
            $updated = wp_parse_args($params['general'], $current);
            update_option('pluginforge_settings', $updated);
        }

        // Update add-on specific settings.
        if (isset($params['addon_id']) && isset($params['addon_settings'])) {
            $addonId = sanitize_text_field($params['addon_id']);
            $addon = $this->plugin->getAddonManager()->get($addonId);

            if ($addon && $this->plugin->getAddonManager()->isActive($addonId)) {
                update_option(
                    "pluginforge_addon_{$addonId}_settings",
                    $params['addon_settings']
                );
            }
        }

        return new WP_REST_Response(['success' => true]);
    }

    /**
     * Get all add-ons and their status.
     */
    public function getAddons(WP_REST_Request $request): WP_REST_Response
    {
        $manager = $this->plugin->getAddonManager();
        $addons = [];

        foreach ($manager->getAll() as $addon) {
            $addons[] = [
                'id'               => $addon->getId(),
                'name'             => $addon->getName(),
                'description'      => $addon->getDescription(),
                'version'          => $addon->getVersion(),
                'active'           => $manager->isActive($addon->getId()),
                'default_active'   => $addon->isActiveByDefault(),
                'dependencies'     => $addon->getDependencies(),
            ];
        }

        return new WP_REST_Response($addons);
    }

    /**
     * Activate an add-on.
     */
    public function activateAddon(WP_REST_Request $request): WP_REST_Response
    {
        $id = $request->get_param('id');
        $success = $this->plugin->getAddonManager()->activate($id);

        if (!$success) {
            return new WP_REST_Response(
                ['error' => 'Failed to activate add-on. It may not exist or dependencies may not be met.'],
                400
            );
        }

        return new WP_REST_Response(['success' => true, 'id' => $id]);
    }

    /**
     * Deactivate an add-on.
     */
    public function deactivateAddon(WP_REST_Request $request): WP_REST_Response
    {
        $id = $request->get_param('id');
        $success = $this->plugin->getAddonManager()->deactivate($id);

        if (!$success) {
            return new WP_REST_Response(
                ['error' => 'Failed to deactivate add-on. Other active add-ons may depend on it.'],
                400
            );
        }

        return new WP_REST_Response(['success' => true, 'id' => $id]);
    }

    /**
     * Get all connections and their status.
     */
    public function getConnections(WP_REST_Request $request): WP_REST_Response
    {
        $summary = $this->plugin->getConnectionManager()->getSummary();
        return new WP_REST_Response($summary);
    }

    /**
     * Save credentials for a connection.
     *
     * Calls the connection's authenticate() method, which validates and
     * stores the credentials (encrypted). Returns the updated connection status.
     */
    public function saveCredentials(WP_REST_Request $request): WP_REST_Response|\WP_Error
    {
        $id = $request->get_param('id');
        $connection = $this->plugin->getConnectionManager()->get($id);

        if (!$connection) {
            return new \WP_Error('not_found', 'Connection not found.', ['status' => 404]);
        }

        $credentials = $request->get_json_params();

        if (empty($credentials) || !is_array($credentials)) {
            return new \WP_Error('bad_request', 'No credentials provided.', ['status' => 400]);
        }

        $success = $connection->authenticate($credentials);

        if (!$success) {
            $detail = method_exists($connection, 'getLastError') ? $connection->getLastError() : null;
            $message = $detail
                ? "Authentication failed: {$detail}"
                : 'Failed to authenticate. Check your credentials and try again.';
            return new \WP_Error('auth_failed', $message, ['status' => 422]);
        }

        return new WP_REST_Response([
            'success'   => true,
            'connected' => $connection->isConnected(),
        ]);
    }

    /**
     * Disconnect a connection and delete its stored credentials.
     */
    public function deleteCredentials(WP_REST_Request $request): WP_REST_Response|\WP_Error
    {
        $id = $request->get_param('id');
        $connection = $this->plugin->getConnectionManager()->get($id);

        if (!$connection) {
            return new \WP_Error('not_found', 'Connection not found.', ['status' => 404]);
        }

        $connection->disconnect();

        return new WP_REST_Response(['success' => true, 'connected' => false]);
    }

    /**
     * Simple health check endpoint.
     */
    public function healthCheck(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'status'  => 'ok',
            'version' => PLUGINFORGE_VERSION,
        ]);
    }
}
