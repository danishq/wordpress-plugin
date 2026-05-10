<?php
defined('ABSPATH') || exit; //Exit if accessed directly

/**
 * Class AdFlipr_Auth
 */
if (! class_exists('AdFlipr_Auth')) {

  class AdFlipr_Auth
  {
    private static $ins = null;
    private $token;
    private $token_expiry;

    private $ADFLIPR_TIMEOUT = 30;

    public function __construct()
    {
      add_action('rest_api_init', [$this, 'adflipr_register_login_token_route']);
    }

    // Authenticate with the API and store the token
    public function authenticate($email, $password)
    {

      $data     = $this->get_or_create_valid_woocommerce_api_key();
      $data     = array_merge(['site_url' => site_url(), 'from' => 'wordpress'], $data);
      $login_url = defined('ADFLIPR_API_WP_USERS_LOGIN_URL') ? ADFLIPR_API_WP_USERS_LOGIN_URL : ADFLIPR_API_USERS_LOGIN_URL;
      $response  = wp_remote_post($login_url, [
        'body'    => wp_json_encode([
          'email'       => $email,
          'password'    => $password,
          'context'     => defined('ADFLIPR_LOGIN_CONTEXT_WORDPRESS') ? ADFLIPR_LOGIN_CONTEXT_WORDPRESS : 'WORDPRESS',
          'queryParams' => $data,
        ]),
        'headers' => ['Content-Type' => 'application/json'],
        'timeout' => 30,
      ]);

      $result = [
        'status'  => false,
        'message' => __('Authentication Failed', 'adflipr')
      ];

      if (is_wp_error($response)) {
        $result['message'] = __('Authentication Failed : ' . $response->get_error_message(), 'adflipr');

        return $result;
      }

      $response_body = json_decode(wp_remote_retrieve_body($response), true);

      if (! is_array($response_body)) {
        return $result;
      }

      // JWT session login (/api/v1/users/login) uses loginToken + tokenExpiry.
      if (! empty($response_body['loginToken'])) {
        $this->token        = $response_body['loginToken'];
        $this->token_expiry = isset($response_body['tokenExpiry']) ? $response_body['tokenExpiry'] : null;
        update_option('adflipr_auth_token', $this->token);
        update_option('adflipr_token_expiry', $this->token_expiry);
        $result = [
          'status'  => true,
          'message' => __('Authentication successful.', 'adflipr'),
        ];

        return $result;
      }

      // WordPress integration login (/api/v1/wp/users/login + context WORDPRESS) returns type wp_token and JSON "token".
      if (! empty($response_body['token']) && isset($response_body['type']) && $response_body['type'] === 'wp_token') {
        $this->token = $response_body['token'];
        if (! empty($response_body['tokenExpiry'])) {
          $this->token_expiry = $response_body['tokenExpiry'];
        } else {
          $ttl_sec            = (defined('YEAR_IN_SECONDS') ? YEAR_IN_SECONDS : 31536000) * 10;
          $this->token_expiry = (time() + $ttl_sec) * 1000;
        }
        update_option('adflipr_auth_token', $this->token);
        update_option('adflipr_token_expiry', $this->token_expiry);
        $result = [
          'status'  => true,
          'message' => __('Authentication successful.', 'adflipr'),
        ];

        return $result;
      }

      if (isset($response_body['message'])) {
        $result['message'] = is_string($response_body['message']) ? $response_body['message'] : wp_json_encode($response_body['message']);
      }

      return $result;
    }

    /**
     * @return AdFlipr_Auth|null
     */
    public static function get_instance()
    {
      if (null === self::$ins) {
        self::$ins = new self();
      }

      return self::$ins;
    }

    // Check if the token has expired
    public function is_token_expired()
    {
      $token_expiry    = get_option('adflipr_token_expiry', false);
      $current_time_ms = round(microtime(true) * 1000);
      if (! $token_expiry) {
        return true; // Token expiry time not set, considered expired
      }

      return $current_time_ms >= intval($token_expiry);
    }

    // Get the stored token (or check if it's expired)
    public function get_token()
    {
      if ($this->is_token_expired()) {
        return false; // Token is expired or missing
      }

      return get_option('adflipr_auth_token', false); // Return the stored token
    }

    public function valid_token()
    {
      if (! $this->is_token_expired() && $this->get_token()) {
        return true;
      }

      return false;
    }

    public function reset_token()
    {
      delete_option('adflipr_auth_token');
      delete_option('adflipr_token_expiry');
      if (defined('ADFLIPR_WEBHOOK_RETRY_QUEUE_OPTION')) {
        delete_option(ADFLIPR_WEBHOOK_RETRY_QUEUE_OPTION);
      }
      delete_option('adflipr_disabled_wc_emails');
      if (defined('ADFLIPR_WC_EMAIL_SUPPRESSION_PENDING_OPTION')) {
        delete_option(ADFLIPR_WC_EMAIL_SUPPRESSION_PENDING_OPTION);
      }
    }

    public function get_super_admin_id()
    {
      if (is_multisite()) {
        $super_admins = get_super_admins();

        return ! empty($super_admins) ? get_user_by('login', $super_admins[0])->ID : null;
      } else {
        $admins = get_users(['role' => 'administrator', 'number' => 1]);

        return ! empty($admins) ? $admins[0]->ID : null;
      }
    }

    public function get_or_create_valid_woocommerce_api_key($permissions = 'read')
    {
      global $wpdb;


      $user_id = $this->get_super_admin_id();
      if (! $user_id) {
        return ['status' => false, 'message' => 'Super Admin not found'];
      }

      // Check if a valid API key already exists
      $existing_key = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}woocommerce_api_keys WHERE user_id = %d AND permissions = %s LIMIT 1", $user_id, $permissions));

      if ($existing_key) {
        $consumer_key    = 'ck_' . $existing_key->truncated_key;
        $consumer_secret = $existing_key->consumer_secret;

        if ($this->validate_woocommerce_api_key($consumer_key, $consumer_secret)) {

          return [
            'status'          => true,
            'super_admin_id'  => $user_id,
            'consumer_key'    => $consumer_key,
            'consumer_secret' => $consumer_secret
          ];
        }
        // If invalid, delete it
        $wpdb->delete("{$wpdb->prefix}woocommerce_api_keys", ['key_id' => $existing_key->key_id]);
      }


      // Create a new API key
      $consumer_key    = 'ck_' . wc_rand_hash();
      $consumer_secret = 'cs_' . wc_rand_hash();

      // Insert API key into WooCommerce database
      $wpdb->insert("{$wpdb->prefix}woocommerce_api_keys", [
        'user_id'         => $user_id,
        'description'     => 'AdFlipr API Key',
        'permissions'     => $permissions,
        'consumer_key'    => $consumer_key, // ✅ Store raw key
        'consumer_secret' => $consumer_secret,
        'truncated_key'   => substr($consumer_key, -7),
        'last_access'     => current_time('mysql')
      ]);

      // Validate new key
      if ($this->validate_woocommerce_api_key($consumer_key, $consumer_secret)) {
        return [
          'status'          => true,
          'super_admin_id'  => $user_id,
          'consumer_key'    => $consumer_key,
          'consumer_secret' => $consumer_secret
        ];
      } else {
        return ['status' => false, 'message' => 'API key validation failed'];
      }
    }


    /**
     * Validate WooCommerce API Key by making a test request
     */
    public function validate_woocommerce_api_key($consumer_key, $consumer_secret)
    {
      $site_url = get_site_url();
      $endpoint = $site_url . '/wp-json/wc/v3/products';

      $response = wp_remote_get($endpoint, [
        'headers' => [
          'Authorization' => 'Basic ' . base64_encode("$consumer_key:$consumer_secret"),
          'Content-Type'  => 'application/json'
        ],
        'timeout' => 30,
      ]);


      if (is_wp_error($response)) {
        return false; // API request failed
      }

      $status_code = wp_remote_retrieve_response_code($response);

      return ($status_code === 200); // Valid if response is 200 OK
    }

    public function adflipr_register_login_token_route()
    {
      register_rest_route('adflipr/v1', '/login-token', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => function () {
          return ['loginToken' => $this->get_token()];
        },
        'permission_callback' => [$this, 'rest_permission_login_token_read'],
      ]);

      register_rest_route('adflipr/v1', '/login-token', [
        'methods' => WP_REST_Server::CREATABLE,
        'callback' => [$this, 'store_token'],
        'permission_callback' => [$this, 'rest_permission_login_token_write'],
      ]);

      // Single proxy endpoint that will handle all API requests
      register_rest_route('adflipr/v1', '/proxy/(?P<path>.*)', [
        'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        'callback' => [$this, 'proxy_handler'],
        'permission_callback' => [$this, 'rest_permission_proxy'],
        'args' => [
          'path' => [
            'required' => true,
            'validate_callback' => function ($param) {
              return is_string($param);
            }
          ]
        ]
      ]);
    }

    public function store_token($request)
    {
      $request_data = $request->get_body();
      $parsed       = [];
      if (! empty($request_data)) {
        $parsed = json_decode($request_data, true);
      }

      if (! is_array($parsed)) {
        return rest_ensure_response(array(
          'status'  => false,
          'message' => __('Invalid request body.', 'adflipr'),
        ));
      }

      $email_raw = isset($parsed['email']) ? $parsed['email'] : '';
      $email     = sanitize_email(is_string($email_raw) ? $email_raw : '');
      $password  = isset($parsed['password']) && is_string($parsed['password']) ? $parsed['password'] : '';

      $get_data = $this->authenticate($email, $password);

      if (is_array($get_data) && isset($get_data['status']) && $get_data['status']) {
        delete_user_meta(get_current_user_id(), 'adflipr_connect_notice_dismissed');
        $result = AdFlipr_Core()->admin->send_data_after_authentication();
        return rest_ensure_response(array(
          'status' => true,
          'message'   => __('Token stored successfully. ' . $result['message'], 'adflipr')
        ));
      } else {
        return rest_ensure_response(array(
          'status' => false,
          'message'   => __('Token stored failed. ' . $get_data['message'], 'adflipr')
        ));
      }
    }

    public function proxy_handler($request)
    {
      // Get the path from the URL
      $path = $request->get_param('path');

      // Get request method
      $method = $request->get_method();

      // Get request parameters based on method
      $params = [];
      if ($method === 'GET') {
        $params = $request->get_query_params();
        return $this->handle_internal_api_get_request($path, $params);
      }

      // Handle POST, PUT, DELETE, ... requests
      $params = $request->get_body();
      if (! empty($params)) {
        $params =  json_decode($params, true);
      } else {
        $params = [];
      }
      return $this->handle_internal_api_request($path, $method, $params);
    }

    public function handle_internal_api_get_request($path, $params = [])
    {
      $url = adflipr_api_url('api/v1/' . ltrim((string) $path, '/')) . '?' . http_build_query($params);
      $response = wp_remote_get($url, [
        'headers' => [
          'Authorization' => $this->get_token()
        ],
        'timeout' => $this->ADFLIPR_TIMEOUT,
      ]);

      return $this->format_remote_proxy_response($response);
    }

    public function handle_internal_api_request($path, $method, $params = [])
    {
      $url = adflipr_api_url('api/v1/' . ltrim((string) $path, '/'));
      $body = '';
      if (is_array($params)) {
        $body = wp_json_encode($params);
      } elseif (is_string($params)) {
        $body = $params;
      }

      $response = wp_remote_request($url, [
        'headers' => [
          'Authorization' => $this->get_token(),
          'Content-Type'  => 'application/json'
        ],
        'method' => $method,
        'body'   => $body,
        'timeout' => $this->ADFLIPR_TIMEOUT,
      ]);

      return $this->format_remote_proxy_response($response);
    }

    /**
     * Normalize upstream HTTP into a WP REST response (avoid leaking raw wp_remote_* arrays).
     *
     * @param array|\WP_Error $response
     * @return \WP_REST_Response|\WP_Error
     */
    private function format_remote_proxy_response($response)
    {
      if (is_wp_error($response)) {
        return new WP_Error(
          'adflipr_proxy_http_error',
          $response->get_error_message(),
          array('status' => 502)
        );
      }

      $code = (int) wp_remote_retrieve_response_code($response);
      $body = wp_remote_retrieve_body($response);
      $decoded = json_decode($body, true);

      if (! is_array($decoded)) {
        $decoded = array(
          'body' => $body,
        );
      }

      return new WP_REST_Response($decoded, $code >= 100 && $code < 600 ? $code : 200);
    }

    /**
     * GET /login-token — administrators only, valid REST + referer, existing session token.
     *
     * @param \WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public function rest_permission_login_token_read($request)
    {
      if (! current_user_can('manage_options')) {
        return false;
      }

      $nonce_check = $this->rest_require_wp_nonce($request);
      if (is_wp_error($nonce_check)) {
        return $nonce_check;
      }

      if (! $this->valid_token()) {
        return new WP_Error(
          'adflipr_no_token',
          __('Not connected to Adflipr.', 'adflipr'),
          array('status' => 403)
        );
      }

      return $this->api_permissions_check($request);
    }

    /**
     * POST /login-token — administrators only, valid REST + referer (stores credentials server-side).
     *
     * @param \WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public function rest_permission_login_token_write($request)
    {
      if (! current_user_can('manage_options')) {
        return false;
      }

      $nonce_check = $this->rest_require_wp_nonce($request);
      if (is_wp_error($nonce_check)) {
        return $nonce_check;
      }

      return $this->api_permissions_check($request);
    }

    /**
     * Proxy — administrators only, REST nonce, referer, integration token.
     *
     * @param \WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public function rest_permission_proxy($request)
    {
      if (! current_user_can('manage_options')) {
        return false;
      }

      $nonce_check = $this->rest_require_wp_nonce($request);
      if (is_wp_error($nonce_check)) {
        return $nonce_check;
      }

      if (! $this->valid_token()) {
        return new WP_Error(
          'adflipr_no_token',
          __('Not connected to Adflipr.', 'adflipr'),
          array('status' => 403)
        );
      }

      return $this->api_permissions_check($request);
    }

    /**
     * @param \WP_REST_Request $request
     * @return true|\WP_Error
     */
    private function rest_require_wp_nonce($request)
    {
      return adflipr_rest_verify_wp_nonce_header($request);
    }

    public function api_permissions_check($request)
    {
      // Check if the request is coming from the same site
      $referer = $request->get_header('referer');
      if (!$referer || strpos($referer, site_url()) !== 0) {
        return new WP_Error('rest_forbidden', 'Invalid request origin', ['status' => 403]);
      }
      return true;
    }
  }

  if (class_exists('AdFlipr_Core')) {
    AdFlipr_Core::register('auth', 'AdFlipr_Auth');
  }
}
