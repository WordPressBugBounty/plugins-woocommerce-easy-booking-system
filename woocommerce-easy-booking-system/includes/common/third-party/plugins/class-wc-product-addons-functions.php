<?php

namespace EasyBooking;

/**
*
* All functions related to WooCommerce Product Add-Ons.
* @version 3.1.7
*
**/

defined( 'ABSPATH' ) || exit;

class Pao_Functions {

	/**
	*
	* Calculate add-on cost after selecting dates.
	* @param float - $addon_cost
	* @param array - $addon
	* @param float - $price
	* @param int - $duration
	* @param int - $quantity
	* @return float - $addon_cost
	*
	**/
	public static function calc_addon_cost( $addon_cost, $addon, $price, $duration, $quantity ) {

		// Multiply addon cost by booking duration?
		$multiply = isset( $addon['multiply_by_booking_duration'] ) ? absint( $addon['multiply_by_booking_duration'] ) : 0;
                
		// Get addon type (percentage base or flat fee)
		$addon_type = isset( $addon['price_type'] ) ? $addon['price_type'] : 'flat_fee';

 	    // Calculate percentage based addon cost.
	    if ( $addon_type === 'percentage_based' ) {
	        $addon_cost = ( $price * $addon_cost ) / 100;
	    }

 	    // Maybe multiply by booking duration.
		if ( $multiply && $addon_type !== 'percentage_based' ) {
			$addon_cost *= $duration;
		}

	    return apply_filters( 'easy_booking_pao_addon_cost', (float) $addon_cost, $duration, $multiply );

	}

	/**
	*
	* Get selected addons data with name and calculated price.
	* @param WC_Product - $_product
	* @param array - $booking_data
	* @return array - $addons_data
	*
	**/
	public static function get_selected_addons_data( $_product, $booking_data ) { 

		$addons_data = array();

		// Selected addons
		$selected_addons = isset( $_POST['additional_cost'] ) ? $_POST['additional_cost'] : array();

		if ( empty( $selected_addons ) ) {
	        return $addons_data;
	    }

	    // Product addons
		$product_addons = \WC_Product_Addons_Helper::get_product_addons( $_product->get_ID() );

	    if ( ! $product_addons || empty( $product_addons ) ) {
	        return $addons_data;
	    }

		for ( $i = 0; $i < count( $selected_addons ); $i++ ) {

			// Get addon corresponding to selected addon ID
			$addon = array_column( $product_addons, null, 'id' )[$selected_addons[$i]['id']] ?? false;

			if ( $addon ) {
	
				// No price and no adjust price? Skip.
				if ( ! $selected_addons[$i]['cost'] && empty( $addon['adjust_price'] ) ) {
					continue;
				}

				// Calculate addon cost
				$addon_cost = self::calc_addon_cost(
					$selected_addons[$i]['cost'],
					$addon,
					$booking_data['new_price'],
					$booking_data['duration'],
					$booking_data['quantity']
				);

				$addons_data[] = array(
					'name'         => $addon['title_format'] === 'hide' ? $selected_addons[$i]['value'] : $addon['name'] . ' - ' . $selected_addons[$i]['value'],
					'type'         => $addon['price_type'],
					'cost'         => $addon_cost
				);

			}

		}

		return $addons_data;

	}

}