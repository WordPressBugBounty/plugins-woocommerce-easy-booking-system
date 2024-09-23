<?php

/**
*
* Show a notice when a database update is available.
* @version 3.3.0
*
**/

defined( 'ABSPATH' ) || exit;

?>

<div class="notice easy-booking-notice">
	
	<p>
        <?php esc_html_e( 'A database update is required for Easy Booking. Please ensure you make sufficient backups before proceeding.', 'woocommerce-easy-booking-system' ); ?>
    </p>
	<p>

		<button type="button" class="button easy-booking-button wceb-db-update">
			<?php esc_html_e( 'Update database', 'woocommerce-easy-booking-system' ); ?>
			<span class="wceb-response"></span>
		</button>

	</p>

</div>