<?php
/**
 * WP-Cron: scan stale draft / pending / failed orders for abandonment.
 *
 * @package AdfliprCartTracker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Adflipr_Tracker_Cron
 */
class Adflipr_Tracker_Cron {

	public const HOOK     = 'adflipr_tracker_abandonment_scan';
	public const SCHEDULE = 'adflipr_every_five_minutes';

	/**
	 * Default inactivity threshold (seconds).
	 *
	 * @return int
	 */
	public static function get_inactivity_seconds() {
		$minutes = (int) apply_filters( 'adflipr_tracker_abandonment_minutes', 15 );
		return max( 60, $minutes * MINUTE_IN_SECONDS );
	}

	/**
	 * Register custom schedule + hook.
	 *
	 * @return void
	 */
	public static function register() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_schedule' ) );
		add_action( self::HOOK, array( __CLASS__, 'run_abandonment_scan' ) );
		add_action(
			'init',
			static function () {
				if ( ! wp_next_scheduled( self::HOOK ) ) {
					self::activate();
				}
			},
			20
		);
	}

	/**
	 * @param array $schedules Schedules.
	 * @return array
	 */
	public static function add_schedule( $schedules ) {
		if ( ! isset( $schedules[ self::SCHEDULE ] ) ) {
			$schedules[ self::SCHEDULE ] = array(
				'interval' => 5 * MINUTE_IN_SECONDS,
				'display'  => __( 'Every 5 minutes (Adflipr Tracker)', 'adflipr-cart-tracker' ),
			);
		}
		return $schedules;
	}

	/**
	 * On plugin activation.
	 *
	 * @return void
	 */
	public static function activate() {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_schedule' ) );
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + 60, self::SCHEDULE, self::HOOK );
		}
	}

	/**
	 * On plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate() {
		$ts = wp_next_scheduled( self::HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::HOOK );
		}
	}

	/**
	 * Find stale actionable orders and log abandonment once.
	 *
	 * @return void
	 */
	public static function run_abandonment_scan() {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return;
		}

		$threshold = time() - self::get_inactivity_seconds();

		$orders = wc_get_orders(
			array(
				'limit'  => (int) apply_filters( 'adflipr_tracker_abandonment_batch_size', 80 ),
				'status' => array( 'checkout-draft', 'pending', 'failed' ),
				'return' => 'objects',
			)
		);

		foreach ( $orders as $order ) {
			if ( ! $order instanceof \WC_Order ) {
				continue;
			}

			$wc_status = $order->get_status();
			// Guard: never flag processing/completed (query already excludes, but keep explicit).
			if ( in_array( $wc_status, array( 'processing', 'completed' ), true ) ) {
				continue;
			}

			$last = (int) $order->get_meta( Adflipr_Tracker_Storage::META_LAST_ACTIVITY );
			if ( ! $last && $order->get_date_modified() ) {
				$last = $order->get_date_modified()->getTimestamp();
			}

			if ( $last >= $threshold ) {
				continue;
			}

			if ( $order->get_meta( Adflipr_Tracker_Storage::META_ABANDON_LOGGED ) ) {
				continue;
			}

			$order->update_meta_data( Adflipr_Tracker_Storage::META_ABANDON_LOGGED, '1' );
			$order->update_meta_data( Adflipr_Tracker_Storage::META_CONVERSION_STATUS, 'abandoned' );
			$order->save();

			$email = $order->get_billing_email();
			if ( ! $email ) {
				$snapshot = $order->get_meta( Adflipr_Tracker_Storage::META_SNAPSHOT );
				if ( is_string( $snapshot ) && $snapshot ) {
					$decoded = json_decode( $snapshot, true );
					if ( is_array( $decoded ) ) {
						if ( ! empty( $decoded['email'] ) ) {
							$email = sanitize_email( $decoded['email'] );
						} elseif ( ! empty( $decoded['billing_address']['email'] ) ) {
							$email = sanitize_email( $decoded['billing_address']['email'] );
						}
					}
				}
			}

			Adflipr_Tracker_Logger::log(
				'ABANDONED CART DETECTED',
				'notice',
				array_filter(
					array(
						'order_id'      => $order->get_id(),
						'wc_status'     => $wc_status,
						'inactive_secs' => time() - $last,
						'email'         => $email,
					)
				)
			);
		}
	}
}
