<?php

namespace EasyBooking;

/**
*
* Install Easy Booking.
* @version 3.4.8
*
**/

defined( 'ABSPATH' ) || exit;

class Install {

    /**
    *
    * Install plugin tables on admin init.
    *
    **/
	public static function init() {

        add_action( 'admin_init', array( __CLASS__, 'install' ) );

	}

    /**
    *
    * Install plugin tables.
    *
    **/
    public static function install() {

        try {

            // Maybe create or update wceb_bookings table
            self::maybe_create_tables();

            // Database updates
            Update_Manager::init();

        } catch ( \Exception $e ) {

            add_action( 'admin_notices', function() use ( $e ) {
                echo '<div class="error"><p>' . $e->getMessage() . '</p></div>';
            });

        }

    }
	
    /**
    *
    * Create 'wceb_order_bookings' table.
    *
    **/
    public static function maybe_create_tables() {
        global $wpdb;

        $wpdb->show_errors();
        
        $table_name      = $wpdb->prefix . 'wceb_order_bookings';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "
        CREATE TABLE $table_name (
            order_item_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            start date NOT NULL,
            end date,
            status varchar(200) NOT NULL,
            qty int(11) NOT NULL,
            order_id bigint(20) NOT NULL,
            PRIMARY KEY  (order_item_id)
        ) $charset_collate;
            ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $success = maybe_create_table( $table_name, $sql );

        // Table creation failed
        if ( ! $success ) {

            $error = ! empty( $wpdb->last_error )
                ? $wpdb->last_error
                : sprintf(
                    esc_html__( 'Does the %1$s user have CREATE privileges on the %2$s database?', 'woocommerce-easy-booking-system' ),
                    '<code>' . esc_html( DB_USER ) . '</code>',
                    '<code>' . esc_html( DB_NAME ) . '</code>'
                );

            throw new \Exception( sprintf(
                __( 'Easy Booking table creation failed. %s', 'woocommerce-easy-booking-system' ),
                $error
            ));
            
        }

        // Maybe update table to latest version
        if ( get_option( 'wceb_order_bookings_table_version' ) !== WCEB_ORDER_BOOKINGS_TABLE_VERSION ) {

            dbDelta( $sql );

            update_option( 'wceb_order_bookings_table_version', WCEB_ORDER_BOOKINGS_TABLE_VERSION );

        }

    }

}

Install::init();