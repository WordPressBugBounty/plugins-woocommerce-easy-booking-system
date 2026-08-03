<?php

namespace EasyBooking;

/**
 * Update Manager with admin notices.
 *
 * @version 3.5.1
 */

defined( 'ABSPATH' ) || exit;

class Update_Manager {

	private const DB_VERSION = '3.3.1';

	/**
	 * Database content migrations, in ascending version order.
	 *
	 * Table structure changes are handled by Install::maybe_create_tables().
	 *
	 * @var array
	 */
	private const UPDATES = array(
		// '3.6.0' => 'update_db_version_360',
	);

	/**
	 * Last version whose migration rebuilt order bookings automatically.
	 */
	private const LEGACY_ORDER_BOOKINGS_VERSION = '3.3.1';

	/**
	 *
	 * Initialize update manager
	 * Registers hooks for update detection and notices
	 **/
	public static function init() {

		// Detect plugin update
		add_action( 'upgrader_process_complete', array( self::class, 'on_plugin_updated' ), 10, 2 );

		// Display admin notices
		add_action( 'admin_notices', array( self::class, 'display_admin_notices' ) );

		// AJAX handler for database update
		add_action( 'wp_ajax_wceb_update_database', array( self::class, 'ajax_update_database' ) );
	}

	/**
	 *
	 * Detect when plugin is updated
	 *
	 * @param object $upgrader_object
	 * @param array  $options
	 **/
	public static function on_plugin_updated( $upgrader_object, $options ) {

		// Check if this is a plugin update
		if ( $options['action'] !== 'update' || $options['type'] !== 'plugin' ) {
			return;
		}

		if ( ! isset( $options['plugins'] ) ) {
			return;
		}

		// Check if our plugin was updated
		foreach ( $options['plugins'] as $plugin ) {

			if ( $plugin === plugin_basename( WCEB_PLUGIN_FILE ) ) {
				set_transient( 'wceb_updated', 1, HOUR_IN_SECONDS );
				break;
			}
		}
	}

	/**
	 *
	 * Display admin notices
	 **/
	public static function display_admin_notices() {

		// Notice after plugin activation
		if ( get_transient( 'wceb_activated' ) ) {

			delete_transient( 'wceb_activated' );
			self::render_notice( 'activation' );

		}

		// Notice after plugin update
		if ( get_transient( 'wceb_updated' ) ) {

			delete_transient( 'wceb_updated' );

			// Only show if database needs update
			if ( ! self::needs_update() ) {
				self::render_notice( 'updated' );
			}
		}

		// Notice for database update
		if ( self::needs_update() ) {
			self::render_notice( 'update-database' );
		}

		// Very old installations must rebuild bookings with the dedicated tool.
		if ( 'yes' === get_option( 'wceb_order_bookings_rebuild_required' ) ) {
			self::render_notice( 'rebuild-order-bookings' );
		}
	}

	/**
	 *
	 * Render a notice template
	 *
	 * @param string $type Notice type.
	 **/
	private static function render_notice( $type ) {

		$template_path = WCEB_PLUGIN_PATH . 'includes/admin/views/notices/html-wceb-notice-' . $type . '.php';

		if ( file_exists( $template_path ) ) {
			include $template_path;
		}
	}

