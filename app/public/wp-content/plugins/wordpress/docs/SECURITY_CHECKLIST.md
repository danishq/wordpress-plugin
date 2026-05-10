# FunnelKit Checkout - Security Checklist

## Authentication & Authorization

### Capability Checks

- [ ] Admin operations require `manage_woocommerce` capability
- [ ] REST API endpoints use permission callbacks
- [ ] Custom role system via `WFACP_Core()->role`

```php
// Example capability check
if ( ! current_user_can( 'manage_woocommerce' ) ) {
    wp_die( __( 'Unauthorized', 'woofunnels-aero-checkout' ) );
}

// REST API permission callback
'permission_callback' => array( $this, 'get_write_api_permission_check' )
```

### Nonce Verification

- [ ] All form submissions verify nonces
- [ ] AJAX operations use nonce validation
- [ ] Admin nonce key: `wfacp_admin_secure_key`

```php
// Creating nonce
wp_nonce_field( 'wfacp_admin_secure_key', 'wfacp_nonce' );

// Verifying nonce
if ( ! wp_verify_nonce( $_POST['wfacp_nonce'], 'wfacp_admin_secure_key' ) ) {
    wp_die( 'Security check failed' );
}
```

---

## Input Validation & Sanitization

### Sanitization Functions Used

| Function | Purpose |
|----------|---------|
| `bwf_clean()` | Array sanitization (WooFunnels custom) |
| `wc_clean()` | WooCommerce data sanitization |
| `sanitize_text_field()` | Single text fields |
| `sanitize_email()` | Email addresses |
| `absint()` | Positive integers |
| `wp_kses_post()` | HTML content |
| `esc_html()` | Plain text output |
| `esc_attr()` | HTML attributes |
| `esc_url()` | URLs |

### Input Sanitization Pattern

```php
// Integer sanitization
$wfacp_id = isset( $_REQUEST['wfacp_id'] ) ? absint( $_REQUEST['wfacp_id'] ) : 0;

// Text sanitization
$title = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';

// Array sanitization
$data = isset( $_POST['data'] ) ? bwf_clean( $_POST['data'] ) : array();

// JSON data
$json_data = json_decode( $data, true );
$clean_data = bwf_clean( $json_data );
```

---

## Output Escaping

### Escaping Functions

| Context | Function |
|---------|----------|
| HTML content | `esc_html()` |
| HTML attributes | `esc_attr()` |
| URLs | `esc_url()` |
| JavaScript | `esc_js()` |
| Rich HTML | `wp_kses_post()` |
| Translation with HTML | `wp_kses()` |

### Template Output Pattern

```php
// Text output
echo esc_html( $value );

// Attribute output
echo '<input value="' . esc_attr( $value ) . '">';

// URL output
echo '<a href="' . esc_url( $url ) . '">';

// HTML content (trusted)
echo wp_kses_post( $content );
```

---

## Database Security

### Prepared Statements

```php
// Always use prepared statements
$result = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->wfacp_stats} WHERE wfacp_id = %d",
        $wfacp_id
    )
);

// Insert with format specifiers
$wpdb->insert(
    $wpdb->wfacp_stats,
    array(
        'order_id'      => $order_id,
        'wfacp_id'      => $wfacp_id,
        'total_revenue' => $revenue,
    ),
    array( '%d', '%d', '%s' )
);
```

### Meta Data Access

```php
// HPOS-compatible order meta access
$value = wfacp_get_order_meta( $order, '_wfacp_post_id' );

// Post meta with sanitization
$data = get_post_meta( $post_id, '_wfacp_checkout_fields', true );
if ( is_array( $data ) ) {
    $data = array_map( 'sanitize_text_field', $data );
}
```

---

## CSRF Protection

### Form Protection

```php
// In form
wp_nonce_field( 'wfacp_save_settings', 'wfacp_settings_nonce' );

// In handler
check_admin_referer( 'wfacp_save_settings', 'wfacp_settings_nonce' );
```

### AJAX Protection

```php
// In JavaScript
jQuery.ajax({
    data: {
        action: 'wfacp_action',
        nonce: wfacp_secure.nonce,
        // ... other data
    }
});

// In PHP handler
check_ajax_referer( 'wfacp_admin_secure_key', 'nonce' );
```

