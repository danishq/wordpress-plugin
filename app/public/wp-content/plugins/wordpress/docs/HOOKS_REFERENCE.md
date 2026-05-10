# FunnelKit Checkout - Hooks Reference

## Actions

### Core Lifecycle

```php
// Before plugin components load
do_action( 'wfacp_before_loaded' );

// Plugin fully initialized
do_action( 'wfacp_loaded' );

// WooFunnels core loaded
do_action( 'woofunnels_loaded', $path );
```

### Page Detection

```php
// Start of page detection process
do_action( 'wfacp_start_page_detection' );

// Checkout page found (fires twice - once on detection, once after setup)
do_action( 'wfacp_checkout_page_found', $page_id, $post );

// After checkout page fully configured
do_action( 'wfacp_after_checkout_page_found' );

// Non-checkout page detected
do_action( 'wfacp_none_checkout_pages', $post );

// Global checkout override triggered
do_action( 'wfacp_changed_default_woocommerce_page', $page_id );
```

### Template Loading

```php
// Template being loaded
do_action( 'wfacp_template_load' );

// Before process checkout template
do_action( 'wfacp_before_process_checkout_template_loader', $wfacp_id, $template_instance );

// Register custom template types
do_action( 'wfacp_register_template_types', $template_loader );
```

### Checkout Form

```php
// Before checkout form fields render
do_action( 'wfacp_before_checkout_form_fields', $checkout );

// After checkout form fields render
do_action( 'wfacp_after_checkout_form_fields', $checkout );

// Before form section
do_action( 'wfacp_before_form_section_' . $section_key );

// After form section
do_action( 'wfacp_after_form_section_' . $section_key );

// Internal CSS output hook
do_action( 'wfacp_internal_css' );
```

### Cart Operations

```php
// Cart add-to-cart initialization
do_action( 'wfacp_add_to_cart_init', $public_instance );

// Before add to cart
do_action( 'wfacp_before_add_to_cart' );

// After add to cart
do_action( 'wfacp_after_add_to_cart' );

// Get product switcher data
do_action( 'wfacp_get_product_switcher_data' );
```

### Settings & Configuration

```php
// Before checking advanced settings
do_action( 'wfacp_before_checking_advanced_settings', $settings, $public_instance );

// After checking advanced settings
do_action( 'wfacp_after_checking_advanced_settings', $settings, $public_instance );
```

### Admin

```php
// Admin JS enqueued
do_action( 'wfacp_admin_js_enqueued' );

// Builder design after template
do_action( 'wfacp_builder_design_after_template' );

// License activated
do_action( 'wfacp_license_activated' );
```

### Order Bumps Integration

```php
// Before order bump removed from cart
do_action( 'wfob_before_remove_bump_from_cart' );

// Before order bump added to cart
do_action( 'wfob_before_add_to_cart' );
```

---

## Filters

### Core Loading

```php
// Control whether core should load
apply_filters( 'wfacp_should_load_core', true );

// Skip common class loading
apply_filters( 'wfacp_skip_common_loading', false );
```

### Post Type

```php
// Modify CPT arguments
apply_filters( 'wfacp_post_type_args', $args );

// Modify URL rewrite slug
apply_filters( 'wfacp_rewrite_slug', array( 'slug' => 'checkouts' ) );
```

### Checkout Fields

```php
// Modify billing field
apply_filters( 'wfacp_billing_field', $field, $key );

// Modify shipping field
apply_filters( 'wfacp_shipping_field', $field, $key );

// Modify checkout fields
apply_filters( 'wfacp_checkout_fields', $fields );

// Modify form field key
apply_filters( 'wfacp_form_field_key', $key, $args, $value );

// Before checkout label
apply_filters( 'wfacp_before_checkout_label', '', $args );

// After checkout label
apply_filters( 'wfacp_after_checkout_label', '', $args );

// Default billing address fields
apply_filters( 'wfacp_default_billing_address_fields', $fields );

// Default shipping address fields
apply_filters( 'wfacp_default_shipping_address_fields', $fields );

// HTML fields control
apply_filters( 'wfacp_html_fields_' . $field_key, true );

// Admin order field display
apply_filters( 'wfacp_admin_order_field', $field, $key, $order );

// Forms field configuration
apply_filters( 'wfacp_forms_field', $field, $key );
```

### Form Sections

```php
// Modify form section
apply_filters( 'wfacp_form_section', $output, $section, $checkout );

// Hide section
apply_filters( 'wfacp_hide_section', false, $section );
```

### Products & Cart

