# RB App Foundry

A WordPress framework plugin with an add-on architecture, connection management, encrypted credential storage, and a React admin panel.

Built by [RB Creative Solutions LLC](https://rbcreativesolutions.net).

---

## What is RB App Foundry?

RB App Foundry is a **WordPress framework plugin** that other plugins depend on. It provides the scaffolding — add-on discovery, connection management, credential encryption, REST API infrastructure — so dependent plugins can focus on their own features.

### What it provides

- **Add-on Architecture** — Self-contained feature modules. Inactive add-ons load zero PHP.
- **Connection Abstraction** — A consistent interface for external API integrations with AES-256-CBC credential encryption and token refresh.
- **React Admin Panel** — `@wordpress/scripts`-based settings UI with dynamic tabs per active add-on.
- **CPT & Taxonomy Base Classes** — Declarative registration — define properties, call `register()`.
- **REST API** — Framework management endpoints under `/rb-app-foundry/v1/`.
- **PHP 8.0+** — Typed properties, enums, readonly constructor promotion.

---

## Requirements

- **WordPress** 6.4 or higher
- **PHP** 8.0 or higher
- **Composer** (for autoloading)
- **Node.js** 18+ and npm (for rebuilding the React admin, development only)

---

## Installation

Install from the [WordPress Plugin Directory](https://wordpress.org/plugins/rb-app-foundry/) or upload `rb-app-foundry.zip` manually. Activate before any plugin that depends on it.

For local development, see `docs/local-development-setup.md`.

---

## Project Structure

```
rb-app-foundry/
├── rb-app-foundry.php        # Bootstrap: constants, Composer autoload, plugin init
├── composer.json             # PSR-4 autoloading
├── uninstall.php             # Clean uninstall handler
│
├── src/                      # Core framework — namespace RBCS\AppFoundry
│   ├── Core/                 # Plugin singleton, Assets
│   ├── Addon/                # AddonInterface, AddonManager, InactiveAddonProxy
│   ├── Admin/                # AdminPage, RestAPI
│   ├── CPT/                  # CPTAbstract, TaxonomyAbstract
│   ├── Connection/           # ConnectionInterface, ConnectionAbstract, ConnectionResponse, AuthType
│   └── Traits/               # Hookable
│
├── addons/                   # Framework's own add-ons (example add-on included)
├── admin/                    # React admin app source (@wordpress/scripts)
├── admin/build/              # Compiled admin assets (committed)
├── languages/                # i18n .pot file
└── docs/                     # Documentation
    ├── user/                 # End-user docs
    └── developer/            # Developer docs
```

---

## Developer Documentation

Full developer docs (boot sequence, REST API, hooks, add-on guide with code examples) live alongside the primary consumer plugin:

| Doc | Link |
|-----|------|
| **Developer overview** (this repo) | [docs/developer/overview.md](docs/developer/overview.md) |
| Architecture & boot sequence | [RB SocialPillar — architecture.md](https://github.com/bignall/social-pillar/blob/main/docs/developer/architecture.md) |
| REST API reference | [RB SocialPillar — rest-api.md](https://github.com/bignall/social-pillar/blob/main/docs/developer/rest-api.md) |
| Hooks reference | [RB SocialPillar — hooks.md](https://github.com/bignall/social-pillar/blob/main/docs/developer/hooks.md) |
| Building an add-on or connection | [RB SocialPillar — building-an-addon.md](https://github.com/bignall/social-pillar/blob/main/docs/developer/building-an-addon.md) |

---

## Quick Example — Add-on

```php
<?php
// addons/my-feature/src/MyFeatureAddon.php

declare(strict_types=1);

namespace MyPlugin\Addons\MyFeature;

defined('ABSPATH') || exit;

use RBCS\AppFoundry\Addon\AddonInterface;
use RBCS\AppFoundry\Core\Plugin;

class MyFeatureAddon implements AddonInterface
{
    public function __construct(private readonly Plugin $framework) {}

    public function getId(): string           { return 'my-feature'; }
    public function getName(): string         { return 'My Feature'; }
    public function getDescription(): string  { return 'Does something.'; }
    public function getVersion(): string      { return '1.0.0'; }
    public function isActiveByDefault(): bool { return false; }
    public function getDependencies(): array  { return []; }
    public function getSettingsSchema(): array { return []; }
    public function activate(): void   {}
    public function deactivate(): void {}
    public function registerRoutes(): void {}

    public function boot(): void
    {
        // Register hooks, CPTs, connections — only runs when active
        add_action('init', [$this, 'registerCPT']);
    }
}
```

```json
// addons/my-feature/addon.json
{
    "id": "my-feature",
    "name": "My Feature",
    "description": "Does something.",
    "version": "1.0.0",
    "namespace": "MyPlugin\\Addons\\MyFeature",
    "entry_class": "MyPlugin\\Addons\\MyFeature\\MyFeatureAddon",
    "default_active": false,
    "dependencies": []
}
```

---

## Development

### Build Admin Panel

```bash
cd admin
npm install
npm run start   # Watch mode
npm run build   # Production
```

### Deploy to LocalWP

See `docs/local-development-setup.md` (or use RB SocialPillar's `bin/deploy-local.sh` which builds both).

---

## License

GPL v2 or later. See [LICENSE](LICENSE) for details.

---

## Support

- **Issues:** [GitHub Issues](https://github.com/bignall/app-forge/issues)
- **Website:** [rbcreativesolutions.net](https://rbcreativesolutions.net)
