<?php

namespace EasyBooking;

/**
*
* Settings.
 *
* @version 3.4.8
*/

defined( 'ABSPATH' ) || exit;

class Settings_Helper {

	/**
	 *
	 * Get plugin settings.
	 *
	 * @return array - $settings
	 **/
	public static function get_settings(): array {

		$settings = array_merge( self::get_general_settings(), self::get_appearance_settings(), self::get_booking_statuses_settings() );

		return $settings;
	}

	/**
	 *
	 * Get plugin general settings.
	 *
	 * @return array - $general_settings
	 **/
	public static function get_general_settings(): array {

		$general_settings = array(

			'all_bookable'         => array(
				'type'    => 'string',
				'default' => 'no',
				'title'   => __( 'Make all products bookable?', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'number_of_dates'      => array(
				'type'    => 'string',
				'default' => 'two',
				'title'   => __( 'Number of dates to select', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'booking_mode'         => array(
				'type'    => 'string',
				'default' => 'nights',
				'title'   => __( 'Booking mode', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'booking_duration'     => array(
				'type'    => 'integer',
				'default' => 1,
				'title'   => __( 'Booking duration', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'booking_min'          => array(
				'type'    => 'integer',
				'default' => 0,
				'title'   => __( 'Minimum duration', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'booking_max'          => array(
				'type'    => 'integer',
				'default' => 0,
				'title'   => __( 'Maximum duration', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'first_available_date' => array(
				'type'    => 'integer',
				'default' => 0,
				'title'   => __( 'First available date', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'last_available_date'  => array(
				'type'    => 'integer',
				'default' => 1825,
				'title'   => __( 'Last available date', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),

		);

		return $general_settings;
	}

	/**
	 *
	 * Get plugin appearance settings.
	 *
	 * @return array - $appearance_settings
	 **/
	public static function get_appearance_settings(): array {

		$appearance_settings = array(
			'calendar_theme'   => array(
				'type'    => 'string',
				'default' => 'dropdown',
				'title'   => __( 'Display mode', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'background_color' => array(
				'type'    => 'string',
				'default' => '#FFFFFF',
				'title'   => __( 'Background color', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'main_color'       => array(
				'type'    => 'string',
				'default' => '#999999',
				'title'   => __( 'Accent color', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'text_color'       => array(
				'type'    => 'string',
				'default' => '#000000',
				'title'   => __( 'Text color', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),

		);

		return $appearance_settings;
	}

	/**
	 *
	 * Get plugin booking statuses settings.
	 *
	 * @return array - $booking_statuses_settings
	 **/
	public static function get_booking_statuses_settings(): array {

		$booking_statuses_settings = array(
			'set_start_booking_status'      => array(
				'type'    => 'string',
				'default' => 'automatic',
				'title'   => __( 'Set "Start" booking status', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'keep_start_status_for'         => array(
				'type'    => 'integer',
				'default' => 0,
				'title'   => __( 'Keep "Start" status for', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'set_processing_booking_status' => array(
				'type'    => 'string',
				'default' => 'automatic',
				'title'   => __( 'Set "Processing" booking status', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'set_end_booking_status'        => array(
				'type'    => 'string',
				'default' => 'automatic',
				'title'   => __( 'Set "End" booking status', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'keep_end_status_for'           => array(
				'type'    => 'integer',
				'default' => 0,
				'title'   => __( 'Keep "End" status for', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),
			'set_completed_booking_status'  => array(
				'type'    => 'string',
				'default' => 'automatic',
				'title'   => __( 'Set "Completed" booking status', 'woocommerce-easy-booking-system' ),
				'section' => 'easy_booking_main_settings',
			),

		);

		return $booking_statuses_settings;
	}
}
