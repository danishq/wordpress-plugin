# FunnelKit Checkout - Feature to Files Mapping

## Core Functionality

| Feature | Primary File(s) |
|---------|-----------------|
| Plugin Bootstrap | `woofunnels-aero-checkout.php`, `start.php` |
| Main Singleton | `includes/class-wfacp-core.php` |
| Common Utilities | `includes/class-wfacp-common.php`, `includes/class-wfacp-common-helper.php` |
| Helper Functions | `includes/functions.php` |
| CPT Registration | `includes/class-wfacp-common.php:register_post_type()` |

## Template System

| Feature | Primary File(s) |
|---------|-----------------|
| Template Loader | `includes/class-wfacp-template-loader.php` |
| Base Template Class | `public/class-template-common.php` |
| Pre-built Templates | `builder/customizer/class-wfacp-pre-built.php` |
| Customizer Integration | `builder/customizer/class-wfacp-customizer.php` |

### Pre-built Template Layouts

| Layout | Directory |
|--------|-----------|
| Layout 1 | `builder/customizer/templates/layout_1/` |
| Layout 2 | `builder/customizer/templates/layout_2/` |
| Layout 4 | `builder/customizer/templates/layout_4/` |
| Layout 9 | `builder/customizer/templates/layout_9/` |
| Embed Forms | `builder/customizer/templates/embed_forms_1/` |

## Page Builders

### Elementor

| Feature | File |
|---------|------|
| Main Integration | `builder/elementor/class-wfacp-elementor.php` |
| Template Class | `builder/elementor/class-wfacp-elementor-template.php` |
| Base Widget | `builder/elementor/class-abstract-wfacp-fields.php` |
| HTML Block | `builder/elementor/class-wfacp-html-block-elementor.php` |
| Widgets | `builder/elementor/widgets/` |
| Importer | `importer/class-wfacp-elementor-importer.php` |

### Divi

| Feature | File |
|---------|------|
| Main Integration | `builder/divi/class-wfacp-divi.php` |
| Template Class | `builder/divi/class-wfacp-divi-template.php` |
| Modules | `builder/divi/modules/` |
| Importer | `importer/class-wfacp-divi-importer.php` |

### Gutenberg

| Feature | File |
|---------|------|
| Main Integration | `builder/gutenberg/class-wfacp-gutenberg.php` |
| Template Class | `builder/gutenberg/template/template.php` |
| Block Source | `builder/gutenberg/src/` |
| Built Blocks | `builder/gutenberg/dist/` |
| Importer | `importer/class-wfacp-gutenberg-importer.php` |

### Oxygen

| Feature | File |
|---------|------|
| Main Integration | `builder/oxygen/class-wfacp-oxygen.php` |
| Template Class | `builder/oxygen/class-wfacp-oxygen-template.php` |
| Elements | `builder/oxygen/elements/` |
| Importer | `importer/class-wfacp-oxy-importer.php` |

## Admin Interface

| Feature | Primary File(s) |
|---------|-----------------|
| Admin Controller | `admin/class-wfacp-admin.php` |
| REST API | `admin/rest-api/class-wfacp-rest-funnels.php` |
| Page Views | `admin/views/` |
| Settings Sections | `admin/views/sections/` |
| Admin Assets | `admin/assets/` |
| Exporter | `admin/class-wfacp-exporter.php` |
| Page Insertion | `admin/class-insert-page.php` |

## Frontend (Public)

| Feature | Primary File(s) |
|---------|-----------------|
| Public Controller | `public/class-wfacp-public.php` |
| Checkout Form | `public/template-common/checkout/form-checkout.php` |
| Order Summary | `public/template-common/order-summary.php` |
| Mini Cart | `public/global/mini-cart/` |
| Order Total | `public/global/order-total/` |
| Product Switcher | `public/template-common/product-switcher/` |
| Shipping Options | `public/template-common/shipping-options.php` |
| Payment Methods | `public/template-common/checkout/payment.php` |

## Checkout Fields

| Feature | Primary File(s) |
|---------|-----------------|
| Field Rendering | `includes/functions.php:wfacp_form_field()` |
| Field Configuration | `admin/views/sections/fields/` |
| Billing Fields | `includes/class-wfacp-common.php` |
| Shipping Fields | `includes/class-wfacp-common.php` |
| Custom Fields | `includes/class-wfacp-common.php:setup_fields_billing()` |

## Product System

