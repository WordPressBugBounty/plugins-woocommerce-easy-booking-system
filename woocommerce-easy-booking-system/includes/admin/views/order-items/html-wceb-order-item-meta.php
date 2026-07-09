<?php

/**
 *
 * Display bookable order items meta data on order pages.
 *
 * @version 3.0.0
 **/

defined( 'ABSPATH' ) || exit;

?>

<div class="view">

	<table cellspacing="0" class="display_meta">
		
		<tbody>

			<tr>
				<th>
					<?php
						// translators: %s is start date label.
						printf( esc_html__( '%s: ', 'woocommerce-easy-booking-system' ), esc_html( $start_date_text ) );
					?>
				</th>
				<td><p><?php echo esc_html( $start_date_i18n ); ?></p></td>
			</tr>
			
			<?php if ( ! empty( $end_date ) ) : ?>

				<tr>
					<th>
						<?php
							// translators: %s is end date label.
							printf( esc_html__( '%s: ', 'woocommerce-easy-booking-system' ), esc_html( $end_date_text ) );
						?>
					</th>
					<td><p><?php echo esc_html( $end_date_i18n ); ?></p></td>
				</tr>

			<?php endif; ?>

			<?php if ( ! empty( $booking_status ) ) : ?>
				
				<tr>
					<th><?php esc_html_e( 'Booking status', 'woocommerce-easy-booking-system' ); ?>: </th>
					<?php

						$status = ucfirst( str_replace( 'wceb-', '', $booking_status ) );
						$display_status = apply_filters( 'easy_booking_display_status_' . $status, ucfirst( $status ) );
						
					?>
					<td><p><?php echo esc_html( $display_status ); ?></p></td>
				</tr>

			<?php endif; ?>

		</tbody>

	</table>

</div>
