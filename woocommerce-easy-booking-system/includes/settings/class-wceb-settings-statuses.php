<?php

namespace EasyBooking;

use EasyBooking\Settings;

/**
*
* Admin: Booking statuses settings.
 *
* @version 3.1.9
*/

defined( 'ABSPATH' ) || exit;

class Settings_Statuses {

	public function __construct() {

		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'easy_booking_settings_statuses_tab', array( $this, 'booking_statuses_settings_tab' ), 10 );
	}

	/**
	 *
	 * Init booking statuses settings.
	 **/
	public function settings() {

		$this->register_settings();

		$this->add_settings_sections();
		$this->add_settings_fields();
	}

	/**
	 *
	 * Register booking statuses settings.
	 **/
	private function register_settings() {

		foreach ( Settings_Helper::get_booking_statuses_settings() as $name => $setting ) {

			$function_name = 'sanitize_' . $name;

			register_setting(
				'easy_booking_statuses_settings',
				'wceb_' . $name,
				array(
					'type'              => $setting['type'],
					'sanitize_callback' => method_exists( $this, $function_name ) ? array( $this, $function_name ) : 'sanitize_text_field',
					'show_in_rest'      => false,
					'default'           => $setting['default'],
				)
			);

		}
	}

	/**
	 *
	 * Add booking statuses settings section.
	 **/
	private function add_settings_sections() {

		add_settings_section(
			'easy_booking_main_settings',
			'',
			array( $this, 'booking_statuses_settings_section' ),
			'easy_booking_statuses_settings'
		);
	}

	/**
	 *
	 * Add booking statuses settings fields.
	 **/
	private function add_settings_fields() {

		foreach ( Settings_Helper::get_booking_statuses_settings() as $name => $setting ) {

			add_settings_field(
				'wceb_' . $name,
				$setting['title'],
				array( $this, $name ),
				'easy_booking_statuses_settings',
				$setting['section'],
				array( 'label_for' => $name )
			);

		}
	}

	/**
	 *
	 * Display booking statuses settings fields in "Booking statuses" tab.
	 **/
	public function booking_statuses_settings_tab() {

		do_settings_sections( 'easy_booking_statuses_settings' );
		settings_fields( 'easy_booking_statuses_settings' );
	}

	/**
	 *
	 * Booking statuses settings section description.
	 **/
	public function booking_statuses_settings_section() {

		?>

		<p>
		<?php
		printf(
			esc_html__( 'Booking statuses will allow you to easily track your bookings in the "Reports" page. If you are not sure how to configure them, %1$scheck the documentation%2$s.', 'woocommerce-easy-booking-system' ),
			'<a href="https://easy-booking.pro/documentation/booking-statuses/">',
			'</a>'
		);
		?>
		</p>
		
		<?php
	}

	/**
	 *
	 * "Set start booking status" option.
	 **/
	public function set_start_booking_status() {

		Settings::select(
			array(
				'id'      => 'set_start_booking_status',
				'name'    => 'wceb_set_start_booking_status',
				'value'   => get_option( 'wceb_set_start_booking_status', 'automatic' ),
				'options' => array(
					'automatic' => __( 'Automatically', 'woocommerce-easy-booking-system' ),
					'manual'    => __( 'Manually', 'woocommerce-easy-booking-system' ),
				),
			)
		);
	}

	/**
	 *
	 * "Keep start booking status for" option.
	 **/
	public function keep_start_status_for() {

		Settings::input(
			array(
				'type'              => 'number',
				'id'                => 'keep_start_status_for',
				'name'              => 'wceb_keep_start_status_for',
				'description'       => __( 'Day(s) before booking start date.', 'woocommerce-easy-booking-system' ),
				'value'             => get_option( 'wceb_keep_start_status_for', 0 ),
				'custom_attributes' => array(
					'min' => 0,
					'max' => 30,
				),
			)
		);
	}

	/**
	 *
	 * "Set processing booking status" option.
	 **/
	public function set_processing_booking_status() {

		Settings::select(
			array(
				'id'      => 'set_processing_booking_status',
				'name'    => 'wceb_set_processing_booking_status',
				'value'   => get_option( 'wceb_set_processing_booking_status', 'automatic' ),
				'options' => array(
					'automatic' => __( 'Automatically', 'woocommerce-easy-booking-system' ),
					'manual'    => __( 'Manually', 'woocommerce-easy-booking-system' ),
				),
			)
		);
	}

	/**
	 *
	 * "Set end booking status" option.
	 **/
	public function set_end_booking_status() {

		Settings::select(
			array(
				'id'      => 'set_end_booking_status',
				'name'    => 'wceb_set_end_booking_status',
				'value'   => get_option( 'wceb_set_end_booking_status', 'automatic' ),
				'options' => array(
					'automatic' => __( 'Automatically', 'woocommerce-easy-booking-system' ),
					'manual'    => __( 'Manually', 'woocommerce-easy-booking-system' ),
				),
			)
		);
	}

	/**
	 *
	 * "Keep end booking status for" option.
	 **/
	public function keep_end_status_for() {

		Settings::input(
			array(
				'type'              => 'number',
				'id'                => 'keep_end_status_for',
				'name'              => 'wceb_keep_end_status_for',
				'description'       => __( 'Day(s) after booking end date.', 'woocommerce-easy-booking-system' ),
				'value'             => get_option( 'wceb_keep_end_status_for', 0 ),
				'custom_attributes' => array(
					'min' => 0,
					'max' => 30,
				),
			)
		);
	}

	/**
	 *
	 * "Set completed booking status" option.
	 **/
	public function set_completed_booking_status() {

		Settings::select(
			array(
				'id'      => 'set_completed_booking_status',
				'name'    => 'wceb_set_completed_booking_status',
				'value'   => get_option( 'wceb_set_completed_booking_status', 'automatic' ),
				'options' => array(
					'automatic' => __( 'Automatically', 'woocommerce-easy-booking-system' ),
					'manual'    => __( 'Manually', 'woocommerce-easy-booking-system' ),
				),
			)
		);
	}

	/**
	 *
	 * Sanitize "Keep start booking status for" option.
	 **/
	public function sanitize_keep_start_status_for( $value ) {
		return Settings::sanitize_duration_field( $value, 0, 30 );
	}

	/**
	 *
	 * Sanitize "Keep end booking status for" option.
	 **/
	public function sanitize_keep_end_status_for( $value ) {
		return Settings::sanitize_duration_field( $value, 0, 30 );
	}
}

new Settings_Statuses();
