<?php

declare(strict_types=1);

namespace RBCS\AppForge\Addon;

use RBCS\AppForge\Core\Plugin;

/**
 * Abstract base class for add-ons.
 *
 * Provides common functionality so add-ons don't have to
 * reimplement boilerplate. Extend this class and implement
 * the abstract methods to create a new add-on.
 *
 * @package RBCS\AppForge\Addon
 */
abstract class AddonAbstract implements AddonInterface
{
    protected Plugin $plugin;

    /**
     * Path to this add-on's directory.
     */
    protected string $path;

    /**
     * URL to this add-on's directory.
     */
    protected string $url;

    /**
     * Addon metadata loaded from addon.json.
     *
     * @var array<string, mixed>
     */
    protected array $metadata = [];

    public function __construct(Plugin $plugin, string $path)
    {
        $this->plugin = $plugin;
        $this->path = trailingslashit($path);
        $this->url = plugin_dir_url($path . '/placeholder');
        $this->loadMetadata();
    }

    /**
     * Load metadata from addon.json.
     */
    private function loadMetadata(): void
    {
        $metaFile = $this->path . 'addon.json';
        if (file_exists($metaFile)) {
            $json = file_get_contents($metaFile);
            $this->metadata = json_decode($json, true) ?: [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->metadata['name'] ?? $this->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return $this->metadata['description'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(): string
    {
        return $this->metadata['version'] ?? '1.0.0';
    }

    /**
     * {@inheritdoc}
     */
    public function isActiveByDefault(): bool
    {
        return $this->metadata['default_active'] ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return $this->metadata['dependencies'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function activate(): void
    {
        // Override in child classes if needed.
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(): void
    {
        // Override in child classes if needed.
    }

    /**
     * {@inheritdoc}
     */
    public function getSettingsSchema(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function registerRoutes(): void
    {
        // Override in child classes to register REST routes.
    }

    /**
     * Get the add-on's directory path.
     */
    public function getPath(string $relative = ''): string
    {
        return $this->path . ltrim($relative, '/');
    }

    /**
     * Get the add-on's URL.
     */
    public function getUrl(string $relative = ''): string
    {
        return $this->url . ltrim($relative, '/');
    }

    /**
     * Get a setting value for this add-on.
     *
     * @param string $key     Setting key.
     * @param mixed  $default Default value if not set.
     * @return mixed
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = get_option("appforge_addon_{$this->getId()}_settings", []);
        return $settings[$key] ?? $default;
    }

    /**
     * Update a setting value for this add-on.
     *
     * @param string $key   Setting key.
     * @param mixed  $value Setting value.
     */
    public function updateSetting(string $key, mixed $value): void
    {
        $settings = get_option("appforge_addon_{$this->getId()}_settings", []);
        $settings[$key] = $value;
        update_option("appforge_addon_{$this->getId()}_settings", $settings);
    }

    /**
     * Get all settings for this add-on.
     *
     * @return array<string, mixed>
     */
    public function getAllSettings(): array
    {
        return get_option("appforge_addon_{$this->getId()}_settings", []);
    }

    /**
     * Enqueue a CSS file from this add-on's assets.
     */
    protected function enqueueStyle(
        string $handle,
        string $file,
        array $deps = [],
        ?string $version = null
    ): void {
        wp_enqueue_style(
            "appforge-{$this->getId()}-{$handle}",
            $this->getUrl("assets/css/{$file}"),
            $deps,
            $version ?? $this->getVersion()
        );
    }

    /**
     * Enqueue a JS file from this add-on's assets.
     */
    protected function enqueueScript(
        string $handle,
        string $file,
        array $deps = [],
        ?string $version = null,
        bool $inFooter = true
    ): void {
        wp_enqueue_script(
            "appforge-{$this->getId()}-{$handle}",
            $this->getUrl("assets/js/{$file}"),
            $deps,
            $version ?? $this->getVersion(),
            $inFooter
        );
    }
}
