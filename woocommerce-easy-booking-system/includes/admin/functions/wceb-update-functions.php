<?php

/**
*
* Update functions.
* @version 3.3.1
*
**/

defined( 'ABSPATH' ) || exit;

/**
*
* Function to update database.
* Check if current db version is inferior to plugin db version, and run update functions if necessary.
* @param bool - $full_update - Set true to run all updates.
*
**/
function wceb_db_update( $full_update = false ) {

    $current_db_version = get_option( 'easy_booking_db_version' );

    // First time init or full update (db updates started in version 2.2.4)
    if ( ! $current_db_version || empty( $current_db_version ) || true === $full_update ) {
        $current_db_version = '2.2.3';
    }

	if ( version_compare( $current_db_version, wceb_get_db_version(), '<' ) ) {

        foreach ( wceb_get_db_updates() as $index => $update_version ) {

            if ( version_compare( $current_db_version, $update_version, '<' ) ) {

                $update = str_replace( '.', '', $update_version );

                if ( function_exists( 'wceb_update_db_version_' . $update ) ) {

                    try {
                        call_user_func( 'wceb_update_db_version_' . $update );
                    } catch ( Throwable $e ) {
                        return __( 'Easy Booking database update failed. The following error was returned: ' . esc_html( $e->getMessage() ), 'woocommerce-easy-booking-system' );
                    }

                }

            }

        }
         
    }

    return __( 'Easy Booking database update complete. Thank you!', 'woocommerce-easy-booking-system' );

}

/**
*
* Get available database updates
* @return array - $updates
*
**/
function wceb_get_db_updates() {
    $updates = array( '2.2.4', '2.2.5', '2.3.0', '3.0.0', '3.3.1' );
    return $updates;
}

/**
*
* In version 2.2.4 "_booking_option" post meta becomes "_bookable".
*
**/
function wceb_update_db_version_224() {

    // Query Products
    $args = array(
        'post_type'      => array( 'product', 'product_variation' ),
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'meta_key'       => '_booking_option',
        'fields'         => 'ids'
    );

    $query_products = new WP_Query( $args );

    if ( $query_products->posts ) foreach ( $query_products->posts as $product_id ) :

        $is_bookable = get_post_meta( $product_id, '_booking_option', true );

        if ( $is_bookable === 'yes' ) {
            update_post_meta( $product_id, '_bookable', 'yes' );
        }

        delete_post_meta( $product_id, '_booking_option' );

    endforeach;

    update_option( 'easy_booking_db_version', '2.2.4' );

}

/**
*
* In version 2.2.5 "_booking_dates" post meta becomes "_number_of_dates".
*
**/
function wceb_update_db_version_225() {

    // Query Products
    $args = array(
        'post_type'      => array( 'product', 'product_variation' ),
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'meta_key'       => '_booking_dates',
        'fields'         => 'ids'
    );

    $query_products = new WP_Query( $args );

    if ( $query_products->posts ) foreach ( $query_products->posts as $product_id ) :

        $number_of_dates = get_post_meta( $product_id, '_booking_dates', true );

        if ( ! is_null( $number_of_dates ) || ! is_empty( $number_of_dates ) ) {
            update_post_meta( $product_id, '_number_of_dates', sanitize_text_field( $number_of_dates ) );
            delete_post_meta( $product_id, '_booking_dates' );
        }

    endforeach;

    update_option( 'easy_booking_db_version', '2.2.5' );

}

/**
*
* In version 2.3.0 "_ebs_start_format" order item meta becomes "_booking_start_date" and "_ebs_end_format" becomes "_booking_end_date".
*
**/
function wceb_update_db_version_230() {

    // Query orders
    $orders = wceb_get_orders( false );

    $products = array();
    foreach ( $orders as $index => $order_id ) :

        $order = wc_get_order( $order_id );
        $items = $order->get_items();

        $data = array();
        if ( $items ) foreach ( $items as $item_id => $item ) {

            if ( isset( $item['_ebs_start_format'] ) && ! isset( $item['_booking_start_date'] ) ) {
                wc_add_order_item_meta( $item_id, '_booking_start_date', $item['_ebs_start_format'] );
            }

            if ( isset( $item['_ebs_end_format'] ) && ! isset( $item['_booking_end_date'] ) ) {
                wc_add_order_item_meta( $item_id, '_booking_end_date', $item['_ebs_end_format'] );
            }

        }

    endforeach;

    update_option( 'easy_booking_db_version', '2.3.0' );

}

