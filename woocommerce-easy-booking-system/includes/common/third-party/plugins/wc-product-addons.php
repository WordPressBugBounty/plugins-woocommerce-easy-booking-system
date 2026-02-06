<?php

/**
*
* Action hooks and filters related to WooCommerce Product Add-Ons.
* @version 3.4.6
*
**/

defined( 'ABSPATH' ) || exit;

/**
*
* WooCommerce Product Add-Ons compatibilty
* Adds an option to multiply addon cost by booking duration
* @param WP_POST - $post
* @param array- $addon
* @param int - $loop
*
**/
function wceb_pao_multiply_option( $post, $addon, $loop ) {

    $multiply_addon = isset( $addon['multiply_by_booking_duration'] ) ? $addon['multiply_by_booking_duration'] : 0;

    ?>

	<div class="wc-pao-addons-secondary-settings show_if_bookable">
        <div class="wc-pao-row wc-pao-addon-multiply-setting">
            <label for="wc-pao-addon-multiply-<?php echo esc_attr( $loop ); ?>">
                <input type="checkbox" id="wc-pao-addon-multiply-<?php echo esc_attr( $loop ); ?>" name="product_addon_multiply[<?php echo esc_attr( $loop ); ?>]" <?php checked( $multiply_addon, 1 ); ?> />
                    <?php esc_html_e( 'Multiply addon cost by booking duration?', 'woocommerce-easy-booking-system' ); ?>
            </label>
        </div>
    </div>

    <?php

}

add_action( 'woocommerce_product_addons_panel_before_options', 'wceb_pao_multiply_option', 10, 3 );

/**
*
* WooCommerce Product Add-Ons compatibilty
* Saves option to multiply addon cost by booking duration
* @param array - $data
* @param int - $i
* @return array - $data
*
**/
function wceb_pao_save_multiply_option( $data, $i ) {

    $multiply_addon = isset( $_POST['product_addon_multiply'] ) ? $_POST['product_addon_multiply'] : array();

    $data['multiply_by_booking_duration'] = isset( $multiply_addon[$i] ) ? 1 : 0;

    // Also have multiply option in each addon option to display "/ day" price.
    foreach ( $data['options'] as $i => $option ) {
        $data['options'][$i]['multiply'] = $data['multiply_by_booking_duration'];
    }

    return $data;

}

add_filter( 'woocommerce_product_addons_save_data', 'wceb_pao_save_multiply_option', 10, 2 );

/**
*
* WooCommerce Product Add-Ons compatibilty
* Displays a custom price if the addon cost is multiplied by booking duration
* @param str $price - Product price
* @param array - $addon
* @param int - $key
* @param str - $type
* @return str $price - Custom or base price
*
**/
function wceb_pao_product_addons_price( $price, $addon, $key, $type ) {
    global $product;

    // Small verification because WC Product Addons is very well coded (...) and the same filter is used to display price in input label and in html data attribute.
    if ( is_float( $price ) ) {
        return $price;
    }

    if ( wceb_is_bookable( $product ) ) {

        $adjust_price = ! empty( $addon['adjust_price'] ) ? $addon['adjust_price'] : '';

        if ( $adjust_price != '1' ) {
            return $price;
        }

        $maybe_multiply = isset( $addon['multiply_by_booking_duration'] ) ? $addon['multiply_by_booking_duration'] : 0;

        if ( $maybe_multiply ) {
            
            $addon_price  = ! empty( $addon['price'] ) ? $addon['price'] : '';
            $price_prefix = 0 < $addon_price ? '+' : '';
            $price_raw    = apply_filters( 'woocommerce_product_addons_price_raw', $addon_price, $addon );

            if ( ! $price_raw ) {
                return $price;
            }

            $price_type = ! empty( $addon['price_type'] ) ? $addon['price_type'] : '';

            if ( 'percentage_based' === $price_type ) {
                $content = $price_prefix . $price_raw . '%';
            } else {
                $content = $price_prefix . wc_price( WC_Product_Addons_Helper::get_product_addon_price_for_display( $price_raw ) );
            }

            $price_suffix = wceb_get_product_price_suffix( $product );

            $wceb_addon_price = apply_filters(
                'easy_booking_price_html',
                $content . '<span class="wceb-price-format">' . esc_html( $price_suffix ) . '</span>',
                $product,
                $content
            );

            $price = '(' . $wceb_addon_price . ')';

        }

    }

    return $price;

}

