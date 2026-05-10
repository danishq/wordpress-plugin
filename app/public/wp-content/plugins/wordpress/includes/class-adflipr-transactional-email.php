<?php

defined('ABSPATH') || exit; //Exit if accessed directly

/**
 * Class AdFlipr_Transactional_Email
 */
if (! class_exists('AdFlipr_Transactional_Email')) {

  class AdFlipr_Transactional_Email
  {
    private static $ins = null;

    public function __construct()
    {
      add_action('woocommerce_email_enabled', array($this, 'adflipr_disable_selected_wc_emails'), 10, 2);
      add_action('rest_api_init', array($this, 'register_setting_email_api'));
    }

    /**
     * @return AdFlipr_Transactional_Email|null
     */
    public static function get_instance()
    {
      if (null === self::$ins) {
        self::$ins = new self();
      }

      return self::$ins;
    }

    public function adflipr_disable_selected_wc_emails($enabled, $email)
    {
      // Fail-open: never suppress WooCommerce core emails without an active Adflipr session.
      if (! AdFlipr_Core()->auth->valid_token()) {
        return $enabled;
      }

      $disabled_emails = get_option('adflipr_disabled_wc_emails', array());

      if (isset($disabled_emails[$email->id]) && $disabled_emails[$email->id] === true) {
        return false;
      }

      return $enabled;
    }


    public function email_settings_form()
    {
      $default_wc_emails = array(
        'new_order'               => false,
        'cancelled_order'         => false,
        'failed_order'            => false,
        'customer_on_hold_order'  => false,
        'customer_processing_order' => false,
        'customer_completed_order'  => false,
        'customer_refunded_order'   => false,
        'customer_invoice'          => false,
        'customer_note'             => false,
        'customer_reset_password'   => false,
        'customer_new_account'      => false,
      );

      $pending = ( defined('ADFLIPR_WC_EMAIL_SUPPRESSION_PENDING_OPTION') )
        ? get_option(ADFLIPR_WC_EMAIL_SUPPRESSION_PENDING_OPTION, null)
        : null;
      if (is_array($pending)) {
        $saved_settings = array_merge($default_wc_emails, $pending);
      } else {
        $saved_settings = get_option('adflipr_disabled_wc_emails', $default_wc_emails);
      }

      if (isset($_POST['adflipr_email_settings'])) {
        check_admin_referer('adflipr_email_settings_action', 'adflipr_email_settings_nonce');
        $disabled_emails = isset($_POST['adflipr_disabled_woocommerce_emails']) ? wp_unslash($_POST['adflipr_disabled_woocommerce_emails']) : array();

        $saved_settings = $default_wc_emails;
        foreach (array_keys($default_wc_emails) as $key) {
          $saved_settings[$key] = isset($disabled_emails[$key]) && '1' === (string) $disabled_emails[$key];
        }

        $get_data = $this->send_email_setting_on_webhook($saved_settings);

        if (is_array($get_data) && ! empty($get_data['status'])) {
          if (isset($get_data['code']) && (int) $get_data['code'] === 200) {
            // Active suppression flags are committed in AdFlipr_Data::maybe_commit_email_settings_payload().
            echo '<div class="updated"><p>' . esc_html($get_data['message']) . '</p></div>';
          } elseif (! empty($get_data['queued']) && defined('ADFLIPR_WC_EMAIL_SUPPRESSION_PENDING_OPTION')) {
            update_option(ADFLIPR_WC_EMAIL_SUPPRESSION_PENDING_OPTION, $saved_settings, false);
            echo '<div class="updated"><p>' . esc_html($get_data['message']) . '</p></div>';
          }
        } else {
          if (is_array($get_data) && isset($get_data['message'])) {
            echo '<div class="error"><p>' . esc_html($get_data['message']) . '</p></div>';
          } else {
            echo '<div class="error"><p>' . esc_html__('Email settings could not be saved.', 'adflipr') . '</p></div>';
          }
        }
      }

      // **WooCommerce Default Emails**
      $default_wc_nice_name = array(
        'new_order'             => 'New Order',
        'cancelled_order'       => 'Cancelled Order',
        'failed_order'          => 'Failed Order',
        'customer_on_hold_order' => 'Order on-hold',
        'customer_processing_order' => 'Processing Order',
        'customer_completed_order'  => 'Completed Order',
        'customer_refunded_order'   => 'Refunded Order',
        'customer_invoice'          => 'Customer Invoice',
        'customer_note'             => 'Customer Note',
        'customer_reset_password'   => 'Reset Password',
        'customer_new_account'      => 'New Account',
      );


?>
      <div class="wrap">
        <form method="post" style="padding-top:25px">
          <h2><?php echo esc_html('AdFlipr Email Settings.'); ?></h2>

          <?php wp_nonce_field('adflipr_email_settings_action', 'adflipr_email_settings_nonce'); ?>
          <table class="form-table">
            <tr>
              <th>Select Emails to Disable</th>
              <td>
                <?php foreach ($default_wc_nice_name as $email_id => $email_label) : ?>
                  <label>
                    <input type="checkbox" name="adflipr_disabled_woocommerce_emails[<?php echo esc_attr($email_id); ?>]" value="1"
                      <?php checked(! empty($saved_settings[$email_id])); ?>>
                    <?php echo esc_html($email_label); ?>
                  </label>
                  <br>
                <?php endforeach; ?>
              </td>
            </tr>
          </table>
          <p><input type="submit" name="adflipr_email_settings" class="button-primary" value="Save Email Settings"></p>
        </form>
      </div>
<?php
    }

    public function send_email_setting_on_webhook($email_settings)
    {
      $data = [
        'action'   => 'email_settings',
        'settings' => $email_settings,
      ];

      $result = AdFlipr_Core()->data->send_data_on_webhook($data);

      if (! empty($result['queued'])) {
        $result['message'] = __(
          'Preferences queued for Adflipr. WooCommerce will keep sending its own emails until Adflipr confirms receipt.',
          'adflipr'
        );
      } elseif (! empty($result['status']) && isset($result['code']) && (int) $result['code'] === 200) {
        $result['message'] = __('Email settings saved successfully.', 'adflipr');
      } elseif (! empty($result['no_token'])) {
        $result['message'] = __('Connect to AdFlipr first.', 'adflipr');
      }

      return $result;
    }

    public function register_setting_email_api()
    {
      register_rest_route('wc', '/v3/adflipr-email-settings', array(
        array(
          'methods'             => WP_REST_Server::READABLE, // GET method
          'callback'            => array($this, 'get_email_settings'),
          'permission_callback' => array($this, 'get_read_api_permission_check'),
          'args'                => array(),
        ),
        array(
          'methods'             => WP_REST_Server::CREATABLE, // POST method
          'callback'            => array($this, 'setup_email_setting'),
          'permission_callback' => array($this, 'get_read_api_permission_check'),
          'args'                => array(),
        ),
      ));
    }

    public function get_read_api_permission_check($request = null)
    {
      error_log('Permission check started');

      // Allow access if using query parameters with consumer key/secret
      if (!empty($_GET['consumer_key']) && !empty($_GET['consumer_secret'])) {
        error_log('Found consumer key in query params');
        return true;
      }

      // Check for WooCommerce authentication
      if (class_exists('WC_REST_Authentication')) {
        error_log('WC_REST_Authentication class exists');

        // Create an instance of the WooCommerce REST authentication handler
        $auth = new WC_REST_Authentication();

        // Check if the current request is authenticated
        if (method_exists($auth, 'authenticate')) {
          $result = $auth->authenticate($request);
          error_log('WC authentication result: ' . ($result ? 'true' : 'false'));

          if ($result) {
            return true;
          }
        }
      }

      error_log('Authentication failed');
      return false;
    }

    // Add a method to get current email settings
    public function get_email_settings()
    {
      if (!function_exists('get_option')) {
        return rest_ensure_response(array(
          'status' => false,
          'msg'    => 'WordPress functions not available'
        ));
      }

      $saved_settings = get_option('adflipr_disabled_wc_emails', array());

      return rest_ensure_response(array(
        'status' => true,
        'data'   => $saved_settings
      ));
    }

    public function setup_email_setting($request)
    {
      $request_data = $request->get_body();
      if (! empty($request_data)) {
        $request_data =  json_decode($request_data, true);
      }
      $email_setting = isset($request_data['email_settings']) ? $request_data['email_settings'] : null;

      $resp = array(
        'status' => false,
        'msg'    => __('Failed', 'adflipr')
      );

      if (is_array($email_setting) && (0 < count($email_setting))) {
        update_option('adflipr_disabled_wc_emails', $email_setting);
        if (defined('ADFLIPR_WC_EMAIL_SUPPRESSION_PENDING_OPTION')) {
          delete_option(ADFLIPR_WC_EMAIL_SUPPRESSION_PENDING_OPTION);
        }

        $resp = array(
          'status' => true,
          'msg'    => __('Success', 'adflipr')
        );
      }

      return rest_ensure_response($resp);
    }
  }

  if (class_exists('AdFlipr_Transactional_Email')) {
    AdFlipr_Core::register('email', 'AdFlipr_Transactional_Email');
  }
}
