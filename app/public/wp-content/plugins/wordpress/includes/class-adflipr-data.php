<?php
defined('ABSPATH') || exit; //Exit if accessed directly

/**
 * Class AdFlipr_Data
 */
if (! class_exists('AdFlipr_Data')) {

  class AdFlipr_Data
  {
    private static $ins = null;

    public function __construct()
    {
      add_action('adflipr_send_data_event', [$this, 'send_data_all_data'], 10, 1);
      add_action('save_post_product', [$this, 'send_product_data_on_save_update'], 10, 2);
      add_action('before_delete_post', [$this, 'send_product_data_on_delete']);
      add_action('save_post_shop_coupon', [$this, 'send_coupon_data_on_save_update'], 10, 2);
      add_action('before_delete_post', [$this, 'send_coupon_data_on_delete']);
      add_filter('cron_schedules', [$this, 'register_adflipr_five_minute_schedule']);
      add_action('init', [$this, 'maybe_schedule_webhook_retry_cron'], 20);
      add_action(ADFLIPR_WEBHOOK_RETRY_CRON_HOOK, [$this, 'process_webhook_retry_queue']);
      add_action('rest_api_init', [$this, 'register_webhook_retry_rest_routes']);
      add_action('wp_ajax_adflipr_process_webhook_retry_queue', [$this, 'ajax_process_webhook_retry_queue']);
      add_action('cli_init', [$this, 'register_wp_cli_webhook_retry_command']);
    }

    /**
     * @return AdFlipr_Data|null
     */
    public static function get_instance()
    {
      if (null === self::$ins) {
        self::$ins = new self();
      }

      return self::$ins;
    }

    public function send_data_all_data($args = array())
    {

      $result = [
        'status'  => false,
        'message' => __('Data submission failed', 'adflipr')
      ];

      if (! function_exists('WC')) {
        $result['message'] = __('Data send failed WooCommerce not active', 'adflipr');

        return $result;
      }

      // Get the token, check if authenticated
      $token = AdFlipr_Core()->auth->valid_token();

      $default_args = array(
        'product_offset'      => 0,
        'product_data'        => true,
        'order_offset'        => 0,
        'order_data'          => true,
        'subscription_offset' => 0,
        'subscription_data'   => true,
        'coupon_offset'       => 0,
        'coupon_data'         => true,
      );
      foreach ($default_args as $from => $to) {
        if (! isset($args[$from])) {
          $args[$from] = $to;
        }
      }

      if (! $token) {
        return $result;
      }

      $limit = 50;

      // Get all product data
      $products = [];
      if ($args['product_data'] === true) {
        $products = $this->get_all_products($args['product_offset'], $limit);
      }

      // Get subscriptions data
      $subscriptions = [];
      if ($args['subscription_data'] === true) {
        $subscriptions = $this->get_subscriptions($args['subscription_offset'], $limit);
      }

      // Get coupons data
      $coupons = [];
      if ($args['coupon_data'] === true) {
        $coupons = $this->get_all_coupons($args['coupon_offset'], $limit);
      }

      // Get orders data
      $orders = [];
      if ($args['order_data'] === true) {
        $orders = $this->get_all_orders($args['order_offset'], $limit);
      }

      // Prepare data payload
      $data = [
        'action'        => 'send_data_on_connect',
        'products'      => $products,
        'coupons'       => $coupons,
        'orders'        => $orders,
        'subscriptions' => $subscriptions
      ];

      $response = $this->send_data_on_webhook($data);

      $batch_accepted = ! empty($response['status'])
        && (
          ( isset($response['code']) && (int) $response['code'] === 200 )
          || ! empty($response['queued'])
        );

      if ($batch_accepted) {
        $this->maybe_schedule_next_event($args, $limit, $products, $subscriptions, $coupons, $orders);
      }

      return $response;
    }

    private function get_all_products($offset, $limit)
    {
      $products = [];

      if (! function_exists('wc_get_products')) {
        return $products;
      }
      $page = (0 === $offset) ? 1 : absint($offset) + 1;
      $args = [
        'limit'  => $limit,
        'paged'  => $page,
        'status' => 'publish',
        'return' => 'objects', // Get product objects
      ];

      $all_products = wc_get_products($args);

      foreach ($all_products as $product) {
        // Get featured image
        if (! $product instanceof WC_Product) {
          continue;
        }

        $products[] = $this->get_single_product_data($product);
      }
      update_option('adflipr_products_last_offset', $offset + 1);

      return $products;
    }

    public function send_product_data_on_save_update($post_id, $post)
    {
      // Ensure it's a product post type
      if (is_null($post) || $post->post_type !== 'product') {
        return;
      }

      if (! function_exists('wc_get_product') || ! class_exists('WC_Product')) {
        return;
      }

      $product = wc_get_product($post_id);

      if (! $product instanceof WC_Product) {
        return;
      }

      // Fetch product data
      $product_data = $this->get_single_product_data($product);

      if (! $product_data) {
        return;
      }

      // Prepare data payload
      $data = [
        'action'  => 'add_product',
        'product' => $product_data
      ];

      // Send data to webhook
      $this->send_data_on_webhook($data);
    }

    /**
     * Send product deletion info
     */
    public function send_product_data_on_delete($post_id)
    {
      if (get_post_type($post_id) !== 'product') {
        return;
      }

      $data = [
        'action'     => 'delete_product',
        'product_id' => $post_id
      ];

      $this->send_data_on_webhook($data);
    }

    public function get_single_product_data($product)
    {

      if (! $product instanceof WC_Product) {
        return false;
      }
      $featured_image = wp_get_attachment_url($product->get_image_id());

      // Get gallery images
      $gallery_images = $this->get_product_gallery_images($product);

      return [
        'id'             => $product->get_id(),
        'name'           => $product->get_name(),
        'sku'            => $product->get_sku(),
        'description'    => $product->get_description(),
        'featured_image' => $featured_image,
        'gallery_images' => $gallery_images,
        'price'          => $product->get_price(),
        'stock_status'   => $product->get_stock_status(),
        'product_type'   => $product->get_type(), // This will return simple, variable, subscription, etc.
        'categories'     => wp_get_post_terms($product->get_id(), 'product_cat', ['fields' => 'names']),
        'tags'           => wp_get_post_terms($product->get_id(), 'product_tag', ['fields' => 'names']),
      ];
    }

    private function get_product_gallery_images($product)
    {
      $gallery_images = [];
      if (empty($product->get_gallery_image_ids())) {
        return $gallery_images;
      }
      $gallery_image_ids = $product->get_gallery_image_ids();


      if (empty($gallery_image_ids)) {
        return $gallery_images;
      }

      foreach ($gallery_image_ids as $image_id) {
        $image_url = wp_get_attachment_url($image_id);
        if ($image_url) {
          $gallery_images[] = $image_url;
        }
      }

      return $gallery_images;
    }

    private function get_all_orders($offset, $limit)
    {
      $orders = [];
      $page   = (0 === $offset) ? 1 : absint($offset) + 1;

      $checkout_sent_key = defined('ADFLIPR_ORDER_CHECKOUT_WEBHOOK_SENT_META')
        ? ADFLIPR_ORDER_CHECKOUT_WEBHOOK_SENT_META
        : '_adflipr_checkout_webhook_sent';

      $args = [
        'limit'      => $limit, // Fetch all orders
        'paged'      => $page,
        'status'     => ['completed', 'processing'], // Only completed & processing orders
        'return'     => 'ids', // Fetch only order IDs for better performance
        'meta_query' => [
          'relation' => 'OR',
          [
            'key'     => $checkout_sent_key,
            'compare' => 'NOT EXISTS',
          ],
          [
            'key'     => $checkout_sent_key,
            'value'   => '',
            'compare' => '=',
          ],
        ],
      ];

      $order_ids = wc_get_orders($args); // Efficiently get order IDs

      if (! is_array($order_ids) || count($order_ids) === 0) {
        return $order_ids;
      }

      foreach ($order_ids as $order_id) {
        $order      = wc_get_order($order_id);
        $order_data = $this->get_order_all_possible_data($order);
        if (! $order_data) {
          continue;
        }
        $orders[] = $order_data;
      }
      update_option('adflipr_orders_last_offset', $offset + 1);

      return $orders;
    }

    public function get_order_all_possible_data($order)
    {
      if (! $order instanceof WC_Order) {
        return false;
      }
      $order_data = [
        'id'                         => $order->get_id(),
        'total'                      => $order->get_total(),
        'status'                     => $order->get_status(),
        'currency'                   => $order->get_currency(),
        'customer_id'                => $order->get_customer_id(),
        'customer_note'              => $order->get_customer_note(),

        // Billing Information
        'billing_first_name'         => $order->get_billing_first_name(),
        'billing_last_name'          => $order->get_billing_last_name(),
        'billing_company'            => $order->get_billing_company(),
        'billing_email'              => $order->get_billing_email(),
        'billing_phone'              => $order->get_billing_phone(),
        'billing_address_1'          => $order->get_billing_address_1(),
        'billing_address_2'          => $order->get_billing_address_2(),
        'billing_city'               => $order->get_billing_city(),
        'billing_state'              => $order->get_billing_state(),
        'billing_postcode'           => $order->get_billing_postcode(),
        'billing_country'            => $order->get_billing_country(),
        'formatted_billing_address'  => wp_kses_post($order->get_formatted_billing_address()),

        // Shipping Information
        'shipping_first_name'        => $order->get_shipping_first_name(),
        'shipping_last_name'         => $order->get_shipping_last_name(),
        'shipping_company'           => $order->get_shipping_company(),
        'shipping_address_1'         => $order->get_shipping_address_1(),
        'shipping_address_2'         => $order->get_shipping_address_2(),
        'shipping_city'              => $order->get_shipping_city(),
        'shipping_state'             => $order->get_shipping_state(),
        'shipping_postcode'          => $order->get_shipping_postcode(),
        'shipping_country'           => $order->get_shipping_country(),
        'formatted_shipping_address' => wp_kses_post($order->get_formatted_shipping_address()),

        // Other Details
        'purchased_on'               => $order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : '',
        'completed_on'               => $order->get_date_completed() ? $order->get_date_completed()->date('Y-m-d H:i:s') : '',
        'payment_method'             => $order->get_payment_method(),
        'payment_method_title'       => $order->get_payment_method_title(),
        'transaction_id'             => $order->get_transaction_id(),

        // Order Items
        'items'                      => $this->get_order_items($order),
      ];

      return $order_data;
    }

    private function get_all_coupons($offset, $limit)
    {
      $coupons = [];
      $page    = (0 === $offset) ? 0 : absint($offset) * $limit;
      $args    = [
        'post_type'      => 'shop_coupon',
        'posts_per_page' => $limit,
        'offset'         => $page,
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'fields'         => 'ids',       // Get only subscription IDs for better performance
      ];

      $coupons_ids = get_posts($args);

      if (! is_array($coupons_ids) || count($coupons_ids) === 0) {
        return $coupons;
      }

      if (! class_exists('WC_Coupon')) {
        return $coupons;
      }

      foreach ($coupons_ids as $coupons_id) {
        $coupon = new WC_Coupon($coupons_id);
        if (empty($coupon)) {
          continue;
        }
        $coupons[] = $this->get_single_coupon_data($coupon);
      }

      update_option('adflipr_coupons_last_offset', $offset + 1);

      return $coupons;
    }

    public function send_coupon_data_on_save_update($post_id, $post)
    {
      // Ensure it's a coupon post type
      if (is_null($post) || $post->post_type !== 'shop_coupon') {
        return;
      }

      // Fetch coupon data
      if (! class_exists('WC_Coupon')) {
        return;
      }
      $coupon = new WC_Coupon($post_id);
      if (empty($coupon)) {
        return;
      }
      $coupon_data = $this->get_single_coupon_data($coupon);

      // Prepare data payload
      $data = [
        'action'  => 'add_coupon',
        'product' => $coupon_data
      ];

      // Send data to webhook
      $this->send_data_on_webhook($data);
    }

    /**
     * Send coupon deletion info
     */
    public function send_coupon_data_on_delete($post_id)
    {
      if (get_post_type($post_id) !== 'shop_coupon') {
        return;
      }

      $data = [
        'action'    => 'delete_coupon',
        'coupon_id' => $post_id
      ];

      $this->send_data_on_webhook($data);
    }

    public function get_single_coupon_data($coupon)
    {
      return [
        'id'                   => $coupon->get_id(),
        'code'                 => $coupon->get_code(),
        'discount_type'        => $coupon->get_discount_type(),
        'amount'               => $coupon->get_amount(),
        'description'          => $coupon->get_description(),
        'date_created'         => $coupon->get_date_created() ? $coupon->get_date_created()->date('Y-m-d H:i:s') : '',
        'expiry_date'          => $coupon->get_date_expires() ? $coupon->get_date_expires()->date('Y-m-d H:i:s') : '',
        'usage_limit'          => $coupon->get_usage_limit(),
        'usage_count'          => $coupon->get_usage_count(),
        'individual_use'       => $coupon->get_individual_use(),
        'product_ids'          => $coupon->get_product_ids(),
        'excluded_product_ids' => $coupon->get_excluded_product_ids(),
        'product_categories'   => $coupon->get_product_categories(),
        'excluded_categories'  => $coupon->get_excluded_product_categories(),
        'minimum_amount'       => $coupon->get_minimum_amount(),
        'maximum_amount'       => $coupon->get_maximum_amount(),
        'free_shipping'        => $coupon->get_free_shipping(),
      ];
    }

    public function get_subscriptions($offset, $limit)
    {
      if (! class_exists('WC_Subscriptions') || ! function_exists('wcs_get_subscription')) {
        return []; // No subscriptions plugin active
      }
      $page = (0 === $offset) ? 0 : absint($offset) * $limit;

      $subscriptions = [];
      $args          = [
        'post_type'      => 'shop_subscription',
        'posts_per_page' => $limit,
        'offset'         => $page,
        'orderby'        => 'ID',
        'order'          => 'ASC',
        'post_status'    => 'wc-active', // Fetch only active subscriptions
        'fields'         => 'ids',       // Get only subscription IDs for better performance
      ];

      $subscription_ids = get_posts($args);

      if (! is_array($subscription_ids) || count($subscription_ids) === 0) {
        return $subscriptions;
      }

      foreach ($subscription_ids as $subscription_id) {
        $subscription = wcs_get_subscription($subscription_id);

        if (empty($subscription->get_id())) {
          continue;
        }
        $subscriptions[] = [
          'id'            => $subscription->get_id(),
          'total'         => $subscription->get_total(),
          'status'        => $subscription->get_status(),
          'next_payment'  => $subscription->get_time('next_payment'),
          'customer_id'   => $subscription->get_user_id(),
          'created_date'  => $subscription->get_date_created()->date('Y-m-d H:i:s'),
          'billing_email' => $subscription->get_billing_email(),
          'items'         => $this->get_order_items($subscription), // Fetch subscription items
        ];
      }
      update_option('adflipr_subscriptions_last_offset', $offset + 1);

      return $subscriptions;
    }

    private function get_order_items($order)
    {
      $items_data = [];

      foreach ($order->get_items() as $item) {
        $product = $item->get_product();

        if ($product) {
          $items_data[] = [
            'product_id'   => $product->get_id(),
            'name'         => $product->get_name(),
            'quantity'     => $item->get_quantity(),
            'subtotal'     => $item->get_subtotal(),
            'total'        => $item->get_total(),
            'product_type' => $product->get_type(),
          ];
        }
      }

      return $items_data;
    }

    /**
     * Register custom cron schedule for webhook retry drain.
     *
     * @param array<string, mixed> $schedules
     * @return array<string, mixed>
     */
    public function register_adflipr_five_minute_schedule($schedules)
    {
      if (! isset($schedules['adflipr_five_minutes'])) {
        $schedules['adflipr_five_minutes'] = [
          'interval' => 300,
          'display'  => 'Every 5 minutes (AdFlipr)',
        ];
      }

      return $schedules;
    }

    public function maybe_schedule_webhook_retry_cron()
    {
      if (! defined('ADFLIPR_WEBHOOK_RETRY_CRON_HOOK')) {
        return;
      }
      if (! wp_next_scheduled(ADFLIPR_WEBHOOK_RETRY_CRON_HOOK)) {
        wp_schedule_event(time() + 120, 'adflipr_five_minutes', ADFLIPR_WEBHOOK_RETRY_CRON_HOOK);
      }
    }

    /**
     * Persist last successful delivery time for integration-health (no PII).
     *
     * @param array<string, mixed> $data
     */
    private function record_last_successful_webhook_delivery(array $data)
    {
      if (! defined('ADFLIPR_LAST_WEBHOOK_SUCCESS_AT_OPTION')
        || ! defined('ADFLIPR_LAST_WEBHOOK_SUCCESS_ACTION_OPTION')) {
        return;
      }

      update_option(ADFLIPR_LAST_WEBHOOK_SUCCESS_AT_OPTION, time(), false);
      $action = isset($data['action']) ? (string) $data['action'] : '';
      update_option(ADFLIPR_LAST_WEBHOOK_SUCCESS_ACTION_OPTION, $action, false);
    }

    /**
     * POST payload to AdFlipr WooCommerce webhook (direct request; no retry queue).
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function execute_woocommerce_webhook_http(array $data)
    {
      $result = [
        'status'  => false,
        'message' => '',
        'code'    => 0,
      ];

      $token = AdFlipr_Core()->auth->get_token();
      if (! $token) {
        $result['message'] = __('Not connected to AdFlipr (missing or expired token).', 'adflipr');
        $result['no_token'] = true;

        return $result;
      }

      $response = wp_remote_post(
        ADFLIPR_WOOCOMMERCE_WEBHOOK_URL,
        [
          'body'    => wp_json_encode($data),
          'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
          ],
          'timeout' => ADFLIPR_WEBHOOK_HTTP_TIMEOUT,
        ]
      );

      if (is_wp_error($response)) {
        $result['message'] = sprintf(
          /* translators: %s: HTTP client error message */
          __('Data submission error: %s', 'adflipr'),
          $response->get_error_message()
        );
        adflipr_debug_log(
          'webhook_wp_error',
          array(
            'error'  => $response->get_error_message(),
            'action' => isset($data['action']) ? (string) $data['action'] : '',
          )
        );

        return $result;
      }

      $response_code = (int) wp_remote_retrieve_response_code($response);
      if ($response_code === 200) {
        $result['status']  = true;
        $result['code']    = 200;
        $result['message'] = __('Data sent successfully.', 'adflipr');
        $this->record_last_successful_webhook_delivery($data);

        return $result;
      }

      $result['code']    = $response_code;
      $result['message'] = sprintf(
        /* translators: %d: HTTP status code */
        __('Data submission failed. Response code: %d', 'adflipr'),
        $response_code
      );

      adflipr_debug_log(
        'webhook_non_200',
        array(
          'code'   => $response_code,
          'action' => isset($data['action']) ? (string) $data['action'] : '',
        )
      );

      return $result;
    }

    /**
     * Send WooCommerce webhook payload; on failure enqueue for WP-Cron retry unless skipped.
     *
     * @param array<string, mixed> $data
     * @param array<string, bool>  $options Optional. skip_retry_enqueue — used by queue processor.
     * @return array<string, mixed>
     */
    public function send_data_on_webhook($data, $options = [])
    {
      $skip_retry_enqueue = ! empty($options['skip_retry_enqueue']);

      if (is_array($data) && function_exists('adflipr_webhook_payload_with_idempotency')) {
        $data = adflipr_webhook_payload_with_idempotency($data);
      }

      $result = $this->execute_woocommerce_webhook_http($data);

      if (! empty($result['status']) && (int) $result['code'] === 200) {
        $this->maybe_commit_email_settings_payload($data);

        return $result;
      }

      if ($skip_retry_enqueue) {
        return $result;
      }

      // Queue even when token is missing so payloads flush after the merchant connects.

      if ($this->enqueue_webhook_retry_payload($data)) {
        return [
          'status'  => true,
          'code'    => 202,
          'message' => __('Stored offline and will sync when AdFlipr is reachable.', 'adflipr'),
          'queued'  => true,
        ];
      }

      return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function enqueue_webhook_retry_payload(array $data)
    {
      $queue = get_option(ADFLIPR_WEBHOOK_RETRY_QUEUE_OPTION, []);
      if (! is_array($queue)) {
        $queue = [];
      }
      if (count($queue) >= ADFLIPR_WEBHOOK_RETRY_MAX_ITEMS) {
        error_log('AdFlipr: webhook retry queue full; payload not queued.');

        return false;
      }

      $queue[] = [
        'payload'   => $data,
        'attempts'  => 0,
        'queued_at' => time(),
      ];
      update_option(ADFLIPR_WEBHOOK_RETRY_QUEUE_OPTION, $queue, false);

      if (defined('ADFLIPR_WEBHOOK_RETRY_CRON_HOOK')) {
        wp_schedule_single_event(time() + 90, ADFLIPR_WEBHOOK_RETRY_CRON_HOOK);
      }

      return true;
    }

    /**
     * Drain queued webhook payloads (WP-Cron).
     */
    public function process_webhook_retry_queue()
    {
      $queue = get_option(ADFLIPR_WEBHOOK_RETRY_QUEUE_OPTION, []);
      if (! is_array($queue) || count($queue) === 0) {
        return;
      }

      $queue       = array_values($queue);
      $new_queue   = [];
      $max_per_run = ADFLIPR_WEBHOOK_RETRY_BATCH_PER_RUN;
      $processed   = 0;

      foreach ($queue as $item) {
        if ($processed >= $max_per_run) {
          $new_queue[] = $item;
          continue;
        }

        if (empty($item['payload']) || ! is_array($item['payload'])) {
          $processed++;
          continue;
        }

        $attempts = isset($item['attempts']) ? (int) $item['attempts'] : 0;
        if ($attempts >= ADFLIPR_WEBHOOK_RETRY_MAX_ATTEMPTS) {
          error_log('AdFlipr: webhook queue item dropped after max attempts.');
          $processed++;
          continue;
        }

        $result = $this->execute_woocommerce_webhook_http($item['payload']);
        if (! empty($result['status']) && (int) $result['code'] === 200) {
          $this->maybe_commit_email_settings_payload($item['payload']);
          $processed++;
          continue;
        }

        // Do not burn retry attempts while the site has no token — wait for merchant login.
        if (! empty($result['no_token'])) {
          $new_queue[] = $item;
          $processed++;
          continue;
        }

        $item['attempts']     = $attempts + 1;
        $item['last_attempt'] = time();
        $new_queue[]          = $item;
        $processed++;
      }

      update_option(ADFLIPR_WEBHOOK_RETRY_QUEUE_OPTION, array_values($new_queue), false);
    }

    /**
     * Current size of the outbound webhook retry queue (admin / diagnostics).
     */
    public function get_webhook_retry_queue_count()
    {
      $queue = get_option(ADFLIPR_WEBHOOK_RETRY_QUEUE_OPTION, []);

      return is_array($queue) ? count($queue) : 0;
    }

    /**
     * Run multiple cron-sized batches in one request (manual REST/AJAX/WP-CLI).
     *
     * @return array{loops_executed: int}
     */
    public function process_webhook_retry_queue_manual()
    {
      $max_loops = defined('ADFLIPR_WEBHOOK_RETRY_MANUAL_MAX_LOOPS')
        ? (int) ADFLIPR_WEBHOOK_RETRY_MANUAL_MAX_LOOPS
        : 10;
      $max_loops = max(1, min(50, $max_loops));

      $executed = 0;
      for ($i = 0; $i < $max_loops; $i++) {
        if ($this->get_webhook_retry_queue_count() === 0) {
          break;
        }
        $this->process_webhook_retry_queue();
        $executed++;
      }

      return ['loops_executed' => $executed];
    }

    /**
     * REST: POST …/adflipr/v1/webhook-retry/process — drain retry queue without waiting for WP-Cron.
     */
    public function register_webhook_retry_rest_routes()
    {
      register_rest_route(
        'adflipr/v1',
        '/webhook-retry/process',
        [
          'methods'             => \WP_REST_Server::CREATABLE,
          'callback'            => [$this, 'rest_process_webhook_retry_queue'],
          'permission_callback' => 'adflipr_rest_permission_manage_options_with_nonce',
        ]
      );

      register_rest_route(
        'adflipr/v1',
        '/webhook-retry/status',
        [
          'methods'             => \WP_REST_Server::READABLE,
          'callback'            => [$this, 'rest_webhook_retry_queue_status'],
          'permission_callback' => 'adflipr_rest_permission_manage_options_with_nonce',
        ]
      );

      register_rest_route(
        'adflipr/v1',
        '/integration-health',
        [
          'methods'             => \WP_REST_Server::READABLE,
          'callback'            => [$this, 'rest_integration_health'],
          'permission_callback' => 'adflipr_rest_permission_manage_options_with_nonce',
        ]
      );
    }

    /**
     * REST: GET …/adflipr/v1/integration-health — admin diagnostic (no secrets).
     *
     * @param \WP_REST_Request $_request Unused.
     * @return \WP_REST_Response
     */
    public function rest_integration_health($_request)
    {
      global $wp_version;

      $last_at = defined('ADFLIPR_LAST_WEBHOOK_SUCCESS_AT_OPTION')
        ? get_option(ADFLIPR_LAST_WEBHOOK_SUCCESS_AT_OPTION, false)
        : false;
      $last_action = defined('ADFLIPR_LAST_WEBHOOK_SUCCESS_ACTION_OPTION')
        ? get_option(ADFLIPR_LAST_WEBHOOK_SUCCESS_ACTION_OPTION, false)
        : false;

      return rest_ensure_response(
        array(
          'plugin_version'                  => defined('ADFLIPR_VERSION') ? ADFLIPR_VERSION : '',
          'wordpress_version'               => $wp_version,
          'php_version'                     => PHP_VERSION,
          'woocommerce_active'              => class_exists('WooCommerce'),
          'environment_meets_requirements'  => function_exists('adflipr_meets_requirements') ? adflipr_meets_requirements() : false,
          'token_valid'                     => AdFlipr_Core()->auth->valid_token(),
          'webhook_retry_queue_items'       => $this->get_webhook_retry_queue_count(),
          'last_webhook_success_unix'       => $last_at !== false ? (int) $last_at : null,
          'last_webhook_success_action'     => $last_action !== false ? (string) $last_action : null,
          'debug_mode'                      => defined('ADFLIPR_DEBUG') && ADFLIPR_DEBUG,
        )
      );
    }

    /**
     * @param \WP_REST_Request $request Request (unused).
     * @return \WP_REST_Response|\WP_Error
     */
    public function rest_process_webhook_retry_queue($_request)
    {
      $before = $this->get_webhook_retry_queue_count();
      $meta   = $this->process_webhook_retry_queue_manual();
      $after  = $this->get_webhook_retry_queue_count();

      return rest_ensure_response(
        [
          'remaining_before' => $before,
          'remaining_after'  => $after,
          'loops_executed'   => $meta['loops_executed'],
        ]
      );
    }

    /**
     * @return \WP_REST_Response
     */
    public function rest_webhook_retry_queue_status($_request)
    {
      return rest_ensure_response(
        [
          'remaining' => $this->get_webhook_retry_queue_count(),
        ]
      );
    }

    /**
     * AJAX: same as REST process (for wp-admin without REST client).
     */
    public function ajax_process_webhook_retry_queue()
    {
      if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Forbidden.', 'adflipr')], 403);
      }

      check_ajax_referer('adflipr_webhook_retry', 'nonce');

      $before = $this->get_webhook_retry_queue_count();
      $meta   = $this->process_webhook_retry_queue_manual();
      $after  = $this->get_webhook_retry_queue_count();

      wp_send_json_success(
        [
          'remaining_before' => $before,
          'remaining_after'  => $after,
          'loops_executed'   => $meta['loops_executed'],
        ]
      );
    }

    public function register_wp_cli_webhook_retry_command()
    {
      if (! defined('WP_CLI') || ! WP_CLI || ! class_exists('WP_CLI', false)) {
        return;
      }

      \WP_CLI::add_command(
        'adflipr webhook-retry-process',
        [$this, 'cli_webhook_retry_process']
      );
    }

    /**
     * @param array<int, string> $args Positional args.
     * @param array<string, mixed> $assoc_args Named args.
     */
    public function cli_webhook_retry_process($args, $assoc_args)
    {
      $before = $this->get_webhook_retry_queue_count();
      $meta   = $this->process_webhook_retry_queue_manual();
      $after  = $this->get_webhook_retry_queue_count();

      \WP_CLI::success(
        sprintf(
          /* translators: 1: queue size after, 2: queue size before, 3: batch loops run */
          __('Webhook retry queue: %1$d item(s) remaining (was %2$d). Ran %3$d batch loop(s).', 'adflipr'),
          $after,
          $before,
          $meta['loops_executed']
        )
      );
    }

    /**
     * After Adflipr confirms receipt of email_settings, persist WooCommerce suppression flags.
     *
     * @param array<string, mixed> $data Webhook payload.
     */
    private function maybe_commit_email_settings_payload(array $data)
    {
      if (! isset($data['action']) || $data['action'] !== 'email_settings') {
        return;
      }
      if (empty($data['settings']) || ! is_array($data['settings'])) {
        return;
      }
      update_option('adflipr_disabled_wc_emails', $data['settings'], false);
      if (defined('ADFLIPR_WC_EMAIL_SUPPRESSION_PENDING_OPTION')) {
        $pending = get_option(ADFLIPR_WC_EMAIL_SUPPRESSION_PENDING_OPTION, null);
        if (is_array($pending) && serialize($pending) === serialize($data['settings'])) {
          delete_option(ADFLIPR_WC_EMAIL_SUPPRESSION_PENDING_OPTION);
        }
      }
    }

    public function maybe_schedule_next_event($args, $limit, $products, $subscriptions, $coupons, $orders)
    {
      if (is_array($products) && count($products) === $limit) {
        $args['product_offset'] = absint($args['product_offset']) + 1;
        $args['product_data']   = true;
      } else {
        $args['product_offset'] = 0;
        $args['product_data']   = false;
      }

      if (is_array($subscriptions) && count($subscriptions) === $limit) {
        $args['subscription_offset'] = absint($args['subscription_offset']) + 1;
        $args['subscription_data']   = true;
      } else {
        $args['subscription_offset'] = 0;
        $args['subscription_data']   = false;
      }

      if (is_array($coupons) && count($coupons) === $limit) {
        $args['coupon_offset'] = absint($args['coupon_offset']) + 1;
        $args['coupon_data']   = true;
      } else {
        $args['coupon_offset'] = 0;
        $args['coupon_data']   = false;
      }

      if (is_array($orders) && count($orders) === $limit) {
        $args['order_offset'] = absint($args['order_offset']) + 1;
        $args['order_data']   = true;
      } else {
        $args['order_offset'] = 0;
        $args['order_data']   = false;
      }

      if ($args['order_data'] || $args['product_data'] || $args['subscription_data'] || $args['coupon_data']) {
        if (function_exists('wp_schedule_single_event')) {
          wp_schedule_single_event(time() + 60, 'adflipr_send_data_event', [$args]);
        }
      } else {
        if (function_exists('wp_clear_scheduled_hook')) {
          wp_clear_scheduled_hook('adflipr_send_product_data');
        }
      }
    }
  }

  if (class_exists('AdFlipr_Core')) {
    AdFlipr_Core::register('data', 'AdFlipr_Data');
  }
}
