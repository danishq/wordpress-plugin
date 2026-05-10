# FunnelKit Checkout - Safe Modification Patterns

## General Principles

1. **Never modify core files directly** - Use hooks and filters
2. **Test with multiple page builders** - Changes may affect Elementor, Divi, Gutenberg, Oxygen
3. **Consider HPOS compatibility** - Use `wfacp_get_order_meta()` for order meta
4. **Respect existing patterns** - Follow the singleton and hook-based architecture

---

## Adding Custom Checkout Fields

### Method 1: Via Filter (Recommended)

```php
add_filter( 'wfacp_checkout_fields', function( $fields ) {
    $fields['billing']['my_custom_field'] = array(
        'type'        => 'text',
        'label'       => __( 'My Custom Field', 'my-plugin' ),
        'placeholder' => __( 'Enter value', 'my-plugin' ),
        'required'    => true,
        'class'       => array( 'form-row-wide' ),
        'priority'    => 110,
    );
    return $fields;
});
```

### Method 2: For Third-Party Fields

```php
// Register field under Billing tab in admin
add_filter( 'wfacp_third_party_billing_fields', function( $fields ) {
    $fields['my_custom_field'] = array(
        'label'    => __( 'My Custom Field', 'my-plugin' ),
        'type'     => 'text',
        'required' => false,
    );
    return $fields;
});
```

### Saving Custom Field Data

```php
add_action( 'woocommerce_checkout_update_order_meta', function( $order_id, $data ) {
    if ( isset( $_POST['my_custom_field'] ) ) {
        $order = wc_get_order( $order_id );
        $order->update_meta_data( '_my_custom_field', sanitize_text_field( $_POST['my_custom_field'] ) );
        $order->save();
    }
}, 10, 2 );
```

---

## Modifying Form Sections

### Adding Content Before/After Sections

```php
// Before a specific section
add_action( 'wfacp_before_form_section_billing', function() {
    echo '<div class="my-custom-notice">Important info here</div>';
});

// After a specific section
add_action( 'wfacp_after_form_section_billing', function() {
    echo '<div class="my-custom-content">Additional content</div>';
});
```

### Modifying Section Output

```php
add_filter( 'wfacp_form_section', function( $output, $section, $checkout ) {
    if ( $section === 'billing' ) {
        // Modify $output
        $output = str_replace( 'original', 'modified', $output );
    }
    return $output;
}, 10, 3 );
```

---

## Modifying Product/Cart Behavior

### Skip Auto Add-to-Cart

```php
add_filter( 'wfacp_skip_add_to_cart', function( $skip, $public ) {
    // Skip for specific checkout pages
    if ( WFACP_Common::get_id() === 123 ) {
        return true;
    }
    return $skip;
}, 10, 2 );
```

### Modifying Cart Item Display

```php
// Modify cart item image
add_filter( 'wfacp_cart_image', function( $image, $product ) {
    // Return custom image for specific products
    if ( $product->get_id() === 456 ) {
        return 'https://example.com/custom-image.jpg';
    }
    return $image;
}, 10, 2 );

// Hide quantity controls for specific items
add_filter( 'wfacp_show_item_quantity', function( $show, $cart_item ) {
    if ( isset( $cart_item['product_id'] ) && $cart_item['product_id'] === 789 ) {
        return false;
    }
    return $show;
}, 10, 2 );
```

---

## Adding Custom Templates

### Registering a Custom Template Type

```php
add_action( 'wfacp_register_template_types', function( $template_loader ) {
    $template_loader->register_template_type( array(
        'slug'  => 'my_custom_builder',
        'title' => __( 'My Custom Builder', 'my-plugin' ),
    ));
});
```

### Registering Custom Templates

```php
add_filter( 'wfacp_register_templates', function( $templates, $template_loader ) {
    $templates['my_custom_builder'] = array(
        'my_template' => array(
            'name'          => __( 'My Template', 'my-plugin' ),
            'path'          => MY_PLUGIN_PATH . '/templates/my-template.php',
            'template_type' => 'my_custom_builder',
        ),
    );
    return $templates;
}, 10, 2 );
```

---

## Adding Page Builder Widgets/Modules

### Elementor Widget

```php
// Register widget category
add_action( 'elementor/elements/categories_registered', function( $elements_manager ) {
    $elements_manager->add_category( 'my-category', array(
        'title' => __( 'My Category', 'my-plugin' ),
    ));
});

// Register widget
add_action( 'elementor/widgets/widgets_registered', function() {
    if ( class_exists( 'WFACP_Elementor' ) ) {
        require_once 'my-widget.php';
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new My_Widget() );
    }
});
```

### Divi Module

