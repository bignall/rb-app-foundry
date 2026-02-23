<?php

declare(strict_types=1);

namespace RBCS\PluginForge\Admin;

/**
 * Centralized settings management.
 *
 * Provides a clean API for getting and setting plugin options
 * with defaults, validation, and caching.
 *
 * @package RBCS\PluginForge\Admin
 */
class SettingsManager
{
    private const OPTION_KEY = 'pluginforge_settings';

    /**
     * Cached settings.
     *
     * @var array<string, mixed>|null
     */
    private static ?array $cache = null;

    /**
     * Get all settings.
     *
     * @return array<string, mixed>
     */
    public static function getAll(): array
    {
        if (self::$cache === null) {
            self::$cache = get_option(self::OPTION_KEY, []);
        }
        return self::$cache;
    }

    /**
     * Get a setting value using dot notation.
     *
     * @param string $key     Dot-notated key (e.g., 'general.delete_data_on_uninstall').
     * @param mixed  $default Default value.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::getAll();
        $keys = explode('.', $key);
        $value = $settings;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set a setting value using dot notation.
     *
     * @param string $key   Dot-notated key.
     * @param mixed  $value Value to set.
     */
    public static function set(string $key, mixed $value): void
    {
        $settings = self::getAll();
        $keys = explode('.', $key);
        $current = &$settings;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $current[$k] = $value;
            } else {
                if (!isset($current[$k]) || !is_array($current[$k])) {
                    $current[$k] = [];
                }
                $current = &$current[$k];
            }
        }

        update_option(self::OPTION_KEY, $settings);
        self::$cache = $settings;
    }

    /**
     * Delete a setting.
     */
    public static function delete(string $key): void
    {
        self::set($key, null);
    }

    /**
     * Clear the settings cache.
     */
    public static function clearCache(): void
    {
        self::$cache = null;
    }
}
