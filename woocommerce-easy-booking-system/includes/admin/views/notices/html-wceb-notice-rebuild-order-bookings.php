<?php

/**
 * Recommend rebuilding order bookings after a legacy database update.
 *
 * @version 3.5.1
 */

defined( 'ABSPATH' ) || exit;

?>

<div class="notice notice-warning easy-booking-notice">

	<p>
		<?php esc_html_e( 'The Easy Booking database structure has been updated. Please rebuild order bookings from WooCommerce orders to synchronize existing bookings.', 'woocommerce-easy-booking-system' ); ?>
	</p>

	<p>
		<a class="button easy-booking-button" href="<?php echo esc_url( admin_url( 'admin.php?page=easy-booking-tools' ) ); ?>">
			<?php esc_html_e( 'Open Easy Booking tools', 'woocommerce-easy-booking-system' ); ?>
		</a>
	</p>

</div>
