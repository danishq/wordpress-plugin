# FunnelKit Checkout - Database Schema

## Custom Tables

### wfacp_stats

Stores checkout conversion statistics and revenue data.

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

| Column | Type | Description |
|--------|------|-------------|
| `ID` | bigint | Auto-increment primary key |
| `order_id` | bigint | WooCommerce order ID |
| `wfacp_id` | bigint | Checkout page ID |
| `total_revenue` | varchar(255) | Revenue attributed to checkout |
| `cid` | bigint | Contact ID (from WooFunnels contacts) |
| `fid` | bigint | Funnel ID |
| `date` | datetime | Conversion date |

**Version Tracking:** `wfacp_db_ver_2_1` option

---

## WordPress Options (wp_options)

### Global Settings

| Option Name | Type | Description |
|-------------|------|-------------|
| `_wfacp_global_settings` | array | Global plugin configuration |
| `wfacp_db_ver_2_1` | string | Database schema version |
| `woofunnels_plugins_info` | array | License and plugin info |

#### _wfacp_global_settings Structure

```php
array(
    'override_checkout_page_id' => 0,      // Global checkout override
    'rewrite_slug'              => 'checkouts', // URL base
    // ... other settings
)
```

### Per-Checkout Customizer Settings

| Option Pattern | Type | Description |
|----------------|------|-------------|
| `wfacp_c_{checkout_id}` | array | Customizer settings for specific checkout |

---

## Post Meta (wp_postmeta)

### Checkout Page Meta

| Meta Key | Type | Description |
|----------|------|-------------|
| `_wfacp_selected_design` | string | Template type (pre_built, elementor, divi, etc.) |
| `_wfacp_selected_design_slug` | string | Specific template slug |
| `_wfacp_page_layout` | array | Form layout configuration |
| `_wfacp_checkout_fields` | array | Custom checkout field definitions |
| `_wfacp_fieldsets_data` | array | Field sections organization |
| `_wfacp_product` | array | Products assigned to checkout |
| `_wfacp_product_switcher_setting` | array | Product switcher configuration |
| `_wfacp_page_settings` | array | Page-specific settings |
| `_wfacp_version` | string | Plugin version at creation |
| `_bwf_in_funnel` | int | Parent funnel ID |
| `_post_description` | string | Internal description |

#### _wfacp_page_layout Structure

```php
array(
    'fieldsets' => array(
        'single_step' => array(
            array(
                'name'   => 'Section Name',
                'fields' => array( 'billing_first_name', 'billing_last_name', ... )
            )
        )
    ),
    'current_step' => 'single_step',
    'have_steps'   => false
)
```

#### _wfacp_checkout_fields Structure

```php
array(
    'billing' => array(
        'billing_first_name' => array(
            'label'       => 'First Name',
            'placeholder' => '',
            'required'    => true,
            'priority'    => 10,
            'class'       => array( 'form-row-first' ),
            'is_wfacp_field' => false
        ),
        // ... more fields
    ),
    'shipping' => array( /* ... */ ),
    'advanced' => array( /* custom fields */ )
)
```

#### _wfacp_product Structure

```php
array(
    'unique_key_123' => array(
        'id'              => 123,           // Product ID
        'title'           => 'Product Name',
        'discount_type'   => 'percent_discount_sale',
        'discount_amount' => 10,
        'quantity'        => 1,
        'variable'        => false
    )
)
```

#### _wfacp_page_settings Structure

```php
array(
    'close_checkout_after_date'   => 'false',
    'close_checkout_on'           => '',
    'close_checkout_redirect_url' => '',
    'close_after_x_purchase'      => 'false',
    'total_purchased_allowed'     => '',
    'total_purchased_redirect_url'=> '',
    'coupons'                     => ''
)
```

### Elementor-Specific Meta

| Meta Key | Type | Description |
|----------|------|-------------|
| `_wfacp_el_product_switcher_us_a_widget` | string | Use as widget flag |
| `_elementor_data` | json | Elementor page structure |
| `_elementor_edit_mode` | string | Edit mode flag |

### Divi-Specific Meta

| Meta Key | Type | Description |
|----------|------|-------------|
| `_et_pb_use_builder` | string | Divi builder active |
| `_et_pb_post_content_backup` | string | Content backup |

---

## Order Meta (wp_postmeta / wc_orders_meta)

| Meta Key | Type | Description |
|----------|------|-------------|
| `_wfacp_post_id` | int | Source checkout page ID |
| `_wfacp_source` | string | Source checkout URL |
| `_wfacp_report_data` | array | Revenue tracking data |
| `_wfacp_report_needs_normalization` | string | IPN gateway normalization flag |
| `_wfacp_checkout_processed` | string | Checkout completion flag |

#### _wfacp_report_data Structure

```php
array(
    'wfacp_total' => 99.99,    // Order total attributed to checkout
    'funnel_id'   => 123       // Parent funnel ID
)
```

---

## Cart Item Data

Cart items store WFACP-specific data:

```php
$cart_item['_wfacp_product']     = true;           // Is WFACP product
$cart_item['_wfacp_product_key'] = 'unique_key';   // Product unique key
$cart_item['_wfacp_options']     = array();        // Product options
```

---

## Session Data (WC Session)

| Session Key | Type | Description |
|-------------|------|-------------|
| `wfacp_id` | int | Current checkout page ID |
| `wfacp_is_override_checkout` | int | Global checkout override flag |
| `wfacp_checkout_processed_{id}` | bool | Checkout processed flag |
| `wfacp_woocommerce_applied_coupon_{id}` | array | Applied coupons |
| `wfacp_product_data_{id}` | array | Product data for checkout |
| `wfacp_await_order_{id}` | int | Awaiting order ID |
| `aero_add_to_checkout_parameter_{id}` | string | URL parameter value |

---

## Transients

| Transient Pattern | Description |
|-------------------|-------------|
| `wfacp_template_cache_{id}` | Cached template data |

---

## Database Operations

### Creating Stats Table

```php
// Located in: includes/class-wfacp-reporting.php
WFACP_Reporting::create_table();
```

### Inserting Stats Record

```php
$wpdb->insert(
    $wpdb->wfacp_stats,
    array(
        'order_id'      => $order_id,
        'wfacp_id'      => $wfacp_id,
        'total_revenue' => $revenue,
        'cid'           => $contact_id,
        'fid'           => $funnel_id,
        'date'          => current_time( 'mysql' )
    ),
    array( '%d', '%d', '%s', '%d', '%d', '%s' )
);
```

### Querying Stats

```php
// Get stats for checkout page
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->wfacp_stats} WHERE wfacp_id = %d",
        $checkout_id
    )
);

// Count conversions
$count = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->wfacp_stats} WHERE wfacp_id = %d",
        $checkout_id
    )
);
```

---

## HPOS Compatibility

For High-Performance Order Storage compatibility:

```php
// Always use this function for order meta
$value = wfacp_get_order_meta( $order, '_wfacp_post_id' );

// Check if HPOS enabled
if ( wfacp_is_hpos_enabled() ) {
    // Use wc_orders_meta table
} else {
    // Use wp_postmeta table
}
```
