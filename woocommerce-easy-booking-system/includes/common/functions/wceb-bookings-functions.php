<?php

/**
*
* Bookings functions.
* @version 3.3.2
*
**/

defined( 'ABSPATH' ) || exit;

/**
*
* Get (filtered) bookings.
* @param array - $filters
* @return array
*
**/
function wceb_get_filtered_bookings( $filters = array() ) {
    global $wpdb;

    $query = apply_filters( 'easy_booking_query_bookings', wceb_query_order_bookings( $filters ), $filters );
    $sql   = $query['sql'] . $query['sort'];

    $bookings = ! empty( $query['placeholders'] ) ? $wpdb->get_results( $wpdb->prepare( $sql, $query['placeholders'] ) ) : $wpdb->get_results( $sql );

    return $bookings;

}

/**
*
* Get bookings product IDs.
* @return array
*
**/
function wceb_get_bookings_product_ids() {

    $product_ids = apply_filters( 'easy_booking_booked_product_ids', wceb_get_order_bookings_product_ids() );
    return array_unique( $product_ids );
    
}