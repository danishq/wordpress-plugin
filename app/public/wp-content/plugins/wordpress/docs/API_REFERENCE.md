# FunnelKit Checkout - API Reference

## PHP Functions

### Template Functions

#### wfacp_template()

Returns the current template instance.

```php
/**
 * @return WFACP_Template_Common|null
 */
function wfacp_template();
```

**Usage:**
```php
$template = wfacp_template();
if ( $template ) {
    $template->get_template_type();
}
```

---

#### wfacp_is_elementor()

Checks if current page is built with Elementor.

```php
/**
 * @return bool
 */
function wfacp_is_elementor();
```

---

#### wfacp_elementor_edit_mode()

Checks if Elementor editor is active.

```php
/**
 * @return bool
 */
function wfacp_elementor_edit_mode();
```

---

### Order Functions

#### wfacp_get_order_meta()

HPOS-compatible function to get order meta.

```php
/**
 * @param WC_Order $order Order object
 * @param string   $key   Meta key
 * @return mixed
 */
function wfacp_get_order_meta( $order, $key );
```

**Usage:**
```php
$checkout_id = wfacp_get_order_meta( $order, '_wfacp_post_id' );
```

---

#### wfacp_is_hpos_enabled()

Checks if WooCommerce HPOS is enabled.

```php
/**
 * @return bool
 */
function wfacp_is_hpos_enabled();
```

---

### Form Functions

#### wfacp_form_field()

Outputs a checkout form field.

```php
/**
 * @param string $key   Field key
 * @param array  $args  Field arguments
 * @param mixed  $value Field value
 * @return string|void
 */
function wfacp_form_field( $key, $args, $value = null );
```

**Arguments:**
```php
$args = array(
    'type'              => 'text',      // text, email, tel, select, checkbox, etc.
    'label'             => '',
    'description'       => '',
    'placeholder'       => '',
    'maxlength'         => false,
    'required'          => false,
    'autocomplete'      => false,
    'id'                => $key,
    'class'             => array(),
    'label_class'       => array(),
    'input_class'       => array(),
    'return'            => false,
    'options'           => array(),     // For select/radio
    'custom_attributes' => array(),
    'validate'          => array(),
    'default'           => '',
    'priority'          => '',
);
```

---

## WFACP_Common Class (Static Methods)

### Page/ID Methods

#### WFACP_Common::get_id()

Get current checkout page ID.

```php
/**
 * @return int
 */
public static function get_id();
```

---

#### WFACP_Common::set_id()

Set current checkout page ID.

```php
/**
 * @param int $wfacp_id
 */
public static function set_id( $wfacp_id );
```

---

#### WFACP_Common::get_post_type_slug()

Get the custom post type slug.

```php
/**
 * @return string 'wfacp_checkout'
 */
public static function get_post_type_slug();
```

---

### Settings Methods

#### WFACP_Common::get_page_settings()

Get settings for a checkout page.

```php
/**
 * @param int $wfacp_id
 * @return array
 */
public static function get_page_settings( $wfacp_id );
```

---

#### WFACP_Common::get_page_design()

Get design/template settings.

```php
/**
 * @param int $wfacp_id
 * @return array
 */
public static function get_page_design( $wfacp_id );
```

---

#### WFACP_Common::get_page_product()

Get products assigned to checkout.

```php
/**
 * @param int $wfacp_id
 * @return array
 */
public static function get_page_product( $wfacp_id );
```

---

#### WFACP_Common::get_page_product_settings()

Get product display settings.

```php
/**
 * @param int $wfacp_id
 * @return array
 */
public static function get_page_product_settings( $wfacp_id );
```

---

#### WFACP_Common::global_settings()

Get global plugin settings.

```php
/**
 * @param int $wfacp_id Optional
 * @return array
 */
public static function global_settings( $wfacp_id = 0 );
```

---

### Field Methods

#### WFACP_Common::get_checkout_fields()

Get all checkout fields configuration.

```php
/**
 * @param int $wfacp_id
 * @return array
 */
public static function get_checkout_fields( $wfacp_id );
```

---

