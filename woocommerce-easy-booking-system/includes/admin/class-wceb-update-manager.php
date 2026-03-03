<?php

namespace EasyBooking;

/**
*
* Update Manager with admin notices.
* @version 3.4.8
*
**/

defined( 'ABSPATH' ) || exit;

class Update_Manager {

    private const DB_VERSION = '3.3.1';
    private const UPDATES = [ '3.3.1' ];
    
    /**
    * 
    * Initialize update manager
    * Registers hooks for update detection and notices
    * 
    **/
    public static function init() {
        
        // Detect plugin update
        add_action( 'upgrader_process_complete', [ self::class, 'on_plugin_updated' ], 10, 2 );
        
        // Display admin notices
        add_action( 'admin_notices', [ self::class, 'display_admin_notices' ] );
        
        // AJAX handler for database update
        add_action( 'wp_ajax_wceb_update_database', [ self::class, 'ajax_update_database' ] );

    }

    /**
    * 
    * Detect when plugin is updated
    * 
    * @param object $upgrader_object
    * @param array $options
    * 
    **/
    public static function on_plugin_updated( $upgrader_object, $options ) {
        
        // Check if this is a plugin update
        if ( $options['action'] !== 'update' || $options['type'] !== 'plugin' ) {
            return;
        }
        
        if ( ! isset( $options['plugins'] ) ) {
            return;
        }
        
        // Check if our plugin was updated
        foreach ( $options['plugins'] as $plugin ) {

            if ( $plugin === plugin_basename( WCEB_PLUGIN_FILE ) ) {
                set_transient( 'wceb_updated', 1, HOUR_IN_SECONDS );
                break;
            }

        }

    }

    /**
    * 
    * Display admin notices
    * 
    **/
    public static function display_admin_notices() {
        
        // Notice after plugin activation
        if ( get_transient( 'wceb_activated' ) ) {

            delete_transient( 'wceb_activated' );
            self::render_notice( 'activation' );

        }
        
        // Notice after plugin update
        if ( get_transient( 'wceb_updated' ) ) {

            delete_transient( 'wceb_updated' );
            
            // Only show if database needs update
            if ( ! self::needs_update() ) {
                self::render_notice( 'updated' );
            }

        }
        
        // Notice for database update
        if ( self::needs_update() ) {
            self::render_notice( 'update-database' );
        }

    }

    /**
    * 
    * Render a notice template
    * 
    * @param string $type Notice type (activation, updated, update_database)
    * 
    **/
    private static function render_notice( $type ) {
        
        $template_path = WCEB_PLUGIN_PATH . 'includes/admin/views/notices/html-wceb-notice-' . $type . '.php';

        if ( file_exists( $template_path ) ) {
            include $template_path;
        }

    }

    /**
    * 
    * AJAX handler to update database
    * 
    **/
    public static function ajax_update_database() {
        
        // Security check
        check_ajax_referer( 'wceb-hide-notice', 'security' );
        
        // Permission check
        if ( ! current_user_can( 'manage_options' ) ) {

            wp_send_json_error( [
                'message' => __( 'You do not have permission to perform this action.', 'woocommerce-easy-booking-system' )
            ] );

        }
        
        // Check if complete update parameter was passed
        $full_update = isset( $_POST['full_update'] ) ? true : false;

        // Run updates
        $result = self::maybe_update_plugin( $full_update );
        
        if ( is_wp_error( $result ) ) {

            wp_send_json_error( [
                'message' => $result->get_error_message()
            ] );

        }

        wp_send_json_success( [
            'message' => $result
        ] );

    }

    /**
    * 
    * Update Easy Booking database if needed
    * 
    * @param bool $full_update Set to true to force all updates
    * @return string Translated status message
    * 
    */
    public static function maybe_update_plugin( $full_update = false ) {
        
        $current_db_version = get_option( 'easy_booking_db_version' );
        
        // Force update or first install
        if ( $full_update || empty( $current_db_version ) ) {
            $current_db_version = '1.0.0';
        }
        
        // Nothing to update
        if ( ! version_compare( $current_db_version, self::DB_VERSION, '<' ) ) {
            return __( 'Easy Booking database is already up to date.', 'woocommerce-easy-booking-system' );
        }

        // Run updates
        foreach ( self::UPDATES as $update_version ) {
            
            // Skip if already applied
            if ( ! version_compare( $current_db_version, $update_version, '<' ) ) {
                continue;
            }
            
            // Build callback name
            $callback = 'update_db_version_' . str_replace( '.', '', $update_version );
            
            // Check if method exists
            if ( ! method_exists( self::class, $callback ) ) {
                continue;
            }
            
            try {

                // Execute update
                call_user_func( [ self::class, $callback ] );
                
                // Update version after successful update
                update_option( 'easy_booking_db_version', $update_version );
                
                wc_get_logger()->info(
                    sprintf( 'Easy Booking: Database updated to version %s', $update_version ),
                    array(
                        'source' => 'easy-booking'
                    )
                );
                
            } catch ( \Throwable $e ) {
                
                wc_get_logger()->error(
                    sprintf( 'Easy Booking: Update failed at version %s', $update_version ),
                    array(
                        'source'  => 'easy-booking',
                        'message' => $e->getMessage()
                    )
                );

                return new \WP_Error(
                    'easy_booking_database_update_errory',
                    sprintf(
                        __( 'Easy Booking database update failed at version %s. Error: %s', 'woocommerce-easy-booking-system' ),
                        $update_version,
                        $e->getMessage()
                    ),
                    'error'
                );

            }
        }
        
        return __( 'Easy Booking database update complete. Thank you!', 'woocommerce-easy-booking-system' );

    }

    /**
    * 
    * Get current database version
    * 
    * @return string
    * 
    **/
    public static function get_db_version() {
        return get_option( 'easy_booking_db_version', '1.0.0' );
    }

    /**
    * 
    * Check if database needs update
    * 
    * @return bool
    * 
    */
    public static function needs_update() {

        $current_version = self::get_db_version();

        return version_compare( $current_version, self::DB_VERSION, '<' );

    }

    /**
    * 
    * Update database to version 3.3.1
    * 
    **/
    private static function update_db_version_331() {
        global $wpdb;

        // Maybe create tables
        Install::maybe_create_tables();
        
        // Prepare args for query
        $order_statuses = wceb_get_valid_order_statuses();

        $order_statuses_placeholder = wceb_create_sql_placeholders( $order_statuses );

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

            $placeholder  = wceb_create_sql_placeholders( $added_item_ids, $format = '%d' );
            $delete_query = "DELETE FROM {$wpdb->prefix}wceb_order_bookings WHERE order_item_id NOT IN ( $placeholder )";

            $wpdb->query( $wpdb->prepare( 
                $delete_query,
                $added_item_ids
            ) );

        }

    }
    
}