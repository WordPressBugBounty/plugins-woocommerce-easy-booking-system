<?php

namespace EasyBooking;

/**
*
* Checkout action hooks and filters.
* @version 3.3.0
*
**/

defined( 'ABSPATH' ) || exit;

class Checkout {

    public function __construct() {

        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'add_order_item_booking_data' ), 10, 4 );
        add_action( 'woocommerce_order_status_changed', array( $this, 'create_or_delete_order_bookings_on_order_status_change' ), 10, 4 );
        add_filter( 'woocommerce_display_item_meta', array( $this, 'display_booking_dates_in_checkout' ), 10, 3 );

    }

    /**
    *
    * Add booking dates and status to the order item meta data.
    * @param int - $item_id
    * @param str - $cart_item_key
    * @param array - $values
    * @param WC_Order - $order
    *
    **/
    public function add_order_item_booking_data( $item, $cart_item_key, $values, $order ) {

        if ( ! empty( $values['_booking_start_date'] ) ) {

            // Start date format yyyy-mm-dd
            $start          = sanitize_text_field( $values['_booking_start_date'] );
            $end            = empty( $values['_booking_end_date'] ) ? null : sanitize_text_field( $values['_booking_end_date'] );
            $booking_status = wceb_get_booking_status( $start, $end );
            
            $item->add_meta_data( '_booking_start_date', $start );
            $item->add_meta_data( '_booking_end_date', $end );
            $item->add_meta_data( '_booking_status', sanitize_text_field( $booking_status ) );

            do_action( 'easy_booking_add_order_item_booking_data', $item, $start, $end );

        }

    }

    /**
    *
    * Create or delete bookings when changing order status.
    * Some order statuses are not used by Easy Booking (e.g. cancelled) so we need to manage associated bookings.
    * @param int - $order_id
    * @param str - $from
    * @param to - $to
    * @param WC_Order - $order
    *
    **/
    public function create_or_delete_order_bookings_on_order_status_change( $order_id, $from, $to, $order ) {

        $order_statuses = wceb_get_valid_order_statuses();

        // Order status not tracked by Easy Booking, delete bookings associated with this order
        if ( ! in_array( 'wc-' . $to, $order_statuses ) ) {
            wceb_delete_order_bookings( $order_id );
            return;
        }

        // Otherwise create or updates bookings associated with this order
        if ( $order->get_items() ) foreach ( $order->get_items() as $item_id => $item ) {
            wceb_create_or_update_order_booking( $item_id, $item, $order );
        }

    }

    /**
    *
    * Display order item localized booking dates in checkout.
    * @param str - $html
    * @param WC_Order_Item - $order_item
    * @param array - $args
    * @return str - $html
    *
    **/
    public function display_booking_dates_in_checkout( $html, $item, $args ) {

        $product   = $item->get_product();
        $strings   = array();
        $meta_data = array();
        
        // Add class for styling
        $args = wp_parse_args(
            array( 'before' => '<ul class="wc-item-meta wceb-item-meta"><li>' ),
            $args
        );

        $start = $item->get_meta( '_booking_start_date' );

        if ( isset( $start ) && ! empty( $start ) ) {

            $meta_data[] = array(
                'display_key'   => wceb_get_start_text( $product ),
                'display_value' => date_i18n( get_option( 'date_format' ), strtotime( $start ) )
            );

        }

        $end = $item->get_meta( '_booking_end_date' );

        if ( isset( $end ) && ! empty( $end ) ) {

            $meta_data[] = array(
                'display_key'   => wceb_get_end_text( $product ),
                'display_value' => date_i18n( get_option( 'date_format' ), strtotime( $end ) )
            );

        }

        foreach ( apply_filters( 'easy_booking_display_item_meta', $meta_data, $item ) as $index => $meta ) {
            $value     = $args['autop'] ? wp_kses_post( $meta['display_value'] ) : wp_kses_post( make_clickable( trim( $meta['display_value'] ) ) );
            $strings[] = $args['label_before'] . wp_kses_post( $meta['display_key'] ) . $args['label_after'] . $value;
        }

        if ( $strings ) {
            $html .= $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
        }

        return $html;

    }

}

new Checkout();