# Privacy Policy — RB App Foundry

**Last updated:** 2026-03-13

> This policy covers the **RB App Foundry** WordPress plugin. It describes what data the plugin stores and what controls you have over that data. This policy does not cover the website at rbcreativesolutions.net, which has its own privacy policy.

---

## Who We Are

RB App Foundry is a WordPress plugin framework developed by RB Creative Solutions. It is distributed free of charge under the GPL v2 license. You can contact us at: **rosina@rbcreativesolutions.net**

---

## What Data the Plugin Stores

RB App Foundry stores data in your WordPress site's own database. **No data is ever transmitted to RB Creative Solutions' servers.**

### Credentials (encrypted)

RB App Foundry provides an encrypted credential storage layer used by add-ons built on the framework. Credentials (such as OAuth tokens or API keys for connected services) are stored inside your WordPress database using AES-256-CBC encryption, with the key derived from your WordPress installation's `AUTH_KEY` and `SECURE_AUTH_KEY` constants.

The specific credentials stored depend entirely on which add-ons are installed and activated. Refer to the privacy policy of each add-on for details.

### Plugin Settings

The framework stores plugin configuration (e.g. which add-ons are active, general settings) in the WordPress `wp_options` table. This data is not personally identifiable.

### No End-User Data

RB App Foundry does not collect, process, or store data about your website's end users. It operates entirely within the WordPress admin and is used only by site administrators.

---

## Third-Party Services

RB App Foundry itself does not contact any external services. External service integrations are introduced by add-ons. Refer to the privacy policy of each installed add-on for information about which services are contacted and what data is transmitted.

For example:
- **RB SocialPillar** adds integrations with Facebook Graph API and Anthropic Claude API. See [RB SocialPillar's Privacy Policy](https://rbcreativesolutions.net/plugins/rb-socialpillar/privacy-policy/).

---

## Who Can Access This Data

Data stored by RB App Foundry is accessible to:

- **WordPress administrators** on your site, who can manage add-ons and connections via the RB App Foundry admin panel.
- **Anyone with direct database access** to your WordPress installation (e.g. hosting providers, site developers).

The framework does not expose stored credentials via any public-facing endpoint.

---

## Data Retention and Deletion

Plugin settings and encrypted credentials are retained until you remove them manually or uninstall the plugin. When you uninstall RB App Foundry, you can opt in to deleting all plugin data. This removes all framework settings and stored connection data from your database.

---

## GDPR and Data Subject Rights

RB App Foundry stores data that relates to your WordPress site's configuration and connected service credentials. It does not process data about your website's end users. If you have questions about compliance for your specific use case, consult a qualified legal professional.

---

## Changes to This Policy

We may update this policy when the framework adds new features that affect data handling. The "Last updated" date at the top of this document reflects the most recent revision.

---

## Contact

Questions about this privacy policy: **rosina@rbcreativesolutions.net**

> *This privacy policy was written to accurately describe the plugin's actual behaviour. It is not a substitute for legal advice.*
