# FunnelKit Checkout - Dependencies Map

## External Dependencies

### Required

| Dependency | Type | Purpose |
|------------|------|---------|
| WordPress | Core | Platform |
| WooCommerce | Plugin | E-commerce functionality |

### Optional (Page Builders)

| Dependency | Type | Files Affected |
|------------|------|----------------|
| Elementor | Plugin | `builder/elementor/*` |
| Divi Theme/Builder | Theme/Plugin | `builder/divi/*` |
| Oxygen Builder | Plugin | `builder/oxygen/*` |
| WordPress Block Editor | Core | `builder/gutenberg/*` |

### Optional (Integrations)

| Dependency | Type | Compatibility File |
|------------|------|-------------------|
| WooCommerce Subscriptions | Plugin | `compatibilities/plugins/class-wc-subscription.php` |
| Stripe | Gateway | `compatibilities/gateways/class-stripe.php` |
| PayPal | Gateway | `compatibilities/gateways/class-paypal.php` |
| 70+ other plugins | Various | `compatibilities/*/` |

---

## Internal Dependencies

### Core Class Dependencies

```
WFACP_Core (Singleton)
├── WFACP_Common (Static utilities)
│   └── WFACP_Common_Helper (Base methods)
├── WFACP_Template_loader
│   └── WFACP_Template_Common (Base template)
├── WFACP_admin
├── WFACP_Public
├── WFACP_Reporting
└── WFACP_Role
```

### Class Initialization Order

```
1. woofunnels-aero-checkout.php
   └── Constants defined

2. start.php
   └── WooFunnel_Loader class
   └── WooFunnel_WFACP class

3. WFACP_Common::init() [plugins_loaded -1]
   └── Registers hooks
   └── Calls WooFunnel_Loader::include_core()

4. woofunnels/includes/class-woofunnels-dashboard-loader.php
   └── WooFunnels core classes

5. WFACP_Core [wfacp_loaded]
   └── template_loader = WFACP_Template_loader
   └── admin = WFACP_admin
   └── public = WFACP_Public
   └── importer = WFACP_Template_Importer
   └── pay = WFACP_Order_Pay
   └── reporting = WFACP_Reporting
   └── role = WFACP_Role
```

### Template Class Dependencies

```
WFACP_Template_Common (Abstract Base)
├── WFACP_Pre_Built
│   └── Layout_1, Layout_2, Layout_4, Layout_9
├── WFACP_Elementor_Template
│   └── Requires: Elementor\Plugin
├── WFACP_Divi_Template
│   └── Requires: ET_Builder_Element
├── WFACP_Gutenberg_Template
│   └── Requires: Block Editor
└── WFACP_Oxygen_Template
    └── Requires: OxygenElement
```

---

## File Dependencies

### Main Plugin File

```
woofunnels-aero-checkout.php
├── start.php
├── includes/class-wfacp-core.php
│   ├── includes/class-wfacp-common-helper.php
│   ├── includes/class-wfacp-common.php
│   ├── includes/class-wfacp-template-loader.php
│   ├── includes/class-wfacp-reporting.php
│   ├── includes/class-wfacp-role.php
│   ├── includes/class-wfacp-order-pay.php
│   ├── includes/functions.php
│   ├── admin/class-wfacp-admin.php
│   ├── public/class-wfacp-public.php
│   └── importer/class-wfacp-template-importer.php
└── woofunnels/ (submodule)
    └── includes/class-woofunnels-dashboard-loader.php
```

### Template Loader Dependencies

```
class-wfacp-template-loader.php
├── public/class-template-common.php
├── builder/customizer/class-wfacp-pre-built.php (if pre_built)
├── builder/customizer/class-wfacp-customizer.php (if pre_built)
├── builder/elementor/class-wfacp-elementor.php (if elementor)
├── builder/elementor/class-wfacp-elementor-template.php (if elementor)
├── builder/divi/class-wfacp-divi.php (if divi)
├── builder/divi/class-wfacp-divi-template.php (if divi)
├── builder/gutenberg/class-wfacp-gutenberg.php (if gutenberg)
└── builder/oxygen/class-wfacp-oxygen.php (if oxygen)
```

### Admin Dependencies

