<?php

namespace EasyBooking;

/**
*
* Variation settings.
 *
* @version 3.4.4
*/

defined( 'ABSPATH' ) || exit;

class Admin_Variation {

	public function __construct() {

		add_action( 'woocommerce_variation_options', array( $this, 'add_bookable_option' ), 10, 3 );
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'variation_booking_options' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation_booking_options' ), 10, 2 );
	}

	/**
	 *
	 * Add a "Bookable" checkbox to product variations.
	 *
	 * @param int -     $loop
	 * @param array -   $variation_data
	 * @param WP_POST - $variation
	 **/
	public function add_bookable_option( $loop, $variation_data, $variation ) {

		$variation_object = wc_get_product( $variation->ID );

		$is_bookable = is_a( $variation_object, 'WC_Product_Variation' ) ? $variation_object->get_meta( '_bookable', true ) : '';

		// Backward compatibility < 2.2.4
		if ( is_a( $variation_object, 'WC_Product_Variation' ) && empty( $is_bookable ) ) {
			$is_bookable = $variation_object->get_meta( '_booking_option', true );
		}

		?>
		
			<label class="show_if_bookable">

				<input type="checkbox" class="checkbox variation_is_bookable" name="_var_bookable[<?php echo absint( $loop ); ?>]" <?php checked( $is_bookable, 'yes' ); ?> />
				<?php esc_html_e( 'Bookable', 'woocommerce-easy-booking-system' ); ?>

			</label>
		
		<?php
	}

	/**
	 *
	 * Display booking options for variations.
	 *
	 * @param int -     $loop
	 * @param array -   $variation_data
	 * @param WP_POST - $variation
	 **/
	public function variation_booking_options( $loop, $variation_data, $variation ) {

		$variation_id = $variation->ID;
		include 'views/products/html-wceb-variation-options.php';
	}

	/**
	 *
	 * Save checkbox value and booking options for variations.
	 *
	 * @param int $variation_id
	 * @param int $i - The loop
	 **/
	public function save_variation_booking_options( $variation_id, $i ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Checked by WooCommerce
		$booking_data = array(
			'bookable'             => isset( $_POST['_var_bookable'][ $i ] ) ? 'yes' : 'no',
			'dates'                => isset( $_POST['_var_number_of_dates'][ $i ] ) ? wp_unslash( $_POST['_var_number_of_dates'][ $i ] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Sanitized in wceb_save_product_booking_options(), nonce checked by WooCommerce
			'booking_min'          => isset( $_POST['_var_booking_min'][ $i ] ) ? wp_unslash( $_POST['_var_booking_min'][ $i ] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Sanitized in wceb_save_product_booking_options(), nonce checked by WooCommerce
			'booking_max'          => isset( $_POST['_var_booking_max'][ $i ] ) ? wp_unslash( $_POST['_var_booking_max'][ $i ] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Sanitized in wceb_save_product_booking_options(), nonce checked by WooCommerce
			'first_available_date' => isset( $_POST['_var_first_available_date'][ $i ] ) ? wp_unslash( $_POST['_var_first_available_date'][ $i ] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Sanitized in wceb_save_product_booking_options(), nonce checked by WooCommerce
			'last_available_date'  => isset( $_POST['_var_last_available_date'][ $i ] ) ? wp_unslash( $_POST['_var_last_available_date'][ $i ] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Sanitized in wceb_save_product_booking_options(), nonce checked by WooCommerce
			'booking_duration'     => isset( $_POST['_var_booking_duration'][ $i ] ) ? wp_unslash( $_POST['_var_booking_duration'][ $i ] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Missing -- Sanitized in wceb_save_product_booking_options(), nonce checked by WooCommerce
		);

		wceb_save_product_booking_options( $variation_id, $booking_data );
	}
}

new Admin_Variation();
