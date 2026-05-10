<?php

/**
 * Plugin Name: AdFlipr Integration
 * Description: Service integration: connects WooCommerce to your Adflipr account (hosted SaaS). Not a standalone product—requires an Adflipr subscription/sign-in to provide value.
 * Version: 1.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 10.3.0
 *  Author: AdFlipr Integration
 *  License: GPLv3 or later
 *  License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *  Text Domain: adflipr
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/includes/adflipr-api-config.php';
require_once __DIR__ . '/includes/adflipr-requirements.php';
if (! defined('ADFLIPR_DEBUG')) {
  /** Set true in wp-config.php for verbose diagnostic logs (never logs tokens). */
  define('ADFLIPR_DEBUG', false);
}
require_once __DIR__ . '/includes/adflipr-debug.php';
require_once __DIR__ . '/includes/adflipr-rest-permissions.php';
require_once __DIR__ . '/includes/adflipr-webhook-idempotency.php';
if (! defined('ADFLIPR_WEBHOOK_RETRY_QUEUE_OPTION')) {
  define('ADFLIPR_WEBHOOK_RETRY_QUEUE_OPTION', 'adflipr_webhook_retry_queue');
}
if (! defined('ADFLIPR_WEBHOOK_RETRY_CRON_HOOK')) {
  define('ADFLIPR_WEBHOOK_RETRY_CRON_HOOK', 'adflipr_webhook_retry_process');
}
if (! defined('ADFLIPR_WEBHOOK_RETRY_MAX_ITEMS')) {
  define('ADFLIPR_WEBHOOK_RETRY_MAX_ITEMS', 300);
}
if (! defined('ADFLIPR_WEBHOOK_RETRY_MAX_ATTEMPTS')) {
  define('ADFLIPR_WEBHOOK_RETRY_MAX_ATTEMPTS', 15);
}
if (! defined('ADFLIPR_WEBHOOK_RETRY_BATCH_PER_RUN')) {
  define('ADFLIPR_WEBHOOK_RETRY_BATCH_PER_RUN', 25);
}
if (! defined('ADFLIPR_WEBHOOK_HTTP_TIMEOUT')) {
  define('ADFLIPR_WEBHOOK_HTTP_TIMEOUT', 15);
}
if (! defined('ADFLIPR_WEBHOOK_RETRY_MANUAL_MAX_LOOPS')) {
  /** Max cron-equivalent batches per manual / WP-CLI drain (each batch ≤ ADFLIPR_WEBHOOK_RETRY_BATCH_PER_RUN items). */
  define('ADFLIPR_WEBHOOK_RETRY_MANUAL_MAX_LOOPS', 10);
}
if (! defined('ADFLIPR_WC_EMAIL_SUPPRESSION_PENDING_OPTION')) {
  define('ADFLIPR_WC_EMAIL_SUPPRESSION_PENDING_OPTION', 'adflipr_disabled_wc_emails_pending');
}
if (! defined('ADFLIPR_UTM_TRACKING_OPTION')) {
  /** Option value 1/0 — public UTM cookie script loads only when enabled (opt-in). */
  define('ADFLIPR_UTM_TRACKING_OPTION', 'adflipr_utm_tracking_enabled');
}
if (! defined('ADFLIPR_SAVE_CART_NONCE_ACTION')) {
  define('ADFLIPR_SAVE_CART_NONCE_ACTION', 'adflipr_save_cart');
}
if (! defined('ADFLIPR_SAVE_CART_RATE_PER_MINUTE')) {
  /** Max cart AJAX submissions per IP per minute (abuse protection). */
  define('ADFLIPR_SAVE_CART_RATE_PER_MINUTE', 40);
}
if (! defined('ADFLIPR_SAVE_CART_MAX_CONTENT_LENGTH')) {
  /** Reject cart AJAX if HTTP Content-Length exceeds this (bytes). */
  define('ADFLIPR_SAVE_CART_MAX_CONTENT_LENGTH', 524288);
}
if (! defined('ADFLIPR_ORDER_CHECKOUT_WEBHOOK_SENT_META')) {
  /** HPOS-safe order meta: checkout_purchased webhook accepted (HTTP 200 or queued). */
  define('ADFLIPR_ORDER_CHECKOUT_WEBHOOK_SENT_META', '_adflipr_checkout_webhook_sent');
}
if (! defined('ADFLIPR_LAST_WEBHOOK_SUCCESS_AT_OPTION')) {
  /** Unix time of last outbound webhook that returned HTTP 200 from AdFlipr (diagnostics only). */
  define('ADFLIPR_LAST_WEBHOOK_SUCCESS_AT_OPTION', 'adflipr_last_webhook_success_at');
}
if (! defined('ADFLIPR_LAST_WEBHOOK_SUCCESS_ACTION_OPTION')) {
  /** Payload action key for the last successful webhook (diagnostics only). */
  define('ADFLIPR_LAST_WEBHOOK_SUCCESS_ACTION_OPTION', 'adflipr_last_webhook_success_action');
}