#### WFACP_Common::get_address_field_order()

Get address field ordering.

```php
/**
 * @param int $wfacp_id
 * @return array
 */
public static function get_address_field_order( $wfacp_id );
```

---

### Utility Methods

#### WFACP_Common::is_customizer()

Check if in customizer preview.

```php
/**
 * @return bool
 */
public static function is_customizer();
```

---

#### WFACP_Common::is_theme_builder()

Check if a theme builder is active.

```php
/**
 * @return bool
 */
public static function is_theme_builder();
```

---

#### WFACP_Common::is_builder()

Check if any page builder edit mode.

```php
/**
 * @return bool
 */
public static function is_builder();
```

---

#### WFACP_Common::get_checkout_page_id()

Get global checkout override page ID.

```php
/**
 * @return int
 */
public static function get_checkout_page_id();
```

---

#### WFACP_Common::get_post_meta_data()

Get post meta with default fallback.

```php
/**
 * @param int    $post_id
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
public static function get_post_meta_data( $post_id, $key, $default = '' );
```

---

## WFACP_Core Class (Singleton)

### Getting Instance

```php
$core = WFACP_Core();
// or
$core = WFACP_Core::get_instance();
```

### Properties

| Property | Type | Description |
|----------|------|-------------|
| `template_loader` | `WFACP_Template_loader` | Template system |
| `admin` | `WFACP_admin` | Admin interface |
| `public` | `WFACP_Public` | Frontend controller |
| `importer` | `WFACP_Template_Importer` | Import/export |
| `pay` | `WFACP_Order_Pay` | Order pay page |
| `reporting` | `WFACP_Reporting` | Analytics |
| `role` | `WFACP_Role` | Role/capability system |

---

## API Types Overview

The plugin has **two distinct API types** with different authentication and access patterns:

| API Type | Base URL/Method | Access Level | Used By |
|----------|-----------------|--------------|---------|
| **Admin REST API** | `/wp-json/wfacp-admin/` | Admin users (role check) | Vue.js admin app |
| **Admin AJAX** | `admin-ajax.php` | Logged-in admins | Legacy admin features |
| **Frontend WC AJAX** | `?wc-ajax={action}` | All users (guest + logged-in) | Checkout page interactions |
| **Login Flow AJAX** | `admin-ajax.php` (nopriv) | Guest users only | Smart login module |

---

## Admin REST API

**Base URL:** `/wp-json/wfacp-admin/`

**Authentication:** Requires WordPress nonce + user role check via `wffn_rest_api_helpers()->get_api_permission_check('funnel', 'read|write')`

**Header Required:**
```
X-WP-Nonce: {nonce from wfacp_secure.nonce}
```

### Permission Levels

```php
// Read permission - view checkout data
get_read_api_permission_check()  // Maps to 'funnel' → 'read'

// Write permission - create/edit/delete
get_write_api_permission_check() // Maps to 'funnel' → 'write'
```

### Checkout Pages

| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/wfacp` | read | List all checkouts |
| POST | `/wfacp` | write | Create checkout |
| GET | `/wfacp/{id}` | read | Get single checkout |
| DELETE | `/wfacp/{id}` | write | Delete checkout |
| DELETE | `/wfacp/{id}/duplicate` | write | Duplicate checkout |
| PUT | `/wfacp/{id}/export` | write | Export single |
| POST | `/wfacp/export` | write | Bulk export |

### Products

| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/wfacp/product-search?term={term}` | read | Search products |
| GET | `/wfacp/products/{id}` | read | Get page products |
| PUT | `/wfacp/products/{id}` | write | Add product |
| DELETE | `/wfacp/products/{id}` | write | Remove product |

### Settings

| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/wfacp/settings/{id}` | read | Get settings |
| PUT | `/wfacp/settings/{id}` | write | Update settings |

### Form Fields

| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/wfacp/form_fields/{id}` | read | Get form fields |
| PUT | `/wfacp/form_fields/{id}` | write | Save form fields |

### Optimizations

| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| GET | `/optimizations/{id}` | read | Get optimizations |
| PUT | `/optimizations/{id}` | write | Save optimizations |

