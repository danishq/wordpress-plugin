<?php
/**
 * Main plugin orchestrator: hooks, REST, assets.
 *
 * @package AdfliprCartTracker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Adflipr_Tracker_Plugin
 */
class Adflipr_Tracker_Plugin {

	/**
	 * Singleton.
	 *
	 * @var self|null
	 */
	protected static $instance = null;

	/**
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Wire WordPress + WooCommerce integration.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		add_action(
			'rest_api_init',
			static function () {
				Adflipr_Tracker_Rest::register();
			}
		);

		add_action(
			'woocommerce_init',
			static function () {
				Adflipr_Tracker_Hooks::register();
			}
		);

		Adflipr_Tracker_Cron::register();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ), 30 );
	}

	/**
	 * i18n.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'adflipr-cart-tracker', false, dirname( plugin_basename( ADFLIPR_TRACKER_FILE ) ) . '/languages' );
	}

	/**
	 * Checkout Blocks require JS-side capture; script only loads on cart/checkout.
	 *
	 * @return void
	 */
	public function enqueue_frontend() {
		if ( is_admin() || ! function_exists( 'is_cart' ) ) {
			return;
		}

		if ( ! is_cart() && ! is_checkout() ) {
			return;
		}

		$handle = 'adflipr-checkout-sync';
		$src    = ADFLIPR_TRACKER_URL . 'assets/js/checkout-sync.js';

		wp_enqueue_script(
			$handle,
			$src,
			array(),
			ADFLIPR_TRACKER_VERSION,
			true
		);

		wp_localize_script(
			$handle,
			'adfliprTracker',
			array(
				'restUrl'       => esc_url_raw( rest_url( Adflipr_Tracker_Rest::NS . '/checkout-sync' ) ),
				'storeCartUrl' => esc_url_raw( rest_url( 'wc/store/v1/cart' ) ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'debounceMs'    => (int) apply_filters( 'adflipr_tracker_frontend_debounce_ms', 1000 ),
				'sessionKey'    => 'adflipr_tracker_sid',
				'sourceCart'    => 'cart_page_frontend',
				'sourceCheckout' => 'checkout_block_frontend',
			)
		);
	}
}
