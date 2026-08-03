<?php

namespace EasyBooking;

/**
*
* Abstract Booking class.
 *
* @version 3.4.3
*/

// phpcs:disable  WordPress.Security.EscapeOutput.ExceptionNotEscaped

defined( 'ABSPATH' ) || exit;

abstract class Booking {

	protected $product_id;
	protected $start;
	protected $end;
	protected $status;
	protected $qty;

	abstract public function read();

	abstract public function save();

	abstract public function check_data();

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 *
	 * Get booking prop.
	 *
	 * @param int - $prop
	 * @return mixed
	 **/
	public function get_prop( $prop ) {
		return $this->$prop;
	}

	/**
	 *
	 * Get booking product ID.
	 *
	 * @return int
	 **/
	public function get_product_id() {
		return $this->get_prop( 'product_id' );
	}

	/**
	 *
	 * Get booking start date.
	 *
	 * @return str
	 **/
	public function get_start() {
		return $this->get_prop( 'start' );
	}

	/**
	 *
	 * Get booking end date.
	 *
	 * @return null | str
	 **/
	public function get_end() {
		return $this->get_prop( 'end' );
	}

	/**
	 *
	 * Get booking booking status.
	 *
	 * @return str
	 **/
	public function get_status() {
		return $this->get_prop( 'status' );
	}

	/**
	 *
	 * Get booking quantity.
	 *
	 * @return int
	 **/
	public function get_qty() {
		return $this->get_prop( 'qty' );
	}