---

## XSS Prevention

### User-Generated Content

```php
// Always escape output
echo esc_html( $user_input );

// For HTML content, use wp_kses
$allowed_tags = array(
    'a'      => array( 'href' => array(), 'title' => array() ),
    'strong' => array(),
    'em'     => array(),
);
echo wp_kses( $user_html, $allowed_tags );
```

### JavaScript Data

```php
// Properly escape for JavaScript
wp_localize_script( 'wfacp', 'wfacp_data', array(
    'ajaxurl' => admin_url( 'admin-ajax.php' ),
    'nonce'   => wp_create_nonce( 'wfacp_admin_secure_key' ),
    // Data is automatically JSON-encoded and escaped
));
```

---

## SQL Injection Prevention

### Query Building

```php
// NEVER do this
$wpdb->query( "SELECT * FROM table WHERE id = " . $_GET['id'] );

// ALWAYS use prepare
$wpdb->prepare( "SELECT * FROM table WHERE id = %d", absint( $_GET['id'] ) );

// For IN clauses
$ids = array_map( 'absint', $ids );
$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
$query = $wpdb->prepare(
    "SELECT * FROM table WHERE id IN ($placeholders)",
    $ids
);
```

---

## File Security

### File Upload Handling

```php
// Validate file type
$allowed_types = array( 'image/jpeg', 'image/png', 'image/gif' );
if ( ! in_array( $_FILES['upload']['type'], $allowed_types ) ) {
    wp_die( 'Invalid file type' );
}

// Use WordPress functions
$upload = wp_handle_upload( $_FILES['upload'], array( 'test_form' => false ) );
```

### File Inclusion

```php
// Always validate file paths
$template = sanitize_file_name( $template );
$path = WFACP_PLUGIN_DIR . '/templates/' . $template . '.php';

if ( file_exists( $path ) && strpos( realpath( $path ), WFACP_PLUGIN_DIR ) === 0 ) {
    include $path;
}
```

---

## REST API Security

### Endpoint Security Pattern

```php
register_rest_route( 'wfacp-admin', '/endpoint', array(
    'methods'             => 'POST',
    'callback'            => array( $this, 'handle_request' ),
    'permission_callback' => array( $this, 'check_permissions' ),
    'args'                => array(
        'param' => array(
            'required'          => true,
            'validate_callback' => 'rest_validate_request_arg',
            'sanitize_callback' => 'sanitize_text_field',
        ),
    ),
));

public function check_permissions( $request ) {
    return current_user_can( 'manage_woocommerce' );
}
```

---

## Sensitive Data Handling

### Order Data

```php
// Never expose sensitive payment data
$safe_data = array(
    'order_id'     => $order->get_id(),
    'status'       => $order->get_status(),
    'total'        => $order->get_total(),
    // Don't include: payment method details, full addresses, etc.
);
```

### Configuration Data

```php
// Filter sensitive data from localization
add_filter( 'wfacp_admin_localize_data', function( $data ) {
    unset( $data['sensitive_key'] );
    return $data;
});
```

---

## Security Headers

### Admin Pages

```php
// Set security headers for admin pages
add_action( 'admin_init', function() {
    if ( isset( $_GET['page'] ) && $_GET['page'] === 'wfacp' ) {
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: SAMEORIGIN' );
    }
});
```

---

## Audit Checklist

### Before Deployment

- [ ] All user inputs are sanitized
- [ ] All outputs are escaped
- [ ] All database queries use prepared statements
- [ ] All forms have nonce protection
- [ ] All AJAX handlers verify nonces
- [ ] All REST endpoints have permission callbacks
- [ ] File paths are validated before inclusion
- [ ] Sensitive data is not exposed in JavaScript
- [ ] Capability checks are in place for admin operations
- [ ] Error messages don't expose system information

### Code Review Points

- [ ] Check for `$_GET`, `$_POST`, `$_REQUEST` usage - ensure sanitization
- [ ] Check for `echo`, `print` - ensure escaping
- [ ] Check for `$wpdb->query()` - ensure prepared statements
- [ ] Check for `include`, `require` - ensure path validation
- [ ] Check for `wp_redirect()` - ensure URL validation
