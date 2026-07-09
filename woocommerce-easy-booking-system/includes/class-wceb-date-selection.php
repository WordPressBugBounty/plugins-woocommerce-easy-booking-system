<?php

namespace EasyBooking;

/**
*
* Date selection.
 *
* @version 3.4.7
*/

defined( 'ABSPATH' ) || exit;

class Date_Selection {

	/**
	 *
	 * Creates a fresh nonce to avoid cache issues.
	 *
	 * @return WP_REST_Response
	 **/
	public static function get_date_selection_nonce() {

		// Prevent caching
		nocache_headers();

		return new \WP_REST_Response( array( 'nonce' => wp_create_nonce( '_wceb_nonce' ) ), 200 );
	}
	/**
	 *
	 * Check nonce.
	 *
	 * @param WP_REST_Request
	 * @return mixed WP_Error | bool
	 **/
	public static function check_date_selection_nonce( \WP_REST_Request $request ) {

		$data = $request->get_json_params();

		$nonce = isset( $data['security'] ) ? $data['security'] : '';

		if ( ! wp_verify_nonce( $nonce, '_wceb_nonce' ) ) {
			return new \WP_Error( 'rest_forbidden', esc_html__( 'Something went wrong. Please refresh the page and try again.' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 *
	 * Validate data, calculate booking price and update page details.
	 *
	 * @param WP_REST_Request
	 **/
	public static function handle_date_selection( \WP_REST_Request $request ) {

		try {

			$data = self::sanitize_booking_data( $request->get_json_params() );

		} catch ( \Exception $e ) {

			return new \WP_REST_Response( array( 'error' => esc_html( $e->getMessage() ) ), 400 );

		}

		// Get booking data for each product type (price, regular price, children data for grouped and bundle products)
		$booking_data = Date_Selection_Helper::{'get_' . $data['product']->get_type() . '_product_booking_data'}( $data );

		try {

			// Return details to frontend
			$fragments = self::get_success_fragments( $data['id'], $booking_data );
			return new \WP_REST_Response(
				array(
					'success'   => true,
					'fragments' => $fragments,
				),
				200
			);

		} catch ( \Exception $e ) {

			// Or return error
			return new \WP_REST_Response( array( 'error' => esc_html( $e->getMessage() ) ), 400 );

		}
	}

	/**
	 * Sanitize and prepare booking data from the request.
	 *
	 * @param WP_REST_Request $request The REST request object.
	 * @return array {
	 *     @type int    $id           The product or variation ID to use.
	 *     @type object $product      The product object.
	 *     @type object $_product     The product or variation object.
	 *     @type int[]  $children     Array of child product IDs.
	 *     @type int    $quantity     The product quantity.
	 *     @type string $start        The start date in 'yyyy-mm-dd' format.
	 *     @type string $end          The end date in 'yyyy-mm-dd' format.
	 *     @type int    $duration     The booking duration.
	 * }
	 */
	private static function sanitize_booking_data( $data ) {

		// Sanitize and set default values
		$product_id   = absint( $data['product_id'] ?? 0 );
		$variation_id = absint( $data['variation_id'] ?? 0 );
		$children     = array_map( 'absint', $data['children'] ?? array() );
		$quantity     = absint( $data['quantity'] ?? 1 );
		$id           = ! empty( $variation_id ) ? $variation_id : $product_id;
		$start        = sanitize_text_field( $data['start_format'] ?? '' );
		$end          = sanitize_text_field( $data['end_format'] ?? '' );

		// Fetch product objects
		$product  = wc_get_product( $product_id );
		$_product = ( $product_id !== $id ) ? wc_get_product( $id ) : $product;

		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		// Make sure product exists and that it is bookable
		if ( ! $product || ! $_product || ! wceb_is_bookable( $_product ) ) {
			throw new \Exception( __( 'Please select a bookable product.', 'woocommerce-easy-booking-system' ) );
		}

		// If product is variable and no variation was selected
		if ( $product->is_type( 'variable' ) && empty( $variation_id ) ) {
			throw new \Exception( __( 'Please select a product option.', 'woocommerce-easy-booking-system' ) );
		}

		// If product is grouped and no quantity was selected for grouped products
		if ( $product->is_type( 'grouped' ) && empty( $children ) ) {
			throw new \Exception( __( 'Please choose the quantity of items you wish to add to your cart&hellip;', 'woocommerce' ) );
		}

		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		Date_Selection_Helper::check_selected_dates( $start, $end, $_product );

		$duration = Date_Selection_Helper::get_selected_booking_duration( $start, $end, $_product );

		return apply_filters(
			'easy_booking_sanitized_booking_data',
			array(
				'id'       => $id,
				'product'  => $product,
				'_product' => $_product,
				'children' => $children,
				'quantity' => $quantity,
				'start'    => $start,
				'end'      => $end,
				'duration' => $duration,
			),
			$data
		);
	}

	/**
	 *
	 * Get booking price details.
	 *
	 * @param WC_Product - $product
	 * @param array -      $booking_data
	 * @param str -        $price_type
	 * @return str - $details
	 **/
	private static function get_booking_price_details( $product, $data, $new_price ) {

		$details = '';

		if ( wceb_get_product_booking_dates( $product ) === 'two' ) {

			$average_price = floatval( $new_price / $data['duration'] );
			$booking_mode  = get_option( 'wceb_booking_mode' );

			// Get total booking duration (multiply selected duration by product booking duration)
			$booking_duration = wceb_get_product_booking_duration( $product );
			$duration         = $data['duration'] * $booking_duration;

			$unit = $booking_mode === 'nights' ? _n( 'night', 'nights', $duration, 'woocommerce-easy-booking-system' ) : _n( 'day', 'days', $duration, 'woocommerce-easy-booking-system' );

			$details .= '<p>';

			$details .= apply_filters(
				'easy_booking_total_booking_duration_text',
				sprintf(
					__( 'Total booking duration: %1$s %2$s', 'woocommerce-easy-booking-system' ),
					absint( $duration ),
					esc_html( $unit )
				),
				$duration,
				$unit
			);

			$details .= '</p>';

			// Maybe display average price (if there are price variations. E.g Duration discounts or custom pricing)
			if ( true === apply_filters( 'easy_booking_display_average_price', false, $product->get_id() ) ) {

				$details .= '<p>';

				$details .= apply_filters(
					'easy_booking_average_price_text',
					sprintf(
						__( 'Average price %1$s: %2$s', 'woocommerce-easy-booking-system' ),
						wceb_get_product_price_suffix( $product ),
						wc_price( $average_price )
					),
					$product,
					$average_price
				);

				$details .= '</p>';

			}
		}

		return apply_filters( 'easy_booking_booking_price_details', $details, $product, $data );
	}

	private static function get_price_to_display( $price, $type, $qty, $_product_id, $data ) {

		$args = array(
			'price' => apply_filters( 'easy_booking_new_' . $type . '_to_display', $price, $_product_id, $data ),
			'qty'   => $qty,
		);

		return wc_get_price_to_display( $data['product'], $args );
	}

	/**
	 *
	 * Return fragments after booking session is successfully set.
	 *
	 * @param int -   $id - Product or variation ID
	 * @param array - $booking_data
	 **/
	private static function get_success_fragments( $id, $booking_data ) {

		// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		// Check that booking data is set for the parent/main product.
		if ( ! isset( $booking_data[ $id ] ) ) {
			throw new \Exception( __( 'Sorry there was a problem. Please try again.', 'woocommerce-easy-booking-system' ) );
		}

		// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

		$new_price         = 0;
		$new_regular_price = 0;

		/*
		* Get total booking price and regular price.
		* Include children prices for grouped and bundle products.
		* Multiply by quantity.
		*/
		foreach ( $booking_data as $_product_id => $data ) {

			// Tweak for bundles, because quantity field is for the whole bundle.
			$bundle_qty = $data['product']->is_type( 'bundle' ) && ! $data['_product']->is_type( 'bundle' ) ? $booking_data[ $id ]['quantity'] : 1;

			$qty = apply_filters( 'easy_booking_selected_quantity', $data['quantity'] * $bundle_qty, $_product_id, $data );

			$price_to_display = self::get_price_to_display( $data['new_price'], 'price', $qty, $_product_id, $data );

			$regular_price_to_display = isset( $data['new_regular_price'] ) ?
					self::get_price_to_display( $data['new_regular_price'], 'regular_price', $qty, $_product_id, $data ) :
					$price_to_display;

			$new_price         += $price_to_display;
			$new_regular_price += $regular_price_to_display;

		}

		// Get booking details.
		$details = self::get_booking_price_details( $booking_data[ $id ]['_product'], $booking_data[ $id ], $new_price );

		$fragments = array(
			'booking_price'         => esc_attr( $new_price ),
			'booking_regular_price' => ( $new_regular_price != $new_price ) ? esc_attr( $new_regular_price ) : '',
			'div.booking_details'   => '<div class="booking_details">' . wp_kses_post( $details ) . '</div>',
		);

		return apply_filters( 'easy_booking_fragments', $fragments, $booking_data, $booking_data[ $id ]['product'] );
	}
}
