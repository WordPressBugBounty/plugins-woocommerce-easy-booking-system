<?php

/**
 *
 * Order bookings functions.
 *
 * @version 3.3.3
 **/

defined( 'ABSPATH' ) || exit;

/**
 *
 * Get an order booking.
 *
 * @param int - $item_id
 * @return EasyBooking\Order_Booking | bool
 **/
function wceb_get_order_booking( $item_id ) {

	$booking = new EasyBooking\Order_Booking( $item_id );

	if ( ! $booking->exists() ) {
		return false;
	}

	return $booking;
}

/**
 *
 * Create or update an order booking.
 *
 * @param int -           $item_id
 * @param WC_Order_Item - $item
 * @param WC_Order -      $order
 * @return int|false
 **/
function wceb_create_or_update_order_booking( $item_id, $item, $order ) {

	if ( ! is_a( $item, 'WC_Order_Item_Product' ) || ! is_a( $order, 'WC_Order' ) ) {
		return false;
	}

	$booking = wceb_get_order_booking( $item_id );

	$start_date     = $item->get_meta( '_booking_start_date' );
	$end_date       = $item->get_meta( '_booking_end_date' );
	$booking_status = $item->get_meta( '_booking_status' );

	// No valid start date = no booking
	if ( ! wceb_is_valid_date( $start_date ) ) {

		// Maybe delete existing booking
		if ( $booking ) {
			wceb_delete_order_booking( $item_id ); }
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
		if ( $refunded_qty >= $qty ) {

			// Maybe delete existing booking
			if ( $booking ) {
				wceb_delete_order_booking( $item_id ); }
			return false;

		}

		$qty -= $refunded_qty;

	}

	// At this point, if booking doesn't exist we need to create it
	if ( ! $booking ) {
		$booking = new EasyBooking\Order_Booking( $item_id );
	}

	$result = $booking->set_props(
		array(
			'product_id' => $_product_id,
			'start'      => $start_date,
			'end'        => $end_date,
			'status'     => $booking_status,
			'qty'        => $qty,
			'order_id'   => $order_id,
		)
	);

	if ( is_wp_error( $result ) ) {

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			wc_get_logger()->error(
				sprintf( 'Error preparing booking: %s', $result->get_error_message() ),
				array( 'source' => 'easy-booking' )
			);
		}

		return false;

	}

	do_action( 'easy_booking_before_order_booking_save', $booking, $item );

	$update = $booking->save();

	if ( is_wp_error( $update ) ) {

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			wc_get_logger()->error(
				sprintf( 'Error saving booking: %s', $update->get_error_message() ),
				array( 'source' => 'easy-booking' )
			);
		}

		return false;

	}

	do_action( 'easy_booking_order_booking_saved', $update, $booking, $item );

	return $update;
}

/**
 *
 * Delete an order booking.
 *
 * @param int - $item_id
 * @return int|false
 **/
function wceb_delete_order_booking( $item_id ) {
	global $wpdb;

	// Keep the booking data available for callbacks after the row is deleted.
	$booking = wceb_get_order_booking( $item_id );

	do_action( 'easy_booking_before_order_booking_delete', $item_id, $booking );

	$delete = $wpdb->delete(
		$wpdb->prefix . 'wceb_order_bookings',
		array( 'order_item_id' => $item_id ),
		array( '%d' )
	);

	do_action( 'easy_booking_order_booking_deleted', $delete, $item_id, $booking );

	return $delete;
}

/**
 *
 * Delete all order bookings associated to an order.
 *
 * @param int - $order_id
 * @return int|false
 **/
function wceb_delete_order_bookings( $order_id ) {
	global $wpdb;

	// Order items may no longer be available after an order is deleted. Load the
	// booking data first so extensions can still update their related data.
	$bookings = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT *
			FROM {$wpdb->prefix}wceb_order_bookings
			WHERE order_id = %d",
			$order_id
		)
	);

	do_action( 'easy_booking_before_order_bookings_delete', $order_id, $bookings );

	$delete = $wpdb->delete(
		$wpdb->prefix . 'wceb_order_bookings',
		array( 'order_id' => $order_id ),
		array( '%d' )
	);

	do_action( 'easy_booking_order_bookings_deleted', $delete, $order_id, $bookings );

	return $delete;
}

/**
 *
 * Get order bookings product IDs.
 *
 * @return array - $product_ids
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
