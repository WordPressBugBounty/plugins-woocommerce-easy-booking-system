<?php

namespace EasyBooking;

/**
*
* Abstract Booking class.
* @version 3.3.3
*
**/

defined( 'ABSPATH' ) || exit;

abstract class Booking {

	protected $product_id;
	protected $start;
	protected $end;
	protected $status;
	protected $qty;

    public abstract function read();

    public abstract function save();

    public abstract function check_data();

    /*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
    *
    * Get booking prop.
    * @param int - $prop
    * @return mixed
    *
    **/
	public function get_prop( $prop ) {
		return $this->$prop;
	}

	/**
    *
    * Get booking product ID.
    * @return int
    *
    **/
	public function get_product_id() {
		return $this->get_prop( 'product_id' );
	}

	/**
    *
    * Get booking start date.
    * @return str
    *
    **/
	public function get_start() {
		return $this->get_prop( 'start' );
	}

	/**
    *
    * Get booking end date.
    * @return null | str
    *
    **/
	public function get_end() {
		return $this->get_prop( 'end' );
	}

	/**
    *
    * Get booking booking status.
    * @return str
    *
    **/
	public function get_status() {
		return $this->get_prop( 'status' );
	}

	/**
    *
    * Get booking quantity.
    * @return int
    *
    **/
	public function get_qty() {
		return $this->get_prop( 'qty' );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
    *
    * Set booking props.
    * @param array - $props
    *
    **/
	public function set_props( $props ) {

		foreach ( $props as $prop => $value ) {
			$this->set_prop( $prop, $value );
		}
	
	}

	/**
    *
    * Validate and set booking prop.
    * @param str - $prop
    * @param str - $value
    *
    **/
	public function set_prop( $prop, $value ) {

		$getter = "get_$prop";

		// Avoid setting the same value
		if ( $this->{$getter}( $prop ) === $value ) {
			return;
		}

		try {

			$checker = "check_$prop";

			if ( is_callable( array( $this, $checker ) ) ) {
				$this->{$checker}( $value );
			}

			$this->$prop = $value;

		} catch ( \Exception $e ) {

			// Set prop value to false (tweak for end date which can be null and valid)
			$this->$prop = false;

			return new \WP_Error(
				'easy_booking_error_setting_property',
				sprintf( 'Error setting booking property: %s', $e->getMessage(), 'easy-booking-pro' ),
				'error'
			);

		}

	}

	/**
    *
    * Set booking product ID.
    * @param int - $_product_id
    *
    **/
	public function set_product_id( $_product_id ) {
		$this->set_prop( 'product_id', $_product_id );
	}

	/**
    *
    * Set booking start date.
    * @param str - $start
    *
    **/
	public function set_start( $start ) {
		$this->set_prop( 'start', $start );
	}

	/**
    *
    * Set booking end date.
    * @param null | str - $end
    *
    **/
	public function set_end( $end ) {
		$this->set_prop( 'end', $end );
	}

	/**
    *
    * Set booking status.
    * @param str - $status
    *
    **/
	public function set_status( $status ) {
		$this->set_prop( 'status', $status );
	}

	/**
    *
    * Set booking qty.
    * @param int - $qty
    *
    **/
	public function set_qty( $qty ) {
		$this->set_prop( 'qty', $qty );
	}

	/*
	|--------------------------------------------------------------------------
	| Data validation
	|--------------------------------------------------------------------------
	*/

	/**
    *
    * Validate product ID.
    * @param int - $_product_id
    * @throws Exception
    *
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
    * @param str - $start
    * @throws Exception
    *
    **/
	public function check_start( $start ) {

		if ( ! wceb_is_valid_date( $start ) ) {
			throw new \Exception( __( 'Invalid start date.', 'woocommerce-easy-booking-system' ) );
		}

	}

	/**
    *
    * Validate end date.
    * @param null | str - $end
    * @throws Exception
    *
    **/
	public function check_end( $end ) {

		if ( ! is_null( $end ) && ! wceb_is_valid_date( $end ) ) {
			throw new \Exception( __( 'Invalid end date.', 'woocommerce-easy-booking-system' ) );
		}

	}

	/**
    *
    * Validate booking status.
    * @param str - $status
    * @throws Exception
    *
    **/
	public function check_status( $status ) {

		$valid_booking_statuses = array( 'wceb-pending', 'wceb-start', 'wceb-processing', 'wceb-end', 'wceb-completed' );

		if ( ! in_array( $status, $valid_booking_statuses ) ) {
			throw new \Exception( __( 'Invalid booking status.', 'woocommerce-easy-booking-system' ) );
		}

	}

	/**
    *
    * Validate quantity.
    * @param int - $qty
    * @throws Exception
    *
    **/
	public function check_qty( $qty ) {

		if ( $qty <= 0 ) {
			throw new \Exception( __( 'Invalid quantity.', 'woocommerce-easy-booking-system' ) );
		}

	}

}