add_filter( 'woocommerce_product_addons_price', 'wceb_pao_product_addons_price', 10, 4 );

/**
*
* WooCommerce Product Add-Ons compatibilty
* Displays a custom price if the addon option cost is multiplied by booking duration
* This is for addons with options (multiple choice or checkbox)
*
* @param str $price - Product price
* @param array - $option
* @param int - $key
* @param str - $type
* @return str $price - Custom or base price
*
**/
function wceb_pao_product_addons_option_price( $price, $option, $key, $type ) {
    global $product;

    // Small verification because WC Product Addons is very well coded (...) and the same filter is used to display price in input label and in html data attribute.
    if ( is_float( $price ) ) {
        return $price;
    }

    if ( wceb_is_bookable( $product ) ) {

        $maybe_multiply = isset( $option['multiply'] ) ? $option['multiply'] : 0;

        if ( $maybe_multiply ) {
            
            $option_price = ! empty( $option['price'] ) ? $option['price'] : '';
            $price_prefix = 0 < $option_price ? '+' : '';
            $price_raw    = apply_filters( 'woocommerce_product_addons_option_price_raw', $option_price, $option );

            if ( ! $price_raw ) {
                return $price;
            }

            $price_type = ! empty( $option['price_type'] ) ? $option['price_type'] : '';

            if ( 'percentage_based' === $price_type ) {
                $content = $price_prefix . $price_raw . '%';
            } else {
                $content = $price_prefix . wc_price( WC_Product_Addons_Helper::get_product_addon_price_for_display( $price_raw ) );
            }

            $price_suffix = wceb_get_product_price_suffix( $product );

            $wceb_addon_price = apply_filters(
                'easy_booking_price_html',
                $content . '<span class="wceb-price-format">' . esc_html( $price_suffix ) . '</span>',
                $product,
                $content
            );

            $price = '(' . $wceb_addon_price . ')';

        }

    }

    return $price;

}

add_filter( 'woocommerce_product_addons_option_price', 'wceb_pao_product_addons_option_price', 10, 4 );

/**
*
* WooCommerce Product Add-Ons compatibilty.
* Sanitize selected addons after selecting dates.
* @param array - $sanitized_data
* @param array - $data - Raw data
* @return array - $sanitized_data
*
**/
function wceb_pao_sanitize_selected_addons( $sanitized_data, $data ) {

    $addons = array();

    // Sanitize
    if ( isset( $data['additional_cost'] ) ) foreach ( $data['additional_cost'] as $i => $additional_cost ) {

        $addons[$i] = [
            'cost'  => (float) $additional_cost['cost'],
            'id'    => sanitize_text_field( $additional_cost['id'] ),
            'value' => sanitize_text_field( $additional_cost['value'] )
        ];
        
    }

    $sanitized_data['selected_addons'] = $addons;

    return $sanitized_data;

}

add_filter( 'easy_booking_sanitized_booking_data', 'wceb_pao_sanitize_selected_addons', 10, 2 );

/**
*
* WooCommerce Product Add-Ons compatibilty.
* Maybe add additional costs to booking price after selecting dates (not in cart).
* @param str - $price
* @param int - $_product_id
* @param array - $booking_data
* @return str - $price
*
**/
function wceb_pao_add_selected_addons_cost( $price, $_product_id, $data ) {

    $addons_data = EasyBooking\Pao_Functions::get_selected_addons_data( $data );

    if ( ! empty( $addons_data ) ) foreach ( $addons_data as $addon_data ) {
        $price += $addon_data['cost'] / $data['quantity'];
    }

    return wc_format_decimal( $price );

}

add_filter( 'easy_booking_new_price_to_display', 'wceb_pao_add_selected_addons_cost', 10, 3 );
add_filter( 'easy_booking_new_regular_price_to_display', 'wceb_pao_add_selected_addons_cost', 10, 3 );