### Utilities

| Method | Endpoint | Permission | Description |
|--------|----------|------------|-------------|
| PUT | `/wfacp/save_state/{id}` | write | Save page state |
| POST | `/wfacp/activate-plugin` | write | Activate builder plugin |
| GET | `/funnels/pages/search?term={term}` | read | Search pages |

---

## Admin AJAX Endpoints (Legacy)

**Base URL:** `admin-ajax.php`
**Access:** Logged-in users only (`wp_ajax_*` hooks, no `nopriv`)
**File:** `includes/class-wfacp-ajax-controller.php`

These are legacy endpoints, mostly replaced by REST API but still used for some features.

| Action | Handler | Purpose |
|--------|---------|---------|
| `wfacp_save_global_settings` | `save_global_settings()` | Save global plugin settings |
| `wfacp_preview_details` | `preview_details()` | Get preview data |
| `wfacp_add_checkout_page` | `add_checkout_page()` | Create new checkout |
| `wfacp_update_page_status` | `update_page_status()` | Toggle publish/draft |
| `wfacp_add_product` | `add_product()` | Add product to checkout |
| `wfacp_remove_product` | `remove_product()` | Remove product |
| `wfacp_product_search` | `product_search()` | Search products |
| `wfacp_save_products` | `save_products()` | Save product settings |
| `wfacp_save_layout` | `save_layout()` | Save form layout |
| `wfacp_add_field` | `add_field()` | Add custom field |
| `wfacp_delete_custom_field` | `delete_custom_field()` | Delete custom field |
| `wfacp_update_custom_field` | `update_custom_field()` | Update custom field |
| `wfacp_save_design` | `save_design()` | Save template design |
| `wfacp_remove_design` | `remove_design()` | Remove template |
| `wfacp_save_settings` | `save_settings()` | Save page settings |
| `wfacp_import_template` | `import_template()` | Import template |
| `wfacp_activate_plugin` | `activate_plugin()` | Activate page builder |

---

## Frontend/Public API

### WooCommerce AJAX Endpoints

**Base URL:** `?wc-ajax={action}` (via WooCommerce AJAX system)
**Access:** All users (guest + logged-in)
**File:** `includes/class-wfacp-ajax-controller.php::handle_public_ajax()`

These endpoints are available on the frontend checkout page for all visitors.

| WC AJAX Action | Handler | Purpose |
|----------------|---------|---------|
| `wfacp_get_divi_form_data` | `get_divi_form_data()` | Get Divi form HTML |
| `wfacp_get_divi_order_summary_data` | `get_divi_order_summary_data()` | Get Divi order summary |
| `wfacp_quick_view_ajax` | `wf_quick_view_ajax()` | Product quick view modal |
| `wfacp_analytics` | `analytics()` | Track checkout analytics |

**Extending Public Endpoints:**
```php
add_filter( 'wfacp_public_endpoints', function( $endpoints ) {
    $endpoints['my_custom_action'] = 'my_handler_method';
    return $endpoints;
});
```

### Checkout Update Hooks

During `woocommerce_checkout_update_order_review`, the plugin intercepts actions via hidden field `wfacp_input_hidden_data`:

| Action | Purpose |
|--------|---------|
| `apply_coupon_field` | Apply coupon from mini-cart field |
| `apply_coupon_main` | Apply coupon from main coupon field |
| `remove_coupon_field` | Remove coupon from mini-cart |
| `remove_coupon_main` | Remove coupon from main field |

Response is merged into WooCommerce fragments as `wfacp_ajax_data`.

---

## Login Flow Module API (Guest Only)

**Base URL:** `admin-ajax.php` with `wp_ajax_nopriv_*`
**Access:** Guest users only (not logged in)
**File:** `modules/login-flow/index.php`

These endpoints handle the smart login feature for returning customers.

| Action | Handler | Security | Purpose |
|--------|---------|----------|---------|
| `funnelkit_search_customer` | `handle_search_customer_request()` | Nonce + Rate limit | Check if email exists |
| `funnelkit_user_login` | `handle_user_login_request()` | Nonce | Perform user login |
| `funnelkit_reset_password` | `handle_reset_password_request()` | Nonce | Send password reset email |

