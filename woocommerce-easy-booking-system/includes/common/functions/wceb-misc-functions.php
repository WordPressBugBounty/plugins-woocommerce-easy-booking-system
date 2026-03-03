<?php

/**
*
* Misc functions.
* @version 3.4.8
*
**/

defined( 'ABSPATH' ) || exit;

/**
*
* Return true if script debug is enabled.
* @return bool
*
**/
function wceb_script_debug() {
	return ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );
}

/**
*
* Return an array of product types compatible with Easy Booking.
* @return array
*
**/
function wceb_get_allowed_product_types() {

	$allowed_types = array(
        'simple',
        'variable',
        'grouped',
        'bundle'
    );

    return apply_filters( 'easy_booking_allowed_product_types', $allowed_types );
    
}

/**
*
* Return the path to a file.
* Loads the file from the theme if it exists (path: easy-booking/$path/$file or easy-booking/$file)
* If it doesn't exist in the theme, loads the file from the plugin
* @param str $path - Path to the file (relative to the plugin directory)
* @param str $file - File name
* @return str $template - Complete path to the file
*
**/
function wceb_load_template( $path, $file ) {

    $template_path = 'easy-booking/';
    $template = '';

    // Get the template from the theme if it exists
    $template = locate_template( 
        array(
            $template_path . trailingslashit( $path ) . $file,
            trailingslashit( $template_path ) . $file
        )
    );

    // If it doesn't, get it from the plugin
    if ( ! $template || empty( $template ) ) {
        $template = plugin_dir_path( WCEB_PLUGIN_FILE ) . trailingslashit( $path ) . $file;
    }

    return apply_filters( 'easy_booking_load_template', $template, $path, $file );

}

/**
* 
* Get the URL of an asset file (minified or not), depending on SCRIPT_DEBUG.
*
* Automatically loads the non-minified version when SCRIPT_DEBUG is enabled,
* and the minified version otherwise.
*
* @param string $path      - Optional subdirectory (e.g. 'admin'). Empty for root.
* @param string $file      - File name without extension.
* @param string $extension - File extension ('js' or 'css').
* @param string $plugin    - Plugin main file path. Default to WCEB_PLUGIN_FILE.
* @return string URL to the requested asset file.
*
**/
function wceb_get_file_path( $path, $file, $extension, $plugin = WCEB_PLUGIN_FILE ) {

    $debug_enabled = wceb_script_debug();

    $dev = $debug_enabled ? 'dev/' : '';
    $min = $debug_enabled ? '' : '.min';

    $path = empty( $path ) ? '' : trailingslashit( $path );

    return plugins_url(
        'assets/' . trailingslashit( $extension ) . $path . $dev . $file . $min . '.' . $extension,
        $plugin
    );

}

/**
*
* Get localized start text.
* @param (optional) WC_Product
* @return str
*
**/
function wceb_get_start_text( $product = false ) {
    return apply_filters( 'easy_booking_start_text', __( 'Start', 'woocommerce-easy-booking-system' ), $product );
}

/**
*
* Get localized end text.
* @param (optional) WC_Product
* @return str
*
**/
function wceb_get_end_text( $product = false ) {
    return apply_filters( 'easy_booking_end_text', __( 'End', 'woocommerce-easy-booking-system' ), $product );
}

/**
*
* Get localized text to display when dates are not selected.
* @param WC_Product
* @return str
*
**/
function wceb_get_select_dates_error_message( $product ) {

    $number_of_dates = wceb_get_product_number_of_dates_to_select( $product );

    $error_message = $number_of_dates === 'one' ? __( 'Please select a date before adding this product to your cart.', 'woocommerce-easy-booking-system' ) : __( 'Please select dates before adding this product to your cart.', 'woocommerce-easy-booking-system' );

    return apply_filters( 'easy_booking_select_dates_error_message', $error_message, $product );

}

/**
*
* Sanitize frontend parameters.
* @param mixed - $param
* @param str - $func
* @return mixed - $param
*
**/
function wceb_sanitize_parameters( $param, $func ) {
    return is_array( $param ) ? array_map( $func, $param ) : $func( $param );
}

/**
*
* Sorts array by product ID.
* @return int
*
**/
function wceb_sort_by_product_id( $a, $b ) {
    return ( $a['product_id'] < $b['product_id'] ) ? -1 : 1;
}

/**
*
* Create placeholders for arrays in SQL requests.
* @param array - $array
* @param str - $format (%s, %d, %f)
* @return str
*
**/
function wceb_create_sql_placeholders( $array, $format = '%s' ) {
    return implode( ', ', array_fill( 0, count( $array ), $format ) );
}