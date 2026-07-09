<?php

/**
 *
 * Admin: Pro page template.
 *
 * @version 3.4.8
 **/

defined( 'ABSPATH' ) || exit;

$active_plugins = (array) get_option( 'active_plugins', array() );

if ( is_multisite() ) {
	$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
}

?>

<?php if ( ! array_key_exists( 'easy-booking-pro/easy-booking-pro.php', $active_plugins ) && ! in_array( 'easy-booking-pro/easy-booking-pro.php', $active_plugins ) ) : ?>

	<div class="wceb-pro-reminder">

		<p>
			<?php esc_html_e( 'Unlock advanced features with Easy Booking PRO: manage stock, pricing, disabled dates and more.', 'woocommerce-easy-booking-system' ); ?>
		</p>

		<p>
			<a
				href="https://easy-booking.pro/pro/"
				class="button easy-booking-button"
				target="_blank">
				<?php esc_html_e( 'Upgrade to PRO', 'woocommerce-easy-booking-system' ); ?>
			</a>
		</p>

	</div>

<?php endif; ?>
