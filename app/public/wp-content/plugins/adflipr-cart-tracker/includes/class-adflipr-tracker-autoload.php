<?php
/**
 * Minimal PSR-4-style autoloader for plugin classes.
 *
 * @package AdfliprCartTracker
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Adflipr_Tracker_Autoload
 */
class Adflipr_Tracker_Autoload {

	/**
	 * Register autoloader.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Load class file.
	 *
	 * @param string $class Class name.
	 * @return void
	 */
	public static function load( $class ) {
		if ( strpos( $class, 'Adflipr_Tracker_' ) !== 0 ) {
			return;
		}
		$relative = strtolower( str_replace( '_', '-', $class ) );
		$file     = ADFLIPR_TRACKER_PATH . 'includes/class-' . $relative . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