/**
*
* WooCommerce Product Add-Ons compatibilty.
* Adjust product booking price in cart with addons.
* @param float - $booking_price
* @param array - $cart_item
* @return float - $booking_price
*
**/
function wceb_pao_add_addons_price_to_booking_price( $booking_price, $cart_item ) {

    if ( isset( $cart_item['addons'] ) && ! empty( $cart_item['addons'] ) ) {

        foreach ( $cart_item['addons'] as $i => $addon ) {
            $booking_price += $addon['price_type'] === 'flat_fee' ? ( $cart_item['quantity'] > 0 ? (float) ( $addon['price'] / $cart_item['quantity'] ) : 0 ) : (float) $addon['price'];      
        }

    }

    return $booking_price;

}

add_filter( 'easy_booking_set_booking_price', 'wceb_pao_add_addons_price_to_booking_price', 10, 2 );
add_filter( 'easy_booking_set_booking_regular_price', 'wceb_pao_add_addons_price_to_booking_price', 10, 2 );

/**
*
* WooCommerce Product Add-Ons compatibilty.
* Adjust product booking price with addons in cart.
* @param array - $updated_product_prices
* @param array - $cart_item
* @param array - $prices
* @return array - $updated_product_prices
*
**/
function wceb_pao_addons_price_in_cart( $updated_product_prices, $cart_item, $prices ) {
    
    if ( ! isset( $cart_item['_booking_price'] ) ) {
        return $updated_product_prices;
    }
    
    // Check if there are addons in cart
    if ( isset( $cart_item['addons'] ) && ! empty( $cart_item['addons'] ) ) {

        $booking_price         = (float) $cart_item['_booking_price'];
        $booking_regular_price = (float) isset( $cart_item['_booking_regular_price'] ) ? $cart_item['_booking_regular_price'] : $cart_item['_booking_price'];
        $booking_sale_price    = (float) $cart_item['_booking_price'];
        
        $flat_fees = 0;

        foreach ( $cart_item['addons'] as $i => $addon ) {

            switch ( $addon['price_type'] ) {

                case 'flat_fee':

                    $flat_fee = $cart_item['quantity'] > 0 ? (float) ( $addon['price'] / $cart_item['quantity'] ) : 0;

                    $booking_price         += $flat_fee;
                    $booking_regular_price += $flat_fee;
                    $booking_sale_price    += $flat_fee;
                    $flat_fees             += $flat_fee;
                    break;

                default:

                    $booking_price         += (float) $addon['price'];
                    $booking_regular_price += (float) $addon['price'];
                    $booking_sale_price    += (float) $addon['price'];
                    break;

            }
            
        }

        $updated_product_prices['price']                = $booking_price;
        $updated_product_prices['regular_price']        = $booking_regular_price;
        $updated_product_prices['sale_price']           = $booking_sale_price;
        $updated_product_prices['addons_flat_fees_sum'] = $flat_fees;

    }
    
    return $updated_product_prices;

}

add_filter( 'woocommerce_product_addons_update_product_price', 'wceb_pao_addons_price_in_cart', 20, 3 );

/**
*
* WooCommerce Product Add-Ons compatibilty.
* Calculate and store addons prices when adding a product to cart.
* @param array - $cart_item
* @return array - $cart_item
*
**/
function wceb_pao_cart_item( $cart_item ) {

    if ( ! isset( $cart_item['_booking_price'] ) ) {
        return $cart_item;
    }

    // Check if there are addons in cart
    if ( isset( $cart_item['addons'] ) && ! empty( $cart_item['addons'] ) ) {

        foreach ( $cart_item['addons'] as $i => $addon ) {

            // The function runs several times so we need to get raw addon price.
            $price = isset( $addon['raw_addon_price'] ) ? $addon['raw_addon_price'] : $addon['price'];

            // Calculate addon price depending on booking duration.
            $addon_price = EasyBooking\Pao_Functions::calc_addon_cost( $price, $addon, $cart_item['_booking_price'], $cart_item['_booking_duration'], $cart_item['quantity'] );

            // Store addon price before updating it to new calculated price.
            $cart_item['addons'][$i]['raw_addon_price'] = $addon['price'];

            // Store new addon price.
            $cart_item['addons'][$i]['price'] = strval( $addon_price );
            
        }

    }

    return $cart_item;

}

add_filter( 'easy_booking_cart_item', 'wceb_pao_cart_item', 10, 1 );

