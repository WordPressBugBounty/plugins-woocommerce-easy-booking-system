<?php
/**
 *
 * Uninstall plugin.
 *
 * @package Easy Booking
 * @version 3.5.0
 */

// If uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Delete plugin settings
delete_option( 'wceb_booking_mode' );
delete_option( 'wceb_all_bookable' );
delete_option( 'wceb_number_of_dates' );
delete_option( 'wceb_booking_duration' );
delete_option( 'wceb_custom_booking_duration' );
delete_option( 'wceb_booking_min' );
delete_option( 'wceb_booking_max' );
delete_option( 'wceb_first_available_date' );
delete_option( 'wceb_last_available_date' );
delete_option( 'wceb_calendar_theme' );
delete_option( 'wceb_background_color' );
delete_option( 'wceb_main_color' );
delete_option( 'wceb_text_color' );
delete_option( 'wceb_set_start_booking_status' );
delete_option( 'wceb_keep_start_status_for' );
delete_option( 'wceb_set_processing_booking_status' );
delete_option( 'wceb_set_end_status' );
delete_option( 'wceb_keep_end_status_for' );
delete_option( 'wceb_set_completed_booking_status' );

// DB and plugin version.
delete_option( 'easy_booking_db_version' );
delete_option( 'wceb_version' );

// Table versions.
delete_option( 'wceb_order_bookings_table_version' );

// Delete db entries
global $wpdb;

// Delete post meta.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->prefix}postmeta
		WHERE meta_key IN ( %s, %s, %s, %s, %s, %s )",
		'_bookable',
		'_number_of_dates',
		'_booking_min',
		'_booking_max',
		'_first_available_date',
		'_booking_duration'
	)
);

// Delete tables.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wceb_order_bookings" );