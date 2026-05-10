<?php

defined('ABSPATH') || exit; // Exit if accessed directly

/**
 * Class AdFlipr_Admin
 */
if (! class_exists('AdFlipr_Admin')) {
  class AdFlipr_Admin
  {
    private static $ins = null;

    /** @var bool */
    private static $privacy_policy_content_registered = false;

    public function __construct()
    {
      add_action('init', array($this, 'check_woocommerce_added'));
      add_action('admin_menu', array($this, 'register_menu'));
      add_action('admin_init', array($this, 'handle_connect_notice_dismiss'), 5);
      add_action('admin_init', array($this, 'handle_admin_form_actions'), 1);
      add_action('admin_notices', array($this, 'admin_settings_feedback_notices'));
      add_action('admin_notices', array($this, 'adflipr_not_connected_notice'));
      add_action('admin_init', array($this, 'maybe_register_privacy_policy_suggested_text'), 5);
      add_action('wp_enqueue_scripts', array($this, 'add_user_scripts'));
      add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
      add_shortcode('adflipr_react_app', array($this, 'render_react_app_shortcode'));
    }

    public function check_woocommerce_added()
    {
      if (! class_exists('WooCommerce')) {
        add_action('admin_notices', array($this, 'woocommerce_missing_notice'));

        return;
      }
    }

    /**
     * Secure disconnect, privacy save, and redirects (capabilities + nonces).
     */
    public function handle_admin_form_actions()
    {
      $page = '';
      if (isset($_REQUEST['page'])) {
        $page = sanitize_key(wp_unslash($_REQUEST['page']));
      }
      if ($page !== 'adflipr-settings') {
        return;
      }

      if (! current_user_can('manage_options')) {
        return;
      }

      if (isset($_GET['reset_token'], $_GET['_wpnonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'adflipr_reset_token')
      ) {
        AdFlipr_Core()->auth->reset_token();
        delete_user_meta(get_current_user_id(), 'adflipr_connect_notice_dismissed');
        wp_safe_redirect(
          add_query_arg(
            array(
              'page'          => 'adflipr-settings',
              'adflipr_reset' => '1',
            ),
            admin_url('admin.php')
          )
        );
        exit;
      }

      if (! isset($_POST['adflipr_privacy_settings_submit'], $_POST['adflipr_privacy_nonce'])) {
        return;
      }

      check_admin_referer('adflipr_privacy_settings', 'adflipr_privacy_nonce');

      if (! defined('ADFLIPR_UTM_TRACKING_OPTION')) {
        return;
      }

      $utm_on = isset($_POST['adflipr_utm_tracking_enabled']) && (string) wp_unslash($_POST['adflipr_utm_tracking_enabled']) === '1';
      update_option(ADFLIPR_UTM_TRACKING_OPTION, $utm_on ? 1 : 0, false);

      wp_safe_redirect(
        add_query_arg(
          array(
            'page'           => 'adflipr-settings',
            'adflipr_saved' => '1',
          ),
          admin_url('admin.php')
        )
      );
      exit;
    }

    /**
     * Dismiss "not connected" admin notice (per user).
     */
    public function handle_connect_notice_dismiss()
    {
      if (! isset($_GET['adflipr_dismiss_connect'], $_GET['_wpnonce'])) {
        return;
      }

      if (! current_user_can('manage_options')) {
        return;
      }

      if (! wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'adflipr_dismiss_connect_notice')) {
        return;
      }

      update_user_meta(get_current_user_id(), 'adflipr_connect_notice_dismissed', '1');

      $redirect = wp_get_referer();
      if (! is_string($redirect) || $redirect === '') {
        $redirect = admin_url();
      }

      wp_safe_redirect(remove_query_arg(array('adflipr_dismiss_connect', '_wpnonce'), $redirect));
      exit;
    }

    /**
     * Prompt administrators to connect when WooCommerce is active but Adflipr token is missing.
     */
    public function adflipr_not_connected_notice()
    {
      if (! current_user_can('manage_options')) {
        return;
      }

      if (! class_exists('WooCommerce')) {
        return;
      }

      if (! function_exists('AdFlipr_Core')) {
        return;
      }

      $core = AdFlipr_Core();
      if (! isset($core->auth) || ! is_object($core->auth)) {
        return;
      }

      if ($core->auth->valid_token()) {
        return;
      }

      if (get_user_meta(get_current_user_id(), 'adflipr_connect_notice_dismissed', true) === '1') {
        return;
      }

      $settings_url = admin_url('admin.php?page=adflipr-settings');
      $dismiss_url  = wp_nonce_url(
        add_query_arg('adflipr_dismiss_connect', '1', admin_url('index.php')),
        'adflipr_dismiss_connect_notice'
      );

      ?>
      <div class="notice notice-warning">
        <p>
          <?php esc_html_e('AdFlipr is not connected. Store-related data may queue locally until you connect your Adflipr account.', 'adflipr'); ?>
          <a href="<?php echo esc_url($settings_url); ?>"><?php esc_html_e('Open AdFlipr settings', 'adflipr'); ?></a>
          |
          <a href="<?php echo esc_url($dismiss_url); ?>"><?php esc_html_e('Dismiss this notice', 'adflipr'); ?></a>
        </p>
      </div>
      <?php
    }

    /**
     * Success notices after redirect (privacy save / disconnect).
     */
    public function admin_settings_feedback_notices()
    {
      if (! isset($_GET['page']) || sanitize_key(wp_unslash($_GET['page'])) !== 'adflipr-settings') {
        return;
      }

      if (! current_user_can('manage_options')) {
        return;
      }

      if (isset($_GET['adflipr_saved'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'adflipr') . '</p></div>';
      }

      if (isset($_GET['adflipr_reset'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('AdFlipr connection data was removed from this site.', 'adflipr') . '</p></div>';
      }
    }

    /**
     * Suggested Privacy Policy text (Tools → Privacy → Policy guide).
     */
    public function maybe_register_privacy_policy_suggested_text()
    {
      if (self::$privacy_policy_content_registered || ! function_exists('wp_add_privacy_policy_content')) {
        return;
      }

      self::$privacy_policy_content_registered = true;

      $body = sprintf(
        '<p>%s</p><ul><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ul><p>%s</p>',
        esc_html__(
          'This site uses the AdFlipr Integration plugin to connect WooCommerce with Adflipr (hosted service). Depending on your settings and store activity, data may be transmitted to Adflipr servers over HTTPS.',
          'adflipr'
        ),
        esc_html__(
          'Product, coupon, order, and (where applicable) subscription data for synchronization and automation.',
          'adflipr'
        ),
        esc_html__(
          'Checkout-related events (for example cart recovery or purchase notifications) when the shopper interacts with checkout.',
          'adflipr'
        ),
        esc_html__(
          'Authentication tokens stored locally after you sign in to AdFlipr from WordPress admin.',
          'adflipr'
        ),
        esc_html__(
          'If you enable “Store UTM parameters” in AdFlipr settings, a small script may store marketing campaign parameters from the URL in first-party session cookies on your domain (utm_* keys only).',
          'adflipr'
        ),
        esc_html__(
          'Data is sent so Adflipr can provide email marketing and automation features you configure in your Adflipr account. See Adflipr’s own privacy policy for how they process data.',
          'adflipr'
        )
      );

      wp_add_privacy_policy_content(__('AdFlipr Integration', 'adflipr'), wp_kses_post($body));
    }

    /**
     * Display notice if WooCommerce is not active
     */
    public function woocommerce_missing_notice()
    {
      ?>
      <div class="notice notice-error"><p><?php echo esc_html__('AdFlipr requires WooCommerce to be installed and active.', 'adflipr'); ?></p></div>
      <?php
    }

    /**
     * @return AdFlipr_Admin|null
     */
    public static function get_instance()
    {
      if (null === self::$ins) {
        self::$ins = new self();
      }

      return self::$ins;
    }

    public function register_menu()
    {
      $icon_svg = '<svg width="20" height="20" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
    <path d="M16.0021 11.4982V15.8818H11.9543C11.4122 15.8818 10.8619 15.8279 10.3664 15.6079C9.01599 15.0084 8.4026 14.0158 8.13636 13.1052C7.76355 11.8301 8.11031 10.1352 9.03473 9.18119C9.37595 8.82904 9.81443 8.49377 10.3685 8.24815C11.7719 7.62606 13.5327 7.83371 14.6573 8.87865C15.572 9.72868 15.9309 10.8559 16.0021 11.4982Z" fill="#9ca2a7"/>
    <path d="M24 11.0847V23.9875C22.3509 24.186 20.5645 21.9749 19.8773 20.8445C19.8773 20.8445 20.537 16.0473 19.8773 11.0847C19.2177 6.12205 15.26 3.88889 12.0443 3.88889C10.7208 4.00957 9.16332 4.32593 7.73225 5.20913C4.0817 7.46212 3.08649 12.9629 5.45268 16.5411C5.75692 17.0012 6.09432 17.3941 6.44863 17.7311C8.85378 20.0184 12.5841 20.0146 15.9027 19.954C16.1808 19.9489 16.4621 19.9425 16.7441 19.9347C19.9809 21.0099 19.9873 23.1052 19.8773 23.9875H10.8899C7.09709 23.4085 -0.241256 20.3482 0.00609841 10.6711C1.65517 0.663176 11.0548 -1.4384 16.5792 0.828612C22.0211 3.06178 23.7252 8.3001 24 11.0847Z" fill="#9ca2a7"/>
    </svg>';

      add_menu_page(
        __('AdFlipr', 'adflipr'),
        __('AdFlipr', 'adflipr'),
        'manage_options',
        'adflipr-settings',
        array($this, 'render_react_admin_page'),
        'data:image/svg+xml;base64,' . base64_encode($icon_svg),
        56
      );
    }

    /**
     * Privacy disclosure + UTM opt-in + disconnect (WordPress.org transparency expectations).
     */
    public function render_react_admin_page()
    {
      if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to access this page.', 'adflipr'));
      }

      echo '<div class="wrap">';
      $this->render_privacy_settings_panel();
      echo '<div id="adflipr-wp-react-app"></div>';
      echo '</div>';
    }

    /**
     * Admin-only: what is sent, where, why; UTM opt-in; disconnect with nonce.
     */
    private function render_privacy_settings_panel()
    {
      $api_host = '';
      if (defined('ADFLIPR_API_BASE_URL')) {
        $api_host = (string) ADFLIPR_API_BASE_URL;
      }

      $utm_on = $this->is_utm_tracking_enabled();

      $reset_url = wp_nonce_url(
        add_query_arg(
          array(
            'page'        => 'adflipr-settings',
            'reset_token' => '1',
          ),
          admin_url('admin.php')
        ),
        'adflipr_reset_token'
      );

      ?>
      <div class="notice notice-info" style="margin-top:12px;">
        <p><strong><?php esc_html_e('Data & privacy', 'adflipr'); ?></strong></p>
        <p><?php esc_html_e('This plugin sends WooCommerce-related data to Adflipr over HTTPS so you can use Adflipr email marketing and automation. Typical payloads include catalog snapshots, webhooks for products/coupons, checkout/cart events you enable, and optional WooCommerce email suppression settings.', 'adflipr'); ?></p>
        <?php if ($api_host !== '') : ?>
          <p>
            <?php esc_html_e('Remote endpoint base (configurable):', 'adflipr'); ?>
            <code><?php echo esc_html(untrailingslashit($api_host)); ?></code>
          </p>
        <?php endif; ?>
      </div>

      <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=adflipr-settings')); ?>" style="margin:12px 0;">
        <?php wp_nonce_field('adflipr_privacy_settings', 'adflipr_privacy_nonce'); ?>
        <fieldset>
          <label for="adflipr_utm_tracking_enabled">
            <input type="checkbox" name="adflipr_utm_tracking_enabled" id="adflipr_utm_tracking_enabled" value="1" <?php checked($utm_on); ?> />
            <?php esc_html_e('Store UTM campaign parameters from the URL in first-party session cookies (utm_* only) on the storefront for attribution in Adflipr.', 'adflipr'); ?>
          </label>
        </fieldset>
        <?php submit_button(__('Save privacy & tracking settings', 'adflipr'), 'secondary', 'adflipr_privacy_settings_submit', false); ?>
      </form>

      <p>
        <a href="<?php echo esc_url($reset_url); ?>" onclick="return confirm(<?php echo wp_json_encode(__('Remove stored AdFlipr tokens and local sync queue data from this WordPress site?', 'adflipr')); ?>);">
          <?php esc_html_e('Disconnect AdFlipr on this site', 'adflipr'); ?>
        </a>
      </p>
      <?php
    }

    /**
     * Public storefront script only when the site administrator opts in.
     */
    private function is_utm_tracking_enabled()
    {
      if (! defined('ADFLIPR_UTM_TRACKING_OPTION')) {
        return false;
      }

      $v = get_option(ADFLIPR_UTM_TRACKING_OPTION, false);

      return $v === true || $v === 1 || $v === '1';
    }

    public function send_data_after_authentication()
    {
      $args = array(
        'product_offset'      => get_option('adflipr_products_last_offset', 0),
        'product_data'        => true,
        'order_offset'        => get_option('adflipr_orders_last_offset', 0),
        'order_data'          => true,
        'subscription_offset' => get_option('adflipr_orders_last_offset', 0),
        'subscription_data'   => true,
        'coupon_offset'       => get_option('adflipr_orders_last_offset', 0),
        'coupon_data'         => true,
      );

      $result = AdFlipr_Core()->data->send_data_all_data($args);

      // Token now exists: drain queued storefront payloads (cart/checkout captured while disconnected).
      if (AdFlipr_Core()->auth->valid_token()) {
        AdFlipr_Core()->data->process_webhook_retry_queue_manual();
      }

      return $result;
    }

    /**
     * Enqueue React scripts and styles for frontend
     */
    public function enqueue_scripts()
    {
      wp_enqueue_script(
        'adflipr-wp-react-app-script',
        ADFLIPR_PLUGIN_URL . '/build/index.js',
        array('wp-element'),
        ADFLIPR_VERSION,
        true
      );

      wp_localize_script(
        'adflipr-wp-react-app-script',
        'adfliprWp',
        array(
          'appBaseUrl'                 => untrailingslashit((string) ADFLIPR_APP_BASE_URL),
          'apiBaseUrl'                 => untrailingslashit((string) ADFLIPR_API_BASE_URL),
          'webhookRetryRestProcessUrl' => esc_url_raw(rest_url('adflipr/v1/webhook-retry/process')),
          'webhookRetryRestStatusUrl'  => esc_url_raw(rest_url('adflipr/v1/webhook-retry/status')),
          'integrationHealthRestUrl'   => esc_url_raw(rest_url('adflipr/v1/integration-health')),
          'restNonce'                  => wp_create_nonce('wp_rest'),
          'ajaxUrl'                    => admin_url('admin-ajax.php'),
          'webhookRetryAjaxNonce'      => wp_create_nonce('adflipr_webhook_retry'),
        )
      );

      wp_enqueue_style(
        'adflipr-wp-react-app-style',
        ADFLIPR_PLUGIN_URL . '/build/index.css',
        array(),
        ADFLIPR_VERSION
      );

      wp_enqueue_style(
        'adflipr-wp-react-app-style-index',
        ADFLIPR_PLUGIN_URL . '/build/style-index.css',
        array(),
        ADFLIPR_VERSION
      );
    }

    public function add_user_scripts()
    {
      if (! $this->is_utm_tracking_enabled()) {
        return;
      }

      wp_enqueue_script(
        'adflipr-wp-utm-tracking',
        ADFLIPR_PLUGIN_URL . '/assets/js/adflipr-utm-tracking.js',
        array(),
        ADFLIPR_VERSION,
        true
      );
    }

    /**
     * Enqueue React scripts and styles for admin
     */
    public function admin_enqueue_scripts($hook)
    {
      if (isset($_GET['page']) && sanitize_key(wp_unslash($_GET['page'])) === 'adflipr-settings') {
        $this->enqueue_scripts();
      }
    }

    /**
     * Render React app shortcode
     *
     * @return string HTML for the React app container
     */
    public function render_react_app_shortcode()
    {
      return '<div id="adflipr-wp-react-app"></div>';
    }
  }

  if (class_exists('AdFlipr_Core')) {
    AdFlipr_Core::register('admin', 'AdFlipr_Admin');
  }
}
