<?php

declare(strict_types=1);

namespace RBCS\AppForge\CPT;

/**
 * Abstract base class for custom taxonomies.
 *
 * Provides a clean, declarative way to register taxonomies.
 *
 * @package RBCS\AppForge\CPT
 */
abstract class TaxonomyAbstract
{
    /**
     * Taxonomy slug. Override in child classes.
     */
    protected string $slug = '';

    /**
     * Singular label.
     */
    protected string $singular = '';

    /**
     * Plural label.
     */
    protected string $plural = '';

    /**
     * Post types this taxonomy is attached to.
     *
     * @var string[]
     */
    protected array $postTypes = [];

    /**
     * Whether the taxonomy is hierarchical (like categories).
     */
    protected bool $hierarchical = false;

    /**
     * Get the taxonomy slug.
     */
    public function getSlug(): string
    {
        return $this->slug;
    }

    /**
     * Get the registration arguments.
     *
     * @return array<string, mixed>
     */
    public function getArgs(): array
    {
        return [
            'labels'            => $this->getLabels(),
            'hierarchical'      => $this->hierarchical,
            'public'            => false,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'rewrite'           => false,
        ];
    }

    /**
     * Register the taxonomy.
     */
    public function register(): void
    {
        add_action('init', function (): void {
            register_taxonomy($this->getSlug(), $this->postTypes, $this->getArgs());
        });
    }

    /**
     * Generate labels.
     *
     * @return array<string, string>
     */
    protected function getLabels(): array
    {
        $s = $this->singular;
        $p = $this->plural;

        return [
            'name'              => $p,
            'singular_name'     => $s,
            'search_items'      => "Search {$p}",
            'all_items'         => "All {$p}",
            'parent_item'       => $this->hierarchical ? "Parent {$s}" : null,
            'parent_item_colon' => $this->hierarchical ? "Parent {$s}:" : null,
            'edit_item'         => "Edit {$s}",
            'update_item'       => "Update {$s}",
            'add_new_item'      => "Add New {$s}",
            'new_item_name'     => "New {$s} Name",
            'menu_name'         => $p,
        ];
    }
}
