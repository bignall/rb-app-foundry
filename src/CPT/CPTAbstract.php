<?php

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

namespace RBCS\AppForge\CPT;

/**
 * Abstract base class for custom post types.
 *
 * Provides a clean, declarative way to register CPTs.
 * Extend this class and define your post type properties.
 *
 * @package RBCS\AppForge\CPT
 */
abstract class CPTAbstract implements CPTInterface
{
    /**
     * Post type slug. Override in child classes.
     */
    protected string $slug = '';

    /**
     * Singular label. Override in child classes.
     */
    protected string $singular = '';

    /**
     * Plural label. Override in child classes.
     */
    protected string $plural = '';

    /**
     * Menu icon (dashicon or URL). Override in child classes.
     */
    protected string $icon = 'dashicons-admin-post';

    /**
     * Whether the CPT is public. Override in child classes.
     */
    protected bool $public = false;

    /**
     * Supports array. Override in child classes.
     *
     * @var string[]
     */
    protected array $supports = ['title', 'editor', 'thumbnail'];

    /**
     * {@inheritdoc}
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * {@inheritdoc}
     */
    public function getArgs(): array
    {
        return [
            'labels'              => $this->getLabels(),
            'public'              => $this->public,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_rest'        => true,
            'menu_icon'           => $this->icon,
            'supports'            => $this->supports,
            'has_archive'         => $this->public,
            'rewrite'             => $this->public ? ['slug' => $this->slug] : false,
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        add_action('init', function (): void {
            register_post_type($this->getSlug(), $this->getArgs());
        });
    }

    /**
     * Generate labels from singular and plural names.
     *
     * @return array<string, string>
     */
    protected function getLabels(): array
    {
        $s = $this->singular;
        $p = $this->plural;

        return [
            'name'                  => $p,
            'singular_name'         => $s,
            'add_new'               => "Add New {$s}",
            'add_new_item'          => "Add New {$s}",
            'edit_item'             => "Edit {$s}",
            'new_item'              => "New {$s}",
            'view_item'             => "View {$s}",
            'view_items'            => "View {$p}",
            'search_items'          => "Search {$p}",
            'not_found'             => "No {$p} found",
            'not_found_in_trash'    => "No {$p} found in Trash",
            'all_items'             => "All {$p}",
            'archives'              => "{$s} Archives",
            'attributes'            => "{$s} Attributes",
            'insert_into_item'      => "Insert into {$s}",
            'uploaded_to_this_item' => "Uploaded to this {$s}",
            'menu_name'             => $p,
        ];
    }
}
