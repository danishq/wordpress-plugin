# FunnelKit Checkout (WooFunnels Aero Checkout) - Plugin Knowledge Base

## 1. Plugin Overview

**Plugin Name:** FunnelKit Checkout (formerly WooFunnels Aero Checkout)
**Plugin Slug:** `wfacp`
**Text Domain:** `woofunnels-aero-checkout`
**Version:** Defined in `woofunnels-aero-checkout.php`
**Main Class:** `WFACP_Core`

### Purpose
A WooCommerce extension for building optimized, conversion-friendly checkout pages with support for multiple page builders (Elementor, Divi, Gutenberg, Oxygen) and a built-in customizer.

### Related Products
- **Funnel Builder Lite** (`funnel-builder`) - Free version on WordPress.org
- **Funnel Builder Pro** (`funnel-builder-pro`) - This repository is a submodule
- **WooFunnels Core** (`woofunnels/`) - Shared library (submodule)

### Requirements
- WordPress 5.0+
- WooCommerce 3.0+
- PHP 7.0+

---

## 2. Architecture

### 2.1 Directory Structure

```
woofunnels-aero-checkout/
├── woofunnels-aero-checkout.php    # Main plugin file
├── start.php                        # WooFunnels core loader
├── includes/                        # Core PHP classes
│   ├── class-wfacp-core.php        # Main singleton class
│   ├── class-wfacp-common.php      # Common utilities (abstract)
│   ├── class-wfacp-common-helper.php # Helper methods
│   ├── class-wfacp-template-loader.php # Template system
│   ├── class-wfacp-reporting.php   # Analytics & reporting
│   ├── class-compatibilities.php   # Compatibility loader
│   └── functions.php               # Global helper functions
├── admin/                           # Admin interface
│   ├── class-wfacp-admin.php       # Admin controller
│   ├── rest-api/                   # REST API endpoints
│   ├── views/                      # Admin templates
│   └── assets/                     # Admin CSS/JS
├── public/                          # Frontend
│   ├── class-wfacp-public.php      # Public controller
│   ├── template-common/            # Shared templates
│   ├── templates/                  # Pre-built layouts
│   └── global/                     # Global components
├── builder/                         # Page builder integrations
│   ├── elementor/                  # Elementor widgets
│   ├── divi/                       # Divi modules
│   ├── gutenberg/                  # Block editor blocks
│   ├── oxygen/                     # Oxygen elements
│   └── customizer/                 # WP Customizer integration
├── compatibilities/                 # Third-party compatibility
│   ├── plugins/                    # Plugin compatibility
│   ├── gateways/                   # Payment gateway compat
│   ├── themes/                     # Theme compatibility
│   ├── fields/                     # Custom field compat
│   ├── template-found/             # Template-specific compat
│   ├── setup-theme/                # Theme setup compat
│   └── ecrm/                       # CRM integrations
├── importer/                        # Template import/export
├── modules/                         # Feature modules
└── woofunnels/                      # Shared core (submodule)
```

### 2.2 Initialization Flow

```
1. woofunnels-aero-checkout.php
   └── Define constants (WFACP_PLUGIN_FILE, WFACP_VERSION, etc.)
   └── Include start.php

2. start.php
   └── WooFunnel_Loader::register() - Register plugin with shared loader
   └── WooFunnel_WFACP::register() - Register this plugin's config

3. plugins_loaded (priority -1)
   └── WFACP_Common::plugins_loaded()
   └── WooFunnel_Loader::include_core() - Load latest woofunnels core

4. woofunnels_loaded action
   └── Load WFACP_Core singleton
   └── Initialize components (admin, public, template_loader)

5. init (priority 98)
   └── WFACP_Common::register_post_type() - Register wfacp_checkout CPT

6. wfacp_loaded action
   └── Plugin fully initialized
   └── All classes available
```

### 2.3 Core Classes

