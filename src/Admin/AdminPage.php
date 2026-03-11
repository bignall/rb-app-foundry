<?php

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

namespace RBCS\AppForge\Admin;

use RBCS\AppForge\Core\Assets;
use RBCS\AppForge\Core\Plugin;

/**
 * Registers the admin menu page and renders the React app shell.
 *
 * The actual UI is rendered by React. This class just provides
 * the WordPress admin menu entry and the mounting div.
 *
 * @package RBCS\AppForge\Admin
 */
class AdminPage
{
    public function __construct(
        private readonly Plugin $plugin
    ) {}

    /**
     * Register admin hooks.
     */
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_enqueue_scripts', [Assets::class, 'enqueueAdmin']);
    }

    /**
     * Add the admin menu page.
     */
    public function addMenuPage(): void
    {
        add_menu_page(
            __('AppForge', 'appforge'),
            __('AppForge', 'appforge'),
            'manage_options',
            APPFORGE_SLUG,
            [$this, 'renderPage'],
            'dashicons-admin-generic',
            30
        );
    }

    /**
     * Render the admin page.
     *
     * Outputs a minimal shell div that React mounts into.
     */
    public function renderPage(): void
    {
        echo '<div id="appforge-admin-root" class="wrap"></div>';
    }
}
