<?php
/**
 * WooCommerce & Store API hooks: reconciliation when PHP runs (blocks often skip classic checkout hooks).
 *
 * @package AdfliprCartTracker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Adflipr_Tracker_Hooks
 */
class Adflipr_Tracker_Hooks {

	/**
	 * Register all hooks.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'woocommerce_add_to_cart', array( __CLASS__, 'on_add_to_cart' ), 25, 6 );

		add_action( 'woocommerce_cart_loaded_from_session', array( __CLASS__, 'on_cart_loaded_from_session' ), 25 );

		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'on_checkout_create_order' ), 10, 2 );

		add_action( 'woocommerce_store_api_checkout_update_customer_from_request', array( __CLASS__, 'on_store_api_customer_update' ), 25, 2 );

		add_action( 'woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'on_store_api_checkout_order_processed' ), 25, 1 );

		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'on_order_status_changed' ), 25, 4 );
	}

	/**
	 * Cart initiated — first line added.
	 *
	 * @param string $cart_item_key Key.
	 * @param int    $product_id    Product id.
	 * @param int    $quantity      Qty.
	 * @param int    $variation_id  Variation.
	 * @param array  $variation     Variation data.
	 * @param array  $cart_item_data Extra.
	 * @return void
	 */
	public static function on_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		unset( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data );
		if ( ! WC()->session ) {
			return;
		}
		$flag = WC()->session->get( 'adflipr_cart_init_logged' );
		if ( $flag ) {
			return;
		}
		WC()->session->set( 'adflipr_cart_init_logged', '1' );

		Adflipr_Tracker_Logger::log(
			'CART INITIATED',
			'info',
			array(
				'customer_id' => WC()->session->get_customer_id(),
			)
		);
	}

	/**
	 * Session restored — catch carts hydrated without firing add_to_cart (e.g. session resume).
	 *
	 * @param \WC_Cart $cart Cart.
	 * @return void
	 */
	public static function on_cart_loaded_from_session( $cart ) {
		if ( ! $cart || $cart->is_empty() || ! WC()->session ) {
			return;
		}
		$flag = WC()->session->get( 'adflipr_cart_init_logged' );
		if ( $flag ) {
			return;
		}
		WC()->session->set( 'adflipr_cart_init_logged', '1' );

		Adflipr_Tracker_Logger::log(
			'CART INITIATED',
			'info',
			array(
				'context'     => 'loaded_from_session',
				'customer_id' => WC()->session->get_customer_id(),
			)
		);
	}

	/**
	 * Classic checkout: order object created from posted data.
	 *
	 * @param \WC_Order $order Order.
	 * @param array     $data  Posted data.
	 * @return void
	 */
	public static function on_checkout_create_order( $order, $data ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}
		unset( $data );

		$session_id = '';
		if ( WC()->session ) {
			$session_id = (string) WC()->session->get_customer_id();
		}

		if ( $session_id ) {
			$order->update_meta_data( Adflipr_Tracker_Storage::META_SESSION_ID, sanitize_text_field( $session_id ) );
		}
		Adflipr_Tracker_Storage::touch_order_activity( $order );
		Adflipr_Tracker_Storage::set_conversion_status( $order, 'classic_checkout_order_created' );
	}

	/**
	 * Store API: customer updated from React checkout payloads.
	 *
	 * @param \WC_Customer     $customer Customer.
	 * @param \WP_REST_Request $request  Request.
	 * @return void
	 */
	public static function on_store_api_customer_update( $customer, $request ) {
		unset( $request );

		if ( ! WC()->session ) {
			return;
		}

		$draft_id = absint( WC()->session->get( 'store_api_draft_order', 0 ) );
		if ( ! $draft_id ) {
			return;
		}

		$order = wc_get_order( $draft_id );
		if ( ! $order ) {
			return;
		}

		Adflipr_Tracker_Storage::touch_order_activity( $order );

		$email = '';
		if ( $customer instanceof \WC_Customer ) {
			$email = $customer->get_billing_email();
		}
		if ( ! $email ) {
			$email = $order->get_billing_email();
		}

		$log_base = array_filter(
			array(
				'context'   => 'store_api_customer',
				'order_id'  => $draft_id,
				'wc_status' => $order->get_status(),
				'email'     => $email,
			)
		);

		Adflipr_Tracker_Logger::log( 'CHECKOUT UPDATED', 'info', $log_base );

		Adflipr_Tracker_Logger::log(
			'DRAFT ORDER UPDATED',
			'info',
			array_filter(
				array(
					'order_id'  => $draft_id,
					'wc_status' => 'wc-checkout-draft',
					'email'     => $email,
				)
			)
		);
	}

	/**
	 * Store API: order processed (typically pending — payment may follow).
	 *
	 * @param \WC_Order $order Order.
	 * @return void
	 */
	public static function on_store_api_checkout_order_processed( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		Adflipr_Tracker_Storage::touch_order_activity( $order );
		Adflipr_Tracker_Storage::set_conversion_status( $order, 'store_api_checkout_processed' );

		Adflipr_Tracker_Logger::log(
			'CHECKOUT UPDATED',
			'info',
			array_filter(
				array(
					'context'    => 'store_api_order_processed',
					'order_id'   => $order->get_id(),
					'new_status' => $order->get_status(),
					'email'      => $order->get_billing_email(),
				)
			)
		);
	}

	/**
	 * Track conversion vs abandonment lifecycle by status.
	 *
	 * @param int       $order_id Order id.
	 * @param string    $from     From status.
	 * @param string    $to       To status.
	 * @param \WC_Order $order    Order object.
	 * @return void
	 */
	public static function on_order_status_changed( $order_id, $from, $to, $order ) {
		unset( $order_id, $from );
		if ( ! $order instanceof \WC_Order ) {
			return;
		}

		if ( in_array( $to, array( 'processing', 'completed' ), true ) ) {
			Adflipr_Tracker_Logger::log(
				'ORDER COMPLETED',
				'info',
				array_filter(
					array(
						'order_id'   => $order->get_id(),
						'status'     => $to,
						'conversion' => 'paid_or_fulfilled',
						'email'      => $order->get_billing_email(),
					)
				)
			);
			Adflipr_Tracker_Storage::set_conversion_status( $order, 'converted_' . $to );
			return;
		}

		if ( 'failed' === $to ) {
			Adflipr_Tracker_Storage::set_conversion_status( $order, 'payment_failed' );
		}
	}

}
