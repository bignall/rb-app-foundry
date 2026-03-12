# Getting Started with RB App Foundry

RB App Foundry is a WordPress framework plugin. On its own it provides a connection management panel and an add-on system. Its primary purpose is to be a required dependency for other plugins — currently **RB SocialPillar** — that build on top of it.

If you're a site owner, you're here because a plugin you installed requires it. If you're a developer, see the [Developer Overview](../developer/overview.md).

---

## Requirements

- WordPress 6.4 or higher
- PHP 8.0 or higher

---

## Installation

1. Go to **Plugins → Add New Plugin** in your WordPress admin
2. Search for **RB App Foundry**
3. Click **Install Now**, then **Activate**

RB App Foundry must be activated **before** any plugin that depends on it (e.g. RB SocialPillar). WordPress 6.5+ handles this automatically if you install the dependent plugin first — it will prompt you to install RB App Foundry.

---

## What You'll See After Activation

A new **RB App Foundry** menu item appears in your WordPress admin sidebar with two sub-pages:

### Connections

Manage credentials for external services. Each plugin that uses RB App Foundry registers its connections here — for example, RB SocialPillar registers a Facebook connection and an optional Claude (AI) connection.

From the Connections panel you can:
- Enter API keys or app credentials
- Click through OAuth flows (e.g. "Connect with Facebook")
- View connection status (connected / disconnected)
- Disconnect or remove individual accounts

### Add-ons

View and manage the add-ons registered by all active plugins. Add-ons can be activated or deactivated individually. Inactive add-ons load zero PHP — they have no performance impact.

---

## That's It

For site owners, there's nothing else to configure in RB App Foundry itself. All feature configuration happens in the plugin that depends on it (e.g. **RB SocialPillar → Social Posts**).

If a connection isn't working, this is the place to check that credentials are saved and the status shows as connected.

---

## Related

- [RB SocialPillar — Getting Started](https://github.com/bignall/social-pillar/blob/main/docs/user/getting-started.md)
- [Developer Overview](../developer/overview.md)
