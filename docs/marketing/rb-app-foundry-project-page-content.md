# RB App Foundry — Project Page Content

Content for the rbcreativesolutions.net/projects/rb-app-foundry/ project page,
organized by CPT field. Mirrors the True Forward page structure.

---

## Hero

**Heading**
RB App Foundry

**Subheading**
A WordPress framework plugin for building add-on-based plugins with built-in connection management, encrypted credential storage, and a React admin panel. The foundation RB SocialPillar runs on.

**CTA Buttons**
- Primary: "Download Free" → https://wordpress.org/plugins/rb-app-foundry/
- Secondary: "View Source" → https://github.com/bignall/app-forge

**Support Links**
- Documentation → /projects/rb-app-foundry/docs/
- GitHub Issues → https://github.com/bignall/app-forge/issues

---

## Problem / Why Section

**Heading**
Why a framework plugin?

**Content**
Every WordPress plugin that connects to an external service ends up solving the same problems: store credentials securely, handle OAuth flows, build a settings UI, and structure features so they don't conflict with each other. Most plugins solve these from scratch — badly.

RB App Foundry extracts that scaffolding into a shared, reusable layer. Plugins that depend on it get AES-256-CBC credential encryption, a consistent OAuth callback system, and a React admin panel that adapts to whatever add-ons are active — without writing any of that themselves.

The result is that dependent plugins (like RB SocialPillar) can focus entirely on their own features. The plumbing is already done.

---

## Feature Cards

**Card 1**
Heading: Add-on Architecture
Content: Features live in self-contained add-ons, each with its own lifecycle — activate, boot, deactivate. Inactive add-ons load zero PHP. No performance penalty for features you're not using.

**Card 2**
Heading: Connection Management
Content: A consistent interface for any external API. Define your auth fields, implement authenticate() and request(), and the framework handles the credentials UI, storage, and OAuth callback routing automatically.

**Card 3**
Heading: Encrypted Credential Storage
Content: API keys, OAuth tokens, and app secrets are encrypted with AES-256-CBC before being written to the database. Sensitive fields are identified and encrypted automatically — no manual handling required.

**Card 4**
Heading: OAuth2 Built In
Content: The framework registers the OAuth callback endpoint and routes it to the right connection. Implement getOAuthUrl() and handleCallback() in your connection class — the rest is handled.

**Card 5**
Heading: React Admin Panel
Content: A @wordpress/scripts-based admin UI with dynamic tabs that appear based on which add-ons are active. Connections, settings, and add-on management all render from schema — no custom React needed for standard fields.

**Card 6**
Heading: CPT & Taxonomy Base Classes
Content: CPTAbstract and TaxonomyAbstract let you register custom post types and taxonomies by setting properties. No repeated register_post_type() boilerplate — just define slug, singular, plural, supports, and call register().

**Card 7**
Heading: PSR-4 Autoloading
Content: Each add-on's namespace is registered automatically when the add-on is discovered. No manual require statements. Classes load on demand via Composer.

**Card 8**
Heading: PHP 8.0+
Content: Typed properties, readonly constructor promotion, enums (AuthType), named arguments. Modern PHP throughout — no legacy compatibility shims.

---

## How It Works Cards

**Card 1**
Step: 1
Heading: Install the framework
Content: Install and activate RB App Foundry as a WordPress plugin. It provides the admin panel, connection management system, and add-on infrastructure — but no features of its own.

**Card 2**
Step: 2
Heading: Build your plugin
Content: Your plugin registers its addons/ directory with the AddonManager at plugins_loaded. The framework discovers addon.json manifests, loads active add-ons, and boots them at init.

**Card 3**
Step: 3
Heading: Implement your add-ons
Content: Each add-on implements AddonInterface — boot(), activate(), deactivate(), registerRoutes(), getSettingsSchema(). For connections, extend ConnectionAbstract and implement authenticate() and request().

**Card 4**
Step: 4
Heading: Let the framework handle the rest
Content: The React admin panel renders your connection's auth fields automatically. OAuth callbacks route to your handleCallback() method. Credentials are encrypted on store and decrypted on retrieval.

---

## Quick Setup (numbered list)

1. Install **RB App Foundry** from WordPress.org and activate it
2. In your plugin, call `AddonManager::addPath()` at `plugins_loaded` priority 20 to register your addons/ directory
3. Create an `addon.json` manifest and an entry class implementing `AddonInterface`
4. For connections: extend `ConnectionAbstract`, implement `getAuthFields()`, `authenticate()`, and `request()`
5. Activate your add-on from **RB App Foundry → Add-ons** — credentials UI appears automatically in **Connections**

Full walkthrough: [Building an Add-on →](https://github.com/bignall/social-pillar/blob/main/docs/developer/building-an-addon.md)

---

## Roadmap / Coming Soon

- More built-in connection types (webhooks, SOAP)
- Scheduled task / WP-Cron management per add-on
- Front-end asset management helpers
- CLI tooling for scaffolding new add-ons
- Expanded settings field types (repeater, color picker, media)

---

## Legal / Third-Party Notice

RB App Foundry itself makes no external connections. Third-party API communication occurs only through connections registered by dependent plugins, and only when the user triggers an action. Credential encryption uses OpenSSL (AES-256-CBC) with a key derived from WordPress security keys.
