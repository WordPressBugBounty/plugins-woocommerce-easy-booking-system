<?php

/**
*
* Order bookings functions.
* @version 3.3.3
*
**/

defined( 'ABSPATH' ) || exit;

/**
*
* Get an order booking.
* @param int - $item_id
* @return EasyBooking\Order_Booking | bool
*
**/
function wceb_get_order_booking( $item_id ) {

    $booking = new EasyBooking\Order_Booking( $item_id );

    if ( ! $booking->get_order_item_id() ) {
        return false;
    }

    return $booking;

}

/**
*
* Create or update an order booking.
* @param int - $item_id
* @param WC_Order_Item - $item
* @param WC_Order - $order
* @return mixed
*
**/
function wceb_create_or_update_order_booking( $item_id, $item, $order ) {

    if ( ! is_a( $order, 'WC_Order' ) ) {
        return false;
    }

    $booking = wceb_get_order_booking( $item_id );

    $start_date     = $item->get_meta( '_booking_start_date' );
    $end_date       = $item->get_meta( '_booking_end_date' );
    $booking_status = $item->get_meta( '_booking_status' );

    // No valid start date = no booking
    if ( ! wceb_is_valid_date( $start_date ) ) {

        // Maybe delete existing booking
        if ( $booking ) { wceb_delete_order_booking( $item_id ); }
        return false;

    }

    $_product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
    $qty         = $item->get_quantity();
    $order_id    = $item->get_order_id();
    $end_date    = wceb_is_valid_date( $end_date ) ? $end_date : null;

    // Check item refunds
    $refunded_qty = abs( $order->get_qty_refunded_for_item( $item_id ) );

    if ( $refunded_qty ) {

        // Item was fully refunded = no booking
        if ( $qty === $refunded_qty ) {

            // Maybe delete existing booking
            if ( $booking ) { wceb_delete_order_booking( $item_id ); }
            return false;

        }

        $qty -= $refunded_qty;

    }

    // At this point, if booking doesn't exist we need to create it
    if ( ! $booking ) {
        $booking = new EasyBooking\Order_Booking( $item_id );
    }

    $booking->set_props( array(
        'product_id' => $_product_id,
        'start'      => $start_date,
        'end'        => $end_date,
        'status'     => $booking_status,
        'qty'        => $qty,
        'order_id'   => $order_id
    ) );
    
    do_action( 'easy_booking_before_order_booking_save', $booking, $item );

    $update = $booking->save();

    if ( is_wp_error( $update ) ) {

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf( "Easy Booking: Error saving booking: %s", $update->get_error_message() ) );
        }

        return false;

    }

    do_action( 'easy_booking_order_booking_saved', $update, $booking, $item );

}

/**
*
* Delete an order booking.
* @param int - $item_id
* @return mixed
*
**/
function wceb_delete_order_booking( $item_id ) {
    global $wpdb;

    do_action( 'easy_booking_before_order_booking_delete', $item_id );

    $delete = $wpdb->delete(
        $wpdb->prefix . 'wceb_order_bookings',
        array( 'order_item_id' => $item_id ),
        array( '%d' )
    );

    do_action( 'easy_booking_order_booking_deleted', $delete, $item_id );

}

/**
*
* Delete all order bookings associated to an order.
* @param int - $order_id
* @return mixed
*
**/
function wceb_delete_order_bookings( $order_id ) {
    global $wpdb;

    do_action( 'easy_booking_before_order_bookings_delete', $order_id );

    $delete = $wpdb->delete(
        $wpdb->prefix . 'wceb_order_bookings',
        array( 'order_id' => $order_id ),
        array( '%d' )
    );

    do_action( 'easy_booking_order_bookings_deleted', $delete, $order_id );

}

/**
*
* Get filtered order bookings.
* @param array - $filters
* @return array - $order_bookings
*
**/
function wceb_get_order_bookings( $filters = array() ) {
    global $wpdb;

    $query = wceb_query_order_bookings( $filters );
    $sql   = $query['sql'] . $query['sort'];

    $order_bookings = ! empty( $query['placeholders'] ) ? $wpdb->get_results( $wpdb->prepare( $sql, $query['placeholders'] ) ) : $wpdb->get_results( $sql );

    return apply_filters( 'easy_booking_get_order_bookings', $order_bookings );

}

/**
*
* Build query for order bookings.
* @param array - $filters
* @return array
*
**/
function wceb_query_order_bookings( $filters ) {
    global $wpdb;

    $sql          = "SELECT * FROM {$wpdb->prefix}wceb_order_bookings";
    $args         = "";
    $placeholders = array();

    $i = 0;

    // Filter booking status.
    $valid_booking_statuses = array( 'pending', 'start', 'processing', 'end', 'completed' );

    if ( isset( $filters['status'] ) && in_array( $filters['status'] , $valid_booking_statuses ) ) {
        $args .= $i === 0 ? ' WHERE status = %s' : ' AND status = %s';
        $placeholders[] = 'wceb-' . $filters['status'];
        $i++;
    } else {
        $args .= ' WHERE status != %s';
        $placeholders[] = 'wceb-completed';
        $i++;
    }

    // Filter products.
    if ( isset( $filters['product_ids'] ) && is_numeric( $filters['product_ids'] ) ) {
        $args .= $i === 0 ? ' WHERE product_id = %d' : ' AND product_id = %d';
        $placeholders[] = $filters['product_ids'];
        $i++;
    }

    // Filter start date.
    if ( isset( $filters['start_date'] ) && wceb_is_valid_date( $filters['start_date'] ) ) {
        $args .= $i === 0 ? ' WHERE start = %s' : ' AND start = %s';
        $placeholders[] = $filters['start_date'];
        $i++;
    }

    // Filter end date.
    if ( isset( $filters['end_date'] ) && wceb_is_valid_date( $filters['end_date'] ) ) {
        $args .= $i === 0 ? ' WHERE end = %s' : ' AND end = %s';
        $placeholders[] = $filters['end_date'];
        $i++;
    }
    
    $valid_order_by = array( 'status', 'order_id', 'product_id', 'start', 'end' );

    // If no sort, default to order ID
    $orderby = isset( $filters['orderby'] ) && in_array( $filters['orderby'] , $valid_order_by ) ? $filters['orderby'] : 'order_id';

    // If no order (asc or desc), default to asc
    $order = isset( $filters['order'] ) && $filters['order'] === 'asc' ? 'ASC' : 'DESC';

    return array(
        'sql'          => $sql . $args,
        'args'         => $args,
        'sort'         => ' ORDER BY ' . $orderby . ' ' . $order,
        'placeholders' => $placeholders
    );

}

/**
*
* Get order bookings product IDs.
* @return array - $product_ids
*
**/
function wceb_get_order_bookings_product_ids() {
    global $wpdb;

    $product_ids = $wpdb->get_col(
        "
        SELECT product_id
        FROM {$wpdb->prefix}wceb_order_bookings
        "
    );

    return apply_filters( 'easy_booking_order_bookings_product_ids', $product_ids );
    
}