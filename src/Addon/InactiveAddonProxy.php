<?php

declare(strict_types=1);

namespace RBCS\AppForge\Addon;

/**
 * Lightweight proxy for inactive add-ons.
 *
 * Allows the admin UI to display information about all available
 * add-ons without loading their full PHP code. Only reads addon.json.
 *
 * @package RBCS\AppForge\Addon
 */
class InactiveAddonProxy implements AddonInterface
{
    /**
     * @param array<string, mixed> $metadata Data from addon.json.
     * @param string               $dir      Path to the add-on directory.
     */
    public function __construct(
        private readonly array $metadata,
        private readonly string $dir
    ) {}

    public function getId(): string
    {
        return $this->metadata['id'] ?? basename($this->dir);
    }

    public function getName(): string
    {
        return $this->metadata['name'] ?? $this->getId();
    }

    public function getDescription(): string
    {
        return $this->metadata['description'] ?? '';
    }

    public function getVersion(): string
    {
        return $this->metadata['version'] ?? '1.0.0';
    }

    public function isActiveByDefault(): bool
    {
        return $this->metadata['default_active'] ?? false;
    }

    public function getDependencies(): array
    {
        return $this->metadata['dependencies'] ?? [];
    }

    public function boot(): void
    {
        // No-op: inactive add-ons don't boot.
    }

    public function activate(): void
    {
        // No-op: handled after full class is loaded.
    }

    public function deactivate(): void
    {
        // No-op.
    }

    public function getSettingsSchema(): array
    {
        return [];
    }

    public function registerRoutes(): void
    {
        // No-op.
    }
}
