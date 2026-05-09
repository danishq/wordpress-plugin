<?php
/**
 * Structured logging via WooCommerce logger.
 *
 * @package AdfliprCartTracker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Adflipr_Tracker_Logger
 */
class Adflipr_Tracker_Logger {

	public const SOURCE = 'adflipr-tracker';

	/**
	 * Log a message.
	 *
	 * @param string $message Message.
	 * @param string $level   WC log level: emergency|alert|critical|error|warning|notice|info|debug.
	 * @param array  $context Extra context (merged into log record).
	 * @return void
	 */
	public static function log( $message, $level = 'info', $context = array() ) {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}
		$logger = wc_get_logger();
		$logger->log(
			$level,
			$message,
			array(
				'source' => self::SOURCE,
			) + $context
		);
	}
}
