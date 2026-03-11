<?php

declare(strict_types=1);

namespace RBCS\AppForge\Traits;

/**
 * Trait for easy WordPress hook registration.
 *
 * Provides a clean way to register actions and filters
 * with automatic method binding.
 *
 * @package RBCS\AppForge\Traits
 */
trait Hookable
{
    /**
     * Register a WordPress action.
     */
    protected function addAction(
        string $hook,
        string $method,
        int $priority = 10,
        int $acceptedArgs = 1
    ): void {
        add_action($hook, [$this, $method], $priority, $acceptedArgs);
    }

    /**
     * Register a WordPress filter.
     */
    protected function addFilter(
        string $hook,
        string $method,
        int $priority = 10,
        int $acceptedArgs = 1
    ): void {
        add_filter($hook, [$this, $method], $priority, $acceptedArgs);
    }

    /**
     * Remove a previously registered action.
     */
    protected function removeAction(
        string $hook,
        string $method,
        int $priority = 10
    ): void {
        remove_action($hook, [$this, $method], $priority);
    }

    /**
     * Remove a previously registered filter.
     */
    protected function removeFilter(
        string $hook,
        string $method,
        int $priority = 10
    ): void {
        remove_filter($hook, [$this, $method], $priority);
    }
}