| Class | File | Purpose |
|-------|------|---------|
| `WFACP_Core` | `includes/class-wfacp-core.php` | Main singleton, component orchestration |
| `WFACP_Common` | `includes/class-wfacp-common.php` | Static utilities, CPT registration, settings |
| `WFACP_Common_Helper` | `includes/class-wfacp-common-helper.php` | Base helper methods |
| `WFACP_Template_loader` | `includes/class-wfacp-template-loader.php` | Template detection & loading |
| `WFACP_Public` | `public/class-wfacp-public.php` | Frontend cart/checkout logic |
| `WFACP_admin` | `admin/class-wfacp-admin.php` | Admin interface |
| `WFACP_Reporting` | `includes/class-wfacp-reporting.php` | Order analytics |
| `WFACP_Template_Common` | `public/class-template-common.php` | Base template class |

---

## 3. Custom Post Type

**Post Type:** `wfacp_checkout`

### Registration
```php
register_post_type( 'wfacp_checkout', array(
    'public'              => true,
    'show_ui'             => true,
    'publicly_queryable'  => true,
    'exclude_from_search' => true,
    'show_in_menu'        => false,
    'show_in_rest'        => true,
    'supports'            => array( 'title', 'elementor', 'editor', 'custom-fields', 'revisions', 'thumbnail', 'author' ),
    'rewrite'             => array( 'slug' => 'checkouts' ), // Configurable
));
```

### Post Meta Keys

| Meta Key | Purpose |
|----------|---------|
| `_wfacp_selected_design` | Selected template type |
| `_wfacp_selected_design_slug` | Template slug |
| `_wfacp_page_layout` | Form layout configuration |
| `_wfacp_checkout_fields` | Custom checkout fields |
| `_wfacp_fieldsets_data` | Field sections data |
| `_wfacp_product` | Products assigned to checkout |
| `_wfacp_product_switcher_setting` | Product switcher config |
| `_wfacp_page_settings` | Page-specific settings |
| `_wfacp_version` | Plugin version that created page |

---

## 4. Database Schema

### Custom Table: `{prefix}wfacp_stats`

```sql
CREATE TABLE {prefix}wfacp_stats (
    ID bigint(20) unsigned NOT NULL auto_increment,
    order_id bigint(20) unsigned NOT NULL,
    wfacp_id bigint(20) unsigned NOT NULL,
    total_revenue varchar(255) not null default 0,
    cid bigint(20) unsigned NOT NULL DEFAULT 0,
    fid bigint(20) unsigned NOT NULL DEFAULT 0,
    date datetime NOT NULL,
    PRIMARY KEY (ID),
    KEY oid (order_id),
    KEY bid (wfacp_id),
    KEY date (date)
);
```

### Options (wp_options)

| Option Name | Purpose |
|-------------|---------|
| `_wfacp_global_settings` | Global plugin settings |
| `wfacp_db_ver_2_1` | Database version tracking |
| `wfacp_c_{checkout_id}` | Per-checkout customizer settings |

### Order Meta

| Meta Key | Purpose |
|----------|---------|
| `_wfacp_post_id` | Source checkout page ID |
| `_wfacp_source` | Source checkout URL |
| `_wfacp_report_data` | Revenue tracking data |
| `_wfacp_report_needs_normalization` | IPN gateway flag |

---

## 5. REST API Endpoints

**Namespace:** `wfacp-admin`

