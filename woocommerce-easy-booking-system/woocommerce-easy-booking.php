<?php
/*
* Plugin Name: Easy Booking for WooCommerce
* Plugin URI: https://easy-booking.pro/
* Description: A simple and flexible WooCommerce booking & reservation plugin to manage dates, availability and pricing on your products.
* Version: 3.5.2
* Author: Noushka
* Author URI: https://noushka.dev
* Requires at least: 5.0
* Tested up to: 7.0.2
* WC tested up to: 10.9.4
* Requires Plugins: woocommerce
* Licence : GPLv3
*/

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Easy_Booking' ) ) :

	class Easy_Booking {

		protected static $_instance = null;

		private const PLUGIN_VERSION = '3.5.2';

		public static function instance() {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 *
		 * Constructor.
		 **/
		public function __construct() {

			$this->define_constants();
			$this->includes();

			// Hook for Easy Booking PRO
			do_action( 'easy_booking_after_init' );

			// Declare compatibility with HPOS (WooCommerce > 8.2)
			add_action(
				'before_woocommerce_init',
				function () {
					if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
						\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
					}
				}
			);

			add_action( 'rest_api_init', array( $this, 'register_easy_booking_rest_routes' ) );

			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_settings_link' ) );

			register_activation_hook( __FILE__, array( $this, 'wceb_activate' ) );
			register_deactivation_hook( __FILE__, array( $this, 'wceb_deactivate' ) );
		}

		/**
		 *
		 * Define constants
		 **/
		private function define_constants() {

			// Plugin version
			defined( 'WCEB_VERSION' ) || define( 'WCEB_VERSION', self::PLUGIN_VERSION );

			// Plugin directory
			defined( 'WCEB_PLUGIN_FILE' ) || define( 'WCEB_PLUGIN_FILE', __FILE__ );
			defined( 'WCEB_PLUGIN_PATH' ) || define( 'WCEB_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

			// Table versions
			defined( 'WCEB_ORDER_BOOKINGS_TABLE_VERSION' ) || define( 'WCEB_ORDER_BOOKINGS_TABLE_VERSION', '1.0.0' );
		}

		/**
		 *
		 * Init plugin settings on activation.
		 **/
		public function wceb_activate() {

			// Store plugin table version in DB
			add_option( 'wceb_version', WCEB_VERSION );

			// Init plugin settings with default values
			foreach ( EasyBooking\Settings_Helper::get_settings() as $name => $setting ) {

				add_option(
					'wceb_' . $name,
					$setting['default']
				);

			}

			set_transient( 'wceb_activated', 1 );
		}

		/**
		 *
		 * Clear scheduled events on deactivation.
		 **/
		public function wceb_deactivate() {
			wp_clear_scheduled_hook( 'wceb_update_booking_statuses_event' );
		}

		/**
		 *
		 * Register REST routes
		 **/
		public function register_easy_booking_rest_routes() {

			register_rest_route(
				'easybooking/v1',
				'/get-fresh-nonce/',
				array(
					'methods'             => 'GET',
					'callback'            => array( 'EasyBooking\Date_Selection', 'get_date_selection_nonce' ),
					'permission_callback' => '__return_true',
				)
			);

			register_rest_route(
				'easybooking/v1',
				'/date-selection/',
				array(
					'methods'             => 'POST',
					'callback'            => array( 'EasyBooking\Date_Selection', 'handle_date_selection' ),
					'permission_callback' => array( 'EasyBooking\Date_Selection', 'check_date_selection_nonce' ),
				)
			);
		}

		/**
		 *
		 * Common includes
		 **/
		public function includes() {

			// Legacy
			require_once __DIR__ . '/includes/legacy/wceb-legacy-functions.php';

			// Functions
			require_once __DIR__ . '/includes/common/functions/wceb-core-functions.php';
			require_once __DIR__ . '/includes/common/functions/wceb-misc-functions.php';
			require_once __DIR__ . '/includes/common/functions/wceb-date-functions.php';
			require_once __DIR__ . '/includes/common/functions/wceb-product-functions.php';
			require_once __DIR__ . '/includes/common/functions/wceb-bookings-functions.php';
			require_once __DIR__ . '/includes/common/functions/wceb-order-booking-functions.php';

			// Date selection helper
			require_once __DIR__ . '/includes/common/class-wceb-date-selection-helper.php';

			// Order booking object
			require_once __DIR__ . '/includes/common/abstract-wceb-booking.php';
			require_once __DIR__ . '/includes/common/class-wceb-order-booking.php';

			// Pickadate assets
			require_once __DIR__ . '/includes/common/class-wceb-pickadate.php';

			// Other
			require_once __DIR__ . '/includes/common/class-wceb-checkout.php';
			require_once __DIR__ . '/includes/common/class-wceb-booking-statuses.php';

			// Third party
			require_once __DIR__ . '/includes/common/third-party/class-wceb-third-party-plugins.php';

			// Admin includes
			if ( is_admin() ) {
				$this->admin_includes();
			}

			// Frontend includes
			if ( ! is_admin() || defined( 'DOING_AJAX' ) ) {
				$this->frontend_includes();
			}
		}

		/**
		 *
		 * Admin includes
		 **/
		public function admin_includes() {

			// Admin
			require_once __DIR__ . '/includes/admin/class-wceb-update-manager.php';
			require_once __DIR__ . '/includes/admin/class-wceb-install.php';

			require_once __DIR__ . '/includes/admin/class-wceb-admin-assets.php';
			require_once __DIR__ . '/includes/admin/class-wceb-admin-ajax.php';

			// Settings
			require_once __DIR__ . '/includes/legacy/wceb-legacy-settings-functions.php';
			require_once __DIR__ . '/includes/settings/class-wceb-settings.php';
			require_once __DIR__ . '/includes/settings/class-wceb-settings-functions.php';
			require_once __DIR__ . '/includes/settings/class-wceb-admin-menu.php';
			require_once __DIR__ . '/includes/settings/class-wceb-settings-page.php';
			require_once __DIR__ . '/includes/settings/class-wceb-tools-page.php';
			require_once __DIR__ . '/includes/settings/class-wceb-settings-general.php';
			require_once __DIR__ . '/includes/settings/class-wceb-settings-appearance.php';
			require_once __DIR__ . '/includes/settings/class-wceb-settings-statuses.php';

			// Reports
			require_once __DIR__ . '/includes/reports/class-wceb-reports-page.php';
			require_once __DIR__ . '/includes/reports/wceb-reports-functions.php';
			require_once __DIR__ . '/includes/reports/class-wceb-reports-bookings.php';
			require_once __DIR__ . '/includes/reports/class-wceb-reports-calendar.php';
			require_once __DIR__ . '/includes/reports/class-wceb-list-bookings.php';

			// Products and orders
			require_once __DIR__ . '/includes/admin/functions/wceb-admin-product-functions.php';
			require_once __DIR__ . '/includes/admin/class-wceb-admin-product.php';
			require_once __DIR__ . '/includes/admin/class-wceb-admin-variation.php';
			require_once __DIR__ . '/includes/admin/class-wceb-order.php';
		}

		/**
		 *
		 * Frontend
		 **/
		public function frontend_includes() {

			// Product and variation hooks
			require_once __DIR__ . '/includes/class-wceb-product.php';
			require_once __DIR__ . '/includes/class-wceb-variable-product.php';

			// Product page
			require_once __DIR__ . '/includes/wceb-single-product.php';

			// Frontend assets
			require_once __DIR__ . '/includes/class-wceb-assets.php';

			// Date selection
			require_once __DIR__ . '/includes/class-wceb-date-selection.php';

			// Cart hooks
			require_once __DIR__ . '/includes/class-wceb-cart.php';
		}

		/**
		 *
		 * Add settings link
		 **/
		public function add_settings_link( $links ) {

			$settings_link = '<a href="admin.php?page=easy-booking">' . esc_html__( 'Settings', 'woocommerce-easy-booking-system' ) . '</a>';
			$pro_link      = '<a href="https://easy-booking.pro/pro" target="_blank" class="wceb-menu-pro">' . esc_html__( 'PRO version', 'woocommerce-easy-booking-system' ) . '</a>';
			array_push( $links, $settings_link, $pro_link );

			return $links;
		}
	}

	function WCEB() {
		return Easy_Booking::instance();
	}

	WCEB();

endif;
