<?php

/**
*
* Core functions.
* @version 3.3.3
*
**/

defined( 'ABSPATH' ) || exit;

/**
*
* Query orders with the right statuses.
* @param bool - $past - False to get only "processing" orders, true to get all orders.
* @return array
*
**/
function wceb_get_orders( $past = true ) {

    $args = array(
        'status' => wceb_get_valid_order_statuses(),
        'limit'  => -1,
        'return' => 'ids'
    );

    // If $past is false, get only processing orders
    if ( ! $past ) {

        $args['meta_query'] = array(
            'key'   => 'order_booking_status',
            'value' => 'processing'
        );

    }

    $query_orders = wc_get_orders( $args );

    return $query_orders;

}

/**
*
* Get current booking status depending on start and/or end date.
* @param str - $start
* @param null | str - $end
* @return str - $booking_status
*
**/
function wceb_get_booking_status( $start, $end = null ) {

    // Default = Pending status
    $item_status = 'wceb-pending';

    // Get current date
    $current_date = strtotime( date( 'Y-m-d' ) );
    
    // Start status is set x days before start date (defined in the plugin settings)
    $change_start_day     = get_option( 'wceb_keep_start_status_for' );
    $change_start_status  = strtotime( $start . ' -' . $change_start_day . ' days' );

    $change_completed_day = get_option( 'wceb_keep_end_status_for' );

    if ( $current_date >= $change_start_status ) {
        $item_status = 'wceb-start';
    }

    $start_time = strtotime( $start );

    if ( ! empty( $end ) && ! is_null( $end ) ) {

        $end_time = strtotime( $end );

        // Automatically change to Processing status between start and end dates
        if ( $current_date > $start_time && $current_date < $end_time ) {
            $item_status = 'wceb-processing';
        }

        // Automatically change to End status on end date
        if ( $current_date >= $end_time ) {
            $item_status = 'wceb-end';
        }

        // If start and end dates are the same day, set Processing instead of Start/End
        if ( $start_time === $end_time && $current_date === $start_time && $current_date === $end_time ) {
            $item_status = 'wceb-processing';
        }

        // Completed status is set x days after end date (defined in the plugin settings)
        $change_completed_status = strtotime( $end . ' +' . $change_completed_day . ' days' );

    } else {

        if ( $current_date >= $start_time ) {
            $item_status = 'wceb-processing';
        }

        // Change to End status after date
        if ( $current_date > $start_time ) {
            $item_status = 'wceb-end';
        }

        // Completed status is set x days after start date (defined in the plugin settings)
        $change_completed_status = strtotime( $start . ' +' . $change_completed_day . ' days' );

    }

    if ( $current_date > $change_completed_status ) {
        $item_status = 'wceb-completed';
    }

    return $item_status;

}

/**
*
* Maybe get new booking status depending on start and/or end date.
* @param str - $start
* @param null | str - $end
* @param str - $item_status - Current booking status
* @return str - $item_status
*
**/
function wceb_maybe_get_updated_booking_status( $start, $end, $item_status = 'wceb-pending' ) {

    // Get booking status corresponding to selected dates
    $booking_status = wceb_get_booking_status( $start, $end );

    // Maybe automatically change status depending on plugin settings
    if ( $item_status !== $booking_status ) {

        if ( $booking_status === 'wceb-pending' && get_option( 'wceb_set_start_booking_status' ) === 'automatic' ) {
            $item_status = $booking_status;
        }

        $status_name = str_replace( 'wceb-', '', $booking_status );

        if ( get_option( 'wceb_set_' . $status_name . '_booking_status' ) === 'automatic' ) {
            $item_status = $booking_status;
        }

    }

    return $item_status;

}

/**
*
* Get order statuses taken into account by Easy Booking.
* @return array - $order_statuses
*
**/
function wceb_get_valid_order_statuses() {

    $order_statuses = apply_filters( 
        'easy_booking_get_order_statuses',
        array(
            'wc-pending',
            'wc-processing',
            'wc-on-hold',
            'wc-completed',
            'wc-refunded'
    ) );

    return $order_statuses;
    
}