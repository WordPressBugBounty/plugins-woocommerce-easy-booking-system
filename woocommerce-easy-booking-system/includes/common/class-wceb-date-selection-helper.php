<?php

namespace EasyBooking;

/**
*
* Date selection.
* @version 3.4.6
*
**/

defined( 'ABSPATH' ) || exit;

class Date_Selection_Helper {

    /**
    *
    * Check selected dates.
    * @param str - $start
    * @param str - $end
    * @param WC_Product - $_product
    * @param bool - Check if start date is in the past or not
    * @return bool | WP_Error
    *
    **/
    public static function check_selected_dates( $start, $end, $_product, $check_past = true ) {

        // Make sure all datetimes are in the same timezone.
        date_default_timezone_set( 'UTC' );

        // Add first available date parameter to current date.
        $first_available_date = wceb_get_product_first_available_date( $_product );
        $first_date           = wceb_shift_date( date( 'Y-m-d' ), $first_available_date );

        $booking_mode = get_option( 'wceb_booking_mode' ); // Booking mode (Days or Nights)
        $dates_format = wceb_get_product_number_of_dates_to_select( $_product );

        if ( $dates_format === 'one' ) {

            // If start is not set, return false.
            if ( ! wceb_is_valid_date( $start ) || ( $check_past && $start < $first_date ) ) {
                throw new \Exception( __( 'Please select a valid date.', 'woocommerce-easy-booking-system' ) );
            }

        } else if ( $dates_format === 'two' ) {
    
            // If start and/or end are not set, return false.
            if ( ! wceb_is_valid_date( $start ) || ! wceb_is_valid_date( $end ) || ( $check_past && $start < $first_date ) || $end < $start ) {
                throw new \Exception( __( 'Please select valid dates.', 'woocommerce-easy-booking-system' ) );
            }

            if ( $booking_mode === 'nights' && $start === $end ) {
                throw new \Exception( __( 'Please select valid dates.', 'woocommerce-easy-booking-system' ) );
            }

        }

        return true;

    }

    /**
    *
    * Get and check selected booking duration after selecting dates.
    * @param str - $start
    * @param str - $end
    * @param WC_Product | WC_Product_Variation - $_product
    * @return int | WP_Error
    *
    **/
    public static function get_selected_booking_duration( $start, $end, $_product ) {

        $booking_mode     = get_option( 'wceb_booking_mode' ); // Booking mode (Days or Nights)
        $booking_duration = wceb_get_product_booking_duration( $_product );

        // One-date selection: booking duration is always 1
        if ( empty( $end ) || ! $end ) {
            return 1;
        }

        // Get booking duration in days
        $startDate = new \DateTime( $start );
        $endDate   = new \DateTime( $end );
        $duration  = $endDate->diff($startDate)->format('%a');
        
        // If booking mode is set to "Days", add one day
        if ( $booking_mode === 'days' ) {
            $duration += 1 ;
        }

        // Make sure booking duration is correct
        if ( $duration % $booking_duration !== 0 || $duration <= 0 ) {
            throw new \Exception( __( 'Please choose valid dates', 'woocommerce-easy-booking-system' ) );
        }

        return apply_filters( 'easy_booking_selected_booking_duration', $duration / $booking_duration, $start, $end, $_product );

    }

    /**
    *
    * Get simple product booking price.
    * @param array - $data
    * @param WC_Product - $product
    * @param WC_Product - $_product
    * @return array - $booking_data
    *
    **/
    public static function get_simple_product_booking_data( $data ) {

        $booking_data = array();
        
        // Get product price and (if on sale) regular price
        foreach ( array( 'price', 'regular_price' ) as $price_type ) {

            $price = $data['_product']->{'get_' . $price_type}();
            
            if ( $price === '' ) {
                continue;
            }

            ${'new_' . $price_type} = self::calculate_booking_price( $price, $data, $price_type, $data['product'], $data['_product'] );

        }

        $data['new_price'] = $new_price;

        if ( isset( $new_regular_price ) && ! empty( $new_regular_price ) && ( $new_regular_price !== $new_price ) ) {
            $data['new_regular_price'] = $new_regular_price;
        }

        $booking_data[$data['id']] = $data;

        return apply_filters( 'easy_booking_simple_product_booking_data', $booking_data, $data['product'] );

    }

    /**
    *
    * Get variable product booking price.
    * @param array - $data
    * @param WC_Product - $product
    * @param WC_Product_Variation - $_product
    * @return array - $booking_data
    *
    **/
    public static function get_variable_product_booking_data( $data ) {

        $booking_data = self::get_simple_product_booking_data( $data );
        return apply_filters( 'easy_booking_variable_product_booking_data', $booking_data, $data['product'], $data['_product'] );

    }

