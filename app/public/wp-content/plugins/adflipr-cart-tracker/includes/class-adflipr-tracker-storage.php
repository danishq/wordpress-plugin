<?php
/**
 * Persists tracker payloads (transients + order meta). HPOS-safe via WC_Order only.
 *
 * @package AdfliprCartTracker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Adflipr_Tracker_Storage
 */
class Adflipr_Tracker_Storage {

	public const META_LAST_ACTIVITY       = '_adflipr_last_activity';
	public const META_SESSION_ID        = '_adflipr_session_id';
	public const META_SNAPSHOT          = '_adflipr_tracking_snapshot';
	public const META_CONVERSION_STATUS  = '_adflipr_conversion_status';
	public const META_ABANDON_LOGGED    = '_adflipr_abandonment_logged';
	public const META_DRAFT_STATUS_NOTE = '_adflipr_draft_status_tracked';

	public const TRANSIENT_PREFIX = 'adflipr_trk_';

	/**
	 * Default transient TTL (extend on each sync).
	 */
	public const TRANSIENT_TTL = WEEK_IN_SECONDS;

	/**
	 * Get transient key for session bridge data (pre-draft-order phase).
	 *
	 * @param string $session_id Client session id.
	 * @return string
	 */
	public static function transient_key( $session_id ) {
		return self::TRANSIENT_PREFIX . md5( $session_id );
	}

	/**
	 * Save payload snapshot to transient and optionally attach to draft order.
	 *
	 * @param string $session_id Client session id.
	 * @param array  $data       Sanitized payload.
	 * @return void
	 */
	public static function save_session_snapshot( $session_id, array $data ) {
		$key = self::transient_key( $session_id );
		set_transient(
			$key,
			array(
				'updated_at' => time(),
				'data'       => $data,
			),
			self::TRANSIENT_TTL
		);

		$draft_id = isset( $data['draft_order_id'] ) ? absint( $data['draft_order_id'] ) : 0;
		if ( $draft_id ) {
			self::attach_snapshot_to_order( $draft_id, $session_id, $data );
		}
	}

	/**
	 * Merge snapshot into a WC_Order (draft or otherwise).
	 *
	 * @param int    $order_id   Order ID.
	 * @param string $session_id Session id.
	 * @param array  $data       Sanitized data.
	 * @return void
	 */
	public static function attach_snapshot_to_order( $order_id, $session_id, array $data ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$order->update_meta_data( self::META_SESSION_ID, sanitize_text_field( $session_id ) );
		$order->update_meta_data( self::META_LAST_ACTIVITY, time() );
		$order->update_meta_data(
			self::META_SNAPSHOT,
			wp_json_encode( $data )
		);

		$status = $order->get_status();
		if ( 'checkout-draft' === $status ) {
			$order->update_meta_data( self::META_DRAFT_STATUS_NOTE, 'wc-checkout-draft' );
		}

		if ( ! $order->get_meta( self::META_CONVERSION_STATUS ) ) {
			$order->update_meta_data( self::META_CONVERSION_STATUS, 'checkout_in_progress' );
		}

		$order->save();
	}

	/**
	 * Touch activity timestamp on order (used by Store API / cron).
	 *
	 * @param \WC_Order $order Order.
	 * @return void
	 */
	public static function touch_order_activity( \WC_Order $order ) {
		$order->update_meta_data( self::META_LAST_ACTIVITY, time() );
		$order->save();
	}

	/**
	 * Set conversion status when order reaches a terminal or progress state.
	 *
	 * @param \WC_Order $order  Order.
	 * @param string    $status Internal label.
	 * @return void
	 */
	public static function set_conversion_status( \WC_Order $order, $status ) {
		$order->update_meta_data( self::META_CONVERSION_STATUS, sanitize_key( $status ) );
		$order->save();
	}
}
