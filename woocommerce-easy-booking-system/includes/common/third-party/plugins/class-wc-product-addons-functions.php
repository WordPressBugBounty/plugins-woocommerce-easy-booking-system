<?php

namespace EasyBooking;

/**
*
* All functions related to WooCommerce Product Add-Ons.
* @version 3.4.6
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
		$addon_type = isset( $addon['type'] ) ? $addon['type'] : 'flat_fee';

 	    // Calculate percentage based addon cost.
	    if ( $addon_type === 'percentage_based' ) {
	        $addon_cost = ( $price * $addon_cost ) / 100;
	    }

 	    // Maybe multiply by booking duration.
		if ( $multiply && $addon_type !== 'percentage_based' ) {
			$addon_cost *= $duration;
		}

		// Multiply quantity based addons price by quantity
		if ( $addon_type === 'quantity_based' ) {
			$addon_cost *= $quantity;
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
	public static function get_selected_addons_data( $data ) { 

		$addons_data = array();

		// No addons selected
		if ( empty( $data['selected_addons'] ) ) {
	        return $addons_data;
	    }

	    // Product addons
		$product_addons = \WC_Product_Addons_Helper::get_product_addons( $data['_product']->get_ID() );

	    if ( ! $product_addons || empty( $product_addons ) ) {
	        return $addons_data;
	    }

		for ( $i = 0; $i < count( $data['selected_addons'] ); $i++ ) {

			// Get addon corresponding to selected addon ID
			$addon = array_column( $product_addons, null, 'id' )[$data['selected_addons'][$i]['id']] ?? false;

			if ( $addon ) {
	
				// No price and no adjust price? Skip.
				if ( ! $data['selected_addons'][$i]['cost'] && empty( $addon['adjust_price'] ) ) {
					continue;
				}

				// Tweak because Product Add-Ons doesn't get option type
				$addon['type'] = $addon['price_type'];

				if ( isset( $addon['options'] ) ) {

					$option = array_column( $addon['options'], null, 'label' )[$data['selected_addons'][$i]['value']] ?? false;
					
					if ( $option ) {
						$addon['type'] = $option['price_type'];
					}

				}

				// Calculate addon cost
				$addon_cost = self::calc_addon_cost(
					$data['selected_addons'][$i]['cost'],
					$addon,
					$data['new_price'],
					$data['duration'],
					$data['quantity']
				);

				$addons_data[] = array(
					'name'         => $addon['title_format'] === 'hide' ? $data['selected_addons'][$i]['value'] : $addon['name'] . ' - ' . $data['selected_addons'][$i]['value'],
					'type'         => $addon['price_type'],
					'cost'         => $addon_cost
				);

			}

		}

		return $addons_data;

	}

}