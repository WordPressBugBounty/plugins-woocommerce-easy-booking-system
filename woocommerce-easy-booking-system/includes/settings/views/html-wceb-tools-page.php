<?php

/**
 *
 * Admin: Tools page template.
 *
 * @version 3.5.1
 **/

defined( 'ABSPATH' ) || exit;

?>

<div class="wrap">

	<h2 class="screen-reader-text"><?php esc_html_e( 'Tools', 'woocommerce-easy-booking-system' ); ?></h2>
	
	<table class="wceb-tools-table widefat striped" cellspacing="0">

		<tbody class="tools">

			<tr>

				<th>
					
					<strong><?php esc_html_e( 'Update database', 'woocommerce-easy-booking-system' ); ?></strong>
					<p class="description"><?php esc_html_e( 'This tool will update your Easy Booking database to the latest version. Please ensure you make sufficient backups before proceeding.', 'woocommerce-easy-booking-system' ); ?></p>
				</th>

				<td class="run-tool">

					<button type="button" class="button easy-booking-button wceb-db-update">
						<?php esc_html_e( 'Update database', 'woocommerce-easy-booking-system' ); ?>
						<span class="wceb-response"></span>
					</button>
					<input type="hidden" name="wceb-full-db-update" value="1">

				</td>

			</tr>

			<tr>

				<th>

					<strong><?php esc_html_e( 'Rebuild order bookings', 'woocommerce-easy-booking-system' ); ?></strong>
					<p class="description"><?php esc_html_e( 'This tool rebuilds the order bookings table from WooCommerce orders. Orders are processed in small batches, and no WooCommerce order data is deleted.', 'woocommerce-easy-booking-system' ); ?></p>
				</th>

				<td class="run-tool">

					<button type="button" class="button easy-booking-button wceb-rebuild-order-bookings">
						<?php esc_html_e( 'Rebuild order bookings', 'woocommerce-easy-booking-system' ); ?>
					</button>
					<p class="wceb-rebuild-order-bookings-progress description" aria-live="polite"></p>

				</td>

			</tr>

			<tr>

				<th>
					
					<strong><?php esc_html_e( 'Initialize booking statuses', 'woocommerce-easy-booking-system' ); ?></strong>
					<p class="description"><?php esc_html_e( 'This tool will initialize booking statuses by getting all bookings made on your store (from orders and imports).', 'woocommerce-easy-booking-system' ); ?></p>
				</th>

				<td class="run-tool">

					<button type="button" class="button easy-booking-button wceb-init-booking-statuses">
						<?php esc_html_e( 'Initialize booking statuses', 'woocommerce-easy-booking-system' ); ?>
						<span class="wceb-response"></span>
					</button>

				</td>

			</tr>

			<?php do_action( 'easy_booking_tools' ); ?>

		</tbody>

	</table>
	
	<?php do_action( 'easy_booking_pro_page' ); // Backward compatibility ?>

</div>
