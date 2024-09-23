<?php

/**
*
* Reports functions.
* @version 3.3.2
*
**/

defined( 'ABSPATH' ) || exit;

/**
*
* Get calendar report.
* We need an array of each date with corresponding bookings.
* @return array - $report
*
**/
function wceb_get_calendar_report() {

    $bookings = wceb_get_filtered_bookings( array( 'orderby' => 'start', 'order' => 'asc' ) );

    // If no booking, return empty array
    if ( empty( $bookings ) ) {
        return array();
    }

    $i = 1;
    $report = array();
    foreach ( $bookings as $index => $booking ) {

    	$start   = $booking->start;
        $end     = isset( $booking->end ) ? $booking->end : $start;

        // One date selection in nights mode: set end date to next day
        // Commented because I'm not sure whether to show them overnight or on a single day
        if ( get_option( 'wceb_booking_mode' ) === 'nights' && $start === $end ) {
            // $end = wceb_shift_date( $end, '1' );
        }

        /**
        * @param str  - start date
        * @param str  - end date
        * @param bool - Get dates in the past.
        **/
        $dates = wceb_get_dates_from_daterange( $start, $end, true );

        foreach ( $dates as $date ) {

            $is_start = $date === $start ? true : false;
            $is_end   = $date === $end ? true : false;

            if ( true === $is_start ) {

                if ( isset( $report[$date] ) ) {

                    // If booking starts, fill first empty index of date or add a new index
                    for ( $j = 1; $j <= array_key_last( $report[$date] ); $j++ ) {

                        if ( ! array_key_exists( $j, $report[$date] ) ) {
                            $i = $j;
                            break;
                        } else {
                            $i = $j+1;
                        }

                    }

                } else {
                    $i = 1;
                }
                
            }

            // Compatibility with imported booking from PRO version
            if ( ! isset( $booking->order_id ) ) {
                $booking->order_id = esc_html__( 'Imported booking', 'woocommerce-easy-booking-system' );
            }

            if ( ! isset( $booking->order_item_name ) && isset( $booking->product_id ) ) {
                $booking->order_item_name = get_the_title( $booking->product_id );
            }

            $report[$date][$i] = array( 
                esc_html( wp_strip_all_tags( '#' . $booking->order_id . ' - ' . $booking->qty . ' x ' . $booking->order_item_name ) ),
                $is_start, $is_end
            );

            // Re-index array
            ksort( $report[$date] );

        }

        $i++;

    }

    return $report;

}