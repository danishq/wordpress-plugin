# AdFlipr WordPress plugin — runtime behavior

Canonical technical overview of how this plugin behaves at runtime. For install and build steps, see the root `README.md`.

> **Note:** Some other files under `docs/` (for example legacy `PLUGIN_KNOWLEDGE_BASE.md`, `HOOKS_REFERENCE.md`) describe third-party products and are **not** authoritative for AdFlipr. Prefer this file plus `includes/*.php` for AdFlipr-specific behavior.

---

## Purpose

This is a **third-party service integration** (not a standalone marketing product): it connects **WooCommerce** to **Adflipr** so merchants who use Adflipr can sync store data and use Adflipr features. Expect **limited utility without an Adflipr account**. Behavior includes admin login and overview UI, token storage, WooCommerce REST API key provisioning, webhook-based data sync, checkout-scoped cart abandonment and purchase events, optional suppression of native WooCommerce emails, and optional storefront UTM cookie capture when enabled in settings.

---

## Environment / API host configuration

All remote URLs derive from **`ADFLIPR_API_BASE_URL`** (default production host). Configuration lives in **`includes/adflipr-api-config.php`**, loaded first from **`adflipr.php`** and **`uninstall.php`**.

**Override the API host** (staging, local tunnel, etc.):

- In **`wp-config.php`** before WordPress loads plugins:

  `define( 'ADFLIPR_API_BASE_URL', 'https://staging.adflipr.com' );`

- Or use the **`adflipr_api_base_url`** filter from a small mu-plugin.

**Optional:** if the customer-facing **web app** runs on a different origin than the API, set **`ADFLIPR_APP_BASE_URL`** (or filter **`adflipr_app_base_url`**). Defaults to the API base. The React admin bundle reads **`window.adfliprWp.appBaseUrl`** / **`apiBaseUrl`** from **`wp_localize_script`**.

Derived constants include **`ADFLIPR_WOOCOMMERCE_WEBHOOK_URL`**, **`ADFLIPR_API_DISCONNECT_URL`**, **`ADFLIPR_API_USERS_LOGIN_URL`**, plus **`adflipr_api_url( $path )`** for internal builds.

---

## Boot sequence

- **`adflipr.php`** loads **`includes/adflipr-api-config.php`** and **`includes/adflipr-requirements.php`**, defines `AdFlipr_Core`, loads PHP classes from `includes/`, and registers them on **`plugins_loaded`** (priority `1`).
- **Requirements:** PHP **8.1+**, WordPress **6.4+**, **WooCommerce active** at **8.0+** (broader adoption than 8.5; same stable REST/Store API hooks). Plugin headers declare **`Requires at least`**, **`Requires PHP`**, **`Requires Plugins: woocommerce`**, and WooCommerce’s **`WC requires at least`** / **`WC tested up to`**. On **activation**, failing checks **deactivates** the plugin and **`wp_die`** with an explanation; admins also see an **`admin_notices`** error if the environment falls below requirements while the plugin stays active (e.g. after a downgrade).
- Each module calls `AdFlipr_Core::register(...)` at load time; **`register_classes()`** then runs `get_instance()` on `admin`, `auth`, `data`, `cart_tracker`, and `email`.

---

## Modules and behavior

### Admin (`AdFlipr_Admin` — `class-adflipr-admin-page.php`)

- If **WooCommerce** is active and there is **no valid Adflipr token**, administrators see a **warning notice** (dismissible per user) linking to **AdFlipr** settings—so connection issues are not invisible.
- Registers a top-level **AdFlipr** admin menu pointing at `#adflipr-wp-react-app` (built assets from `build/`).
- Shortcode **`[adflipr_react_app]`** renders the same React mount container on the front end.
- **Data & privacy** panel (above the React app): explains what is sent, where (API base), and why; **UTM storefront script is opt-in** via option **`adflipr_utm_tracking_enabled`** (`ADFLIPR_UTM_TRACKING_OPTION`) — when disabled (default), **`assets/js/adflipr-utm-tracking.js`** is **not** enqueued on public pages.
- **Disconnect:** **`admin.php?page=adflipr-settings&reset_token=1&_wpnonce=…`** (nonce action **`adflipr_reset_token`**) triggers **`reset_token()`** after **`manage_options`** + **`wp_verify_nonce`**; avoids unauthenticated GET resets.
- If **WooCommerce** is not active, shows an admin notice (`esc_html__`, text domain **`adflipr`**).
- Registers suggested Privacy Policy text via **`wp_add_privacy_policy_content`** for the WordPress privacy guide.
- Enqueues the React bundle on **`admin_enqueue_scripts`** when the AdFlipr settings page is open.

