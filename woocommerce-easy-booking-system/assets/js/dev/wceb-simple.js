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

			const datepickers = new EasyBooking.SimpleDatepickers( $(this) );

		});

	});

}(jQuery, window));