    /**
    *
    * Get grouped product booking price.
    * @param array - $data
    * @param WC_Product - $product
    * @param WC_Product | WC_Product_Variation - $_product
    * @param array - $children
    * @return array - $booking_data
    *
    **/
    public static function get_grouped_product_booking_data( $data ) {

        $booking_data = array();
        $new_price = 0;
        $new_regular_price = 0;

        // Save for later
        $_product = $data['_product'];
        
        foreach ( $data['children'] as $child_id => $quantity ) {

            if ( $quantity <= 0 || ( $child_id === $data['id'] ) ) {
                continue;
            }

            $child = wc_get_product( $child_id );
            $data['_product'] = $child;

             foreach ( array( 'price', 'regular_price' ) as $price_type ) {

                $price = $child->{'get_' . $price_type}();

                if ( $price === '' ) {
                    continue;
                }

                // Multiply price by duration only if children is bookable
                ${'child_new_' . $price_type} = self::calculate_booking_price( $price, $data, $price_type );

            }

            $data['new_price'] = $child_new_price;

            if ( isset( $child_new_regular_price ) && ! empty( $child_new_regular_price ) && ( $child_new_regular_price !== $child_new_price ) ) {
                $data['new_regular_price'] = $child_new_regular_price;
            }

            // Store child booking data
            $booking_data[$child_id] = $data;

            $booking_data[$child_id]['quantity'] = $quantity;

            // Make sure to set parent product price to 0 and remove regular price (parent product has no price).
            $data['new_price'] = 0;
            unset( $data['new_regular_price'] );

        }

        // Reset variable
        $data['_product'] = $_product;

        // Store parent product data
        $booking_data[$data['id']] = $data;
        
        return apply_filters( 'easy_booking_grouped_product_booking_data', $booking_data, $data['product'], $data['children'] );

    }
  
  	/**
    *
    * Get bundle product booking price.
    * @param array - $data
    * @param WC_Product - $product
    * @param WC_Product | WC_Product_Variation - $_product
    * @param array - $children
    * @return array - $booking_data
    *
    **/
    public static function get_bundle_product_booking_data( $data ) {

        $booking_data = array();

        // Save for later
        $_product = $data['_product'];

        if ( ! empty( $data['children'] ) ) foreach ( $data['children'] as $child_id => $quantity ) {

            // Parent ID is in $children array for technical reasons, but is not a child.
            if ( $child_id === $data['id'] ) {
                continue;
            }

            $child = wc_get_product( $child_id );
            $data['_product'] = $child;
            
            $bundled_item = class_exists( 'EasyBooking\Pb_Functions' ) ? Pb_Functions::get_corresponding_bundled_item( $data['product'], $child ) : false;

            // Return if no bundled item or if quantity is 0
            if ( ! $bundled_item || $quantity <= 0 ) {
                continue;
            }

            if ( $bundled_item->is_priced_individually() ) {

                foreach ( array( 'price', 'regular_price' ) as $price_type ) {

                    $price = $child->{'get_' . $price_type}();

                    if ( empty( $price ) ) {
                        continue;
                    }

                    // Maybe apply bundle discount.
                    $discount = $bundled_item->get_discount();

                    if ( isset( $discount ) && ! empty( $discount ) ) {
                        $price -= ( $price * $discount / 100 );
                    }

                    // Multiply price by duration only if product is bookable
                    ${'child_new_' . $price_type} = self::calculate_booking_price( $price, $data, $price_type );

                }

            } else { // Tweak for not individually priced bundled products
                
                $child_new_price = 0;
                $child_new_regular_price = 0;

            }

            $data['new_price'] = $child_new_price;
            $data['new_regular_price'] = isset( $child_new_regular_price ) ? $child_new_regular_price : 0;

            $booking_data[$child_id] = $data;

            // Store parent product
            $booking_data[$child_id]['grouped_by'] = $data['id'];

            // Store child quantity
            $booking_data[$child_id]['quantity'] = $quantity;

        }

        // Reset variable
        $data['_product'] = $_product;

        // Get parent product price and (if on sale) regular price
        foreach ( array( 'price', 'regular_price' ) as $price_type ) {

            $price = $data['product']->{'get_' . $price_type}();

            if ( empty( $price ) ) {
                continue;
            }

            ${'new_' . $price_type} = self::calculate_booking_price( $price, $data, $price_type );

        }

        $data['new_price'] = isset( $new_price ) ? $new_price : 0;

        if ( isset( $new_regular_price ) && ! empty( $new_regular_price ) && ( $new_regular_price !== $new_price ) ) {
            $data['new_regular_price'] = $new_regular_price;
        } else {
            unset( $data['new_regular_price'] ); // Unset value in case it was set for a child product
        }

        $booking_data[$data['id']] = $data;

        return apply_filters( 'easy_booking_bundle_product_booking_data', $booking_data, $data['product'], $data['children'] );

    }

    /**
    *
    * Calculate product booking price.
    * @param str - $price
    * @param array - $data
    * @param str - $price_type
    * @param WC_Product - $product
    * @param WC_Product | WC_Product_Variation - $_product
    * @return str - $price
    *
    **/
	public static function calculate_booking_price( $price, $data, $price_type ) {

        if ( true === wceb_is_bookable( $data['_product'] ) && apply_filters( 'easy_booking_calculate_booking_price', true, $data['_product'] ) ) {
                
            $number_of_dates = wceb_get_product_number_of_dates_to_select( $data['_product'] );
            $dates = $number_of_dates === 'one' ? 'one_date' : 'two_dates';
            
            $price = apply_filters(
                'easy_booking_' . $dates . '_price',
                $price * $data['duration'],
                $data['product'], $data['_product'], $data, $price_type
            );

        }
        
	    return apply_filters( 'easy_booking_new_' . $price_type, wc_format_decimal( $price ), $data, $data['product'], $data['_product'] );

	}

}