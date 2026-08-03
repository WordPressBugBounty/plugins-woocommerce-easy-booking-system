<?php

/**
 * Legacy booking query functions.
 *
 * @deprecated Use wceb_get_bookings() or EasyBooking\Bookings_Query.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get bookings from every registered source.
 *
 * @deprecated Use wceb_get_bookings().
 *
 * @param array $filters Query filters.
 * @return object[]
 */
function wceb_get_filtered_bookings( $filters = array() ) {

	_deprecated_function( __FUNCTION__, WCEB_VERSION, 'wceb_get_bookings' );

	return wceb_get_bookings( $filters );
}

/**
 * Get bookings created from WooCommerce orders.
 *
 * @deprecated Use wceb_get_bookings().
 *
 * @param array $filters Query filters.
 * @return object[]
 */
function wceb_get_order_bookings( $filters = array() ) {

	_deprecated_function( __FUNCTION__, WCEB_VERSION, 'wceb_get_bookings' );

	return wceb_get_bookings( $filters, 'order' );
}

/**
 * Build an order bookings query without executing it.
 *
 * @deprecated Use EasyBooking\Bookings_Query::get_query().
 *
 * @param array $filters Query filters.
 * @return array
 */
function wceb_query_order_bookings( $filters = array() ) {

	_deprecated_function( __FUNCTION__, WCEB_VERSION, 'EasyBooking\Bookings_Query::get_query' );

	$query = new EasyBooking\Bookings_Query( $filters, 'order' );

	return $query->get_query();
}