```php
// Cart image
apply_filters( 'wfacp_cart_image', $thumbnail, $product );

// Default product configuration
apply_filters( 'wfacp_default_product', $product_data, $product, $wfacp_id );

// Skip add to cart
apply_filters( 'wfacp_skip_add_to_cart', false, $public_instance );

// Product image
apply_filters( 'wfacp_product_image', $image_url, $product );

// Show item quantity
apply_filters( 'wfacp_show_item_quantity', true, $cart_item );

// Display quantity increment
apply_filters( 'wfacp_display_quantity_increment', true, $cart_item );

// Show "you save" text
apply_filters( 'wfacp_show_you_save_text', true, $cart_item );

// Enable delete item
apply_filters( 'wfacp_enable_delete_item', true, $cart_item );

// Product switcher show quantity
apply_filters( 'wfacp_product_switcher_show_quantity_incrementer', true, $product, $settings );
```

### Templates

```php
// Register templates
apply_filters( 'wfacp_register_templates', array(), $template_loader );

// Page located status
apply_filters( 'wfacp_page_located', $status, $post, $template_loader );

// Current post
apply_filters( 'wfacp_post', $post );

// Customize URL
apply_filters( 'wfacp_customize_url', $url, $admin_instance );

// Template edit link
apply_filters( 'wfacp_template_edit_link', $urls, $admin_instance );

// Builder pages path
apply_filters( 'wfacp_builder_pages_path', $path, $section, $admin_instance );
```

### Page Detection

```php
// Skip checkout page detection
apply_filters( 'wfacp_skip_checkout_page_detection', false );

// Do not check for global checkout
apply_filters( 'wfacp_do_not_check_for_global_checkout', false, $post, $template_loader );

// WPML checkout page ID
apply_filters( 'wfacp_wpml_checkout_page_id', $page_id );

// Global embed form redirect URL
apply_filters( 'wfacp_global_embed_form_redirect_url', $url, $page_id, $post );

// Redirect embed global checkout
apply_filters( 'wfacp_redirect_embed_global_checkout_url', true, $page_id, $post, $design_data );
```

### Settings

```php
// Global settings
apply_filters( 'woofunnels_global_settings', $settings );

// Global settings fields
apply_filters( 'woofunnels_global_settings_fields', $fields );

// BWF general settings fields
apply_filters( 'bwf_general_settings_fields', $fields );

// Admin localize data
apply_filters( 'wfacp_admin_localize_data', $data );

// Import checkout settings
apply_filters( 'wfacp_import_checkout_settings', $settings, $page_id, $builder_type );
```

### Reporting

```php
// Mark conversion post ID
apply_filters( 'wfacp_mark_conversion_post_id', 0, $posted_data );

// Maybe update order
apply_filters( 'wfacp_maybe_update_order', $order );

// Show advanced field order
apply_filters( 'wfacp_show_advanced_field_order', $wfacp_id );
```

### Custom Field Printing

```php
// Default hook for printing custom fields in email
apply_filters( 'wfacp_default_custom_field_print_hook_for_email', 'woocommerce_email_order_meta' );

// Default hook for printing custom fields on thank you page
apply_filters( 'wfacp_default_custom_field_print_hook_for_thankyou', 'woocommerce_order_details_after_order_table' );
```

### Enqueue Scripts

```php
// Enqueue scripts control
apply_filters( 'wfacp_enqueue_scripts', false, $screen_type );

// Tracking options data
apply_filters( 'wfacp_tracking_options_data', $data );
```

### Miscellaneous

```php
// Remove persistent cart after merging
apply_filters( 'wfacp_remove_persistent_cart_after_merging', true );

// Checkout post list
apply_filters( 'wfacp_checkout_post_list', $posts );

// Builder merge field arguments
apply_filters( 'wfacp_builder_merge_field_arguments', $args, $field, $key, $builder_type );

// Divi compatibility templates
apply_filters( 'et_builder_compatibility_wfacp_checkout_templates_without_theme_builder', $templates );

// Display shipping placeholder message
apply_filters( 'wfacp_display_shipping_placeholder_message', $message );
```

---

## Usage Examples

### Adding Custom Field to Checkout

```php
add_filter( 'wfacp_checkout_fields', function( $fields ) {
    $fields['billing']['custom_field'] = array(
        'type'     => 'text',
        'label'    => 'Custom Field',
        'required' => false,
        'priority' => 100,
    );
    return $fields;
});
```

### Modifying Cart Image

```php
add_filter( 'wfacp_cart_image', function( $image, $product ) {
    // Return custom image URL
    return 'https://example.com/custom-image.jpg';
}, 10, 2 );
```

### Running Code After Checkout Page Found

```php
add_action( 'wfacp_after_checkout_page_found', function() {
    // Checkout page is ready
    $checkout_id = WFACP_Common::get_id();
    // Do something...
});
```

### Skipping Auto Add-to-Cart

```php
add_filter( 'wfacp_skip_add_to_cart', function( $skip, $public ) {
    // Skip add to cart for specific conditions
    if ( some_condition() ) {
        return true;
    }
    return $skip;
}, 10, 2 );
```
