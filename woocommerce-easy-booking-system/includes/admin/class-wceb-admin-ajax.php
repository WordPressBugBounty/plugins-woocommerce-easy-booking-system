<?php

namespace EasyBooking;

/**
*
* Admin AJAX.
* @version 3.3.0
*
**/

defined( 'ABSPATH' ) || exit;

class Admin_Ajax {

	public function __construct() {

        add_action( 'wp_ajax_wceb_update_database', array( $this, 'maybe_update_database' ) );
        add_action( 'wp_ajax_wceb_hide_admin_notice', array( $this, 'hide_admin_notice' ) );
        add_action( 'wp_ajax_wceb_init_booking_statuses', array( $this, 'init_booking_statuses' ) );
        add_action( 'wp_ajax_wceb_reports_product_search', array( $this, 'reports_product_search' ) );

	}

	/**
    *
    * Ajax function to update database.
    *
    **/
    public function maybe_update_database() {

        check_admin_referer( 'wceb-hide-notice', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce-easy-booking-system' ) );
        }

        // Check if complete update parameter was passed
        $full_update    = isset( $_POST['full_update'] ) ? true : false;
        $update_message = wceb_db_update( $full_update );
            
        wp_die( esc_html( $update_message ) );

    }

    /**
    *
    * Hide notices after clicking the "close" button.
    *
    **/
    public static function hide_admin_notice() {

        check_admin_referer( 'wceb-hide-notice', 'security' );

        $notice = isset( $_POST['notice'] ) ? sanitize_text_field( $_POST['notice'] ) : '';

        if ( get_option( 'easy_booking_display_notice_' . $notice ) !== '1' ) {
            update_option( 'easy_booking_display_notice_' . $notice, '1' );
        }

        die();

    }

    /**
    *
    * Init booking statuses.
    *
    **/
    public static function init_booking_statuses() {

        check_admin_referer( 'wceb-hide-notice', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You don&#8217;t have permission to do this.', 'woocommerce-easy-booking-system' ) );
        }

        do_action( 'wceb_update_booking_statuses_event' );

        wp_die( esc_html__( 'Booking statuses successfully initialized!', 'woocommerce-easy-booking-system' ) );

    }

    /**
    *
    * Get filtered products on reports page.
    *
    **/
    public static function reports_product_search() {
        
        ob_start();

        check_ajax_referer( 'search-products', 'security' );

        $term               = (string) wc_clean( stripslashes( $_GET['term'] ) );
        $booked_product_ids = wceb_get_bookings_product_ids();
        $found_products     = array();
        
        if ( $booked_product_ids ) foreach ( $booked_product_ids as $product_id ) {

            $product = wc_get_product( $product_id );

            if ( ! $product ) {
                continue;
            }
            
            $product_name = get_the_title( $product_id );
            
            if ( stristr( $product_name, $term ) !== FALSE ) {
                $found_products[ $product_id ] = $product->get_formatted_name();
            }

        }

        wp_send_json( $found_products );
        die();

    }

}

new Admin_Ajax();