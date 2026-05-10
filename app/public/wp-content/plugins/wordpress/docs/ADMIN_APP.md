# FunnelKit Checkout - Admin App Architecture

## Overview

The FunnelKit Checkout admin interface is built with **Vue.js 2.x** and uses a combination of PHP-rendered templates with Vue.js reactive components. The app follows a hybrid rendering approach where PHP generates the HTML shell and Vue.js hydrates specific sections.

---

## 1. URL Structure

### Base URL
```
admin.php?page=wfacp
```

### URL Patterns

| URL Pattern | Purpose | View File |
|-------------|---------|-----------|
| `?page=wfacp` | Listing page (all checkouts) | `admin/views/admin.php` |
| `?page=wfacp&wfacp_id={id}` | Single checkout (design section) | `admin/views/view.php` |
| `?page=wfacp&wfacp_id={id}&section=design` | Template selection | `admin/views/sections/design.php` |
| `?page=wfacp&wfacp_id={id}&section=product` | Product assignment | `admin/views/sections/product.php` |
| `?page=wfacp&wfacp_id={id}&section=fields` | Form fields builder | `admin/views/sections/fields.php` |
| `?page=wfacp&wfacp_id={id}&section=settings` | Page settings | `admin/views/sections/settings.php` |
| `?page=wfacp&section=import` | Import checkouts | `admin/views/sections/import.php` |
| `?page=wfacp&section=export` | Export checkouts | `admin/views/sections/export.php` |
| `?page=wfacp&section=bwf_settings` | General BWF settings | BWF Settings class |

### Menu Registration

```php
// admin/class-wfacp-admin.php:167
add_submenu_page( 'woofunnels', 'Checkouts', 'Checkouts', $user, 'wfacp', [$this, 'admin_page'] );
```

---

## 2. Tech Stack

| Library | Version | Purpose | Location |
|---------|---------|---------|----------|
| Vue.js | 2.6.10 | Reactive UI framework | `admin/includes/vuejs/vue.min.js` |
| vue-form-generator | 2.3.4 | Dynamic form generation | `admin/includes/vuejs/vfg.min.js` |
| vue-multiselect | 2.1.0 | Dropdown/select components | `admin/includes/vuejs/vue-multiselect.min.js` |
| iziModal | - | Modal dialogs | `admin/includes/iziModal/iziModal.js` |
| SweetAlert2 | - | Alert dialogs | `admin/assets/js/wfacp-sweetalert.min.js` |
| jQuery | WP bundled | DOM manipulation | WordPress core |
| Backbone.js | WP bundled | Legacy list tables | WordPress core |

---

## 3. File Structure

```
admin/
├── class-wfacp-admin.php          # Main admin controller
├── class-wfacp-wizard.php         # Setup wizard
├── class-wfacp-importer.php       # Import/export handler
├── rest-api/
│   └── class-wfacp-rest-funnels.php  # REST API endpoints
├── assets/
│   ├── js/
│   │   ├── wfacp.js               # Main Vue app (265KB)
│   │   ├── global.js              # Global utilities
│   │   └── wfacp-modal.js         # Modal helpers
│   └── css/
│       ├── wfacp-admin-app.css    # Vue app styles
│       └── wfacp-admin.css        # General admin styles
├── includes/
│   ├── vuejs/                     # Vue.js libraries
│   ├── iziModal/                  # Modal library
│   └── wfacpkirki/                # Customizer framework
└── views/
    ├── admin.php                  # Listing page
    ├── view.php                   # Single checkout wrapper
    ├── global/
    │   └── model.php              # Shared modals
    └── sections/
        ├── design.php             # Design section
        ├── design/
        │   ├── template-preview.php
        │   ├── template-new.php
        │   └── models.php
        ├── product.php            # Products section
        ├── fields.php             # Fields builder section
        ├── fields/
        │   ├── field_container.php
        │   ├── input_fields.php
        │   └── models.php
        └── settings.php           # Settings section
```

---

## 4. Vue.js Application Architecture

### Main Entry Point

```javascript
// admin/assets/js/wfacp.js:5292
$(window).on('load', function () {
    Vue.component('multiselect', window.VueMultiselect.default);
    window.builder = new wfacp_builder(wfacp_data);
});
```

### Class Hierarchy

```
wfacp_builder (Main orchestrator)
├── wfacp_products (Products section Vue app)
├── wfacp_layouts (Fields/Layout section Vue app)
├── wfacp_settings (Settings section Vue app)
└── wfacp_design (Design section Vue app)
```

### wfacp_builder Class

The main builder class initializes all section-specific Vue instances:

```javascript
class wfacp_builder {
    constructor(data) {
        this.el = '#wfacp_control';
        this.setupData(data);      // Parse wfacp_data
        this.initializeVue();      // Create Vue instances
        this.model();              // Initialize modals
    }

    setupData(data) {
        this.id = data.id;
        this.name = data.name;
        this.products = data.products;
        this.design = data.design;
        this.layout = data.layout;
        this.settings = data.settings;
        // ...more properties
    }
}
```

### Section Vue Instances

