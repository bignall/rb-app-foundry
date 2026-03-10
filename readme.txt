=== PluginForge ===
Contributors: rbcreativesolutions
Tags: framework, developer tools, api connections, add-ons
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight WordPress plugin framework with connection management, encrypted credential storage, and React admin panels.

== Description ==

PluginForge is a developer framework for building extensible WordPress plugins. It provides:

* **Connection management** — A unified admin UI for configuring API connections (OAuth2, API keys, webhooks). Connections are displayed in a clean card-based interface with connect/disconnect flows.
* **Encrypted credential storage** — API keys and OAuth tokens are stored encrypted in the WordPress database using AES-256-CBC.
* **Add-on architecture** — Drop-in add-ons extend the framework with new connections and features. Add-ons are auto-discovered from registered directories.
* **React admin panels** — Built with `@wordpress/components` for a native WordPress look and feel.
* **PSR-4 autoloading** — Modern PHP 8.0+ architecture with Composer autoloading.

PluginForge is the required foundation for [SocialPillar](https://wordpress.org/plugins/socialpillar/), a social media management plugin. It is designed to be useful independently for WordPress developers building API-integrated plugins.

== Installation ==

1. Upload the `pluginforge` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. PluginForge appears as **PluginForge** in your admin sidebar
4. Configure connections under **PluginForge → Connections**

If you are installing PluginForge as a dependency for SocialPillar, WordPress 6.5+ will prompt you to install it automatically when you activate SocialPillar.

== Frequently Asked Questions ==

= Who is PluginForge for? =

PluginForge serves two audiences:

1. **SocialPillar users** — It is a required dependency. You don't need to configure anything in PluginForge directly; SocialPillar's setup guides you through it.
2. **WordPress developers** — PluginForge provides a clean foundation for building plugins that integrate with external APIs and need a connection management UI.

= Does PluginForge do anything on its own? =

It provides a Connections admin page where you can manage API connections. Without add-ons (like SocialPillar's Facebook and Claude connections), it displays an empty connections list. Its value is as a framework for plugins built on top of it.

= How are credentials secured? =

API keys, secrets, and OAuth tokens are encrypted with AES-256-CBC using a key derived from your WordPress `AUTH_KEY` and `SECURE_AUTH_KEY` salts before being stored in the database.

= Can I build my own plugin on PluginForge? =

Yes. Extend `ConnectionAbstract` to add new API connections, and extend `AddonAbstract` to create add-ons. See the [developer documentation](https://github.com/rbcreativesolutions/pluginforge) for details.

== Privacy ==

PluginForge itself does not communicate with any external services and does not collect any data.

Individual connections registered by add-ons (such as SocialPillar's Facebook or Claude connections) may communicate with external APIs when configured and used. Refer to each add-on's documentation for specifics.

Credential data stored by PluginForge is kept in your WordPress database, encrypted, and is never transmitted anywhere by PluginForge itself.

== Changelog ==

= 0.1.0 =
* Initial release
* Connection management framework with OAuth2 and API key support
* Add-on auto-discovery architecture
* Encrypted credential storage (AES-256-CBC)
* React admin panel using @wordpress/components
* PSR-4 autoloading via Composer

== Upgrade Notice ==

= 0.1.0 =
Initial release.
