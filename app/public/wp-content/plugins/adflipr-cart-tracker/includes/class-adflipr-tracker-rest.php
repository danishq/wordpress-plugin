<?php
/**
 * REST API: /adflipr/v1/checkout-sync
 *
 * Receives debounced frontend payloads and reconciles with WC session + draft orders.
 *
 * @package AdfliprCartTracker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Adflipr_Tracker_Rest
 */
class Adflipr_Tracker_Rest {

	/**
	 * REST namespace and route.
	 */
	public const NS    = 'adflipr/v1';
	public const ROUTE = '/checkout-sync';

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public static function register() {
		register_rest_route(
			self::NS,
			self::ROUTE,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'handle_sync' ),
				'permission_callback' => array( __CLASS__, 'permission' ),
				'args'                => array(),
			)
		);
	}

	/**
	 * Allow guests with cart session and logged-in shoppers; always require REST nonce.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool
	 */
	public static function permission( \WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return false;
		}

		if ( ! function_exists( 'WC' ) ) {
			return false;
		}

		if ( function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		if ( ! WC()->cart ) {
			return false;
		}

		// Ensure session exists for anonymous carts (Store API / frontend sync).
		if ( WC()->session && ! WC()->session->has_session() && WC()->cart->get_cart_contents_count() > 0 ) {
			WC()->session->set_customer_session_cookie( true );
		}

		return true;
	}

	/**
	 * Sanitize and persist checkout sync payload.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public static function handle_sync( \WP_REST_Request $request ) {
		if ( function_exists( 'wc_load_cart' ) ) {
			wc_load_cart();
		}

		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			$params = array();
		}

		$session_id = isset( $params['session_id'] ) ? sanitize_text_field( wp_unslash( $params['session_id'] ) ) : '';
		if ( strlen( $session_id ) < 8 ) {
			return new \WP_Error(
				'adflipr_invalid_session',
				__( 'Invalid session_id.', 'adflipr-cart-tracker' ),
				array( 'status' => 400 )
			);
		}

		$email = isset( $params['email'] ) ? sanitize_email( wp_unslash( $params['email'] ) ) : '';

		$sanitized = array(
			'session_id'      => $session_id,
			'email'           => $email,
			'first_name'      => isset( $params['first_name'] ) ? sanitize_text_field( wp_unslash( $params['first_name'] ) ) : '',
			'last_name'       => isset( $params['last_name'] ) ? sanitize_text_field( wp_unslash( $params['last_name'] ) ) : '',
			'billing_address' => self::sanitize_address( isset( $params['billing_address'] ) ? $params['billing_address'] : array() ),
			// Flat fields from example payload / legacy.
			'billing_city'    => isset( $params['billing_city'] ) ? sanitize_text_field( wp_unslash( $params['billing_city'] ) ) : '',
			'cart_items'      => self::sanitize_cart_items( isset( $params['cart_items'] ) ? $params['cart_items'] : array() ),
			'subtotal'        => self::sanitize_amount( $params['subtotal'] ?? null ),
			'cart_subtotal'   => self::sanitize_amount( $params['cart_subtotal'] ?? null ),
			'total'           => self::sanitize_amount( $params['total'] ?? null ),
			'source'          => isset( $params['source'] ) ? sanitize_key( $params['source'] ) : 'frontend',
			'draft_order_id'  => isset( $params['draft_order_id'] ) ? absint( $params['draft_order_id'] ) : 0,
			'event'           => isset( $params['event'] ) ? sanitize_key( $params['event'] ) : 'checkout_updated',
		);

		// Merge flat billing_city into billing_address if empty.
		if ( empty( $sanitized['billing_address']['city'] ) && $sanitized['billing_city'] ) {
			$sanitized['billing_address']['city'] = $sanitized['billing_city'];
		}

		// Single canonical email for persistence + logs (top-level or nested billing email).
		if ( empty( $sanitized['email'] ) && ! empty( $sanitized['billing_address']['email'] ) ) {
			$sanitized['email'] = $sanitized['billing_address']['email'];
		}
		if ( ! empty( $sanitized['email'] ) && empty( $sanitized['billing_address']['email'] ) ) {
			$sanitized['billing_address']['email'] = $sanitized['email'];
		}

		$server_draft = 0;
		if ( WC()->session ) {
			$server_draft = absint( WC()->session->get( 'store_api_draft_order', 0 ) );
		}
		if ( $server_draft && ! $sanitized['draft_order_id'] ) {
			$sanitized['draft_order_id'] = $server_draft;
		}

		// Reconcile line items / totals from live cart when possible (authoritative for PHP context).
		$sanitized['cart_reconciled'] = self::build_cart_reconciliation();

		Adflipr_Tracker_Storage::save_session_snapshot( $session_id, $sanitized );

		$checkout_started = ( $sanitized['email'] || self::billing_populated( $sanitized['billing_address'] ) );

		$log_email = self::resolve_email_for_log( $sanitized );

		if ( $checkout_started ) {
			$start_flag_key = 'adflipr_chk_start_' . md5( $session_id );
			if ( ! get_transient( $start_flag_key ) ) {
				set_transient( $start_flag_key, '1', WEEK_IN_SECONDS );
				Adflipr_Tracker_Logger::log(
					'CHECKOUT STARTED',
					'info',
					array_filter(
						array(
							'session_id' => $session_id,
							'source'     => $sanitized['source'],
							'email'      => $log_email,
						)
					)
				);
			}

			Adflipr_Tracker_Logger::log(
				'CHECKOUT UPDATED',
				'info',
				array_filter(
					array(
						'session_id'     => $session_id,
						'draft_order_id' => $sanitized['draft_order_id'],
						'source'         => $sanitized['source'],
						'email'          => $log_email,
					)
				)
			);
		}

		if ( $sanitized['draft_order_id'] ) {
			Adflipr_Tracker_Logger::log(
				'DRAFT ORDER UPDATED',
				'info',
				array_filter(
					array(
						'order_id'   => $sanitized['draft_order_id'],
						'session_id' => $session_id,
						'email'      => $log_email,
					)
				)
			);
		}

		return new \WP_REST_Response(
			array(
				'success'          => true,
				'session_id'       => $session_id,
				'draft_order_id'   => $sanitized['draft_order_id'],
				'server_draft_id'  => $server_draft,
				'wc_session_key'   => WC()->session ? WC()->session->get_customer_id() : '',
				'checkout_started' => $checkout_started,
			),
			200
		);
	}

	/**
	 * @param array|null $addr Address.
	 * @return array
	 */
	private static function sanitize_address( $addr ) {
		if ( ! is_array( $addr ) ) {
			$addr = array();
		}
		$keys = array(
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'country',
			'phone',
			'email',
		);
		$out = array();
		foreach ( $keys as $key ) {
			if ( isset( $addr[ $key ] ) ) {
				$val = wp_unslash( $addr[ $key ] );
				$out[ $key ] = 'email' === $key ? sanitize_email( $val ) : sanitize_text_field( $val );
			}
		}
		return $out;
	}

	/**
	 * @param mixed $items Items.
	 * @return array
	 */
	private static function sanitize_cart_items( $items ) {
		if ( ! is_array( $items ) ) {
			return array();
		}
		$out = array();
		foreach ( $items as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$out[] = array(
				'product_id'   => isset( $row['product_id'] ) ? absint( $row['product_id'] ) : 0,
				'variation_id' => isset( $row['variation_id'] ) ? absint( $row['variation_id'] ) : 0,
				'quantity'     => isset( $row['quantity'] ) ? (float) wc_stock_amount( $row['quantity'] ) : 0,
				'subtotal'     => self::sanitize_amount( $row['subtotal'] ?? null ),
				'total'        => self::sanitize_amount( $row['total'] ?? null ),
			);
		}
		return $out;
	}

	/**
	 * @param mixed $value Value.
	 * @return float
	 */
	private static function sanitize_amount( $value ) {
		if ( null === $value || '' === $value ) {
			return 0.0;
		}
		return (float) wc_format_decimal( $value, wc_get_price_decimals() );
	}

	/**
	 * @param array $billing_address Billing.
	 * @return bool
	 */
	private static function billing_populated( array $billing_address ) {
		foreach ( array( 'first_name', 'last_name', 'address_1', 'city', 'postcode', 'country' ) as $k ) {
			if ( ! empty( $billing_address[ $k ] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Best-known customer email for log context (payload, session customer, order).
	 *
	 * @param array $sanitized Sanitized REST payload.
	 * @return string
	 */
	private static function resolve_email_for_log( array $sanitized ) {
		if ( ! empty( $sanitized['email'] ) ) {
			return $sanitized['email'];
		}
		if ( ! empty( $sanitized['billing_address']['email'] ) ) {
			return $sanitized['billing_address']['email'];
		}
		if ( function_exists( 'WC' ) && WC()->customer ) {
			$session_email = WC()->customer->get_billing_email();
			if ( $session_email ) {
				return $session_email;
			}
		}
		$draft_id = isset( $sanitized['draft_order_id'] ) ? absint( $sanitized['draft_order_id'] ) : 0;
		if ( $draft_id ) {
			$order = wc_get_order( $draft_id );
			if ( $order ) {
				return $order->get_billing_email();
			}
		}
		return '';
	}

	/**
	 * Mirror WC()->cart into structured meta for reconciliation logging.
	 *
	 * @return array
	 */
	private static function build_cart_reconciliation() {
		if ( ! WC()->cart ) {
			return array();
		}
		$items = array();
		foreach ( WC()->cart->get_cart() as $key => $line ) {
			$product = isset( $line['data'] ) ? $line['data'] : null;
			$items[] = array(
				'product_id'   => isset( $line['product_id'] ) ? absint( $line['product_id'] ) : 0,
				'variation_id' => isset( $line['variation_id'] ) ? absint( $line['variation_id'] ) : 0,
				'quantity'     => isset( $line['quantity'] ) ? (float) $line['quantity'] : 0,
				'subtotal'     => isset( $line['line_subtotal'] ) ? (float) $line['line_subtotal'] : 0,
				'total'        => isset( $line['line_total'] ) ? (float) $line['line_total'] : 0,
				'name'         => $product && is_a( $product, '\WC_Product' ) ? $product->get_name() : '',
			);
		}

		return array(
			'items'          => $items,
			'subtotal'       => (float) WC()->cart->get_subtotal(),
			'discount_total' => (float) WC()->cart->get_discount_total(),
			'shipping_total' => (float) WC()->cart->get_shipping_total(),
			'total'          => (float) WC()->cart->get_total( 'edit' ),
			'tax_total'      => (float) WC()->cart->get_total_tax(),
		);
	}
}
