"use strict";

( function( $, window ) {

	EasyBooking.SimpleDatepickers = ( function() {
		
		class SimpleDatepickers extends EasyBooking.datepickersClass( 'simple' ) {

			constructor( $cart ) {
				super( $cart );
			}

		}

		return SimpleDatepickers;

	}());

	$( function() {

		$('body').find( 'form.cart:not( .variations_form, .grouped_form, .bundle_form, .bundle_data )' ).each( function() {

			// Variable and grouped block forms have their own initialization.
			if ( $(this).find( '.wceb_picker_wrap[data-wp-interactive="easy-booking/variable-product"], .wceb_picker_wrap[data-wp-interactive="easy-booking/grouped-product"]' ).length ) {
				return;
			}

			const datepickers = new EasyBooking.SimpleDatepickers( $(this) );

		});

	});

}(jQuery, window));
