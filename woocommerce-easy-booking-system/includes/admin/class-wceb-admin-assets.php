<?php

namespace EasyBooking;

/**
*
* Admin assets.
 *
 * @version 3.5.1
*/

defined( 'ABSPATH' ) || exit;

class Admin_Assets {

	public function __construct() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ), 20 );
	}

	/**
	 *
	 * Enqueue admin scripts and styles.
	 **/
	public function enqueue_admin_scripts() {

		// Register scripts and styles
		$this->register_admin_scripts();
		$this->register_admin_styles();

		// Get current screen ID
		$screen    = get_current_screen();
		$screen_id = $screen->id;

		// Enqueue common admin scripts and styles
		wp_enqueue_script( 'wceb-admin-js' );
		wp_enqueue_style( 'wceb-admin-css' );

		// Admin product pages
		if ( in_array( $screen_id, array( 'product' ) ) ) {

			wp_enqueue_script( 'wceb-admin-product' );
			wp_enqueue_style( 'picker' );

		}

		// Admin order pages (page name differ whether you enable HPOS or not)
		if ( in_array( $screen_id, array( 'shop_order', 'woocommerce_page_wc-orders' ) ) ) {

			wp_enqueue_script( 'wceb-admin-order' );
			wp_enqueue_style( 'picker' );

		}

		// Admin product and order pages
		if ( in_array( $screen_id, array( 'product' ) ) || in_array( $screen_id, array( 'shop_order', 'woocommerce_page_wc-orders' ) ) ) {

			wp_enqueue_script( 'pickadate' );

			if ( is_rtl() ) {
				wp_enqueue_style( 'rtl-style' );
			}

			wp_enqueue_script( 'pickadate.language' );

		}
	}

	/**
	 *
	 * Register admin scripts.
	 **/
	private function register_admin_scripts() {

		Pickadate::register_scripts();

		// JS for pickadate.js in the admin panel
		wp_register_script(
			'wceb-admin-order',
			wceb_get_file_path( 'admin', 'wceb-admin-order', 'js' ),
			array(),
			WCEB_VERSION,
			true
		);

		wp_add_inline_script(
			'wceb-admin-order',
			'const wceb_admin_order = ' . json_encode(
				array(
					'booking_mode' => esc_html( get_option( 'wceb_booking_mode' ) ),
				)
			),
			'before'
		);

		// JS for admin product settings
		wp_register_script(
			'wceb-admin-product',
			wceb_get_file_path( 'admin', 'wceb-admin-product', 'js' ),
			array( 'jquery' ),
			WCEB_VERSION,
			true
		);

		wp_add_inline_script(
			'wceb-admin-product',
			'const wceb_admin_product = ' . json_encode(
				array(
					'number_of_dates' => esc_html( get_option( 'wceb_number_of_dates' ) ),
				)
			),
			'before'
		);

		// JS for global admin functions
		wp_register_script(
			'wceb-admin-js',
			wceb_get_file_path( 'admin', 'wceb-admin', 'js' ),
			array( 'jquery' ),
			WCEB_VERSION,
			true
		);

		wp_add_inline_script(
			'wceb-admin-js',
			'const wceb_admin = ' . json_encode(
				array(
					'ajax_url'                 => esc_url( admin_url( 'admin-ajax.php' ) ),
					'hide_notice_nonce'        => wp_create_nonce( 'wceb-hide-notice' ),
					'rebuild_bookings_nonce'   => wp_create_nonce( 'wceb-rebuild-order-bookings' ),
					'rebuild_bookings_confirm' => esc_html__( 'Rebuild order bookings from WooCommerce orders?', 'woocommerce-easy-booking-system' ),
					'rebuild_bookings_error'   => esc_html__( 'The order bookings rebuild could not continue. Please try again.', 'woocommerce-easy-booking-system' ),
					'rebuild_bookings_stalled' => esc_html__( 'The order bookings rebuild stopped because no progress was detected. Please reload the page and try again.', 'woocommerce-easy-booking-system' ),
				)
			),
			'before'
		);
	}

	/**
	 *
	 * Register admin styles.
	 **/
	private function register_admin_styles() {

		Pickadate::register_styles();

		// Global CSS for admin
		wp_register_style(
			'wceb-admin-css',
			wceb_get_file_path( 'admin', 'wceb-admin', 'css' ),
			WCEB_PLUGIN_FILE,
			'3.4.8'
		);
	}
}

new Admin_Assets();
