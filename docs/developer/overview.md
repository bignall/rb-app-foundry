# Developer Overview

RB App Foundry is a WordPress framework plugin that other plugins depend on. It provides:

| Feature | What it does |
|---------|-------------|
| **Add-on system** | Discover, load, activate, and deactivate feature modules. Inactive add-ons load zero PHP. |
| **Connection system** | A consistent interface for external API integrations with encrypted credential storage. |
| **CPT / Taxonomy abstractions** | Declarative base classes — define properties, call `register()`. |
| **React admin panel** | `@wordpress/scripts`-based settings UI with dynamic tabs per active add-on. |
| **REST API** | Framework management endpoints under `/rb-app-foundry/v1/`. |
| **Hookable trait** | Auto-binding `addAction()` / `addFilter()` helpers. |

---

## Full Developer Documentation

The detailed guides — boot sequence, data model, REST reference, hooks, and a complete add-on + connection walkthrough with code examples — live in the **RB SocialPillar** repository, since SocialPillar is the primary consumer of the framework:

| Doc | Link |
|-----|------|
| Architecture & boot sequence | [architecture.md](https://github.com/bignall/social-pillar/blob/main/docs/developer/architecture.md) |
| Data model (CPTs, meta, taxonomies) | [data-model.md](https://github.com/bignall/social-pillar/blob/main/docs/developer/data-model.md) |
| REST API reference (both plugins) | [rest-api.md](https://github.com/bignall/social-pillar/blob/main/docs/developer/rest-api.md) |
| Hooks reference | [hooks.md](https://github.com/bignall/social-pillar/blob/main/docs/developer/hooks.md) |
| Building an add-on or connection | [building-an-addon.md](https://github.com/bignall/social-pillar/blob/main/docs/developer/building-an-addon.md) |

---

## Quick Reference

### Key Interfaces

| Interface / Class | Package | Purpose |
|-------------------|---------|---------|
| `AddonInterface` | `RBCS\AppFoundry\Addon` | Contract for all add-ons |
| `ConnectionInterface` | `RBCS\AppFoundry\Connection` | Contract for all connections |
| `ConnectionAbstract` | `RBCS\AppFoundry\Connection` | Base class — implement this for connections |
| `CPTAbstract` | `RBCS\AppFoundry\CPT` | Base class for custom post types |
| `TaxonomyAbstract` | `RBCS\AppFoundry\CPT` | Base class for taxonomies |
| `ConnectionResponse` | `RBCS\AppFoundry\Connection` | Immutable HTTP response value object |
| `AuthType` | `RBCS\AppFoundry\Connection` | Enum: `OAuth2`, `APIKey`, `Bearer`, `Basic`, `Webhook`, `Custom` |

### Getting Framework Instances

```php
// After plugins_loaded — from anywhere
$framework  = \RBCS\AppFoundry\Core\Plugin::getInstance();
$addons     = $framework->getAddonManager();
$connections = $framework->getConnectionManager();

// Safe hook point
add_action('appfoundry_loaded', function(\RBCS\AppFoundry\Core\Plugin $framework): void {
    // Framework is fully booted here
});
```

### `addon.json` Manifest

```json
{
  "id": "my-addon",
  "name": "My Add-on",
  "description": "One-liner description",
  "version": "1.0.0",
  "namespace": "My\\Addon\\Namespace",
  "entry_class": "My\\Addon\\Namespace\\MyAddonClass",
  "default_active": false,
  "dependencies": []
}
```

The framework scans for `addon.json` files in:
1. `{rb-app-foundry}/addons/`
2. Any path registered via `AddonManager::addPath()` before `init` priority 0

### Registering Additional Add-on Paths

Call this at `plugins_loaded` (priority 20 or later, after the framework boots):

```php
add_action('plugins_loaded', function(): void {
    if (!class_exists(\RBCS\AppFoundry\Core\Plugin::class)) {
        return; // RB App Foundry not active
    }

    \RBCS\AppFoundry\Core\Plugin::getInstance()
        ->getAddonManager()
        ->addPath(MY_PLUGIN_PATH . 'addons/');
}, 20);
```

### Minimal Add-on Class

```php
<?php

declare(strict_types=1);

namespace My\Addon\Namespace;

defined('ABSPATH') || exit;

use RBCS\AppFoundry\Addon\AddonInterface;
use RBCS\AppFoundry\Core\Plugin;

class MyAddonClass implements AddonInterface
{
    public function __construct(private readonly Plugin $framework) {}

    public function getId(): string          { return 'my-addon'; }
    public function getName(): string        { return 'My Add-on'; }
    public function getDescription(): string { return 'Does something.'; }
    public function getVersion(): string     { return '1.0.0'; }
    public function isActiveByDefault(): bool { return false; }
    public function getDependencies(): array { return []; }
    public function getSettingsSchema(): array { return []; }

    public function boot(): void
    {
        // Register hooks, CPTs, connections, etc.
        // Only called when add-on is active.
    }

    public function activate(): void   { /* first-time setup */ }
    public function deactivate(): void { /* cleanup, but preserve data */ }
    public function registerRoutes(): void { /* REST endpoints during rest_api_init */ }
}
```

### Minimal Connection Class

```php
<?php

declare(strict_types=1);

namespace My\Addon\Namespace;

defined('ABSPATH') || exit;

use RBCS\AppFoundry\Connection\ConnectionAbstract;
use RBCS\AppFoundry\Connection\ConnectionResponse;
use RBCS\AppFoundry\Connection\AuthType;

class MyConnection extends ConnectionAbstract
{
    public function getId(): string   { return 'my-service'; }
    public function getName(): string { return 'My Service'; }

    public function getAuthType(): AuthType { return AuthType::APIKey; }

    public function getAuthFields(): array
    {
        return [['id' => 'api_key', 'type' => 'password', 'label' => 'API Key', 'required' => true]];
    }

    public function authenticate(array $credentials): bool
    {
        if (empty($credentials['api_key'])) return false;
        $this->storeCredentials($this->encryptSensitiveFields($credentials));
        return true;
    }

    public function isConnected(): bool
    {
        return !empty($this->getStoredCredentials()['api_key']);
    }

    public function refreshToken(): bool { return $this->isConnected(); }

    public function request(string $method, string $endpoint, array $data = []): ConnectionResponse
    {
        $creds = $this->decryptSensitiveFields($this->getStoredCredentials());
        return $this->httpRequest($method, 'https://api.myservice.com' . $endpoint, [
            'headers' => ['Authorization' => 'Bearer ' . $creds['api_key']],
            'body'    => $data,
        ]);
    }
}
```

Register the connection in your add-on's `boot()`:

```php
public function boot(): void
{
    $this->framework->getConnectionManager()->register(new MyConnection());
}
```

---

## REST API

AppFoundry exposes its own management endpoints at `/wp-json/rb-app-foundry/v1/`. Full reference: [rest-api.md](https://github.com/bignall/social-pillar/blob/main/docs/developer/rest-api.md#rb-app-foundry----rb-app-foundryv1).

**Summary:**

| Endpoint | Purpose |
|----------|---------|
| `GET /addons` | List all add-ons |
| `POST /addons/<id>/activate` | Activate add-on |
| `POST /addons/<id>/deactivate` | Deactivate add-on |
| `GET /connections` | List connections and status |
| `POST /connections/<id>/credentials` | Save credentials |
| `DELETE /connections/<id>/credentials` | Disconnect |
| `GET /connections/<id>/oauth-url` | Get OAuth dialog URL |
| `GET /connections/<id>/oauth-callback` | Handle OAuth redirect (public) |
| `GET /health` | Health check (public) |

---

## Hooks

Full reference: [hooks.md](https://github.com/bignall/social-pillar/blob/main/docs/developer/hooks.md)

| Hook | Type | Fired when |
|------|------|-----------|
| `appfoundry_loaded` | action | Framework fully booted |
| `appfoundry_addon_activated` | action | Add-on activated |
| `appfoundry_addon_deactivated` | action | Add-on deactivated |
| `appfoundry_connection_registered` | action | Connection registered |
| `appfoundry_connection_disconnected` | action | Connection disconnected |
| `appfoundry_clear_scheduled_events` | action | Plugin deactivation |
