<?php

namespace EasyBooking;

/**
*
* Load frontend assets.
* @version 3.3.6
*
**/

defined( 'ABSPATH' ) || exit;

class Frontend_Assets {

    public function __construct() {

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 15 );
        
    }

    public function enqueue_scripts() {
        global $post;

        $IDS = array();

        // Load scripts on pages containing Single product blocks
        if ( has_block('woocommerce/single-product', $post->ID ) ) {

            $content = get_post_field( 'post_content' );
            $blocks  = parse_blocks( $content );

            foreach ( $blocks as $block ) {

                if ( $block['blockName'] === 'woocommerce/single-product' ) {
                    $IDS[] = $block['attrs']['productId'];
                }

            }

            $this->maybe_enqueue_product_assets( $IDS );

        // Load scripts on single product page
        } else if ( is_product() ) {

            $this->maybe_enqueue_product_assets( array( $post->ID ) );

        }
        
    }

    private function maybe_enqueue_product_assets( $product_ids ) {

        $products      = array();
        $product_types = array();

        foreach ( $product_ids as $product_id ) {

            $product = wc_get_product( $product_id );

            // Don't load scripts if product is out-of-stock (non-variable products only)
            if ( ! $product->is_type( 'variable' ) && $product->managing_stock() && ! $product->is_in_stock() ) {
                continue;
            }

            // Load scripts only if product is bookable
            if ( wceb_is_bookable( $product ) ) {
                $products[]      = $product;
                $product_types[] = $product->get_type();
            }

        }

        if ( ! empty( $products ) ) {

            $product_types = array_unique( $product_types );

            // Register scripts
            $this->register_frontend_scripts( $products, $product_types );

            // Register styles
            $this->register_frontend_styles();

            wp_enqueue_script( 'accounting' );
            wp_enqueue_script( 'pickadate' );

            wp_enqueue_script( 'wceb-datepickers' );
            
            // Hook to load additional scipts or stylesheets
            do_action( 'easy_booking_enqueue_additional_scripts', $products, $product_types ); 

            foreach ( $product_types as $product_type ) {
                wp_enqueue_script( 'wceb-single-product-' . $product_type );
            }

            wp_enqueue_script( 'pickadate.language' );

            wp_enqueue_style( 'picker' );

            // Load Right to left CSS file if necessary
            if ( is_rtl() ) {
                wp_enqueue_style( 'rtl-style' );
            }

        }

    }

    /**
    *
    * Register frontend scripts.
    *
    **/
    private function register_frontend_scripts( $products, $product_types ) {

        Pickadate::register_scripts();

        // Load accounting.js script
        wp_register_script(
            'accounting',
            WC()->plugin_url() . '/assets/js/accounting/accounting' . WCEB_SUFFIX . '.js',
            array( 'jquery' ),
            '0.4.2'
        );

        // Filter for third-party plugins
        $dependencies = apply_filters(
            'easy_booking_script_dependencies',
            array( 'jquery', 'pickadate', 'accounting' )
        );

        // Main Easy Booking script
        wp_register_script(
            'wceb-datepickers',
            wceb_get_file_path( '', 'wceb', 'js' ),
            $dependencies,
            '1.0',
            true
        );

        wp_add_inline_script( 
            'wceb-datepickers',
            'const EASYBOOKING = ' . json_encode( $this->get_frontend_parameters( $products ) ),
            'before'
        );
        
        // Script for each product type
        foreach ( $product_types as $product_type ) {

            // Filter for Easy Booking PRO
            $product_dependencies = apply_filters(
                'easy_booking_single_product_script_dependencies',
                array( 'jquery', 'pickadate', 'wceb-datepickers' ),
                $product_type
            );

            wp_register_script(
                'wceb-single-product-' . $product_type,
                wceb_get_file_path( '', 'wceb-' . $product_type, 'js' ),
                $product_dependencies,
                '1.0',
                true
            );

        }

    }

    /**
    *
    * Register frontend styles.
    *
    **/
    private function register_frontend_styles() {
        Pickadate::register_styles();
    }

    /**
    *
    * Get parameters to pass to frontend scripts.
    * @param WC_Product - $product
    *
    * @return array - $frontend_parameters
    *
    **/
    private function get_frontend_parameters( $products ) {
 
        // Ajax URL
        $home_url = apply_filters( 'easy_booking_home_url', home_url( '/' ) );
        $ajax_url = add_query_arg( 'wceb-ajax', '%%endpoint%%', $home_url );
        $ajax_url = str_replace( array( 'http:', 'https:' ), '', $ajax_url ); // Fix to avoid security fails

        // Days or Nights mode
        $booking_mode = get_option( 'wceb_booking_mode' );

        // Last available date relative to the current day
        $last_available_date = wceb_shift_date( date( 'Y-m-d' ), get_option( 'wceb_last_available_date' ) );

        $product_params = array();
        foreach ( $products as $product ) :

            // Product type
            $product_type = $product->get_type();

            // Booking settings and prices for each product ype
            switch ( $product_type ) {
                case 'variable' :

                    // Parent booking settings
                    $product_params[$product->get_id()] = $this->get_product_settings( $product, $product_type );

                    if ( $product->get_children() ) foreach ( $product->get_children() as $variation_id ) {

                        $variation = wc_get_product( $variation_id );

                        if ( ! wceb_is_bookable( $variation ) ) {
                            continue;
                        }

                        $product_params[$variation_id] = $this->get_variation_settings( $variation );

                    }

                break;
                case 'grouped' :

                    $children = $product->get_children();

                    $product_params[$product->get_id()] = $this->get_product_settings( $product, $product_type, $children );

                    // Get grouped product children prices
                    if ( $children ) foreach ( $children as $child_id ) {

                        $child = wc_get_product( $child_id );

                        $child_price         = wc_get_price_to_display( $child, array( 'price' => $child->get_price() ) );
                        $child_regular_price = wc_get_price_to_display( $child, array( 'price' => $child->get_regular_price() ) );

                        $product_params[$product->get_id()]['prices'][$child_id]         = wceb_sanitize_parameters( $child_price, 'wc_format_decimal' );
                        $product_params[$product->get_id()]['regular_prices'][$child_id] = wceb_sanitize_parameters( $child_regular_price, 'wc_format_decimal' );

                    }

                break;
                default:

                    $product_params[$product->get_id()] = $this->get_product_settings( $product, $product_type );
                    
                break;

            }

        endforeach;
        
        // Datepickers parameters
        $frontend_parameters = array(
            'ajax_url'                     => esc_url_raw( $ajax_url ),
            'calc_mode'                    => esc_html( $booking_mode ),
            'last_date'                    => esc_html( $last_available_date ),
            'first_weekday'                => absint( get_option( 'start_of_week' ) ),
            'currency_format_num_decimals' => absint( get_option( 'woocommerce_price_num_decimals' ) ),
            'currency_format_symbol'       => get_woocommerce_currency_symbol(),
            'currency_format_decimal_sep'  => esc_attr( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ) ),
            'currency_format_thousand_sep' => esc_attr( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ) ),
            'currency_format'              => esc_attr( str_replace( array( '%1$s', '%2$s' ), array( '%s', '%v' ), get_woocommerce_price_format() ) ), // For accounting JS
            'product_params'               => $product_params
        );

        return apply_filters( 'easy_booking_frontend_parameters', $frontend_parameters );

    }

    private function get_product_settings( $_product, $product_type, $children = array() ) {

        $settings = wceb_get_product_booking_settings( $_product );

        $product_params = array(
            'product_type'         => esc_html( $product_type ),
            'children'             => array_map( 'absint', $children ),
            'start_text'           => esc_html( wceb_get_start_text( $_product ) ),
            'end_text'             => esc_html( wceb_get_end_text( $_product ) ),
            'select_dates_message' => esc_html( wceb_get_select_dates_error_message( $_product ) ),
            'booking_dates'        => wceb_sanitize_parameters( $settings['booking_dates'], 'esc_html' ),
            'booking_duration'     => wceb_sanitize_parameters( $settings['booking_duration'], 'absint' ),
            'min'                  => wceb_sanitize_parameters( $settings['booking_min'], 'absint' ),
            'max'                  => wceb_sanitize_parameters( $settings['booking_max'], 'esc_html' ),
            'first_date'           => wceb_sanitize_parameters( $settings['first_available_date'], 'absint' ),
            'prices_html'          => wceb_sanitize_parameters( wceb_get_product_price_suffix( $_product ), 'esc_html' ),
            'price_suffix'         => $_product->get_price_suffix()
        );

        return $product_params;

    }

    private function get_variation_settings( $variation ) {

        $settings = wceb_get_product_booking_settings( $variation );

        $variation_params = array(
            'booking_dates'    => wceb_sanitize_parameters( $settings['booking_dates'], 'esc_html' ),
            'booking_duration' => wceb_sanitize_parameters( $settings['booking_duration'], 'absint' ),
            'min'              => wceb_sanitize_parameters( $settings['booking_min'], 'absint' ),
            'max'              => wceb_sanitize_parameters( $settings['booking_max'], 'esc_html' ),
            'first_date'       => wceb_sanitize_parameters( $settings['first_available_date'], 'absint' ),
            'prices_html'      => wceb_sanitize_parameters( wceb_get_product_price_suffix( $variation ), 'esc_html' )
        );

        return $variation_params;

    }
}

new Frontend_Assets();