### Security Measures

1. **Nonce Verification:**
   ```php
   wp_verify_nonce( $_POST['nonce'], 'flf-nonce' )
   ```

2. **Rate Limiting (Email Search):**
   ```php
   $rate_limit = apply_filters( 'wfacp_login_email_rate_limit', 5 );
   // Stored in WC session: _wfacp_email_check_attempt
   ```

3. **Email Validation:**
   ```php
   $email = sanitize_email( $_POST['email'] );
   if ( ! is_email( $email ) ) { /* error */ }
   ```

### Request Examples

**Search Customer:**
```javascript
jQuery.post(wc_checkout_params.ajax_url, {
    action: 'funnelkit_search_customer',
    nonce: wfacp_localize.nonce,
    email: 'customer@example.com',
    page_id: 123
});
```

**User Login:**
```javascript
jQuery.post(wc_checkout_params.ajax_url, {
    action: 'funnelkit_user_login',
    'funnelkit-login-nonce': nonce,
    username: 'user@example.com',
    password: 'password123',
    rememberme: true
});
```

---

## JavaScript API

### Global Objects

#### wfacp_data

Localized data available on admin pages.

```javascript
wfacp_data = {
    id: 123,                    // Checkout ID
    name: 'Checkout Name',      // Page title
    post_name: 'checkout-slug', // URL slug
    post_url: 'https://...',    // Page URL
    base_url: 'https://...',    // Site URL
    currency: '$',              // Currency symbol
    products: [...],            // Assigned products
    design: {...},              // Design settings
    layout: {...},              // Layout configuration
    settings: {...},            // Page settings
    // ... more
};
```

#### wfacp_secure

Security tokens.

```javascript
wfacp_secure = {
    nonce: 'abc123...'  // Admin AJAX nonce
};
```

#### wfacp_localization

Translatable strings.

```javascript
wfacp_localization = {
    // Localized strings for admin interface
};
```

### Frontend Events

```javascript
// Checkout form updated
jQuery( document.body ).on( 'updated_checkout', function() {
    // Handle checkout update
});

// Cart fragments updated
jQuery( document.body ).on( 'wc_fragments_refreshed', function() {
    // Handle fragment refresh
});
```

### Custom Hooks (JS)

```javascript
// If using hooks.js
wfacp_hooks.add_action( 'wfacp_checkout_updated', function() {
    // Custom action
});

wfacp_hooks.apply_filters( 'wfacp_field_value', value, field_key );
```

---

## URL Parameters

### Add Products

| Parameter | Description | Example |
|-----------|-------------|---------|
| `aero-add-to-checkout` | Product IDs to add | `?aero-add-to-checkout=123,456` |
| `aero-qty` | Quantities | `?aero-qty=2,1` |

### Product Selection

| Parameter | Description | Example |
|-----------|-------------|---------|
| `aero-default` | Default selected product | `?aero-default=unique_key` |
| `aero-best-value` | Mark as best value | `?aero-best-value=unique_key` |

### Coupons

| Parameter | Description | Example |
|-----------|-------------|---------|
| `aero-coupon` | Auto-apply coupon | `?aero-coupon=SAVE10` |

---

## Shortcodes

### wfacp_order_custom_field

Display custom field value on order pages.

```php
[wfacp_order_custom_field field_key="my_field"]
```

**Attributes:**
- `field_key` - The custom field meta key

### wfacp_order_total

Display order total.

```php
[wfacp_order_total]
```

---

## Constants

| Constant | Description |
|----------|-------------|
| `WFACP_PLUGIN_FILE` | Main plugin file path |
| `WFACP_PLUGIN_DIR` | Plugin directory path |
| `WFACP_PLUGIN_URL` | Plugin URL |
| `WFACP_PLUGIN_BASENAME` | Plugin basename |
| `WFACP_VERSION` | Plugin version |
| `WFACP_VERSION_DEV` | Development version |
| `WFACP_SLUG` | Plugin slug (`wfacp`) |
| `WFACP_FULL_NAME` | Full plugin name |
| `WFACP_BWF_VERSION` | WooFunnels core version |
| `WFACP_IS_DEV` | Debug mode flag (define in wp-config.php) |

