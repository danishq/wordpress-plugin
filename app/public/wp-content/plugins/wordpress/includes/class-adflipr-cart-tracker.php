<?php
defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Class Adflipr_Cart_Tracker
 */
if (! class_exists('Adflipr_Cart_Tracker')) {

  class Adflipr_Cart_Tracker
  {
    /**
     * @var null
     */
    public static $_instance = null;

    public function __construct()
    {
      add_action('wp_enqueue_scripts', [$this, 'track_cart_abandonment_script']);
      add_action('wp_ajax_adflipr_save_cart', [$this, 'save_cart']);
      add_action('wp_ajax_nopriv_adflipr_save_cart', [$this, 'save_cart']);
      add_action(
        'woocommerce_checkout_create_order',
        function ($order, $data) {
          if (! empty(WC()->cart)) {
            $order->update_meta_data('_adflipr_cart_hash', WC()->cart->get_cart_hash());
          }
        },
        10,
        2
      );

      add_action('woocommerce_checkout_order_created', [$this, 'handle_order_completion']);
      // Block / Store API checkout: fire when order is processed (not merely updated from request).
      add_action('woocommerce_store_api_checkout_order_processed', [$this, 'handle_order_completion'], 10, 1);
    }

    /**
     * @return Adflipr_Cart_Tracker|null
     */
    public static function get_instance()
    {
      if (null === self::$_instance) {
        self::$_instance = new self();
      }

      return self::$_instance;
    }

    public function track_cart_abandonment_script()
    {
      if (function_exists('is_checkout') && is_checkout()) {
        wp_enqueue_script('adflipr-cart-tracker', ADFLIPR_PLUGIN_URL . '/assets/js/adflipr-cart-tracker.js', array('jquery'), ADFLIPR_VERSION, true);
        $nonce_action = defined('ADFLIPR_SAVE_CART_NONCE_ACTION') ? ADFLIPR_SAVE_CART_NONCE_ACTION : 'adflipr_save_cart';
        wp_add_inline_script(
          'adflipr-cart-tracker',
          'var adflipr_vars = ' . wp_json_encode(
            array(
              'ajax_url' => admin_url('admin-ajax.php'),
              'nonce'    => wp_create_nonce($nonce_action),
              'security' => wp_create_nonce($nonce_action),
            )
          ) . ';'
        );
      }
    }

    /**
     * Simple per-IP rate limit for public AJAX (abuse / cost control).
     */
    private function save_cart_rate_limit_ok()
    {
      if (! defined('ADFLIPR_SAVE_CART_RATE_PER_MINUTE')) {
        return true;
      }

      $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : 'unknown';
      $key = 'adflipr_cart_rl_' . md5($ip);
      $n   = (int) get_transient($key);
      if ($n >= (int) ADFLIPR_SAVE_CART_RATE_PER_MINUTE) {
        return false;
      }

      set_transient($key, $n + 1, MINUTE_IN_SECONDS);

      return true;
    }

    public function save_cart()
    {
      if (defined('ADFLIPR_SAVE_CART_MAX_CONTENT_LENGTH')) {
        $content_length = isset($_SERVER['CONTENT_LENGTH'])
          ? (int) sanitize_text_field(wp_unslash((string) $_SERVER['CONTENT_LENGTH']))
          : 0;
        if ($content_length > (int) ADFLIPR_SAVE_CART_MAX_CONTENT_LENGTH) {
          wp_send_json_error(array('message' => __('Request too large.', 'adflipr')), 413);
        }
      }

      $nonce_action = defined('ADFLIPR_SAVE_CART_NONCE_ACTION') ? ADFLIPR_SAVE_CART_NONCE_ACTION : 'adflipr_save_cart';
      $nonce_value  = '';
      if (isset($_POST['security'])) {
        $nonce_value = sanitize_text_field(wp_unslash($_POST['security']));
      } elseif (isset($_POST['nonce'])) {
        $nonce_value = sanitize_text_field(wp_unslash($_POST['nonce']));
      }
      if ($nonce_value === '' || ! wp_verify_nonce($nonce_value, $nonce_action)) {
        wp_send_json_error(array('message' => __('Invalid security token.', 'adflipr')), 403);
      }

      if (! $this->save_cart_rate_limit_ok()) {
        wp_send_json_error(array('message' => __('Too many requests.', 'adflipr')), 429);
      }

      if (! function_exists('WC') || is_null(WC()->cart) || empty(WC()->cart->get_cart())) {
        wp_send_json_success(array('message' => 'empty cart'));
      }

      $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
      $url   = '';
      if (isset($_POST['url'])) {
        $url = esc_url_raw(wp_unslash($_POST['url']));
      }
      if ($url === '' && isset($_SERVER['HTTP_REFERER'])) {
        $url = esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER']));
      }

      if ($email === '') {
        wp_send_json_error(array('message' => __('Email is missing.', 'adflipr')));

        return;
      }

      $cart      = WC()->cart->get_cart();
      $cart_hash = WC()->cart->get_cart_hash();
      $currency  = get_woocommerce_currency();

      $cart_items = [];
      foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $product = $cart_item['data'];
        if (! $product) {
          continue;
        }

        $cart_items[] = array(
          'product_id'    => $cart_item['product_id'],
          'variation_id'  => $cart_item['variation_id'],
          'name'          => $product->get_name(),
          'sku'           => $product->get_sku(),
          'quantity'      => $cart_item['quantity'],
          'price'         => $product->get_price(),
          'line_total'    => $cart_item['line_total'],
          'line_subtotal' => $cart_item['line_subtotal'],
          'image'         => wp_get_attachment_url(get_post_thumbnail_id($cart_item['product_id'])),
          'product_url'   => get_permalink($cart_item['product_id']),
        );
      }

      if ($url !== '') {
        $product_ids = array();
        foreach (WC()->cart->get_cart() as $cart_item) {
          $product_ids[] = $cart_item['product_id'];
        }

        $url = $url . '?add-to-cart=' . implode(',', $product_ids);
      }

      $user_data = array();
      $user_id   = get_current_user_id();
      if ($user_id) {
        $user = get_userdata($user_id);
        if ($user) {
          $user_data = array(
            'id'         => $user_id,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'username'   => $user->user_login,
            'email'      => $user->user_email,
          );
        }
      }

      $customer_data = array();
      if (WC()->customer) {
        $customer_data['billing'] = array(
          'first_name' => WC()->customer->get_billing_first_name(),
          'last_name'  => WC()->customer->get_billing_last_name(),
          'company'    => WC()->customer->get_billing_company(),
          'address_1'  => WC()->customer->get_billing_address_1(),
          'address_2'  => WC()->customer->get_billing_address_2(),
          'city'       => WC()->customer->get_billing_city(),
          'state'      => WC()->customer->get_billing_state(),
          'postcode'   => WC()->customer->get_billing_postcode(),
          'country'    => WC()->customer->get_billing_country(),
          'email'      => WC()->customer->get_billing_email(),
          'phone'      => WC()->customer->get_billing_phone(),
        );

        $customer_data['shipping'] = array(
          'first_name' => WC()->customer->get_shipping_first_name(),
          'last_name'  => WC()->customer->get_shipping_last_name(),
          'company'    => WC()->customer->get_shipping_company(),
          'address_1'  => WC()->customer->get_shipping_address_1(),
          'address_2'  => WC()->customer->get_shipping_address_2(),
          'city'       => WC()->customer->get_shipping_city(),
          'state'      => WC()->customer->get_shipping_state(),
          'postcode'   => WC()->customer->get_shipping_postcode(),
          'country'    => WC()->customer->get_shipping_country(),
        );
      }

      $cart_totals = array(
        'subtotal'              => WC()->cart->get_subtotal(),
        'subtotal_tax'          => WC()->cart->get_subtotal_tax(),
        'cart_contents_total'   => WC()->cart->get_cart_contents_total(),
        'cart_contents_tax'     => WC()->cart->get_cart_contents_tax(),
        'cart_discount'         => WC()->cart->get_discount_total(),
        'discount_tax'          => WC()->cart->get_discount_tax(),
        'shipping_total'        => WC()->cart->get_shipping_total(),
        'shipping_tax'          => WC()->cart->get_shipping_tax(),
        'total'                 => WC()->cart->get_total('edit'),
        'total_tax'             => WC()->cart->get_total_tax(),
      );

      $applied_coupons = WC()->cart->get_applied_coupons();
      $coupons         = array();
      foreach ($applied_coupons as $coupon_code) {
        $coupon    = new WC_Coupon($coupon_code);
        $coupons[] = array(
          'code'          => $coupon->get_code(),
          'discount_type' => $coupon->get_discount_type(),
          'amount'        => $coupon->get_amount(),
        );
      }

      $cart_abandonment = array(
        'action'          => 'cart_abandonment',
        'email'           => $email,
        'cart_data'       => $cart,
        'cart_items'      => $cart_items,
        'cart_totals'     => $cart_totals,
        'applied_coupons' => $coupons,
        'url'             => $url,
        'cart_hash'       => $cart_hash,
        'currency'        => $currency,
        'user'            => $user_data,
        'customer'        => $customer_data,
        'type'            => 'recoverable',
      );

      $result = AdFlipr_Core()->data->send_data_on_webhook($cart_abandonment);
      wp_send_json_success($result);
    }

    public function handle_order_completion($order)
    {
      if (is_numeric($order)) {
        $order = wc_get_order($order);
      }

      if (! $order instanceof WC_Order) {
        return;
      }

      $sent_meta = defined('ADFLIPR_ORDER_CHECKOUT_WEBHOOK_SENT_META')
        ? ADFLIPR_ORDER_CHECKOUT_WEBHOOK_SENT_META
        : '_adflipr_checkout_webhook_sent';

      if ($order->get_meta($sent_meta) === 'yes') {
        return;
      }

      $cart_hash = $order->get_meta('_adflipr_cart_hash', true);

      if ($cart_hash === '' && function_exists('WC') && ! empty(WC()->cart)) {
        $cart_hash = WC()->cart->get_cart_hash();
      }

      $email      = $order->get_billing_email();
      $order_data = AdFlipr_Core()->data->get_order_all_possible_data($order);

      $data = array(
        'action'     => 'checkout_purchased',
        'email'      => $email,
        'cart_hash'  => $cart_hash ? $cart_hash : '',
        'type'       => 'recovered',
        'order_data' => $order_data,
        'tracking'   => $this->fetch_utm_from_cookies(),
        'ip_address' => $this->get_client_ip(),
      );

      $result = AdFlipr_Core()->data->send_data_on_webhook($data);

      $ok_http = ! empty($result['status']) && isset($result['code']) && (int) $result['code'] === 200;
      $queued  = ! empty($result['queued']);

      if ($ok_http || $queued) {
        $order->update_meta_data($sent_meta, 'yes');
        if (method_exists($order, 'delete_meta_data')) {
          $order->delete_meta_data('_adflipr_cart_hash');
        }
        $order->save();
      }
    }

    /**
     * Filter cookies to only include UTM parameters
     */
    public function fetch_utm_from_cookies()
    {
      $filtered = array();

      foreach ($_COOKIE as $key => $value) {
        if (strpos($key, 'utm_') === 0) {
          $filtered[$key] = $value;
        }
      }

      return $filtered;
    }

    /**
     * Get client IP address
     */
    private function get_client_ip()
    {
      $ip_keys = array(
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
      );

      foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
          $ip = $_SERVER[$key];
          if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
          }
        }
      }

      return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    }
  }

  if (class_exists('Adflipr_Cart_Tracker')) {
    AdFlipr_Core::register('cart_tracker', 'Adflipr_Cart_Tracker');
  }
}