| Feature | Primary File(s) |
|---------|-----------------|
| Product Management | `public/class-wfacp-public.php:add_to_cart()` |
| Product Switcher | `public/template-common/product-switcher/` |
| Product Settings | `includes/class-wfacp-common.php:get_page_product_settings()` |
| Discount Handling | `public/class-wfacp-public.php:calculate_totals()` |

## Analytics & Reporting

| Feature | Primary File(s) |
|---------|-----------------|
| Stats Collection | `includes/class-wfacp-reporting.php` |
| Database Table | `includes/class-wfacp-reporting.php:create_table()` |
| Revenue Tracking | `includes/class-wfacp-reporting.php:updating_reports_from_orders()` |

## Import/Export

| Feature | Primary File(s) |
|---------|-----------------|
| Base Importer | `importer/class-wfacp-template-importer.php` |
| Customizer Import | `importer/class-wfacp-customizer-importer.php` |
| Exporter | `admin/class-wfacp-exporter.php` |

## Compatibility System

| Category | Index File | Classes Directory |
|----------|------------|-------------------|
| Plugins | `compatibilities/plugins/index.php` | `compatibilities/plugins/` |
| Gateways | `compatibilities/gateways/index.php` | `compatibilities/gateways/` |
| Themes | `compatibilities/themes/index.php` | `compatibilities/themes/` |
| Fields | `compatibilities/fields/` | `compatibilities/fields/` |
| Template Found | `compatibilities/template-found/` | `compatibilities/template-found/` |
| Setup Theme | `compatibilities/setup-theme/` | `compatibilities/setup-theme/` |
| eCRM | `compatibilities/ecrm/` | `compatibilities/ecrm/` |

### Key Compatibility Classes

| Plugin/Theme | File |
|--------------|------|
| Stripe | `compatibilities/gateways/class-stripe.php` |
| PayPal | `compatibilities/gateways/class-paypal.php` |
| WooCommerce Subscriptions | `compatibilities/plugins/class-wc-subscription.php` |
| Astra Theme | `compatibilities/themes/class-astra.php` |
| Divi Theme | `compatibilities/themes/class-divi.php` |
| CartFlows | `compatibilities/setup-theme/class-cartflows.php` |

## Customizer Options

| Section | File |
|---------|------|
| Layout | `builder/customizer/customizer-options/class-section-layout.php` |
| Form | `builder/customizer/customizer-options/class-section-form.php` |
| Cart | `builder/customizer/customizer-options/class-section-cart.php` |
| Header | `builder/customizer/customizer-options/class-section-header.php` |
| Footer | `builder/customizer/customizer-options/class-section-footer.php` |
| Product Switcher | `builder/customizer/customizer-options/class-product-switcher.php` |
| Mini Cart | `builder/customizer/customizer-options/class-section-mini-cart-summary.php` |
| Order Summary | `builder/customizer/customizer-options/class-section-order-summary.php` |
| Styles | `builder/customizer/customizer-options/class-section-styles.php` |
| Custom CSS | `builder/customizer/customizer-options/class-section-custom-css.php` |
| Testimonials | `builder/customizer/customizer-options/class-section-testimonial.php` |
| Guarantees | `builder/customizer/customizer-options/class-section-guarantee-badge.php` |

## JavaScript Files

| Feature | File |
|---------|------|
| Main Checkout | `assets/js/checkout.js` |
| Cart Operations | `assets/js/cart.js` |
| Customizer Preview | `assets/js/customizer.js` |
| Embed Forms | `assets/js/embed.js` |
| Phone Input | `assets/js/intl.js`, `assets/js/intlTelInput.min.js` |
| JS Hooks | `assets/js/hooks.js` |
| Express Buttons | `assets/js/smart-buttons.js` |
| Analytics | `assets/js/tracks.js`, `assets/js/native-tracks.js` |
| Address Autocomplete | `assets/js/google.js` |

## CSS Files

| Feature | Location |
|---------|----------|
| Frontend Styles | `assets/css/` |
| Admin Styles | `admin/assets/css/` |
| Template Styles | `builder/customizer/templates/*/assets/css/` |

## Modules

| Module | Directory |
|--------|-----------|
| Login Flow | `modules/login-flow/` |

## WooFunnels Core (Submodule)

| Feature | Directory |
|---------|-----------|
| Core Classes | `woofunnels/includes/` |
| Contact System | `woofunnels/contact/` |
| Connectors | `woofunnels/connector/` |
| Action Scheduler | `woofunnels/as-data-store/` |
| PHPCS Config | `woofunnels/phpcs.xml` |
