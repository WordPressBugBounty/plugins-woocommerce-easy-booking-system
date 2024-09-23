<?php

namespace EasyBooking;

/**
*
* Order booking object.
* @version 3.3.2
*
**/

defined( 'ABSPATH' ) || exit;

class Order_Booking extends Booking {

	protected $order_item_id = 0;
	protected $order_id;

    /**
    *
	* @param int | EasyBooking\Order_Booking | object $booking.
	* 
	**/
	public function __construct( $booking ) {

		if ( is_numeric( $booking ) && $booking > 0 ) {
			$this->set_order_item_id( $booking );
		} else if ( ! empty( $booking->order_item_id ) ) {
			$this->set_order_item_id( absint( $booking->order_item_id ) );
		}

		if ( $this->get_order_item_id() > 0 ) {
			$this->read();
		}

    }

    /**
    *
    * Maybe read booking from DB.
    *
    **/
    public function read() {
    	global $wpdb;

		$order_item_id = $this->get_order_item_id();

		$data = $wpdb->get_row( $wpdb->prepare(
			"SELECT *
			FROM {$wpdb->prefix}wceb_order_bookings
			WHERE order_item_id = %d
			LIMIT 1
			",
			$order_item_id
		) );

		if ( ! $data ) {
			return false;
		}

		$this->set_props( array(
	        'product_id' => $data->product_id,
	        'start'      => $data->start,
	        'end'        => $data->end,
	        'status'     => $data->status,
	        'qty'        => $data->qty,
	        'order_id'   => $data->order_id
	    ) );

    }

    /**
    *
    * Save booking in DB.
    *
    **/
    public function save() {
    	global $wpdb;

	    try {

	    	$data = $this->check_data();

	    	$save = $wpdb->replace( 
	            $wpdb->prefix . 'wceb_order_bookings', 
	            $data,
	            array( '%d', '%d', '%s', '%s', '%s', '%d', '%d' )
	        );
	        
	        return $save;

    	} catch ( \Exception $e ) {

    		return new \WP_Error(
				'easy_booking_error_saving_booking',
				sprintf( 'Error saving booking: %s', $e->getMessage(), 'woocommerce-easy-booking-system' ),
				'error'
			);

	    }

    }

    /**
    *
    * Validate booking data.
    * @return array
    *
    **/
    public function check_data() {

    	$data = array( 
        	'order_item_id' => $this->get_order_item_id(),
            'product_id'    => $this->get_product_id(), 
            'start'         => $this->get_start(),
            'end'           => $this->get_end(),
            'status'        => $this->get_status(),
            'qty'           => $this->get_qty(),
            'order_id'      => $this->get_order_id()
        );

        foreach ( $data as $prop => $value ) {
			$this->{"check_$prop"}( $value );
        }

        return $data;

    } 

    /*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
    *
    * Get booking order item ID.
    * @return int
    *
    **/
	public function get_order_item_id() {
		return $this->get_prop( 'order_item_id' );
	}

	/**
    *
    * Get booking order ID.
    * @return int
    *
    **/
	public function get_order_id() {
		return $this->get_prop( 'order_id' );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
    *
    * Set booking order item ID.
    * @param int - $id
    *
    **/
	public function set_order_item_id( $id ) {
		$this->set_prop( 'order_item_id', $id );
	}

	/**
    *
    * Set booking order ID.
    * @param int - $order_id
    *
    **/
	public function set_order_id( $order_id ) {
		$this->set_prop( 'order_id', $order_id );
	}

	/*
	|--------------------------------------------------------------------------
	| Data validation
	|--------------------------------------------------------------------------
	*/

	/**
    *
    * Validate order item ID.
    * @param int - $id
    * @throws Exception
    *
    **/
	public function check_order_item_id( $id ) {

		$item = \WC_Order_Factory::get_order_item( absint( $id ) );

		if ( ! $item ) {
			throw new \Exception( __( 'Invalid order item ID.', 'woocommerce-easy-booking-system' ) );
		}

	}

	/**
    *
    * Validate order ID.
    * @param int - $order_id
    * @throws Exception
    *
    **/
	public function check_order_id( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			throw new \Exception( __( 'Invalid order ID.', 'woocommerce-easy-booking-system' ) );
		}

	}

}