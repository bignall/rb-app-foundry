# Contributing to RB App Foundry

Thank you for your interest in contributing! RB App Foundry is the framework that other plugins depend on, so stability and backwards compatibility are top priorities. This document covers how to report issues, request features, submit code, and — for internal contributors — the day-to-day dev workflow.

---

## Table of Contents

- [Reporting Bugs](#reporting-bugs)
- [Requesting Features](#requesting-features)
- [Development Setup](#development-setup)
- [Branch & Commit Conventions](#branch--commit-conventions)
- [Code Standards](#code-standards)
- [Backwards Compatibility](#backwards-compatibility)
- [Submitting a Pull Request](#submitting-a-pull-request)
- [What to Expect](#what-to-expect)

---

## Reporting Bugs

Use the [Bug Report](.github/ISSUE_TEMPLATE/bug-report.yml) issue template on GitHub. Before filing, please:

- Search existing issues to avoid duplicates
- Confirm you are running the latest version of RB App Foundry
- Note which dependent plugins (e.g. RB SocialPillar) are active — some issues only appear with a specific add-on loaded

Security vulnerabilities should be reported privately — see the [security contact](.github/ISSUE_TEMPLATE/config.yml) in the issue template chooser rather than filing a public issue.

---

## Requesting Features

Use the [Feature Request](.github/ISSUE_TEMPLATE/feature-request.yml) issue template. Because this is a framework, new features are evaluated on whether they benefit multiple dependent plugins, not just one use case. Proposals for new interfaces, hook contracts, or REST endpoints are welcome.

---

## Development Setup

### Requirements

- PHP 8.0+
- WordPress 6.4+
- Composer
- Node.js 18+ and npm (for rebuilding the React admin panel)
- A local WordPress environment — [LocalWP](https://localwp.com/) is recommended

### Install

```bash
git clone https://github.com/bignall/app-forge.git wp-content/plugins/rb-app-foundry
cd wp-content/plugins/rb-app-foundry
composer install
```

Activate **RB App Foundry** in WordPress admin. Any dependent plugin (e.g. RB SocialPillar) can then be installed and activated.

### Build the React admin panel

The admin panel is a `@wordpress/scripts`-based React app. The compiled assets in `admin/build/` are committed to the repository so dependent installations do not require Node. Only rebuild when making UI changes.

```bash
cd admin
npm install
npm run start   # Watch mode (development)
npm run build   # Production build — commit the result
```

### Build commands

| Command | What it does |
|---|---|
| `bash bin/build.sh` | Runs `composer install` |
| `bash bin/build.sh --with-js` | Composer install + React production build |
| `bash bin/package.sh` | Creates a production ZIP in `dist/` |

> **Note:** `bin/deploy-local.sh` lives in RB SocialPillar and deploys both plugins together.

---

## Branch & Commit Conventions

### Branches

| Pattern | Purpose |
|---|---|
| `main` | Stable, releasable code — never commit directly |
| `develop` | Integration branch for features in progress |
| `feature/<short-description>` | New features |
| `fix/<short-description>` | Bug fixes |
| `docs/<short-description>` | Documentation only |
| `chore/<short-description>` | Build, CI, dependencies — no functional change |

Branch off `develop` for features and fixes. Branch off `main` only for urgent hotfixes, and merge back to both `main` and `develop`.

### Commits

Write commits in the imperative mood, present tense:

```
Add ConnectionInterface::refreshToken() method
Fix AES-256-CBC key derivation for sites without AUTH_KEY
Update React admin to show inactive add-on descriptions
```

- Keep the subject line under 72 characters
- Use the body (separated by a blank line) for context — *why*, not just *what*
- Reference GitHub issues with `Fixes #123` or `Refs #123` in the body

---

## Code Standards

### PHP

This project follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) with PHP 8.0+ typed properties and constructor promotion.

Every PHP file must begin with:

```php
<?php

declare(strict_types=1);

namespace RBCS\AppFoundry\...;

defined( 'ABSPATH' ) || exit;
```

Run the linter before pushing:

```bash
composer lint       # or: ./vendor/bin/phpcs
```

Fix auto-fixable violations:

```bash
composer lint:fix   # or: ./vendor/bin/phpcbf
```

### Key rules

- All global variables must be prefixed with `appfoundry_` or `rb_app_foundry_`
- Use `wp_parse_url()` instead of `parse_url()`
- Use `sanitize_*()` / `esc_*()` at all input and output boundaries
- Add `/* translators: ... */` comments before every translated string that contains placeholders
- Every class file must include an `ABSPATH` guard

### JavaScript / React

- Follow the existing component structure in `admin/src/`
- Commit the production build (`admin/build/`) alongside source changes
- Keep the admin panel stateless where possible — it reads from REST endpoints and posts back

---

## Backwards Compatibility

RB App Foundry is a framework. Dependent plugins rely on its interfaces, hooks, and REST API contracts. Any change that could break a dependent plugin requires:

1. A deprecation notice (at minimum one minor version before removal)
2. An updated entry in `docs/developer/overview.md`
3. A note in the PR description explaining the migration path

**Do not rename or remove public interface methods, action/filter hooks, or REST endpoints without going through deprecation first.**

If you are unsure whether a change is breaking, open an issue or PR for discussion before proceeding.

---

## Submitting a Pull Request

1. Fork the repository and create your branch from `develop`
2. Make your changes, following the code standards above
3. If the React admin is affected, rebuild and commit `admin/build/`
4. Write or update any relevant documentation in `docs/`
5. Test your changes with at least one dependent plugin active (e.g. RB SocialPillar)
6. Push your branch and open a PR against `develop` (not `main`)
7. Fill out the PR description — what changed, why, any backwards-compatibility implications, and how you tested it

PRs that add new framework interfaces or REST endpoints should update `docs/developer/overview.md` and — where applicable — the corresponding docs in RB SocialPillar.

---

## What to Expect

- **Bug reports:** acknowledged within a few business days
- **Feature requests:** reviewed and labelled; framework changes get extra scrutiny given downstream impact
- **Pull requests:** reviewed as bandwidth allows; small, focused PRs move faster than large ones
- **Response time:** this is a small team — please be patient

If a PR is not the right fit, feedback will be given so you can adjust or close gracefully.

---

## Publishing a Release

> **Maintainers only.** This is the checklist for cutting an official release.

1. **Update CHANGELOG.md** — Move everything under `[Unreleased]` to a new versioned section:
   ```markdown
   ## [0.2.0] - YYYY-MM-DD
   ### Added
   - ...
   ```
   Then update the comparison links at the bottom of the file:
   ```markdown
   [Unreleased]: https://github.com/bignall/app-forge/compare/v0.2.0...HEAD
   [0.2.0]: https://github.com/bignall/app-forge/compare/v0.1.0...v0.2.0
   ```

2. **Bump the version** in `rb-app-foundry.php` (the `Version:` plugin header) and in `readme.txt` (`Stable tag:`).

3. **Rebuild the React admin** if any JS changes are included:
   ```bash
   cd admin && npm run build
   ```
   Commit the updated `admin/build/` files.

4. **Commit**:
   ```bash
   git add rb-app-foundry.php readme.txt CHANGELOG.md
   git commit -m "Release v0.2.0"
   ```

5. **Tag and push**:
   ```bash
   git tag v0.2.0
   git push origin main --tags
   ```

GitHub Actions picks up the tag, builds the ZIP (including a fresh React build), and creates the GitHub Release automatically with the changelog section as the release body and the ZIP attached as a downloadable asset.

Pre-release tags (`v0.2.0-beta.1`) are automatically marked as pre-release on GitHub.

---

## License

By contributing, you agree that your contributions will be licensed under the [GPL v2 or later](LICENSE).