#### Products Section (`wfacp_products`)
- **Mount Point:** `#wfacp_product_container`
- **Features:** Product search, drag-drop sorting, discount settings
- **Save Method:** `save_products()` via AJAX

#### Fields/Layout Section (`wfacp_layouts`)
- **Mount Point:** `#wfacp_layout_container`
- **Features:** Drag-drop field builder, multi-step forms, field editing
- **Save Method:** `save_template()` via AJAX

---

## 5. Data Flow

### PHP to JavaScript (Localization)

```php
// admin/class-wfacp-admin.php:232-238
wp_localize_script( 'wfacp', 'wfacp_data', $this->get_localize_data() );
wp_localize_script( 'wfacp', 'wfacp_localization', WFACP_Common::get_builder_localization() );
wp_localize_script( 'wfacp', 'wfacp_secure', [
    'nonce' => wp_create_nonce( 'wfacp_admin_secure_key' ),
]);
```

### Localized Data Structure (`wfacp_data`)

```javascript
wfacp_data = {
    // Checkout identification
    id: 123,                          // Checkout post ID
    name: 'My Checkout',              // Post title
    post_name: 'my-checkout',         // URL slug
    post_url: 'https://...',          // Frontend URL
    base_url: 'https://...',          // Site URL

    // Products
    products: {
        'unique_key_1': {
            id: 456,
            title: 'Product Name',
            type: 'simple',
            price: '$10.00',
            image: 'https://...',
            discount_type: 'none',
            discount_amount: 0
        }
    },
    products_settings: {
        add_to_cart_setting: 'add_to_cart'
    },

    // Design/Template
    design: {
        selected_type: 'pre_built',
        selected: 'layout_1',
        designs: {...},               // Available templates
        design_types: {...},
        template_active: 'yes'
    },

    // Layout/Fields
    layout: {
        fieldsets: {
            single_step: [
                {
                    name: 'Billing Details',
                    class: 'wfacp-section',
                    fields: [
                        {
                            id: 'billing_first_name',
                            field_type: 'billing',
                            type: 'text',
                            label: 'First Name',
                            required: true
                        }
                    ]
                }
            ]
        },
        steps: {
            single_step: { name: 'Single Step', active: 'yes' },
            two_step: { name: 'Two Step', active: 'no' },
            third_step: { name: 'Three Step', active: 'no' }
        },
        input_fields: {...},          // Available fields
        available_fields: {...}       // All field definitions
    },

    // Settings
    settings: {...},

    // Global
    currency: '$',
    global_settings: {...},
    global_dependency_messages: {...}
};
```

---

## 6. REST API Communication

### Namespace & Base
```
Namespace: wfacp-admin
Base URL: /wp-json/wfacp-admin/
```

### AJAX Wrapper Class

```javascript
// In wfacp.js
class wfacp.ajax {
    ajax(action, data) {
        // Makes REST API call to /wp-json/wfacp-admin/{action}
    }
}

// Usage
let wp_ajax = new wfacp.ajax();
wp_ajax.ajax('save_layout', { fieldsets: {...}, wfacp_id: 123 });
wp_ajax.success = (response) => { /* handle success */ };
wp_ajax.complete = (response) => { /* always runs */ };
```

### Key Endpoints

| Action | HTTP Method | Endpoint | Handler |
|--------|-------------|----------|---------|
| `save_layout` | PUT | `/wfacp/form_fields/{id}` | `save_form_fields()` |
| `save_products` | PUT | `/wfacp/products/{id}` | `add_product()` |
| `remove_product` | DELETE | `/wfacp/products/{id}` | `remove_product()` |
| `add_product` | PUT | `/wfacp/products/{id}` | `add_product()` |
| `save_settings` | PUT | `/wfacp/settings/{id}` | `update_customsettings()` |
| `save_optimizations` | PUT | `/optimizations/{id}` | `save_optimizations()` |

---

## 7. Settings Save/Load Mechanisms

### Save Flow (JavaScript → PHP)

```
1. User clicks "Save Changes"
   ↓
2. Vue method called (e.g., save_template())
   ↓
3. Data collected from Vue reactive data
   ↓
4. wfacp.ajax() sends REST request
   ↓
5. REST endpoint receives data
   ↓
6. PHP sanitizes with bwf_clean() / sanitize_custom()
   ↓
7. WFACP_Common::update_page_*() saves to post_meta
   ↓
8. Response returned to JavaScript
   ↓
9. Success notification shown
```

### PHP Save Methods (WFACP_Common)

```php
// Products
WFACP_Common::update_page_product($wfacp_id, $products);
// Saves to: _wfacp_selected_products meta

// Product Settings
WFACP_Common::update_page_product_setting($wfacp_id, $settings);
// Saves to: _wfacp_selected_products_settings meta

// Layout/Fields
WFACP_Common::update_page_layout($page_id, $data);
// Saves to: _wfacp_page_layout, _wfacp_fieldsets_data, _wfacp_checkout_fields

// Design/Template
WFACP_Common::update_page_design($page_id, $data);
// Saves to: _wfacp_selected_design meta

// Page Settings
WFACP_Common::update_page_settings($page_id, $data);
// Saves to: _wfacp_page_settings meta
```