	/**
	 * Get the dates occupied by the booking.
	 *
	 * The selected start and end dates are never changed. In Days mode, both
	 * dates are occupied. In Nights mode, the end date is the departure date
	 * and is therefore not occupied.
	 *
	 * @return string[]
	 */
	public function get_occupied_dates() {

		$start = $this->get_start();
		$end   = $this->get_end();

		if ( ! wceb_is_valid_date( $start ) ) {
			return array();
		}

		if ( empty( $end ) ) {
			$end = $start;
		} elseif ( ! wceb_is_valid_date( $end ) ) {
			$end = $start;
		} elseif ( 'nights' === get_option( 'wceb_booking_mode' ) ) {
			$end = wceb_shift_date( $end, 1, 'minus' );
		}

		$period = apply_filters(
			'easy_booking_booking_occupied_period',
			array(
				'start' => $start,
				'end'   => $end,
			),
			$this
		);

		if ( ! is_array( $period ) || ! isset( $period['start'], $period['end'] ) ) {
			return array();
		}

		// Avoid another validation if dates have not changed.
		$valid_start = $start === $period['start'] || wceb_is_valid_date( $period['start'] );
		$valid_end   = $end === $period['end'] || wceb_is_valid_date( $period['end'] );

		if ( ! $valid_start || ! $valid_end || $period['end'] < $period['start'] ) {
			return array();
		}

		return wceb_get_dates_from_daterange( $period['start'], $period['end'], true );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 *
	 * Set booking props.
	 *
	 * @param array - $props
	 * @return true|\WP_Error
	 **/
	public function set_props( $props ) {

		$error = null;

		foreach ( $props as $prop => $value ) {

			$result = $this->set_prop( $prop, $value );

			if ( is_wp_error( $result ) && ! $error ) {
				$error = $result;
			}
		}

		return $error ?: true;
	}

	/**
	 *
	 * Validate and set booking prop.
	 *
	 * @param str - $prop
	 * @param str - $value
	 * @return true|\WP_Error
	 **/
	public function set_prop( $prop, $value ) {

		$getter = "get_$prop";

		if ( ! property_exists( $this, $prop ) || ! is_callable( array( $this, $getter ) ) ) {
			return new \WP_Error(
				'easy_booking_invalid_booking_property',
				sprintf(
					/* translators: %s: Booking property name. */
					esc_html__( 'Invalid booking property: %s', 'woocommerce-easy-booking-system' ),
					esc_html( $prop )
				)
			);
		}

		// Avoid setting the same value
		if ( $this->{$getter}( $prop ) === $value ) {
			return true;
		}

		try {

			$checker = "check_$prop";

			if ( is_callable( array( $this, $checker ) ) ) {
				$this->{$checker}( $value );
			}

			$this->$prop = $value;

		} catch ( \Exception $e ) {

			return new \WP_Error(
				'easy_booking_error_setting_property',
				sprintf(
					// translators: %s is error message.
					esc_html__( 'Error setting booking property: %s', 'woocommerce-easy-booking-system' ),
					esc_html( $e->getMessage() )
				),
				'error'
			);

		}

		return true;
	}

	/**
	 *
	 * Set booking product ID.
	 *
	 * @param int - $_product_id
	 * @return true|\WP_Error
	 **/
	public function set_product_id( $_product_id ) {
		return $this->set_prop( 'product_id', $_product_id );
	}

	/**
	 *
	 * Set booking start date.
	 *
	 * @param str - $start
	 * @return true|\WP_Error
	 **/
	public function set_start( $start ) {
		return $this->set_prop( 'start', $start );
	}

	/**
	 *
	 * Set booking end date.
	 *
	 * @param null | str - $end
	 * @return true|\WP_Error
	 **/
	public function set_end( $end ) {
		return $this->set_prop( 'end', $end );
	}

	/**
	 *
	 * Set booking status.
	 *
	 * @param str - $status
	 * @return true|\WP_Error
	 **/
	public function set_status( $status ) {
		return $this->set_prop( 'status', $status );
	}

	/**
	 *
	 * Set booking qty.
	 *
	 * @param int - $qty
	 * @return true|\WP_Error
	 **/
	public function set_qty( $qty ) {
		return $this->set_prop( 'qty', $qty );
	}

	/*
	|--------------------------------------------------------------------------
	| Data validation
	|--------------------------------------------------------------------------
	*/

	/**
	 *
	 * Validate product ID.
	 *
	 * @param int - $_product_id
	 * @throws Exception
	 **/
	public function check_product_id( $_product_id ) {

		$product = wc_get_product( $_product_id );

		if ( ! $product ) {
			throw new \Exception( __( 'Invalid product ID.', 'woocommerce-easy-booking-system' ) );
		}
	}

	/**
	 *
	 * Validate start date.
	 *
	 * @param str - $start
	 * @throws Exception
	 **/
	public function check_start( $start ) {

		if ( ! wceb_is_valid_date( $start ) ) {
			throw new \Exception( __( 'Invalid start date.', 'woocommerce-easy-booking-system' ) );
		}
	}

	/**
	 *
	 * Validate end date.
	 *
	 * @param null | str - $end
	 * @throws Exception
	 **/
	public function check_end( $end ) {

		if ( ! is_null( $end ) && ! wceb_is_valid_date( $end ) ) {
			throw new \Exception( __( 'Invalid end date.', 'woocommerce-easy-booking-system' ) );
		}
	}

	/**
	 *
	 * Validate booking status.
	 *
	 * @param str - $status
	 * @throws Exception
	 **/
	public function check_status( $status ) {

		$valid_booking_statuses = array( 'wceb-pending', 'wceb-start', 'wceb-processing', 'wceb-end', 'wceb-completed' );

		if ( ! in_array( $status, $valid_booking_statuses, true ) ) {
			throw new \Exception( __( 'Invalid booking status.', 'woocommerce-easy-booking-system' ) );
		}
	}

	/**
	 *
	 * Validate quantity.
	 *
	 * @param int - $qty
	 * @throws Exception
	 **/
	public function check_qty( $qty ) {

		if ( ! is_numeric( $qty ) ) {
			throw new \Exception( __( 'Invalid quantity.', 'woocommerce-easy-booking-system' ) );
		}

		if ( ! apply_filters( 'easy_booking_allow_negative_qty_in_imports', false ) && $qty <= 0 ) {
			throw new \Exception( __( 'Invalid quantity.', 'woocommerce-easy-booking-system' ) );
		}
	}
}
