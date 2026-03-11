<?php

declare(strict_types=1);

namespace RBCS\AppForge\Traits;

use RBCS\AppForge\Admin\SettingsManager;

/**
 * Trait for classes that need easy settings access.
 *
 * @package RBCS\AppForge\Traits
 */
trait HasSettings
{
    /**
     * Get a plugin setting.
     */
    protected function setting(string $key, mixed $default = null): mixed
    {
        return SettingsManager::get($key, $default);
    }

    /**
     * Update a plugin setting.
     */
    protected function updateSetting(string $key, mixed $value): void
    {
        SettingsManager::set($key, $value);
    }
}