if (! class_exists('AdFlipr_Core')) {

  /**
   * Class AdFlipr_Core
   */
  class AdFlipr_Core
  {

    /**
     * @var null
     */
    public static $_instance = null;

    /**
     * @var array
     */
    private static $_registered_entity = array(
      'active'   => array(),
      'inactive' => array(),
    );

    /**
     * Registered module instances (keys match AdFlipr_Core::register names).
     *
     * @var AdFlipr_Admin|null
     */
    public $admin;

    /** @var AdFlipr_Auth|null */
    public $auth;

    /** @var AdFlipr_Data|null */
    public $data;

    /** @var AdFlipr_Transactional_Email|null */
    public $email;

    /** @var Adflipr_Cart_Tracker|null */
    public $cart_tracker;

    /**
     * AdFlipr_Core constructor.
     */
    public function __construct()
    {
      $this->define_plugin_properties();
      $this->load_classes();
    }

    public function define_plugin_properties()
    {

      define('ADFLIPR_VERSION', '1.0.0');
      define('ADFLIPR_SLUG', 'adflipr');
      define('ADFLIPR_PLUGIN_FILE', __FILE__);
      define('ADFLIPR_PLUGIN_DIR', __DIR__);
      define('ADFLIPR_PLUGIN_URL', untrailingslashit(plugin_dir_url(ADFLIPR_PLUGIN_FILE)));
      define('ADFLIPR_PLUGIN_BASENAME', plugin_basename(__FILE__));
      add_action('plugins_loaded', array($this, 'register_classes'), 1);
      add_action('admin_notices', 'adflipr_maybe_requirements_notice');
    }

    public function load_classes()
    {
      require __DIR__ . '/includes/class-adflipr-auth.php';
      require __DIR__ . '/includes/class-adflipr-data.php';
      require __DIR__ . '/includes/class-adflipr-admin-page.php';
      require __DIR__ . '/includes/class-adflipr-transactional-email.php';
      require __DIR__ . '/includes/class-adflipr-cart-tracker.php';
    }

    /**
     * @return AdFlipr_Core|null
     */
    public static function get_instance()
    {
      if (null === self::$_instance) {
        self::$_instance = new self;
      }

      return self::$_instance;
    }

    /**
     * Register classes
     */
    public function register_classes()
    {

      $load_classes = self::get_registered_class();
      if (is_array($load_classes) && count($load_classes) > 0) {
        foreach ($load_classes as $access_key => $class) {
          $this->$access_key = $class::get_instance();
        }
      }
    }

    /**
     * @return mixed
     */
    public static function get_registered_class()
    {
      return self::$_registered_entity['active'];
    }

    public static function register($short_name, $class)
    {

      self::$_registered_entity['active'][$short_name] = $class;
    }
  }
}
if (! function_exists('AdFlipr_Core')) {
  /**
   * @return AdFlipr_Core|null
   */
  function AdFlipr_Core()
  {
    return AdFlipr_Core::get_instance();
  }
}

$GLOBALS['AdFlipr_Core'] = AdFlipr_Core();

/**
 * Schedule periodic drain of the outbound WooCommerce webhook retry queue.
 */
function adflipr_schedule_webhook_retry_cron()
{
  if (! defined('ADFLIPR_WEBHOOK_RETRY_CRON_HOOK')) {
    return;
  }
  if (! wp_next_scheduled(ADFLIPR_WEBHOOK_RETRY_CRON_HOOK)) {
    wp_schedule_event(time() + 120, 'adflipr_five_minutes', ADFLIPR_WEBHOOK_RETRY_CRON_HOOK);
  }
}

/**
 * Plugin activation: requirements check, then webhook retry cron.
 */
function adflipr_on_activate()
{
  if (! adflipr_meets_requirements()) {
    if (! function_exists('deactivate_plugins')) {
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    deactivate_plugins(ADFLIPR_PLUGIN_BASENAME);
    wp_die(
      wp_kses_post('<div class="wrap">' . adflipr_requirements_fail_html() . '</div>'),
      esc_html__('Plugin activation blocked', 'adflipr'),
      array(
        'response' => 200,
        'back_link' => true,
      )
    );
  }

  adflipr_schedule_webhook_retry_cron();
}

/**
 * Plugin deactivation: clear webhook retry cron (queue option is retained).
 */
function adflipr_on_deactivate()
{
  if (defined('ADFLIPR_WEBHOOK_RETRY_CRON_HOOK')) {
    wp_clear_scheduled_hook(ADFLIPR_WEBHOOK_RETRY_CRON_HOOK);
  }
}

register_activation_hook(ADFLIPR_PLUGIN_FILE, 'adflipr_on_activate');
register_deactivation_hook(ADFLIPR_PLUGIN_FILE, 'adflipr_on_deactivate');
