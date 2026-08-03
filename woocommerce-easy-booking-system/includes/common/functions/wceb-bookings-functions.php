<?php

/**
 *
 * Bookings functions.
 *
 * @version 3.5.1
 **/

defined( 'ABSPATH' ) || exit;

/**
 *
 * Get bookings from one or all registered sources.
 *
 * The Free plugin registers the "order" source. Extensions can register
 * additional sources, such as the "manual" source provided by PRO.
 *
 * @param array  $filters Query filters.
 * @param string $source  Source name or "all".
 * @return object[]
 **/
function wceb_get_bookings( $filters = array(), $source = 'all' ) {

	// Normalize public arguments before querying or passing them to filters.
	$filters = is_array( $filters ) ? $filters : array();
	$source = is_string( $source ) ? sanitize_key( $source ) : 'all';
	$source = $source ? $source : 'all';

	$query    = new EasyBooking\Bookings_Query( $filters, $source );
	$bookings = $query->get_bookings();

	if ( 'all' === $source ) {
		return apply_filters( 'easy_booking_get_bookings', $bookings, $filters );
	}

	/*
	 * Keep the historical source-specific filters while allowing extensions to
	 * register another source without adding a new retrieval function.
	 */
	return apply_filters( "easy_booking_get_{$source}_bookings", $bookings, $filters );
}

/**
 *
 * Get bookings product IDs.
 *
 * @return array
 **/
function wceb_get_bookings_product_ids() {

	$product_ids = apply_filters( 'easy_booking_booked_product_ids', wceb_get_order_bookings_product_ids() );
	return array_unique( $product_ids );
}
