<?php

/**
*
* '_ebs_start_format' was changed to '_booking_start_date'
* '_ebs_end_format' was changed to '_booking_end_date'
* @version 2.3.0
*
**/

defined( 'ABSPATH' ) || exit;

/**
*
* Backward compatibility for meta data on order pages.
*
*/
add_filter( 'woocommerce_hidden_order_itemmeta', 'wceb_legacy_hide_order_item_booking_data', 10, 1 );

function wceb_legacy_hide_order_item_booking_data( $item_meta ) {

    $item_meta[] = '_ebs_start_format';
    $item_meta[] = '_ebs_end_format';

    return $item_meta;

}