| Endpoint | Method | Callback | Purpose |
|----------|--------|----------|---------|
| `/wfacp` | GET | `wfacp_get_posts` | List checkouts |
| `/wfacp` | POST | `wfacp_create_page` | Create checkout |
| `/wfacp/{id}` | GET | `wfacp_get_page` | Get single checkout |
| `/wfacp/{id}` | DELETE | `wfacp_remove_page` | Delete checkout |
| `/wfacp/{id}/duplicate` | DELETE | `wfocu_duplicate_single` | Duplicate checkout |
| `/wfacp/{id}/export` | PUT | `wfacp_export_single` | Export checkout |
| `/wfacp/export` | POST | `wfacp_page_export` | Bulk export |
| `/wfacp/product-search` | GET | `product_list` | Search products |
| `/wfacp/products/{id}` | GET/PUT/DELETE | Multiple | Manage products |
| `/wfacp/save_state/{id}` | PUT | `save_state` | Save page state |
| `/wfacp/settings/{id}` | GET/PUT | Multiple | Manage settings |
| `/wfacp/form_fields/{id}` | GET/PUT | Multiple | Manage form fields |
| `/wfacp/activate-plugin` | POST | `activate_plugin` | Activate builder plugin |
| `/optimizations/{id}` | GET/PUT | Multiple | Optimization settings |
| `/funnels/pages/search` | GET | `search_pages` | Search pages |

---

## 6. Template System

### Template Types
1. **pre_built** - Built-in customizer templates (Layout 1, 2, 4, 9)
2. **elementor** - Elementor page builder
3. **divi** - Divi theme builder
4. **gutenberg** - WordPress block editor
5. **oxygen** - Oxygen builder
6. **embed_forms** - Embeddable form templates

### Template Loading Flow
```
1. WFACP_Template_loader::is_wfacp_checkout_page()
   └── Detect if current page is WFACP checkout

2. WFACP_Template_loader::maybe_setup_page()
   └── Load template class based on selected_type

3. WFACP_Template_loader::load_template($wfacp_id)
   └── Instantiate appropriate template class
   └── Fire wfacp_template_load action

4. Template Class (extends WFACP_Template_Common)
   └── Render checkout form
   └── Apply customizations
```

### Template Class Hierarchy
```
WFACP_Template_Common (abstract base)
├── WFACP_Pre_Built (customizer templates)
├── WFACP_Elementor_Template
├── WFACP_Divi_Template
├── WFACP_Gutenberg_Template
└── WFACP_Oxygen_Template
```

---

## 7. Page Builder Integration

### Elementor
- **Location:** `builder/elementor/`
- **Main Class:** `WFACP_Elementor`
- **Widgets:**
  - Checkout Form
  - Order Summary / Mini Cart
  - Product Switcher
  - Order Total

### Divi
- **Location:** `builder/divi/`
- **Main Class:** `WFACP_Divi`
- **Modules:**
  - Form Module
  - Mini Cart Module
  - Order Summary Module

### Gutenberg
- **Location:** `builder/gutenberg/`
- **Build:** `npm run build` in `builder/gutenberg/`
- **Blocks:**
  - Checkout Form Block
  - Mini Cart Block
  - Order Summary Block

### Oxygen
- **Location:** `builder/oxygen/`
- **Main Class:** `WFACP_Oxygen`
- **Elements:** Checkout Form, Mini Cart

---

## 8. Compatibility System

### Architecture
Compatibilities are loaded via `includes/class-compatibilities.php` which scans directories and conditionally loads classes.

### Categories

| Directory | Count | Purpose |
|-----------|-------|---------|
| `plugins/` | 30+ | General plugin compatibility |
| `gateways/` | 40+ | Payment gateway integrations |
| `themes/` | 25+ | Theme-specific fixes |
| `fields/` | 30+ | Custom checkout field plugins |
| `template-found/` | 50+ | Template-specific compatibility |
| `setup-theme/` | 10+ | Theme setup hooks |
| `ecrm/` | 5+ | CRM/Marketing integrations |

### Adding New Compatibility

1. Create class in appropriate directory
2. Class auto-loaded if conditions met
3. Hook into appropriate actions/filters

Example structure:
```php
class WFACP_Compatibility_Example {
    public function __construct() {
        // Check if target plugin/theme active
        if ( ! class_exists( 'Target_Plugin' ) ) {
            return;
        }
        // Add hooks
        add_filter( 'wfacp_checkout_fields', [ $this, 'modify_fields' ] );
    }
}
new WFACP_Compatibility_Example();
```

