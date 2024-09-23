<?php

namespace EasyBooking;

/**
*
* Booking statuses.
* @version 3.3.2
*
**/

defined( 'ABSPATH' ) || exit;

class Booking_Statuses {

    public function __construct() {   

        // Schedule CRON event to automatically update booking statuses every day.
        if ( ! wp_next_scheduled( 'wceb_update_booking_statuses_event' ) ) {

            $ve = get_option( 'gmt_offset' ) > 0 ? '-' : '+';
            wp_schedule_event(  strtotime( '00:00 tomorrow ' . $ve . absint( get_option( 'gmt_offset' ) ) . ' HOURS' ), 'daily', 'wceb_update_booking_statuses_event' );

        }

        // Hook function to update booking statuses to CRON event.
        add_action( 'wceb_update_booking_statuses_event', array( $this, 'update_booking_statuses' ) );

        // Update booking statuses when saving Easy Booking settings.
        add_action( 'easy_booking_save_settings', array( $this, 'update_booking_statuses' ) ); 

    }

    /**
    *
    * Update order items booking statuses.
    *
    **/
    public function update_booking_statuses() {

        $order_bookings = wceb_get_order_bookings();

        if ( $order_bookings ) foreach ( $order_bookings as $booking ) :

            $booking_status = $booking->status;
            $item_status    = wceb_maybe_get_updated_booking_status( $booking->start, $booking->end, $booking_status );

            // Update order item status once it is defined (only when using the CRON function)
            if ( $booking_status !== $item_status ) {

                $item_id  = $booking->order_item_id;
                $_booking = wceb_get_order_booking( $item_id );

                if ( ! $_booking ) {
                    continue;
                }

                // Update order item meta
                wc_update_order_item_meta( $item_id, '_booking_status', sanitize_text_field( $item_status ) );

                // Update booking
                $_booking->set_status( $item_status );
                $_booking->save();

                $old_status = str_replace( 'wceb-', '', $booking_status );
                $new_status = str_replace( 'wceb-', '', $item_status );

                do_action( 'wceb_order_item_status_' . $new_status, $item_id );
                do_action( 'wceb_order_item_status_changed_from_' . $old_status . '_to_' . $new_status, $item_id );

            }

        endforeach;

        do_action( 'wceb_update_booking_statuses' );

    }

}

new Booking_Statuses();