/**
*
* In version 3.0.0 Booking duration and Custom booking duration were merged into one option.
*
**/
function wceb_update_db_version_300() {

    $booking_duration = get_option( 'wceb_booking_duration' );

    if ( $booking_duration === 'days' ) {

        update_option( 'wceb_booking_duration', 1 );

    } else if ( $booking_duration === 'weeks' ) {

        update_option( 'wceb_booking_duration', 7 );

    }  else if ( $booking_duration === 'custom' ) {

        $custom_booking_duration = get_option( 'wceb_custom_booking_duration' );

        if ( $custom_booking_duration ) {
            update_option( 'wceb_booking_duration', $custom_booking_duration );
        } else {
            update_option( 'wceb_booking_duration', 1 );
        }

    }

    delete_option( 'wceb_custom_booking_duration' );

    // Query Products
    $args = array(
        'post_type'      => array( 'product', 'product_variation' ),
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'meta_key'       => '_booking_duration',
        'fields'         => 'ids'
    );

    $query_products = new WP_Query( $args );

    if ( $query_products->posts ) foreach ( $query_products->posts as $product_id ) :

        $booking_duration = get_post_meta( $product_id, '_booking_duration', true );

        if ( $booking_duration === 'days' ) {

            update_post_meta( $product_id, '_booking_duration', 1 );

        } else if ( $booking_duration === 'weeks' ) {

            update_post_meta( $product_id, '_booking_duration', 7 );

        }  else if ( $booking_duration === 'custom' ) {

            $custom_booking_duration = get_post_meta( $product_id, '_custom_booking_duration', true );

            if ( ! is_null( $custom_booking_duration ) || ! is_empty( $custom_booking_duration ) ) {
                update_post_meta( $product_id, '_booking_duration', $custom_booking_duration );
            } else {
                delete_post_meta( $product_id, '_booking_duration' );
            }

        } else if ( $booking_duration === 'global' || $booking_duration === 'parent' ) {
            delete_post_meta( $product_id, '_booking_duration' );
        }

        delete_post_meta( $product_id, '_custom_booking_duration' );

    endforeach;

    update_option( 'easy_booking_db_version', '3.0.0' );

}