	/**
	 *
	 * AJAX handler to update database
	 **/
	public static function ajax_update_database() {

		// Security check
		check_ajax_referer( 'wceb-hide-notice', 'security' );

		// Permission check
		if ( ! current_user_can( 'manage_options' ) ) {

			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to perform this action.', 'woocommerce-easy-booking-system' ),
				)
			);

		}

		// Check if complete update parameter was passed
		$full_update = isset( $_POST['full_update'] ) ? true : false;

		// Run updates
		$result = self::maybe_update_plugin( $full_update );

		if ( is_wp_error( $result ) ) {

			wp_send_json_error(
				array(
					'message' => $result->get_error_message(),
				)
			);

		}

		wp_send_json_success(
			array(
				'message' => $result,
			)
		);
	}

	/**
	 *
	 * Update Easy Booking database if needed
	 *
	 * @param bool $full_update Whether to verify and repair the current schema.
	 * @return string Translated status message
	 */
	public static function maybe_update_plugin( $full_update = false ) {

		$current_db_version = self::get_db_version();
		$needs_update       = version_compare( $current_db_version, self::DB_VERSION, '<' );

		if ( ! $full_update && ! $needs_update ) {
			return __( 'Easy Booking database is already up to date.', 'woocommerce-easy-booking-system' );
		}

		try {
			// The database update now repairs table structures only.
			Install::maybe_create_tables();
		} catch ( \Throwable $error ) {

			wc_get_logger()->error(
				sprintf( 'Easy Booking database update failed: %s', $error->getMessage() ),
				array( 'source' => 'easy-booking' )
			);

			return new \WP_Error(
				'easy_booking_database_update_error',
				__( 'Easy Booking could not update its database tables. Please check the WooCommerce logs.', 'woocommerce-easy-booking-system' )
			);
		}

		if ( ! $needs_update ) {
			return __( 'Easy Booking database tables checked successfully.', 'woocommerce-easy-booking-system' );
		}

		// Keep the installed version before any migration changes it.
		$rebuild_order_bookings = version_compare( $current_db_version, self::LEGACY_ORDER_BOOKINGS_VERSION, '<' );

		foreach ( self::UPDATES as $update_version => $callback ) {

			if ( ! version_compare( $current_db_version, $update_version, '<' ) ) {
				continue;
			}

			if ( ! method_exists( self::class, $callback ) ) {
				return new \WP_Error(
					'easy_booking_database_update_callback_missing',
					sprintf(
						/* translators: %s: database version. */
						__( 'Easy Booking database update %s could not be found.', 'woocommerce-easy-booking-system' ),
						$update_version
					)
				);
			}

			try {
				call_user_func( array( self::class, $callback ) );
			} catch ( \Throwable $error ) {

				wc_get_logger()->error(
					sprintf( 'Easy Booking database update %1$s failed: %2$s', $update_version, $error->getMessage() ),
					array( 'source' => 'easy-booking' )
				);

				return new \WP_Error(
					'easy_booking_database_update_error',
					sprintf(
						/* translators: %s: database version. */
						__( 'Easy Booking could not complete database update %s. Please check the WooCommerce logs.', 'woocommerce-easy-booking-system' ),
						$update_version
					)
				);
			}

			/*
			 * Save progress after each successful migration. If a later migration
			 * fails, completed migrations will not run again on the next attempt.
			 */
			update_option( 'easy_booking_db_version', $update_version );
			$current_db_version = $update_version;
		}

		// Schema-only versions do not need a dedicated migration callback.
		update_option( 'easy_booking_db_version', self::DB_VERSION );

		if ( $rebuild_order_bookings ) {

			/*
			 * Version 3.3.1 previously rebuilt bookings automatically. Very old
			 * installations now use the safer, explicit tool instead.
			 */
			update_option( 'wceb_order_bookings_rebuild_required', 'yes' );

			return __( 'Database tables updated. Please rebuild order bookings from Easy Booking > Tools.', 'woocommerce-easy-booking-system' );
		}

		return __( 'Easy Booking database update complete.', 'woocommerce-easy-booking-system' );
	}

	/**
	 * Store the current database version on a new installation.
	 */
	public static function initialize_db_version() {
		add_option( 'easy_booking_db_version', self::DB_VERSION );
	}

	/**
	 *
	 * Get current database version
	 *
	 * @return string
	 **/
	public static function get_db_version() {
		return get_option( 'easy_booking_db_version', '1.0.0' );
	}

	/**
	 *
	 * Check if database needs update
	 *
	 * @return bool
	 */
	public static function needs_update() {

		$current_version = self::get_db_version();

		return version_compare( $current_version, self::DB_VERSION, '<' );
	}

}
