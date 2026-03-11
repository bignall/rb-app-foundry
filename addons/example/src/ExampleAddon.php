<?php

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

namespace RBCS\AppForge\Addons\Example;

use RBCS\AppForge\Addon\AddonAbstract;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Example Add-on
 *
 * Demonstrates how to create a AppForge add-on.
 * This add-on is safe to delete — it's just a reference implementation.
 *
 * @package RBCS\AppForge\Addons\Example
 */
class ExampleAddon extends AddonAbstract
{
    /**
     * {@inheritdoc}
     */
    public function getId(): string
    {
        return 'example';
    }

    /**
     * {@inheritdoc}
     *
     * This is where you register all your hooks, filters, CPTs,
     * shortcodes, blocks, etc. It only runs when the add-on is active.
     */
    public function boot(): void
    {
        // Example: Register a shortcode.
        add_shortcode('appforge_example', [$this, 'renderShortcode']);

        // Example: Add a filter.
        add_filter('the_content', [$this, 'appendExampleNotice'], 999);
    }

    /**
     * {@inheritdoc}
     *
     * First-time activation tasks. Create tables, set defaults, etc.
     */
    public function activate(): void
    {
        // Set default settings for this add-on.
        $this->updateSetting('show_notice', false);
        $this->updateSetting('notice_text', 'This is an example notice from AppForge!');
    }

    /**
     * {@inheritdoc}
     *
     * Define the settings that appear in the admin UI.
     */
    public function getSettingsSchema(): array
    {
        return [
            [
                'id'      => 'show_notice',
                'type'    => 'toggle',
                'label'   => 'Show Example Notice',
                'help'    => 'Appends a notice to the bottom of all post content.',
                'default' => false,
            ],
            [
                'id'      => 'notice_text',
                'type'    => 'text',
                'label'   => 'Notice Text',
                'default' => 'This is an example notice from AppForge!',
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Register REST API endpoints for this add-on.
     */
    public function registerRoutes(): void
    {
        register_rest_route('appforge/v1', '/example/hello', [
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => [$this, 'helloEndpoint'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Example REST endpoint.
     */
    public function helloEndpoint(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'message' => 'Hello from the Example add-on!',
            'version' => $this->getVersion(),
        ]);
    }

    /**
     * Example shortcode handler.
     */
    public function renderShortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'text' => 'Hello from AppForge!',
        ], $atts);

        return sprintf(
            '<div class="appforge-example">%s</div>',
            esc_html($atts['text'])
        );
    }

    /**
     * Example filter: append a notice to post content.
     */
    public function appendExampleNotice(string $content): string
    {
        if (!is_singular() || !$this->getSetting('show_notice', false)) {
            return $content;
        }

        $noticeText = $this->getSetting('notice_text', '');

        if (empty($noticeText)) {
            return $content;
        }

        return $content . sprintf(
            '<div class="appforge-example-notice" style="padding:12px;background:#f0f0f1;border-left:4px solid #2271b1;margin-top:20px;">%s</div>',
            esc_html($noticeText)
        );
    }
}