### Auth (`AdFlipr_Auth` — `class-adflipr-auth.php`)

- Authenticates via **`POST …/api/v1/users/login`** (`ADFLIPR_API_USERS_LOGIN_URL`) with email, password, and WooCommerce API key material in **`queryParams`**. The plugin intentionally uses the normal web login flow because the proxy forwards to existing **`/api/v1/*`** backend routes. The API returns **`loginToken`** and **`tokenExpiry`**; the token is sent as the raw **`Authorization`** header value.
- Persists **`adflipr_auth_token`** and **`adflipr_token_expiry`** in WordPress options.
- Ensures a **read** WooCommerce REST API key exists for the first super admin (multisite) or first administrator (single site); validates it against `GET /wp-json/wc/v3/products`.
- Registers custom REST routes under **`adflipr/v1`**:
  - **`GET /login-token`** — returns token if valid; **`permission_callback`**: **`manage_options`**, valid **`X-WP-Nonce`** (`wp_rest`), **`api_permissions_check`** (referer must start with `site_url()`), and an existing Adflipr login token.
  - **`POST /login-token`** — runs `authenticate`, then triggers **`send_data_after_authentication`** (bulk sync) and **manual drain** of the webhook retry queue so payloads captured while disconnected flush without waiting for WP-Cron. Same admin + REST nonce + referer as above (no token required before login).
  - **`/proxy/(?P<path>.*)`** — forwards-method requests to **`https://adflipr.com/api/v1/{path}`** with stored token; **`manage_options`** + REST nonce + referer + valid Adflipr login token. Responses are normalized to **`WP_REST_Response`**: decoded JSON body (or a small wrapper if upstream is not JSON) and the upstream HTTP status code — not the raw **`wp_remote_*`** array.
  - **`GET /integration-health`**, **`GET/POST webhook-retry/…`** — **`manage_options`** + REST nonce (see **`adflipr_rest_permission_manage_options_with_nonce()`** in **`includes/adflipr-rest-permissions.php`**).

**WooCommerce REST API:** the plugin does **not** override **`woocommerce_rest_check_permissions`**. Store data accessed via WC REST is subject to WooCommerce’s normal permission and API-key checks. The internal **`validate_woocommerce_api_key`** test uses **Basic** auth with the key pair and expects standard WC behavior.

### Data (`AdFlipr_Data` — `class-adflipr-data.php`)

- **`save_post_product`** / **`before_delete_post`** (product): webhook actions **`add_product`** / **`delete_product`**.
- **`save_post_shop_coupon`** / **`before_delete_post`** (coupon): analogous coupon sync.
- **`send_data_all_data`**: batches products, orders, coupons, and subscriptions (if **WooCommerce Subscriptions** is available), POSTs JSON to the WooCommerce webhook URL (see constants in `adflipr.php`) with the login token in the `Authorization` header.
- **Idempotency:** Before each POST, **`adflipr_webhook_payload_with_idempotency()`** adds a deterministic **`webhookId`** (SHA-256 over site host + action + stable fingerprint). High-volume flows use stable keys — e.g. **`cart_abandonment`**: host + email + **`cart_hash`**; **`checkout_purchased`**: host + WooCommerce order id — so retries do not mint new ids when unrelated payload fields reorder. The Java **`WooCommerceService`** rejects duplicate **`webhookId`** values for provider **WooCommerce** (returns HTTP 200 with an idempotent message, no second persisted webhook row). Retried queue payloads keep the same **`webhookId`**.
- **Diagnostics:** **`GET /wp-json/adflipr/v1/integration-health`** (`manage_options`) returns plugin/WP/PHP versions, WooCommerce active flag, **`adflipr_meets_requirements()`**, token-valid boolean, retry-queue depth, **`last_webhook_success_unix`** / **`last_webhook_success_action`** (last HTTP **200** from Adflipr), and whether **`ADFLIPR_DEBUG`** is on—no secrets. Optional **`ADFLIPR_DEBUG`** (define in `wp-config.php`) enables **`adflipr_debug_log()`** lines for webhook HTTP failures (action key + code/error only).
- **Offline / API outage:** failed webhook POSTs are appended to option **`adflipr_webhook_retry_queue`** (capped, max attempts per item). WP-Cron hook **`adflipr_webhook_retry_process`** runs on a **5-minute** schedule (plus **`wp_schedule_single_event`** ~90s after enqueue) to retry; successful sends remove items. **Missing Adflipr token:** drain cycles **do not increment attempt counts** for items that fail only because the token is absent — payloads stay queued until the merchant connects. Low-traffic sites may run cron late—admins can **POST** **`/wp-json/adflipr/v1/webhook-retry/process`** (logged-in `manage_options`, **`X-WP-Nonce`**), **GET** **`…/webhook-retry/status`** for queue depth, **`admin-ajax.php`** **`action=adflipr_process_webhook_retry_queue`** and POST **`nonce`** (`wp_create_nonce( 'adflipr_webhook_retry' )`, exposed as **`adfliprWp.webhookRetryAjaxNonce`**), or **`wp adflipr webhook-retry-process`** to drain up to **`ADFLIPR_WEBHOOK_RETRY_MANUAL_MAX_LOOPS`** batches without waiting. Long-term, durable processing can still move to a **backend queue** fed by successful webhook delivery or pull sync.
- **Bulk sync pagination:** the next batch is scheduled only when the webhook returns **HTTP 200** or the payload was **accepted into the retry queue** (`queued`), so paging does not skip ahead when the API is unreachable but the payload was persisted for retry.
- Uses option keys such as **`adflipr_products_last_offset`** (and related) for paging; if a batch is full (`limit` 50), schedules **`adflipr_send_data_event`** **`60` seconds** later for the next chunk via **`wp_schedule_single_event`**.
- **`AdFlipr_Data`** does **not** register per-order WooCommerce hooks; order data is included in bulk sync and in cart/checkout-driven payloads (see cart tracker).