/**
*
* WooCommerce Product Add-Ons compatibilty
* Store multiply by booking duration in each addon when adding a product to cart
*
* @param array - $data
* @param array - $addon
* @param int - $product_id
* @param array - $post_data
* @return array - $data
*
**/
function wceb_pao_product_addon_cart_item_data( $data, $addon, $product_id, $post_data ) {

    $maybe_multiply = isset( $addon['multiply_by_booking_duration'] ) ? $addon['multiply_by_booking_duration'] : 0;

    foreach ( $data as $i => $addon_data ) {
        $data[$i]['multiply_by_booking_duration'] = intval( $maybe_multiply );
    }

    return $data;

}

add_filter( 'woocommerce_product_addon_cart_item_data', 'wceb_pao_product_addon_cart_item_data', 10, 4 );

/**
*
* WooCommerce Product Add-Ons compatibilty
* Hide addons total on product page to avoid confusion (detail is shown after selecting dates)
*
* @param bool - $show
* @param WC_Product - $product
* @return bool - $show
*
**/
function wceb_pao_hide_addons_total( $show, $product ) {
    return wceb_is_bookable( $product ) ? false : $show;
}

add_filter( 'woocommerce_product_addons_show_grand_total', 'wceb_pao_hide_addons_total', 10, 2 );

/**
*
* WooCommerce Product Add-Ons compatibilty
* Display addons price in cart.
*
**/

add_filter( 'woocommerce_addons_add_cart_price_to_value', '__return_true' );

/**
*
* WooCommerce Product Add-Ons compatibilty
* Display booking price (without addons price) in cart.
*
* @param array - $other_data
* @param array - $cart_item
* @return array - $other_data
*
**/
function wceb_pao_display_booking_price_in_cart( $other_data, $cart_item ) {

    // Display booking price only if there are addons in cart
    if ( isset( $cart_item['_booking_price'] ) && ( isset( $cart_item['addons'] ) && ! empty( $cart_item['addons'] ) ) ) {

        $other_data[] = array(
            'name'  => esc_html__( 'Booking price', 'woocommerce-easy-booking-system' ),
            'value' => wc_price( $cart_item['_booking_price'] )
        );

    }

    return $other_data;

}

add_filter( 'woocommerce_get_item_data', 'wceb_pao_display_booking_price_in_cart', 5, 2 );

/**
*
* WooCommerce Product Add-Ons compatibilty
* Display booking price and addons price details after selecting dates.
*
* @param str - $details
* @param WC_Product - $product
* @param array - $booking_data
* @return str - $details
*
**/
function wceb_pao_addons_price_details( $details, $product, $data ) {
    
    $addons_data = EasyBooking\Pao_Functions::get_selected_addons_data( $data );

    if ( empty( $addons_data ) ) {
        return $details;
    }

    $details .= '<p><span>';

    $details .= sprintf(
        esc_html__( 'Booking price: %s', 'woocommerce-easy-booking-system' ),
        wc_price( $data['new_price'] * $data['quantity'] )
    );

    $details .= '</span></br>';

    foreach ( $addons_data as $addon_data ) {

        $details .= '<span>';

        $details .= sprintf(
            esc_html__( '%s: %s', 'woocommerce-easy-booking-system' ),
            wptexturize( $addon_data['name'] ),
            wc_price( \WC_Product_Addons_Helper::get_product_addon_price_for_display( $addon_data['cost'] ) ),
        );

        $details .= '</span></br>';

    }

    $details .= '</p>';

    return $details;
    
}

add_filter( 'easy_booking_booking_price_details', 'wceb_pao_addons_price_details', 10, 3 );

/**
*
* WooCommerce Product Add-Ons compatibilty
* Make sure to load addons.js script before main Easy Booking script.
*
* @param array - $dependencies
* @return array - $dependencies
*
**/
function wceb_add_addons_script_dependency( $dependencies ) {

    if ( true === EasyBooking\Third_Party_Plugins::wc_pao_is_active() ) {
        $dependencies[] = 'woocommerce-addons';
    }

    return $dependencies;

}

add_filter( 'easy_booking_script_dependencies', 'wceb_add_addons_script_dependency', 10, 1 );