```
admin/class-wfacp-admin.php
├── admin/rest-api/class-wfacp-rest-funnels.php
├── admin/class-wfacp-exporter.php
├── admin/class-insert-page.php
├── admin/views/*.php
└── admin/assets/*
```

### Public Dependencies

```
public/class-wfacp-public.php
├── public/class-template-common.php
├── public/template-common/*.php
├── public/global/mini-cart/*.php
├── public/global/order-total/*.php
└── assets/js/*.js
```

---

## Compatibility Loading Order

```
includes/class-compatibilities.php
├── compatibilities/setup-theme/index.php [after_setup_theme]
│   └── Theme-specific setup classes
├── compatibilities/plugins/index.php [plugins_loaded 99]
│   └── Plugin compatibility classes
├── compatibilities/themes/index.php [plugins_loaded 100]
│   └── Theme compatibility classes
├── compatibilities/gateways/index.php [wfacp_loaded]
│   └── Payment gateway classes
├── compatibilities/fields/index.php [wfacp_template_load]
│   └── Field compatibility classes
├── compatibilities/template-found/index.php [wfacp_after_checkout_page_found]
│   └── Template-specific compatibility
└── compatibilities/ecrm/index.php [wfacp_loaded]
    └── CRM integration classes
```

---

## JavaScript Dependencies

### Frontend

```
checkout.js
├── jQuery
├── wc-checkout (WooCommerce)
├── wc-cart-fragments (WooCommerce)
└── hooks.js (internal)

cart.js
├── jQuery
└── hooks.js

intl.js
├── jQuery
└── intlTelInput.min.js

smart-buttons.js
├── jQuery
└── PayPal/Stripe SDK (external)

google.js
├── jQuery
└── Google Maps API (external)
```

### Admin

```
wfacp_combined.js
├── jQuery
├── underscore
├── backbone
├── updates (WordPress)
├── vue.min.js
├── vfg.min.js (vue-form-generator)
├── vue-multiselect.min.js
├── sweetalert2
└── iziModal.js
```

---

## Submodule Dependencies

### woofunnels/ Submodule

```
woofunnels/
├── includes/
│   ├── class-woofunnels-dashboard-loader.php
│   ├── class-bwf-ecomm-tracking-common.php
│   └── class-woofunnels-api.php
├── contact/
│   ├── class-woofunnels-db-tables.php
│   └── class-woofunnels-contact.php
├── connector/
│   └── class-wfco-load-connectors.php
└── as-data-store/
    └── class-woofunnels-as-ds.php
```

---

## Conditional Loading

### Page Builder Detection

```php
// Elementor
if ( defined( 'ELEMENTOR_VERSION' ) ) {
    // Load Elementor integration
}

// Divi
if ( defined( 'ET_BUILDER_VERSION' ) || function_exists( 'et_setup_theme' ) ) {
    // Load Divi integration
}

// Oxygen
if ( defined( 'CT_VERSION' ) ) {
    // Load Oxygen integration
}

// Gutenberg
if ( function_exists( 'register_block_type' ) ) {
    // Load Gutenberg integration
}
```

### Compatibility Detection

```php
// WooCommerce Subscriptions
if ( class_exists( 'WC_Subscriptions' ) ) {
    require_once 'class-wc-subscription.php';
}

// Stripe
if ( class_exists( 'WC_Stripe' ) || class_exists( 'WC_Gateway_Stripe' ) ) {
    require_once 'class-stripe.php';
}
```

---

## Version Dependencies

| Component | Minimum Version | Notes |
|-----------|-----------------|-------|
| WordPress | 5.0 | Block editor support |
| WooCommerce | 3.0 | CRUD methods |
| PHP | 7.0 | Type declarations |
| Elementor | 3.0 | Widget registration API |
| Divi | 4.0 | Module API |

---

## Circular Dependency Prevention

The plugin avoids circular dependencies through:

1. **Singleton Pattern** - Core classes use singletons
2. **Lazy Loading** - Components loaded on demand
3. **Hook-based Initialization** - Classes initialize via WordPress hooks
4. **Dependency Injection** - Template loader receives dependencies

```php
// Example: Lazy loading in WFACP_Core
public function __get( $key ) {
    if ( $key === 'admin' && is_null( $this->admin ) ) {
        $this->admin = WFACP_admin::get_instance();
    }
    return $this->$key;
}
```
