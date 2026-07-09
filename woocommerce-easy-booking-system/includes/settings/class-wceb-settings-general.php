<?php

namespace EasyBooking;

use EasyBooking\Settings;

/**
*
* Admin: General settings.
 *
* @version 3.0.6
*/

defined( 'ABSPATH' ) || exit;

class Settings_General {

	public function __construct() {

		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'easy_booking_settings_general_tab', array( $this, 'general_settings_tab' ), 10 );
		add_action( 'add_option_wceb_all_bookable', array( $this, 'enable_bookable_option_for_all_products' ), 10, 2 );

	}

	/**
	 *
	 * Init general settings.
	 **/
	public function settings() {

		$this->register_settings();

		$this->add_settings_sections();
		$this->add_settings_fields();
	}

	/**
	 *
	 * Register general settings.
	 **/
	private function register_settings() {

		foreach ( Settings_Helper::get_general_settings() as $name => $setting ) {

			$function_name = 'sanitize_' . $name;

			register_setting(
				'easy_booking_general_settings',
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
	 * Add general settings section.
	 **/
	private function add_settings_sections() {

		add_settings_section(
			'easy_booking_main_settings',
			'',
			'',
			'easy_booking_general_settings'
		);
	}

	/**
	 *
	 * Add general settings fields.
	 **/
	private function add_settings_fields() {

		foreach ( Settings_Helper::get_general_settings() as $name => $setting ) {

			add_settings_field(
				'wceb_' . $name,
				$setting['title'],
				array( $this, $name ),
				'easy_booking_general_settings',
				$setting['section'],
				array( 'label_for' => $name )
			);

		}
	}

	/**
	 *
	 * Display general settings fields in "General" tab.
	 **/
	public function general_settings_tab() {

		do_settings_sections( 'easy_booking_general_settings' );
		settings_fields( 'easy_booking_general_settings' );
	}

	/**
	 *
	 * "All bookable" option.
	 **/
	public function all_bookable() {

		Settings::checkbox(
			array(
				'id'          => 'all_bookable',
				'name'        => 'wceb_all_bookable',
				'description' => __( 'If checked, any new or modified product will be automatically bookable.', 'woocommerce-easy-booking-system' ),
				'value'       => get_option( 'wceb_all_bookable' ) ? get_option( 'wceb_all_bookable' ) : '',
				'cbvalue'     => 'yes',
			)
		);
	}

	/**
	 *
	 * "Number of dates" option.
	 **/
	public function number_of_dates() {

		Settings::select(
			array(
				'id'          => 'number_of_dates',
				'name'        => 'wceb_number_of_dates',
				'description' => __( 'Customizable at product level.', 'woocommerce-easy-booking-system' ),
				'value'       => get_option( 'wceb_number_of_dates', 'two' ),
				'options'     => array(
					'one' => __( 'One', 'woocommerce-easy-booking-system' ),
					'two' => __( 'Two', 'woocommerce-easy-booking-system' ),
				),
			)
		);
	}

	/**
	 *
	 * "Booking mode" option.
	 **/
	public function booking_mode() {

		Settings::select(
			array(
				'id'          => 'booking_mode',
				'name'        => 'wceb_booking_mode',
				'value'       => get_option( 'wceb_booking_mode', 'nights' ),
				'description' => __( 'Rent your products by day or by night (i.e. 5 days = 4 nights).', 'woocommerce-easy-booking-system' ),
				'options'     => array(
					'days'   => __( 'Days', 'woocommerce-easy-booking-system' ),
					'nights' => __( 'Nights', 'woocommerce-easy-booking-system' ),
				),
			)
		);
	}

	/**
	 *
	 * "Booking duration" option.
	 **/
	public function booking_duration() {

		Settings::input(
			array(
				'type'              => 'number',
				'id'                => 'booking_duration',
				'name'              => 'wceb_booking_duration',
				'description'       => __( 'Number of consecutive days/nights forming a block. Only for two dates selection. Customizable at product level.', 'woocommerce-easy-booking-system' ),
				'value'             => get_option( 'wceb_booking_duration', 1 ),
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '1',
					'max'  => '366',
				),
			)
		);
	}

	/**
	 *
	 * "Minimum booking duration" option.
	 **/
	public function booking_min() {

		Settings::input(
			array(
				'type'              => 'number',
				'id'                => 'booking_min',
				'name'              => 'wceb_booking_min',
				'value'             => get_option( 'wceb_booking_min', 0 ),
				'description'       => __( 'Minimum number of blocks to select. Leave 0 or empty to set no minmum. Customizable at product level.', 'woocommerce-easy-booking-system' ),
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '0',
					'max'  => '3650',
				),
			)
		);
	}

	/**
	 *
	 * "Maximum booking duration" option.
	 **/
	public function booking_max() {

		Settings::input(
			array(
				'type'              => 'number',
				'id'                => 'booking_max',
				'name'              => 'wceb_booking_max',
				'value'             => get_option( 'wceb_booking_max', 0 ),
				'description'       => __( 'Maximum number of blocks to select. Leave 0 or empty to set no maximum. Customizable at product level.', 'woocommerce-easy-booking-system' ),
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '0',
					'max'  => '3650',
				),
			)
		);
	}

	/**
	 *
	 * "First available date" option.
	 **/
	public function first_available_date() {

		Settings::input(
			array(
				'type'              => 'number',
				'id'                => 'first_available_date',
				'name'              => 'wceb_first_available_date',
				'value'             => get_option( 'wceb_first_available_date', 0 ),
				'description'       => __( 'First available date, relative to the current day. Leave 0 or empty to keep the current day. Customizable at product level.', 'woocommerce-easy-booking-system' ),
				'custom_attributes' => array(
					'min' => '0',
					'max' => '3650',
				),
			)
		);
	}

	/**
	 *
	 * "Last available date" option.
	 **/
	public function last_available_date() {

		Settings::input(
			array(
				'type'              => 'number',
				'id'                => 'last_available_date',
				'name'              => 'wceb_last_available_date',
				'value'             => get_option( 'wceb_last_available_date', 1825 ),
				'description'       => __( 'Last available date, relative to the current day. Max: 3650 days (10 years).', 'woocommerce-easy-booking-system' ),
				'custom_attributes' => array(
					'min' => '1',
					'max' => '3650',
				),
			)
		);
	}

	/**
	 *
	 * Sanitize "All bookable" option.
	 **/
	public function sanitize_all_bookable( $value ) {
		return Settings::sanitize_checkbox( $value );
	}

	/**
	 *
	 * Sanitize "Custom booking duration" option.
	 **/
	public function sanitize_booking_duration( $value ) {
		return Settings::sanitize_duration_field( $value );
	}

	/**
	 *
	 * Sanitize "Minimum booking duration" option.
	 **/
	public function sanitize_booking_min( $value ) {
		return Settings::sanitize_duration_field( $value );
	}

	/**
	 *
	 * Sanitize "Maximum booking duration" option.
	 **/
	public function sanitize_booking_max( $value ) {
		return Settings::sanitize_duration_field( $value );
	}

	/**
	 *
	 * Sanitize "First available date" option.
	 **/
	public function sanitize_first_available_date( $value ) {
		return Settings::sanitize_duration_field( $value );
	}

	/**
	 *
	 * Sanitize "Last available date" option.
	 **/
	public function sanitize_last_available_date( $value ) {
		return Settings::sanitize_duration_field( $value );
	}

	/**
	 *
	 * Maybe make all products and variations bookable when saving "All bookable option".
	 * @param mixed $old_value
	 * @param mixed $value
	 * 
	 **/
	public function enable_bookable_option_for_all_products( $old_value, $value ) {

		// Return if option is not checked.
		if ( 'yes' !== $value ) {
			return;
		}

		$product_ids = wc_get_products(
			array(
				'limit'  => -1,
				'return' => 'ids',
				'type'   => array( 'simple', 'variable', 'grouped', 'variation', 'bundle' ),
			)
		);

		foreach ( $product_ids as $product_id ) {
			$updated = update_post_meta( $product_id, '_bookable', 'yes' );
		}
	}
}

new Settings_General();
