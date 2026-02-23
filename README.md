# PluginForge

A modern, lightweight WordPress plugin starter framework with an add-on architecture, PSR-4 autoloading, and React admin panels.

Built by [RB Creative Solutions LLC](https://rbcreativesolutions.com).

---

## 🎯 What is PluginForge?

PluginForge is a **starter framework** for building WordPress plugins. It gives you a solid, opinionated foundation so you can focus on your plugin's features instead of reinventing boilerplate.

### Key Features

- **Add-on Architecture** — Features live in self-contained add-ons that can be activated/deactivated. Inactive add-ons don't load any PHP at all.
- **PSR-4 Autoloading** — Classes load on demand via Composer. No manual `require` spaghetti.
- **Connection Abstraction** — A consistent interface for connecting to external platforms (social media, AI, APIs) with built-in credential encryption, token refresh, and rate limiting.
- **React Admin Panel** — Modern settings UI built with `@wordpress/scripts`, featuring dynamic tabs that appear based on active add-ons.
- **CPT & Taxonomy Abstractions** — Clean, declarative base classes for registering custom post types and taxonomies.
- **Minimal Footprint** — Nothing loads unless it's needed. The framework stays out of WordPress's regular flow to keep page loads fast.
- **PHP 8.0+** — Takes advantage of typed properties, enums, named arguments, and union types.

---

## 📋 Requirements

- **PHP** 8.0 or higher
- **WordPress** 6.4 or higher
- **Composer** (for autoloading)
- **Node.js** 18+ and npm (for building the React admin)

---

## 🚀 Getting Started

### 1. Clone or Download

```bash
git clone https://github.com/rbcreativesolutions/pluginforge.git
cd pluginforge
```

### 2. Install Dependencies

```bash
# PHP autoloader
composer install

# JavaScript dependencies (for the admin panel)
cd admin
npm install
npm run build
cd ..
```

### 3. Install in WordPress

Copy or symlink the `pluginforge` directory into your WordPress `wp-content/plugins/` directory, then activate it from the WordPress admin.

---

## 📁 Project Structure

```
pluginforge/
├── pluginforge.php              # Main plugin file (minimal bootstrap)
├── composer.json                # PSR-4 autoloading
├── uninstall.php                # Clean uninstall handler
│
├── src/                         # Core framework (RBCS\PluginForge)
│   ├── Core/                    # Plugin orchestrator, activator, deactivator, assets
│   ├── Addon/                   # Add-on system (interface, abstract, manager, proxy)
│   ├── Admin/                   # Admin page, REST API, settings manager
│   ├── CPT/                     # Custom post type & taxonomy abstractions
│   ├── Connection/              # Platform connection abstractions
│   └── Traits/                  # Reusable traits (HasSettings, Hookable, Renderable)
│
├── addons/                      # Add-on directory (each add-on is a subfolder)
├── admin/                       # React admin app source
├── templates/                   # PHP template files
├── languages/                   # i18n translation files
└── assets/                      # Static assets (CSS, JS, images)
```

---

## 🧩 Creating an Add-on

Add-ons are the primary way to extend PluginForge. Each add-on is a self-contained folder inside `addons/`.

### 1. Create the add-on directory

```
addons/
└── my-feature/
    ├── addon.json
    ├── src/
    │   └── MyFeatureAddon.php
    └── assets/        (optional)
```

### 2. Define `addon.json`

```json
{
    "id": "my-feature",
    "name": "My Feature",
    "description": "Does something awesome.",
    "version": "1.0.0",
    "author": "Your Name",
    "default_active": false,
    "dependencies": [],
    "namespace": "RBCS\\SocialPillar\\Addons\\MyFeature",
    "entry_class": "RBCS\\SocialPillar\\Addons\\MyFeature\\MyFeatureAddon"
}
```

### 3. Create the add-on class

```php
<?php

declare(strict_types=1);

namespace RBCS\SocialPillar\Addons\MyFeature;

use RBCS\PluginForge\Addon\AddonAbstract;

class MyFeatureAddon extends AddonAbstract
{
    public function getId(): string
    {
        return 'my-feature';
    }

    public function boot(): void
    {
        // Register hooks, CPTs, shortcodes, blocks, etc.
        // This only runs when the add-on is active.
    }

    public function activate(): void
    {
        // First-time activation: create tables, set defaults, etc.
    }

    public function getSettingsSchema(): array
    {
        return [
            [
                'id'      => 'enabled',
                'type'    => 'toggle',
                'label'   => 'Enable Feature',
                'default' => true,
            ],
        ];
    }

    public function registerRoutes(): void
    {
        // Register REST API endpoints for this add-on.
    }
}
```

### 4. Activate it

Go to **PluginForge → Add-ons** in the WordPress admin and toggle your add-on on.

---

## 🔌 Creating a Connection

Connections provide a consistent interface for communicating with external platforms.

```php
<?php

declare(strict_types=1);

namespace RBCS\SocialPillar\Connections;

use RBCS\PluginForge\Connection\AuthType;
use RBCS\PluginForge\Connection\ConnectionAbstract;
use RBCS\PluginForge\Connection\ConnectionResponse;

class FacebookConnection extends ConnectionAbstract
{
    public function getId(): string
    {
        return 'facebook';
    }

    public function getName(): string
    {
        return 'Facebook';
    }

    public function getAuthType(): AuthType
    {
        return AuthType::OAuth2;
    }

    public function getAuthFields(): array
    {
        return [
            ['id' => 'app_id',     'type' => 'text',     'label' => 'App ID'],
            ['id' => 'app_secret', 'type' => 'password', 'label' => 'App Secret'],
        ];
    }

    public function authenticate(array $credentials): bool
    {
        // Implement OAuth2 flow...
        $this->storeCredentials($credentials);
        return true;
    }

    public function refreshToken(): bool
    {
        // Implement token refresh...
        return true;
    }

    public function request(string $method, string $endpoint, array $data = []): ConnectionResponse
    {
        $credentials = $this->getStoredCredentials();
        $baseUrl = 'https://graph.facebook.com/v18.0';

        return $this->httpRequest($method, "{$baseUrl}/{$endpoint}", [
            'headers' => [
                'Authorization' => 'Bearer ' . ($credentials['access_token'] ?? ''),
            ],
            'body' => $data,
        ]);
    }
}
```

Register connections in your add-on's `boot()` method:

```php
public function boot(): void
{
    $connectionManager = $this->plugin->getConnectionManager();
    $connectionManager->register(new FacebookConnection());
}
```

---

## 📝 Creating Custom Post Types

```php
<?php

namespace RBCS\SocialPillar\CPT;

use RBCS\PluginForge\CPT\CPTAbstract;

class SocialPostCPT extends CPTAbstract
{
    protected string $slug = 'pf_social_post';
    protected string $singular = 'Social Post';
    protected string $plural = 'Social Posts';
    protected string $icon = 'dashicons-share';
    protected array $supports = ['title', 'editor', 'thumbnail'];
}
```

Register in your add-on's `boot()`:

```php
$cpt = new SocialPostCPT();
$cpt->register();
```

---

## 🛠 Development

### Building the Admin Panel

```bash
cd admin
npm run start   # Development with hot reload
npm run build   # Production build
```

### Code Quality

```bash
composer phpcs     # PHP CodeSniffer
composer phpstan   # Static analysis
composer test      # PHPUnit tests
```

---

## 🔒 Security

- All REST endpoints require `manage_options` capability
- Sensitive credentials (API keys, tokens) are encrypted using OpenSSL before storage
- All user inputs are sanitized and validated
- Nonce verification on all admin actions

---

## 📄 License

GPL v2 or later. See [LICENSE](LICENSE) for details.

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📮 Support

- **Issues:** [GitHub Issues](https://github.com/rbcreativesolutions/pluginforge/issues)
- **Website:** [rbcreativesolutions.com](https://rbcreativesolutions.com)