### Cart tracker (`Adflipr_Cart_Tracker` — `class-adflipr-cart-tracker.php`)

- On checkout pages, enqueues **`assets/js/adflipr-cart-tracker.js`** and passes **`adflipr_vars.ajax_url`**, **`nonce`** (`wp_create_nonce` action **`ADFLIPR_SAVE_CART_NONCE_ACTION`**, default **`adflipr_save_cart`**).
- **`wp_ajax_adflipr_save_cart`** / **`wp_ajax_nopriv_adflipr_save_cart`**: verifies AJAX nonce via **`nonce`** or WooCommerce-style **`security`** (same value), applies a **per-IP rate limit** and optional **`Content-Length`** cap (`ADFLIPR_SAVE_CART_MAX_CONTENT_LENGTH`), **`sanitize_email`** / **`esc_url_raw`**, accepts email (and optional URL), builds **`cart_abandonment`** payload, sends via **`send_data_on_webhook`**.
- **`woocommerce_checkout_create_order`**: stores **`_adflipr_cart_hash`** on the order.
- **`woocommerce_checkout_order_created`** (classic) and **`woocommerce_store_api_checkout_order_processed`** (block / Store API checkout, WC 7.2+): **`handle_order_completion`** sends **`checkout_purchased`** with order data, UTM cookies (`utm_*`), client IP. Dedup uses order meta **`ADFLIPR_ORDER_CHECKOUT_WEBHOOK_SENT_META`** (`_adflipr_checkout_webhook_sent` = `yes`) after **HTTP 200** or **`queued`** only. **`_adflipr_cart_hash`** is cleared only after a successful mark.

### Transactional email (`AdFlipr_Transactional_Email` — `class-adflipr-transactional-email.php`)

- **`woocommerce_email_enabled` fail-open:** if there is **no valid Adflipr login token**, the filter **does not** suppress WooCommerce emails (native store emails keep sending).
- **Active suppression** uses **`adflipr_disabled_wc_emails`** only after Adflipr confirms an `email_settings` webhook (**HTTP 200**), including after a queued payload succeeds (`AdFlipr_Data::maybe_commit_email_settings_payload`). Queued-only saves store UI state in **`adflipr_disabled_wc_emails_pending`** without changing committed suppression until sync completes.
- REST **`setup_email_setting`** may apply settings from Adflipr (authenticated) and clears **pending** when applied.
- Registers REST/settings-related behavior for syncing email preferences (see class for details).

### React app (`src/`)

- On mount, calls **`GET`** the **`login-token`** REST route; if a token is returned, shows **Dashboard**, otherwise **Login**.
- After login, token is stored server-side and the UI can open the main AdFlipr app in a new tab.

### Uninstall (`uninstall.php`)

- If a token exists, sends **`PUT`** to **`https://adflipr.com/api/v1/integration/connection/disconnect`** (best effort if Adflipr is down).
- Deletes AdFlipr-related options (including **`adflipr_webhook_retry_queue`**) and clears scheduled hooks **`adflipr_send_data_event`** and **`adflipr_webhook_retry_process`**.

---

## Packaging

From the plugin root: **`npm run build`** produces `build/`; **`./create-plugin-zip.sh`** creates **`adflipr.zip`** for installation.

---

## Implementation notes (for maintainers)

- **Login and REST proxy** (`/users/login`, **`adflipr/v1/proxy`**): still require a live Adflipr API; there is no local queue for those. Store-facing webhook payloads are what the retry queue covers.
