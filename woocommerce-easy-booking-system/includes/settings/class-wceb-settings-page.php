<?php

namespace EasyBooking;

/**
*
* Admin: Settings page.
 *
* @version 3.1.9
*/

defined( 'ABSPATH' ) || exit;

class Settings_Page {

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 10 );
	}

	/**
	 *
	 * Add settings page into "Easy Booking" menu.
	 **/
	public function add_settings_page() {

		// Create a "Settings" page inside "Easy Booking" menu
		$settings_page = add_submenu_page(
			'easy-booking',
			__( 'Settings', 'woocommerce-easy-booking-system' ),
			__( 'Settings', 'woocommerce-easy-booking-system' ),
			apply_filters( 'easy_booking_settings_capability', 'manage_options', 'easy-booking' ),
			'easy-booking',
			array( $this, 'display_settings_page' )
		);

		// Maybe load scripts on the "Settings" page.
		add_action( 'admin_print_scripts-' . $settings_page, array( $this, 'load_settings_scripts' ) );

		// Add a "Help" tab at the top of the settings page.
		add_action( 'load-' . $settings_page, array( $this, 'add_help_tab' ) );
		add_filter( 'admin_footer_text', array( $this, 'add_review_footer' ), 20, 1 );
	}

	/**
	 * Display a friendly review request in the settings footer.
	 *
	 * @param string $footer_text Default admin footer text.
	 * @return string
	 */
	public function add_review_footer( $footer_text ) {

		if ( ! current_user_can( apply_filters( 'easy_booking_settings_capability', 'manage_options', 'easy-booking' ) ) ) {
			return $footer_text;
		}

		$current_screen = get_current_screen();

		if ( isset( $current_screen->parent_base ) && 'easy-booking' === $current_screen->parent_base ) {

			return sprintf( 
				esc_html__( 'Enjoying Easy Booking? A %s★★★★★%s review on WordPress.org would mean a lot and help support the plugin\'s continued development &hearts;', 'woocommerce-easy-booking-system' ),
				'<a href="https://wordpress.org/support/plugin/woocommerce-easy-booking-system/reviews?rate=5#new-post" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);

		}

		return $footer_text;

	}

	/**
	 *
	 * Load HTML for settings page.
	 **/
	public function display_settings_page() {
		include_once 'views/html-wceb-settings-page.php';
	}

	/**
	 *
	 * Load CSS and JS for settings page.
	 **/
	public function load_settings_scripts() {

		// WP colorpicker CSS.
		wp_enqueue_style( 'wp-color-picker' );

		// WP colorpicker JS.
		wp_enqueue_script(
			'color-picker',
			plugins_url( 'assets/js/admin/colorpicker.min.js', WCEB_PLUGIN_FILE ),
			array( 'wp-color-picker' ),
			false,
			true
		);
	}

	/**
	 *
	 * Add a "Help" tab at the top of the settings page.
	 **/
	public function add_help_tab() {

		$screen = get_current_screen();

		$screen->add_help_tab(
			array(
				'id'      => 'wceb-help-support',
				'title'   => __( 'Help and support', 'woocommerce-easy-booking-system' ),
				'content' => sprintf(
					__( '%1$sPlugin settings%2$sFind detailed instructions in the %3$sdocumentation%4$s to configure the plugin exactly the way you need..%5$sHelp and support%6$sNeed assistance? Check the %7$sFAQ%8$s first, or contact us via email for support.%9$s', 'woocommerce-easy-booking-system' ),
					'<h2>',
					'</h2><p>',
					'<a href="https://easy-booking.pro/documentation/" target="_blank">',
					'</a>',
					'</p><h2>',
					'</h2><p>',
					'<a href="https://easy-booking.pro/faq/" target="_blank">',
					'</a>',
					'</p>'
				),
			)
		);

		$screen->set_help_sidebar(
			'<p><strong>' . __( 'For more information:', 'woocommerce-easy-booking-system' ) . '</strong></p>' .
			'<p><a href="https://easy-booking.pro/documentation/" target="_blank">' . __( 'Documentation', 'woocommerce-easy-booking-system' ) . '</a></p>' .
			'<p><a href="https://easy-booking.pro/faq/" target="_blank">' . __( 'FAQ', 'woocommerce-easy-booking-system' ) . '</a></p>'
		);
	}
}

new Settings_Page();