---

## Template Tags

### In Template Files

```php
// Get current checkout ID
$checkout_id = WFACP_Common::get_id();

// Get template instance
$template = wfacp_template();

// Get customizer settings
$settings = $template->get_template_settings();

// Get form sections
$sections = $template->get_form_sections();

// Render specific section
$template->render_section( 'billing' );

// Get product switcher
$template->get_product_switcher();

// Get mini cart
$template->get_mini_cart();

// Get order summary
$template->get_order_summary();
```

### Available Template Methods

```php
// WFACP_Template_Common methods
$template->get_template_type();      // Get template type
$template->get_template_slug();      // Get template slug
$template->get_checkout_fields();    // Get fields config
$template->get_step_count();         // Get number of steps
$template->is_multistep();           // Check if multi-step
$template->get_current_step();       // Get current step
```

---

## REST API Details

### Authentication

All admin REST API endpoints require authentication via WordPress nonce:

```javascript
// Header for REST requests
X-WP-Nonce: {wfacp_secure.nonce}
```

### Request/Response Examples

#### Create Checkout Page

**Request:**
```http
POST /wp-json/wfacp-admin/wfacp
Content-Type: application/json

{
    "wfacp_id": 0,
    "data": "{\"wfacp_name\":\"My Checkout\",\"post_name\":\"my-checkout\"}"
}
```

**Response:**
```json
{
    "success": true,
    "redirect_url": "admin.php?page=wfacp&section=design&wfacp_id=123",
    "msg": "Checkout Page Successfully Created"
}
```

#### Save Form Fields/Layout

**Request:**
```http
PUT /wp-json/wfacp-admin/wfacp/form_fields/123
Content-Type: application/json

{
    "steps": {"single_step": {"name": "Single Step", "active": "yes"}},
    "fieldsets": {
        "single_step": [{
            "name": "Billing Details",
            "fields": [{
                "id": "billing_first_name",
                "field_type": "billing",
                "type": "text",
                "label": "First Name",
                "required": true
            }]
        }]
    },
    "wfacp_id": 123,
    "have_billing_address": true,
    "have_shipping_address": false,
    "current_step": "single_step"
}
```

**Response:**
```json
{
    "success": true,
    "msg": "Layout saved successfully"
}
```

#### Add Product to Checkout

**Request:**
```http
PUT /wp-json/wfacp-admin/wfacp/products/123
Content-Type: application/json

{
    "products": [456, 789],
    "wfacp_id": 123
}
```

**Response:**
```json
{
    "success": true,
    "products": {
        "unique_key_1": {
            "id": 456,
            "title": "Product 1",
            "price": "$10.00",
            "type": "simple"
        }
    }
}
```

#### Search Products

**Request:**
```http
GET /wp-json/wfacp-admin/wfacp/product-search?term=shirt
```

**Response:**
```json
{
    "success": true,
    "products": [
        {"id": 456, "name": "Blue Shirt", "price": "$29.99"},
        {"id": 789, "name": "Red Shirt", "price": "$24.99"}
    ]
}
```

### Error Responses

```json
{
    "success": false,
    "msg": "Failed",
    "code": "rest_forbidden"
}
```

---

## Admin AJAX Actions (Legacy)

Some operations still use `admin-ajax.php`:

| Action | Handler | Purpose |
|--------|---------|---------|
| `wfacp_export` | Export handler | Export checkout pages |
| `wfacp_import` | Import handler | Import checkout pages |

### Export Request

```http
GET /wp-admin/admin.php?action=wfacp-export&id=123&_wpnonce={nonce}
```

Returns: JSON file download

---

## Admin App Architecture

For detailed documentation about the admin Vue.js application, including:
- URL structure and routing
- Vue.js component hierarchy
- Data flow between PHP and JavaScript
- Settings save/load mechanisms
- Post meta keys

See: **[ADMIN_APP.md](./ADMIN_APP.md)**