/**
*
* In version 3.3.1, a "wceb_order_bookings" table was added so we need to populate it with existing bookings.
* Function was fixed in 3.3.1 for people not using WooCommerce HPOS, in which case table wc_orders did not return anything.
*
**/
function wceb_update_db_version_331() {
    global $wpdb;

    // Maybe create tables
    EasyBooking\Install::maybe_create_tables();
    
    // Prepare args for query
    $order_statuses = wceb_get_valid_order_statuses();

    $order_statuses_placeholder = implode(', ', array_fill( 0, count( $order_statuses ), '%s' ) );

    $meta_keys = array(
        '_booking_start_date',
        '_product_id',
        '_variation_id',
        '_booking_end_date',
        '_booking_status',
        '_qty',
        '_booking_start_date',
    );

    $args = array_merge( $order_statuses, $meta_keys );

    // Prepare query
    $query = "SELECT key1.order_item_id, key2.ID as order_id, key4.meta_key, key4.meta_value
    FROM {$wpdb->prefix}woocommerce_order_items key1
    INNER JOIN {$wpdb->prefix}posts key2 ON key2.ID = key1.order_id
    INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta key3 ON key3.order_item_id = key1.order_item_id
    INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta key4 ON key4.order_item_id = key1.order_item_id
    WHERE key2.post_status IN ( $order_statuses_placeholder )
    AND key3.meta_key = %s
    AND key4.meta_key IN ( %s, %s, %s, %s, %s, %s )
    ORDER BY key1.order_item_id ASC";

    // Query order items
    $results = $wpdb->get_results( $wpdb->prepare( 
        $query,
        $args
    ) );
    
    // Get an array of refunded items with quantity refunded so we can maybe remove it later
    $refunded_items = $wpdb->get_results( $wpdb->prepare(
        "
        SELECT      key2.meta_value as order_item_id, key1.meta_value as qty_refunded
        FROM        {$wpdb->prefix}woocommerce_order_itemmeta key1
        INNER JOIN  {$wpdb->prefix}woocommerce_order_itemmeta key2 ON key2.order_item_id = key1.order_item_id
        WHERE       key1.meta_key = %s
        AND         key2.meta_key = %s
        ORDER BY    order_item_id ASC
        ",
        '_qty',
        '_refunded_item_id'
    ), OBJECT_K );
    
    // Make an array of all bookings with corresponding metadata
    $bookings = array();
    foreach ( $results as $i => $data ) {

        $order_item_id = $data->order_item_id;

        $bookings[$order_item_id][$data->meta_key] = $data->meta_value;
        $bookings[$order_item_id]['_order_id']     = $data->order_id;

    }

    // Prepare array to save item ids added to the database
    $added_item_ids = array();

    // Loop through each booking and maybe add them to wceb_order_bookings table
    foreach ( $bookings as $order_item_id => $booking_data ) {

        // Remove booking is required metadata is not set
        if ( ! isset( $booking_data['_product_id'] )
        || ! isset( $booking_data['_variation_id'] )
        || ( ! isset( $booking_data['_booking_start_date'] ) || ! wceb_is_valid_date( $booking_data['_booking_start_date'] ) )
        || ! isset( $booking_data['_qty'] )
        || ! isset( $booking_data['_order_id'] ) ) {
            continue;
        }

        $_product_id = $booking_data['_variation_id'] === '0' ? $booking_data['_product_id'] : $booking_data['_variation_id'];
        $quantity    = $booking_data['_qty'];
        $end_date    = isset( $booking_data['_booking_end_date'] ) && wceb_is_valid_date( $booking_data['_booking_end_date'] ) ? $booking_data['_booking_end_date'] : NULL; 

        // Maybe get booking status
        if ( ! isset( $booking_data['_booking_status'] ) || empty( $booking_data['_booking_status'] ) ) {
            $booking_data['_booking_status'] = wceb_get_booking_status( $booking_data['_booking_start_date'], $end_date );
        }

        // Maybe remove refunded quantity
        if ( isset( $refunded_items[$order_item_id] ) ) {
            $quantity -= abs( $refunded_items[$order_item_id]->qty_refunded );
        }

        // Remove booking is quantity is < 0
        if ( $quantity <= 0 ) { continue; }

        // Add or update bookings in database
        $replace = $wpdb->replace(
            $wpdb->prefix . 'wceb_order_bookings', 
            array( 
                'order_item_id' => $order_item_id, 
                'product_id'    => $_product_id, 
                'start'         => $booking_data['_booking_start_date'],
                'end'           => $end_date,
                'status'        => $booking_data['_booking_status'],
                'qty'           => $quantity,
                'order_id'      => $booking_data['_order_id']
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%d', '%d' )
        );

        // If row was successfully added or replaced, we store order item id for later
        if ( $replace ) { $added_item_ids[] = $order_item_id; }

    }

    // Maybe remove not wanted order items ids
    if ( ! empty( $added_item_ids ) ) {

        $placeholder  = implode( ', ', array_fill( 0, count( $added_item_ids ), '%d' ) );
        $delete_query = "DELETE FROM {$wpdb->prefix}wceb_order_bookings WHERE order_item_id NOT IN ( $placeholder )";

        $wpdb->query( $wpdb->prepare( 
            $delete_query,
            $added_item_ids
        ) );

    }

    update_option( 'easy_booking_db_version', '3.3.1' );

}