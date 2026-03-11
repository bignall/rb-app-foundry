<?php

declare(strict_types=1);

namespace RBCS\AppFoundry\CPT;

/**
 * Contract for custom post type registration.
 *
 * @package RBCS\AppFoundry\CPT
 */
interface CPTInterface
{
    /**
     * Get the post type slug.
     */
    public function getSlug(): string;

    /**
     * Get the post type registration arguments.
     *
     * @return array<string, mixed> Arguments for register_post_type().
     */
    public function getArgs(): array;

    /**
     * Register the post type.
     */
    public function register(): void;
}
