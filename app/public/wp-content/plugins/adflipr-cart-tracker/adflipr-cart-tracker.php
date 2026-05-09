<?php
/**
 * Plugin Name: Adflipr Cart Tracker
 * Description: Modern abandoned-cart and checkout tracking for WooCommerce 6+, HPOS, and Checkout Blocks (Store API + frontend sync).
 * Version: 1.0.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Adflipr
 * Text Domain: adflipr-cart-tracker
 * WC requires at least: 6.0
 * WC tested up to: 10.0
 *
 * @package AdfliprCartTracker
 */

defined( 'ABSPATH' ) || exit;

define( 'ADFLIPR_TRACKER_VERSION', '1.0.1' );
define( 'ADFLIPR_TRACKER_FILE', __FILE__ );
define( 'ADFLIPR_TRACKER_PATH', plugin_dir_path( __FILE__ ) );
define( 'ADFLIPR_TRACKER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Bootstrap after plugins_loaded so WooCommerce can load first.
 */
add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( '\WooCommerce' ) ) {
			return;
		}

		require_once ADFLIPR_TRACKER_PATH . 'includes/class-adflipr-tracker-autoload.php';
		Adflipr_Tracker_Autoload::register();

		\Adflipr_Tracker_Plugin::instance()->init();
	},
	5
);

/**
 * Declare compatibility with HPOS and Checkout Blocks feature flags.
 */
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', ADFLIPR_TRACKER_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', ADFLIPR_TRACKER_FILE, true );
		}
	}
);

register_activation_hook(
	ADFLIPR_TRACKER_FILE,
	static function () {
		if ( ! class_exists( '\WooCommerce' ) ) {
			return;
		}
		if ( ! class_exists( '\Adflipr_Tracker_Cron' ) ) {
			require_once ADFLIPR_TRACKER_PATH . 'includes/class-adflipr-tracker-autoload.php';
			Adflipr_Tracker_Autoload::register();
		}
		\Adflipr_Tracker_Cron::activate();
	}
);

register_deactivation_hook(
	ADFLIPR_TRACKER_FILE,
	static function () {
		if ( ! class_exists( '\Adflipr_Tracker_Cron' ) ) {
			require_once ADFLIPR_TRACKER_PATH . 'includes/class-adflipr-tracker-autoload.php';
			Adflipr_Tracker_Autoload::register();
		}
		\Adflipr_Tracker_Cron::deactivate();
	}
);
