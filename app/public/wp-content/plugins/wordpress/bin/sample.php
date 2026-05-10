<?php
/**
 * Plugin Name: FunnelKit Checkout
 * Plugin URI: https://funnelkit.com
 * Description: FunnelKit Checkout lets you build highly optimized checkout page. Choose from list of growing templates to create dedicated order pages or swap your native checkout with conversion friendly checkout template.
 * Version: {{{VERSION}}}
 * Author: FunnelKit
 * Author URI: https://funnelkit.com
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: woofunnels-aero-checkout
 * Domain Path: /languages/
 *
 * Requires at least: 4.9.0
 * Tested up to: 6.8.3
 * WC requires at least: 3.6.0
 * WC tested up to: 10.3.0
 * WooFunnels: true
 *
 * FunnelKit Checkout is free software.
 * You can redistribute it and/or modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * FunnelKit Checkout is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with FunnelKit Checkout. If not, see <http://www.gnu.org/licenses/>.
 */

defined( 'ABSPATH' ) || exit;
if ( ! class_exists( 'WFACP_Core' ) ):
	#[AllowDynamicProperties]
	final class WFACP_Core {

		private static $ins = null;
		private static $_registered_entity = [];
		public $is_dependency_exists = true;
		private $dir = '';

		private $url = '';
		/**
		 * @var WFACP_Template_loader
		 */
		public $template_loader;

		/**
		 * @var WFACP_public
		 */
		public $public;

		/**
		 * @var WFACP_Customizer
		 */
		public $customizer;

		/**
		 * @var WFACP_WooFunnels_Support
		 */
		public $support;

		/**
		 * @var WFACP_Template_Importer
		 */
		public $importer;

		/**
		 * @var WFACP_Embed_Form_loader
		 */
		public $embed_forms;
		/**
		 * @var WFACP_Order_pay
		 */
		public $pay;
		/**
		 * @var WFACP_Reporting
		 */
		public $reporting;

		/**
		 * @var WFACP_Role_Capability
		 */
		public $role;

		/**
		 * Using protected method no one create new instance this class
		 * WFACP_Core constructor.
		 */
		protected function __construct() {

			$this->definition();
			$this->do_dependency_check();
			/**
			 * Initiates and loads WooFunnels start file
			 */
			if ( true === $this->is_dependency_exists ) {

				if ( true === apply_filters( 'wfacp_should_load_core', true ) ) {
					$this->load_core_classes();
				}
				/**
				 * Loads common file
				 */
				$this->load_commons();
			}
		}

		private function definition() {

			define( 'WFACP_VERSION', '{{{VERSION}}}' );
			define( 'WFACP_BWF_VERSION', '1.10.12.69' );

			define( 'WFACP_MIN_WP_VERSION', '4.9' );
			define( 'WFACP_MIN_WC_VERSION', '3.3' );
			define( 'WFACP_SLUG', 'wfacp' );
			define( 'WFACP_TEXTDOMAIN', 'woofunnels-aero-checkout' );
			define( 'WFACP_FULL_NAME', 'FunnelKit Checkout' );
			define( 'WFACP_PLUGIN_FILE', __FILE__ );
			define( 'WFACP_PLUGIN_DIR', __DIR__ );
			define( 'WFACP_TEMPLATE_COMMON', plugin_dir_path( WFACP_PLUGIN_FILE ) . 'public/template-common' );
			define( 'WFACP_BUILDER_DIR', plugin_dir_path( WFACP_PLUGIN_FILE ) . 'builder' );
			define( 'WFACP_TEMPLATE_DIR', plugin_dir_path( WFACP_PLUGIN_FILE ) . 'public/templates' );
			define( 'WFACP_PLUGIN_URL', untrailingslashit( plugin_dir_url( WFACP_PLUGIN_FILE ) ) );
			define( 'WFACP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			( defined( 'WFACP_IS_DEV' ) && true === WFACP_IS_DEV ) ? define( 'WFACP_VERSION_DEV', time() ) : define( 'WFACP_VERSION_DEV', WFACP_VERSION );

			$this->dir = plugin_dir_path( __FILE__ );
			$this->url = untrailingslashit( plugin_dir_url( __FILE__ ) );
		}

		private function do_dependency_check() {
			include_once WFACP_PLUGIN_DIR . '/woo-includes/woo-functions.php';
			if ( ! wfacp_is_woocommerce_active() ) {
				add_action( 'admin_notices', array( $this, 'wc_not_installed_notice' ) );
				$this->is_dependency_exists = false;

				add_action( 'activated_plugin', array( $this, 'maybe_flush_permalink' ) );
			}
		}

		private function load_core_classes() {
			/** Setting Up WooFunnels Core */
			require_once( 'start.php' );
		}

		private function load_commons() {
			require WFACP_PLUGIN_DIR . '/includes/functions.php';
			require WFACP_PLUGIN_DIR . '/includes/class-wfacp-common-helper.php';
			require WFACP_PLUGIN_DIR . '/includes/class-wfacp-common.php';
			require WFACP_PLUGIN_DIR . '/includes/class-wfacp-optimizations.php';
			require WFACP_PLUGIN_DIR . '/includes/class-wfacp-support.php';
			require WFACP_PLUGIN_DIR . '/includes/class-compatibilities.php';


			require WFACP_PLUGIN_DIR . '/includes/class-wfacp-ajax-controller.php';
			// Include REST API
			require WFACP_PLUGIN_DIR . '/admin/rest-api/class-wfacp-rest-funnels.php';

			$this->importer_files();
			WFACP_Common::init();
			$this->load_hooks();
		}

		private function load_hooks() {
			/**
			 * Initialize Localization
			 */
			add_action( 'init', array( $this, 'localization' ) );
			add_action( 'plugins_loaded', array( $this, 'load_classes' ), 1 );
			add_action( 'plugins_loaded', array( $this, 'register_classes' ), 2 );
			add_action( 'activated_plugin', array( $this, 'redirect_on_activation' ) );
			add_action( 'wfacp_before_loaded', [ $this, 'init_elementor' ] );
			add_action( 'plugins_loaded', array( $this, 'elementor_importer' ), 10 );
			add_action( 'before_woocommerce_init', [ $this, 'declare_hpos_compatibility' ] );

			register_activation_hook( __FILE__, [ $this, 'plugin_activation_hook' ] );
		}

		/**
		 * @return null|WFACP_Core
		 */
		public static function get_instance() {
			if ( is_null( self::$ins ) ) {
				self::$ins = new self();
			}

			return self::$ins;
		}

		public static function register( $short_name, $class ) {

			if ( ! isset( self::$_registered_entity[ $short_name ] ) ) {
				self::$_registered_entity[ $short_name ] = $class;
			}
		}

		public function localization() {
			load_plugin_textdomain( 'woofunnels-aero-checkout', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
		}

		public function load_classes() {

			global $woocommerce;
			global $wp_version;
			if ( ! version_compare( $wp_version, WFACP_MIN_WP_VERSION, '>=' ) ) {
				add_action( 'admin_notices', array( $this, 'wp_version_check_notice' ) );

				return false;
			}
			if ( ! version_compare( $woocommerce->version, WFACP_MIN_WC_VERSION, '>=' ) ) {
				add_action( 'admin_notices', array( $this, 'wc_version_check_notice' ) );

				return false;
			}

			require WFACP_PLUGIN_DIR . '/admin/class-wfacp-admin.php';
			if ( is_admin() ) {
				require WFACP_PLUGIN_DIR . '/admin/includes/class-bwf-admin-settings.php';
				require WFACP_PLUGIN_DIR . '/admin/includes/class-bwf-admin-breadcrumbs.php';
				require WFACP_PLUGIN_DIR . '/admin/class-insert-page.php';
			}

			require WFACP_PLUGIN_DIR . '/admin/class-wfacp-exporter.php';
			require WFACP_PLUGIN_DIR . '/admin/class-wfacp-importer.php';
			require WFACP_PLUGIN_DIR . '/admin/class-wfacp-wizard.php';
			require WFACP_PLUGIN_DIR . '/admin/includes/autonami/class-wfacp-autonami.php';
			require WFACP_PLUGIN_DIR . '/includes/class-dynamic-merge-tags.php';
			require WFACP_PLUGIN_DIR . '/builder/customizer/class-wfacp-customizer.php';
			require WFACP_PLUGIN_DIR . '/includes/class-embed-form-loader.php';
			require WFACP_PLUGIN_DIR . '/includes/class-wfacp-template-loader.php';
			require WFACP_PLUGIN_DIR . '/public/class-wfacp-public.php';
			require WFACP_PLUGIN_DIR . '/includes/class-order-pay.php';
			require WFACP_PLUGIN_DIR . '/includes/class-mobile-detect.php';
			require WFACP_PLUGIN_DIR . '/includes/class-wfacp-reporting.php';
			require WFACP_PLUGIN_DIR . '/includes/class-wfacp-role-capability.php';
		}

		public function register_classes() {
			do_action( 'wfacp_before_loaded' );
			$load_classes = self::get_registered_class();
			if ( is_array( $load_classes ) && count( $load_classes ) > 0 ) {
				foreach ( $load_classes as $access_key => $class ) {
					$this->$access_key = $class::get_instance();
				}

				$this->remove_embed_form();
				do_action( 'wfacp_loaded' );
				$this->load_modules();
			}
		}

		private function load_modules() {
			include WFACP_PLUGIN_DIR . '/modules/login-flow/index.php';
		}

		public static function get_registered_class() {
			return self::$_registered_entity;
		}

		public function redirect_on_activation( $plugin ) {
			if ( ! wfacp_is_woocommerce_active() || ! class_exists( 'WooCommerce' ) ) {
				return;
			}

			if ( $plugin != plugin_basename( __FILE__ ) ) {
				return;
			}

			update_option( 'bwf_needs_rewrite', 'yes', true );

			$g_setting = get_option( '_wfacp_global_settings', [] );
			if ( is_array( $g_setting ) && count( $g_setting ) > 0 ) {
				return;
			}

			update_option( '_wfacp_global_settings', $g_setting );
			wp_redirect( add_query_arg( array(
				'page' => 'wfacp',
			), admin_url( 'admin.php' ) ) );
			exit;
		}


		public function plugin_activation_hook() {
			update_option( 'bwf_needs_rewrite', 'yes', true );
		}

		public function wc_version_check_notice() {
			?>
            <div class="error">
                <p>
					<?php
					/* translators: %1$s: Min required woocommerce version */
					printf( __( '<strong> Attention: </strong>FunnelKit Checkout requires WooCommerce version %1$s or greater. Kindly update the WooCommerce plugin.', 'woofunnels-aero-checkout' ), WFACP_MIN_WC_VERSION );
					?>
                </p>
            </div>
			<?php
		}

		public function wp_version_check_notice() {
			?>
            <div class="error">
                <p>
					<?php
					/* translators: %1$s: Min required woocommerce version */
					printf( __( '<strong> Attention: </strong>FunnelKit Checkout requires WordPress version %1$s or greater. Kindly update the WordPress.', 'woofunnels-aero-checkout' ), WFACP_MIN_WP_VERSION );
					?>
                </p>
            </div>
			<?php
		}


		public function wc_not_installed_notice() {
			?>
            <div class="error">
                <p>
					<?php
					_e( '<strong> Attention: </strong>WooCommerce is not installed or activated. FunnelKit Checkout is a WooCommerce Extension and would only work if WooCommerce is activated. Please install the WooCommerce Plugin first.', 'woofunnels-aero-checkout' );
					?>
                </p>
            </div>
			<?php
		}

		public function maybe_flush_permalink( $plugin ) {
			if ( 'woocommerce/woocommerce.php' !== $plugin ) {
				return;
			}
			update_option( 'bwf_needs_rewrite', 'yes', true );
		}

		private function remove_embed_form() {
			if ( class_exists( 'WFACPEF_Core' ) ) {
				$embed_form_instance = WFACPEF_Core();
				remove_action( 'wfacp_loaded', [ $embed_form_instance, 'wfacp_loaded' ] );

			}
		}


		private function importer_files() {
			require WFACP_PLUGIN_DIR . '/importer/interface-import-export.php';
			require WFACP_PLUGIN_DIR . '/importer/class-wfacp-template-importer.php';
			require WFACP_PLUGIN_DIR . '/importer/class-wfacp-customizer-importer.php';
			require WFACP_PLUGIN_DIR . '/importer/class-wfacp-customizer-embed-form-importer.php';
			add_action( 'wp_loaded', [ $this, 'load_divi_importer' ], 150 );
			do_action( 'wfacp_importer' );
		}

		public function load_divi_importer() {

			$response = WFACP_Common::check_builder_status( 'divi' );

			if ( true === $response['found'] && empty( $response['error'] ) ) {
				require WFACP_PLUGIN_DIR . '/importer/class-wfacp-divi-importer.php';
			}


			$response = WFACP_Common::check_builder_status( 'oxy' );

			if ( true === $response['found'] && empty( $response['error'] ) ) {
				require WFACP_PLUGIN_DIR . '/importer/class-wfacp-oxy-importer.php';
			}
			require WFACP_PLUGIN_DIR . '/importer/class-wfacp-gutenberg-importer.php';
		}


		/**
		 * @param $path
		 * Return plugin full path
		 */
		public function dir( $path = '' ) {
			$dir = $this->dir . $path;
			if ( file_exists( $dir ) ) {
				return $dir;
			}

			return $this->dir;
		}

		/**
		 * @param $path
		 * Return plugin full path
		 */
		public function url( $path = '' ) {
			$url = $this->url . $path;

			return $url;
		}

		public function elementor_importer() {
			if ( defined( 'ELEMENTOR_VERSION' ) ) {
				if ( ! ( ! version_compare( get_bloginfo( 'version' ), '5.0', '>=' ) && ( version_compare( ELEMENTOR_VERSION, '2.8.0', '>=' ) ) ) ) {

					include_once WFACP_PLUGIN_DIR . '/importer/class-wfacp-elementor-importer.php';
				}
			}
		}

		public function init_elementor() {

			add_post_type_support( 'wfacp_checkout', 'elementor' );
			require_once WFACP_PLUGIN_DIR . '/builder/elementor/class-wfacp-elementor.php';
			require_once WFACP_PLUGIN_DIR . '/builder/divi/class-wfacp-divi.php';
			require_once WFACP_PLUGIN_DIR . '/builder/oxygen/class-wfacp-oxy.php';
			require_once WFACP_PLUGIN_DIR . '/builder/gutenberg/class-wfacp-gutenberg.php';

		}

		/**
		 * This method declared ours compat with the HPOS mechanism
		 *
		 * @return void
		 * @since 3.8.3
		 */
		public function declare_hpos_compatibility() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WFACP_PLUGIN_FILE, true );
			}
		}

		/**
		 * to avoid unserialize of the current class
		 */
		public function __wakeup() {
			throw new ErrorException( 'WFACP_Core can`t converted to string' );
		}

		/**
		 * to avoid serialize of the current class
		 */
		public function __sleep() {
			throw new ErrorException( 'WFACP_Core can`t converted to string' );
		}

		/**
		 * To avoid cloning of current class
		 */
		protected function __clone() {
		}

	}
endif;

if ( ! function_exists( 'WFACP_Core' ) ) {
	function WFACP_Core() {

		return WFACP_Core::get_instance();
	}
}

WFACP_Core();
