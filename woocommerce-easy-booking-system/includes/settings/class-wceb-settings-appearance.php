<?php

namespace EasyBooking;
use EasyBooking\Settings;

/**
*
* Admin: appearance settings.
* @version 3.0.6
*
**/

defined( 'ABSPATH' ) || exit;

class Settings_Appearance {

	public function __construct() {

		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'easy_booking_settings_appearance_tab', array( $this, 'appearance_settings_tab' ), 10 );

	}

	/**
	*
	* Init appearance settings.
	*
	**/
	public function settings() {

		$this->register_settings();

		$this->add_settings_sections();
		$this->add_settings_fields();

	}

	/**
	*
	* Register appearance settings.
	*
	**/
	private function register_settings() {

		foreach ( Settings_Helper::get_appearance_settings() as $name => $setting ) {

			$function_name = 'sanitize_' . $name;

			$args = array(
				'type'              => $setting['type'],
				'sanitize_callback' => method_exists( $this, $function_name ) ? array( $this, 'sanitize_' . $name ) : 'sanitize_text_field',
				'show_in_rest'      => false,
				'default'           => $setting['default']
			);

			register_setting(
				'easy_booking_appearance_settings',
				'wceb_' . $name,
				$args
			);

		}

	}
	
	/**
	*
	* Add appearance settings section.
	*
	**/
	private function add_settings_sections() {

		add_settings_section(
			'easy_booking_main_settings',
			__( 'Picker appearance', 'woocommerce-easy-booking-system' ),
			array( $this, 'appearance_settings_section' ),
			'easy_booking_appearance_settings'
		);

	}

	

	/**
	*
	* Add appearance settings fields.
	*
	**/
	private function add_settings_fields() {

		foreach ( Settings_Helper::get_appearance_settings() as $name => $setting ) {

			 add_settings_field(
				'wceb_' . $name,
				$setting['title'],
				array( $this, $name ),
                'easy_booking_appearance_settings',
				$setting['section'],
				array( 'label_for' => $name )
			);

		}

	}

	/**
	*
	* Display appearance settings fields in "Appearance" tab.
	*
	**/
	public function appearance_settings_tab() {
		
		do_settings_sections( 'easy_booking_appearance_settings' );
		settings_fields( 'easy_booking_appearance_settings' );

	}

	/**
	*
	* Appearance settings section description.
	*
	**/
	public function appearance_settings_section() {
		echo '<p>' . esc_html__( 'Customize the calendar to match your theme. For best results, use a light background with dark text.', 'woocommerce-easy-booking-system' );
	}

	/**
	*
	* "Calendar theme" option.
	*
	**/
	public function calendar_theme() {

		Settings::select( array(
			'id'          => 'calendar_theme',
			'name'        => 'wceb_calendar_theme',
			'value'       => get_option( 'wceb_calendar_theme', 'default' ),
			'options'     => array(
				'default' => esc_html__( 'Overlay', 'woocommerce-easy-booking-system' ),
				'classic' => esc_html__( 'Dropdown', 'woocommerce-easy-booking-system' )
			),
            'description' => __( 'Choose how the date picker is displayed.', 'woocommerce-easy-booking-system' )
		));

	}

	/**
	*
	* "Background color" option.
	*
	**/
	public function background_color() {

		Settings::input( array(
			'type'  => 'text',
			'id'    => 'background_color',
			'name'  => 'wceb_background_color',
			'value' =>  get_option( 'wceb_background_color', '#FFFFFF' ),
			'class' => 'color-field'
		));

	}

	/**
	*
	* "Main color" option.
	*
	**/
	public function main_color() {

		Settings::input( array(
			'type'  => 'text',
			'id'    => 'main_color',
			'name'  => 'wceb_main_color',
			'value' =>  get_option( 'wceb_main_color', '#999999' ),
			'class' => 'color-field'
		));

	}

	/**
	*
	* "Text color" option.
	*
	**/
	public function text_color() {

		Settings::input( array(
			'type'  => 'text',
			'id'    => 'text_color',
			'name'  => 'wceb_text_color',
			'value' =>  get_option( 'wceb_text_color', '#000000' ),
			'class' => 'color-field'
		));

	}

	/**
	*
	* Sanitize "Background color" option.
	*
	**/
	public function sanitize_background_color( $value ) {
		return sanitize_hex_color( $value );
	}

	/**
	*
	* Sanitize "Text color" option.
	*
	**/
	public function sanitize_text_color( $value ) {
		return sanitize_hex_color( $value );
	}

	/**
	*
	* Sanitize "Main color" option.
	*
	**/
	public function sanitize_main_color( $value ) {
		return sanitize_hex_color( $value );
	}
	
}

new Settings_Appearance();