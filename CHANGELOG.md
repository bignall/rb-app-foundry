# Changelog

All notable changes to RB App Foundry will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [0.1.0] - 2026-03-11

### Added

- Initial release of the RB App Foundry framework
- Core plugin orchestrator with singleton pattern
- Add-on architecture with discovery, activation/deactivation, and dependency management
- `AddonInterface` for creating add-ons
- `InactiveAddonProxy` for lightweight metadata display of inactive add-ons
- Connection abstraction layer with `ConnectionInterface` and `ConnectionAbstract`
- `AuthType` enum supporting OAuth2, API Key, Bearer, Basic, Webhook, and Custom auth
- `ConnectionResponse` standardized response object
- `ConnectionManager` registry for all platform connections
- Encrypted credential storage using AES-256-CBC
- CPT and Taxonomy abstract base classes for declarative registration
- React-based admin panel with dynamic tabs per active add-on
- REST API endpoints for settings, add-on, and connection management under `/rb-app-foundry/v1/`
- `SettingsManager` with dot-notation access and caching
- `HasSettings`, `Hookable`, and `Renderable` traits
- Conditional asset loading (only on plugin admin pages)
- `@wordpress/scripts` build tooling for the admin panel
- PHP 8.0+ requirement with typed properties and enums
- PSR-4 autoloading via Composer
- WordPress 6.4+ requirement
- Minimum requirements check with admin notices
- Clean uninstall handler with opt-in data deletion
- GPL v2 license

---

[Unreleased]: https://github.com/bignall/app-forge/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/bignall/app-forge/releases/tag/v0.1.0