### Load Flow (PHP → JavaScript)

```
1. Admin page loads
   ↓
2. WFACP_admin::get_localize_data() called
   ↓
3. WFACP_Common::get_page_*() methods retrieve from post_meta
   ↓
4. Data assembled into $localize_data array
   ↓
5. wp_localize_script() outputs as wfacp_data
   ↓
6. Vue.js initializes with wfacp_data
   ↓
7. Reactive UI rendered
```

### PHP Load Methods (WFACP_Common)

```php
// Products
WFACP_Common::get_page_product($wfacp_id);
// Reads from: _wfacp_selected_products meta

// Product Settings
WFACP_Common::get_page_product_settings($wfacp_id);
// Reads from: _wfacp_selected_products_settings meta

// Layout/Fields
WFACP_Common::get_page_layout($page_id);
// Reads from: _wfacp_page_layout meta

// Design/Template
WFACP_Common::get_page_design($page_id, $with_defaults);
// Reads from: _wfacp_selected_design meta

// Page Settings
WFACP_Common::get_page_settings($page_id);
// Reads from: _wfacp_page_settings meta
```

---

## 8. Post Meta Keys Reference

| Meta Key | Purpose | Saved By |
|----------|---------|----------|
| `_wfacp_selected_design` | Template type and slug | `update_page_design()` |
| `_wfacp_selected_design_slug` | Template slug only | Template import |
| `_wfacp_page_layout` | Full layout data for builder | `update_page_layout()` |
| `_wfacp_fieldsets_data` | Processed fieldset data for rendering | `update_page_layout()` |
| `_wfacp_checkout_fields` | WooCommerce-compatible field format | `update_page_layout()` |
| `_wfacp_selected_products` | Products array with settings | `update_page_product()` |
| `_wfacp_selected_products_settings` | Product display settings | `update_page_product_setting()` |
| `_wfacp_product_switcher_setting` | Product switcher configuration | `update_product_switcher_setting()` |
| `_wfacp_page_settings` | Page-level settings | `update_page_settings()` |
| `_wfacp_save_address_order` | Field ordering for addresses | `update_page_layout()` |
| `_wfacp_version` | Plugin version that saved page | `update_page_layout()` |
| `_post_description` | Page description | Page create/update |

---

## 9. Vue Component Patterns

### Template Binding (PHP + Vue)

```php
<!-- PHP renders shell with Vue directives -->
<div id="wfacp_product_container">
    <div v-for="(product, key) in products" :key="key">
        <input v-model="product.title" @change="save_products()">
    </div>
</div>
```

### Modal Pattern

```javascript
// Open modal
$('#modal-add-product').iziModal('open');

// Modal with Vue
this.add_product_vue = new Vue({
    el: '#modal-add-product-form',
    components: { Multiselect: window.VueMultiselect.default },
    data: { selectedProducts: [] },
    methods: {
        onSubmit() {
            // Handle form submission
        }
    }
});
```

### Sortable Pattern

```javascript
$('.sortable-container').sortable({
    items: '.sortable-item',
    handle: '.drag-handle',
    stop: (event, ui) => {
        this.save_template();
    }
});
```

---

## 10. Extending the Admin App

### Adding New Section

1. Create section file: `admin/views/sections/my-section.php`
2. Register in `WFACP_Common::get_admin_menu()`
3. Add Vue container with unique ID
4. Create Vue instance in `wfacp.js` or separate file
5. Add REST endpoint if needed

### Adding New Field Type

1. Register field in `WFACP_Common::get_advanced_fields()`
2. Add field template in `admin/views/sections/fields/`
3. Handle in Vue `editField()` method
4. Add save handling in `save_form_fields()` REST endpoint

### Adding New Setting

1. Add to settings schema in `get_localize_data()`
2. Add form field in settings section PHP
3. Handle in `update_customsettings()` REST handler
4. Retrieve in `get_customsettings()` REST handler

---

## 11. Security

### Nonce Verification
```javascript
// Passed via localization
wfacp_secure.nonce
```

### Permission Checks
```php
// REST API permission callbacks
public function get_read_api_permission_check() {
    return wffn_rest_api_helpers()->get_api_permission_check('funnel', 'read');
}

public function get_write_api_permission_check() {
    return wffn_rest_api_helpers()->get_api_permission_check('funnel', 'write');
}
```

### Data Sanitization
```php
// Custom sanitizer for JSON data
public function sanitize_custom($data) {
    $data = json_decode($data, true);
    return bwf_clean($data);
}
```

---

## 12. Debugging

### Enable Dev Mode
```php
// wp-config.php
define('BWF_DEV', true);
```

Effects:
- Loads unminified `wfacp_combined.js` instead of `.min.js`
- Additional console logging

### Vue DevTools
Install Vue.js DevTools browser extension to inspect:
- Component hierarchy
- Reactive data
- Events

### REST API Debugging
```bash
# Test endpoint
curl -X GET "https://site.com/wp-json/wfacp-admin/wfacp/123" \
  -H "X-WP-Nonce: {nonce}"
```