---

## 9. Key Hooks Reference

### Actions

| Hook | Location | Purpose |
|------|----------|---------|
| `wfacp_before_loaded` | Core init | Before plugin loads |
| `wfacp_loaded` | Core init | Plugin fully loaded |
| `wfacp_checkout_page_found` | Template loader | Checkout page detected |
| `wfacp_after_checkout_page_found` | Template loader | After page setup |
| `wfacp_template_load` | Template loader | Template being loaded |
| `wfacp_before_checkout_form_fields` | Form render | Before fields render |
| `wfacp_after_checkout_form_fields` | Form render | After fields render |
| `wfacp_before_add_to_cart` | Public | Before cart manipulation |
| `wfacp_after_add_to_cart` | Public | After cart manipulation |
| `wfacp_internal_css` | Template | Internal CSS output |

### Filters

| Filter | Purpose |
|--------|---------|
| `wfacp_should_load_core` | Control core loading |
| `wfacp_skip_common_loading` | Skip common class loading |
| `wfacp_post_type_args` | Modify CPT arguments |
| `wfacp_billing_field` | Modify billing field |
| `wfacp_shipping_field` | Modify shipping field |
| `wfacp_cart_image` | Modify cart item image |
| `wfacp_checkout_fields` | Modify all checkout fields |
| `wfacp_form_section` | Modify form sections |
| `wfacp_skip_add_to_cart` | Skip auto add-to-cart |
| `wfacp_template_edit_link` | Modify edit URLs |
| `wfacp_admin_localize_data` | Modify admin JS data |

---

## 10. Frontend Assets

### JavaScript Files (`assets/js/`)

| File | Purpose |
|------|---------|
| `checkout.js` | Main checkout form handling |
| `cart.js` | Cart functionality |
| `customizer.js` | Customizer preview |
| `embed.js` | Embed form functionality |
| `intl.js` / `intlTelInput.min.js` | International phone input |
| `hooks.js` | JS hooks system |
| `smart-buttons.js` | Express checkout buttons |
| `tracks.js` / `native-tracks.js` | Analytics tracking |
| `google.js` | Google address autocomplete |

### CSS Files (`assets/css/`)
- Template-specific styles
- Customizer styles
- Admin styles

---

## 11. Helper Functions

```php
// Get current template instance
wfacp_template();

// Check if Elementor active
wfacp_is_elementor();

// Check Elementor edit mode
wfacp_elementor_edit_mode();

// HPOS-compatible order meta
wfacp_get_order_meta( $order, $key );

// Check HPOS status
wfacp_is_hpos_enabled();

// Custom form field output
wfacp_form_field( $key, $args, $value );
```

---

## 12. URL Parameters

| Parameter | Purpose |
|-----------|---------|
| `aero-add-to-checkout` | Add products via URL |
| `aero-qty` | Set product quantities |
| `aero-default` | Set default product selection |
| `aero-best-value` | Mark best value product |
| `aero-coupon` | Auto-apply coupon |

---

## 13. Security

### Nonce Keys
- `wfacp_admin_secure_key` - Admin AJAX operations
- Various per-operation nonces

### Capability Checks
- `manage_woocommerce` - Primary capability
- Custom role system via `WFACP_Core()->role`

### Sanitization
- Uses `bwf_clean()` for array sanitization
- `wc_clean()` for WooCommerce data
- Standard WordPress escaping functions

---

## 14. Debug Mode

Enable in `wp-config.php`:
```php
define( 'WFACP_IS_DEV', true );
```

Effects:
- Loads unminified JS/CSS
- Enables additional logging
- Shows debug information

---

## 15. HPOS Compatibility

The plugin declares WooCommerce HPOS (High-Performance Order Storage) compatibility:

```php
// Always use for order meta access
wfacp_get_order_meta( $order, $key );

// Check HPOS status
if ( wfacp_is_hpos_enabled() ) {
    // HPOS-specific code
}
```
