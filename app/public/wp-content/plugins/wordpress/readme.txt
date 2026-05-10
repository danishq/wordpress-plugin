=== AdFlipr Integration ===
Contributors: adflipr
Tags: woocommerce, ecommerce, email marketing, automation, marketing, integration
Requires at least: 6.4
Tested up to: 6.7
Stable tag: 1.0
Requires PHP: 8.1
Requires Plugins: woocommerce
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Service integration plugin: connects WooCommerce to Adflipr (third-party SaaS). Requires an Adflipr account for real value—this is not a standalone email marketing tool. Provides sync, webhooks, optional UTM cookies (opt-in), and checkout-related events for Adflipr users.

== Description ==

This plugin is a **connector for an external service**. Its purpose is to securely bridge your WordPress/WooCommerce site to **Adflipr** so you can use Adflipr’s product features (email marketing, automation, etc.) with store data. **Standalone value is limited without an active Adflipr account** — install it when you intentionally use (or plan to use) Adflipr.

AdFlipr Integration links your WooCommerce store to **Adflipr** (hosted SaaS). After you authenticate from WordPress admin, the plugin can:

* Send product, coupon, order, and (where available) subscription snapshots to Adflipr.
* Deliver webhook-style updates when catalog entities change.
* Forward checkout-scoped events used for automation (for example cart recovery flows where configured in Adflipr).
* Optionally mirror WooCommerce transactional email suppression settings when you manage them through Adflipr.

**Where data goes**

Payloads are sent over HTTPS to Adflipr API endpoints derived from your configuration (default production hosts are defined in `includes/adflipr-api-config.php`). You may override the API base URL in `wp-config.php` for staging or custom deployments—see the plugin `README.md`.

**Transparency**

The AdFlipr admin screen includes a **Data & privacy** section describing what is transmitted and why. **UTM parameter storage on the public storefront is disabled by default**; enable it only if you want first-party session cookies for `utm_*` keys from the visitor’s URL.

Suggested wording for your site’s Privacy Policy is registered for the WordPress privacy guide (**Settings → Privacy**) when this plugin is active.

== External services ==

This plugin **connects your WordPress site to Adflipr**, a third-party hosted service. Data is transmitted over **HTTPS** to Adflipr API endpoints (default host is derived from the plugin configuration—typically **https://adflipr.com**). Administrators may override the API base URL via `wp-config.php` or filters; see the plugin `README.md`.

**Categories of data that may be sent** (depending on features you use and store activity):

* **Catalog & operations:** product, coupon, order, and (where applicable) subscription-related payloads for synchronization and automation.
* **Checkout / cart flows:** checkout-related events used for automation (for example recovery flows configured in Adflipr), including **customer email addresses entered at checkout** where applicable.
* **Authentication:** after an administrator signs in through the integration, an authentication token is stored in the WordPress database (`wp_options`) and sent with outbound requests.

Review Adflipr’s own terms and privacy documentation for how they process data. The plugin includes an in-admin **Data & privacy** disclosure and registers suggested Privacy Policy text for the WordPress privacy guide.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or install through **Plugins → Add New**.
2. Activate the plugin. PHP 8.1+, WordPress 6.4+, and WooCommerce 8.0+ are required.
3. Open **AdFlipr** in the WordPress admin menu, review **Data & privacy**, choose whether to enable UTM cookie storage, then connect your Adflipr account.

== Frequently Asked Questions ==

= Does this plugin work without WooCommerce? =

No. WooCommerce must be installed and active. The plugin will not activate unless minimum versions are satisfied.

= Does this plugin work without an Adflipr account? =

No meaningful benefit. This is a **service integration**: it authenticates to Adflipr and sends/receives data for features you configure there. Without signing in to Adflipr, catalog sync, automation forwarding, and related behavior either do not run or have no destination.

= What data leaves my site? =

Catalog and operational data needed for Adflipr features (see the in-admin disclosure). Authentication uses a token stored in WordPress options. Optional UTM handling stores only `utm_*` query keys in first-party session cookies when you explicitly enable it.

= How do I disconnect? =

Use **Disconnect AdFlipr on this site** on the AdFlipr admin page (protected by a nonce). Uninstalling the plugin removes local options and notifies Adflipr’s disconnect endpoint when a token exists—see `uninstall.php`.

= Where can I change the API hostname? =

Define `ADFLIPR_API_BASE_URL` in `wp-config.php` or use the `adflipr_api_base_url` filter. See `README.md` in the plugin directory.

= How do I diagnose connection or queue issues? =

Administrators can **GET** `/wp-json/adflipr/v1/integration-health` with cookie authentication and the **`X-WP-Nonce`** header (`wp_rest`) for a JSON summary: WooCommerce active, requirement checks, token validity (boolean only), outbound retry queue size, last successful webhook time/action (HTTP 200), and plugin versions. No secrets are returned.

= How do I enable verbose logging? =

In `wp-config.php` before loading WordPress, add `define( 'ADFLIPR_DEBUG', true );`. Diagnostic lines are prefixed with `[AdFlipr]` and avoid tokens or payload bodies.

== Screenshots ==

1. AdFlipr WordPress admin — privacy disclosure, optional UTM opt-in, disconnect control, and embedded app.

== Changelog ==

= 1.0 =
* Initial public structure: WooCommerce sync and webhooks, retry queue, checkout/cart helpers, optional transactional email suppression when confirmed by Adflipr, admin transparency and UTM opt-in.

== Upgrade Notice ==

= 1.0 =
Initial release. Enable **Store UTM campaign parameters** under AdFlipr → Data & privacy if you rely on storefront UTM cookies.

== Privacy ==

* **Sync & webhooks:** Product, coupon, order, subscription (if applicable), and related operational payloads may be sent to Adflipr over HTTPS for features you enable in your Adflipr account.
* **Authentication:** Login tokens are stored in the WordPress database (`wp_options`) after you sign in through the integration.
* **UTM cookies (optional):** When enabled in plugin settings, a small script stores `utm_*` values from the query string in first-party session cookies on your domain for attribution.
* **Disconnect & uninstall:** Disconnect clears local tokens from WordPress; uninstall removes plugin options and attempts to notify Adflipr. Review Adflipr’s own privacy policy for processor obligations.
