<?php

namespace EasyBooking;

/**
*
* Orders action hooks.
* @version 3.3.4
*
**/

defined( 'ABSPATH' ) || exit;

class Order {

    public function __construct() {

        add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_order_item_booking_data' ), 10, 1 );
        add_action( 'woocommerce_before_order_itemmeta', array( $this, 'display_order_item_booking_data' ), 10, 3 );
        add_action( 'woocommerce_before_order_item_object_save', array( $this, 'check_booking_dates' ), 10, 1 );
        add_action( 'woocommerce_after_order_item_object_save', array( $this, 'create_or_update_order_booking' ), 10, 1 );
        add_action( 'woocommerce_delete_order_item', array( $this, 'delete_order_booking' ), 10, 1 );
        add_action( 'woocommerce_order_partially_refunded', array( $this, 'update_or_delete_order_bookings_after_refund' ), 10, 2 );
        add_action( 'woocommerce_delete_order_items', array( $this, 'delete_order_bookings_after_order_delete' ), 10, 1 );
        add_action( 'woocommerce_refund_deleted', array( $this, 'update_order_booking_after_refund_delete' ), 10, 2 );

    }
    
    /**
    *
    * Hide dates on order pages (to display a custom form instead).
    *
    * @param array - $item_meta - Hidden values
    * @return array - $item_meta
    *
    **/
    public function hide_order_item_booking_data( $item_meta ) {

        $item_meta[] = '_booking_start_date';
        $item_meta[] = '_booking_end_date';
        $item_meta[] = '_booking_status';

        return $item_meta;

    }

    /**
    *
    * Display dates and a datepicker form on order pages.
    *
    * @param int - $item_id
    * @param WC_Order_Item - $item
    * @param WC_Product | WC_Product_Variation - $product
    *
    **/
    public function display_order_item_booking_data( $item_id, $item, $product ) {
        global $wpdb;

        if ( ! $product || is_null( $product ) ) {
            return;
        }

        $start_date     = wc_get_order_item_meta( $item_id, '_booking_start_date' );
        $end_date       = wc_get_order_item_meta( $item_id, '_booking_end_date' );
        $booking_status = wc_get_order_item_meta( $item_id, '_booking_status' );

        $start_date_text = wceb_get_start_text( $product );
        $end_date_text   = wceb_get_end_text( $product );

        $item_order_meta_table = $wpdb->prefix . 'woocommerce_order_itemmeta';
        
        if ( ! empty( $start_date ) ) {

            // Localized start date
            $start_date_i18n = date_i18n( get_option( 'date_format' ), strtotime( $start_date ) );

            // Localized end date
            if ( ! empty( $end_date ) ) {
                $end_date_i18n = date_i18n( get_option( 'date_format' ), strtotime( $end_date ) );
            }

            include( 'views/order-items/html-wceb-order-item-meta.php' );

        }

        if ( wceb_is_bookable( $product ) ) {

            $meta_array = array(
                'start_date_meta_id'     => '_booking_start_date',
                'end_date_meta_id'       => '_booking_end_date',
                'booking_status_meta_id' => '_booking_status'
            );

            foreach ( $meta_array as $var => $meta_name ) {

                // Check if there's already an entry in the database.
                ${$var} = $wpdb->get_var( $wpdb->prepare(
                    "SELECT `meta_id` FROM $item_order_meta_table WHERE `order_item_id` = %d AND `meta_key` LIKE %s",
                    $item_id, $meta_name
                ));

                // Otherwise create order item meta.
                if ( is_null( ${$var} ) ) {
                    ${$var} = wc_add_order_item_meta( $item_id, $meta_name, '' );
                }

            }

            include( 'views/order-items/html-wceb-edit-order-item-meta.php' );

        }

    }

    /**
    *
    * Check booking dates before saving order item and maybe update booking status.
    *
    * @param WC_Order_Item - $item
    *
    **/
    public function check_booking_dates( $item ) {

        // Make sure it is a product
        if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
            return false;
        }

        $_product   = $item->get_product();
        $start_date = $item->get_meta( '_booking_start_date' );
        $end_date   = $item->get_meta( '_booking_end_date' );

        $valid_dates = Date_Selection::check_selected_dates( $start_date, $end_date, $_product, false );

        if ( is_wp_error( $valid_dates ) ) {

            $item->delete_meta_data( '_booking_start_date' );
            $item->delete_meta_data( '_booking_end_date' );
            $item->delete_meta_data( '_booking_status' );

            return false;

        }

        $this->maybe_update_item_booking_status( $item, $start_date, $end_date );

    }

    /**
    *
    * Maybe update order item booking status.
    *
    * @param WC_Order_Item - $item
    * @param str - $start_date
    * @param str - $end_date
    *
    **/
    public function maybe_update_item_booking_status( $item, $start_date, $end_date ) {
        
        $item_status    = $item->get_meta( '_booking_status' );
        $booking_status = wceb_maybe_get_updated_booking_status( $start_date, $end_date, $item_status );

        $item->update_meta_data( '_booking_status', sanitize_text_field( $booking_status ) );

    }

    /**
    *
    * Create or update a booking when the associated order item is saved.
    * @param WC_Order_Item - $item
    *
    **/
    public function create_or_update_order_booking( $item ) {

        // Make sure it is a product
        if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
            return false;
        }

        $item_id = $item->get_id();
        $order   = $item->get_order();

        wceb_create_or_update_order_booking( $item_id, $item, $order );

    }

    /**
    *
    * Delete a booking when the associated order item is deleted.
    * @param int - $item_id
    *
    **/
    public function delete_order_booking( $item_id ) {
        wceb_delete_order_booking( $item_id );
    }

    /**
    *
    * Update or delete a booking after refunding the associated order item.
    * @param int - $order_id
    * @param int - $refund_id
    *
    **/
    public function update_or_delete_order_bookings_after_refund( $order_id, $refund_id ) {

        $order = wc_get_order( $order_id );

        if ( $order->get_items() ) foreach ( $order->get_items() as $item_id => $item ) {

            // Check item refunds.
            $refunded_qty = abs( $order->get_qty_refunded_for_item( $item_id ) );

            // Item was not refunded
            if ( ! $refunded_qty ) {
                continue;
            }
            
            if ( $item->get_quantity() === $refunded_qty ) {

                // Item was fully refunded, delete booking
                wceb_delete_order_booking( $item_id );

            } else {

                // Item was partially refunded, update booking
                wceb_create_or_update_order_booking( $item_id, $item, $order );

            }

        }

    }

    /**
    *
    * Delete bookings associated to an order after deleting it.
    * @param int - $order_id
    *
    **/
    public function delete_order_bookings_after_order_delete( $order_id ) {
        wceb_delete_order_bookings( $order_id );
    }

    /**
    *
    * Re-create or update a booking after deleting a refund.
    * @param int - $refund_id
    * @param int - $order_id
    *
    **/
    public function update_order_booking_after_refund_delete( $refund_id, $order_id ) {

        $order = wc_get_order( $order_id );

        if ( $order->get_items() ) foreach ( $order->get_items() as $item_id => $item ) {
            wceb_create_or_update_order_booking( $item_id, $item, $order );
        }

    }

}

new Order();