```php
add_action( 'et_builder_ready', function() {
    if ( class_exists( 'WFACP_Divi' ) ) {
        require_once 'my-divi-module.php';
    }
});
```

---

## Adding Compatibility Classes

### Structure

```php
// File: compatibilities/plugins/class-my-plugin-compat.php

if ( ! class_exists( 'WFACP_My_Plugin_Compat' ) ) {
    class WFACP_My_Plugin_Compat {

        public function __construct() {
            // Check if target plugin is active
            if ( ! class_exists( 'Target_Plugin_Class' ) ) {
                return;
            }

            // Add compatibility hooks
            add_filter( 'wfacp_checkout_fields', array( $this, 'modify_fields' ) );
            add_action( 'wfacp_after_checkout_page_found', array( $this, 'setup_compatibility' ) );
        }

        public function modify_fields( $fields ) {
            // Modifications here
            return $fields;
        }

        public function setup_compatibility() {
            // Setup code here
        }
    }

    new WFACP_My_Plugin_Compat();
}
```

### Auto-loading Pattern

Add to `compatibilities/plugins/index.php`:

```php
if ( class_exists( 'Target_Plugin_Class' ) ) {
    require_once __DIR__ . '/class-my-plugin-compat.php';
}
```

---

## Modifying Admin Interface

### Adding Admin Menu Items

```php
add_action( 'admin_menu', function() {
    add_submenu_page(
        'wfacp',
        __( 'My Page', 'my-plugin' ),
        __( 'My Page', 'my-plugin' ),
        'manage_woocommerce',
        'my-custom-page',
        'my_custom_page_callback'
    );
}, 100 );
```

### Adding Admin Scripts/Styles

```php
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'wfacp' ) {
        wp_enqueue_script( 'my-admin-script', MY_PLUGIN_URL . '/admin.js', array( 'jquery' ), '1.0.0' );
        wp_enqueue_style( 'my-admin-style', MY_PLUGIN_URL . '/admin.css', array(), '1.0.0' );
    }
});
```

### Modifying Admin Localize Data

```php
add_filter( 'wfacp_admin_localize_data', function( $data ) {
    $data['my_custom_data'] = array(
        'setting1' => 'value1',
        'setting2' => 'value2',
    );
    return $data;
});
```

---

## Adding REST API Endpoints

```php
add_action( 'rest_api_init', function() {
    register_rest_route( 'wfacp-custom/v1', '/my-endpoint', array(
        'methods'             => 'GET',
        'callback'            => 'my_endpoint_callback',
        'permission_callback' => function() {
            return current_user_can( 'manage_woocommerce' );
        },
    ));
});

function my_endpoint_callback( $request ) {
    return new WP_REST_Response( array( 'success' => true ), 200 );
}
```

---

## Modifying Checkout Flow

### Before Checkout Page Detection

```php
add_filter( 'wfacp_skip_checkout_page_detection', function( $skip ) {
    // Skip detection for specific conditions
    if ( some_condition() ) {
        return true;
    }
    return $skip;
});
```

### After Checkout Page Found

```php
add_action( 'wfacp_after_checkout_page_found', function() {
    // Checkout page is ready, do setup
    $checkout_id = WFACP_Common::get_id();

    // Add custom scripts
    add_action( 'wp_enqueue_scripts', function() {
        wp_enqueue_script( 'my-checkout-script', MY_PLUGIN_URL . '/checkout.js' );
    });
});
```

### Modifying Order Data

```php
add_action( 'woocommerce_checkout_create_order', function( $order, $data ) {
    if ( isset( $data['wfacp_post_id'] ) ) {
        $order->update_meta_data( '_my_custom_meta', 'value' );
    }
}, 10, 2 );
```

---

## Testing Considerations

### Multi-Builder Testing

Always test changes with:
- Pre-built templates (Customizer)
- Elementor
- Divi
- Gutenberg
- Oxygen

### HPOS Testing

```php
// Test with both storage modes
if ( wfacp_is_hpos_enabled() ) {
    // HPOS active - uses wc_orders_meta
} else {
    // Traditional - uses wp_postmeta
}
```

### Debug Mode

Enable for development:
```php
define( 'WFACP_IS_DEV', true );
```

---

## Common Pitfalls to Avoid

1. **Don't assume single page builder** - Check which builder is active
2. **Don't directly query wp_postmeta for orders** - Use `wfacp_get_order_meta()`
3. **Don't skip nonce verification** - Always verify nonces in admin operations
4. **Don't forget priority** - Hook priority matters, especially for form fields
5. **Don't break AJAX** - Test with AJAX operations (update order review, payment)
6. **Don't ignore caching** - Consider caching plugins when reading settings
