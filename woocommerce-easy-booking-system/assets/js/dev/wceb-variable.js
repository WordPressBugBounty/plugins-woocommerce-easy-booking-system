"use strict";

( function( $, _window ) {

	EasyBooking.VariableDatepickers = ( function() {

		/**
		 * VariableDatepickers class handles the datepicker functionality for variable products in the EasyBooking system.
		 * It extends either ProDatepickers or Datepickers class based on their availability.
		 * 
		 * @param {object} $cart - jQuery object representing the cart element.
		 */
		class VariableDatepickers extends EasyBooking.datepickersClass( 'variable' ) {

			constructor( $cart ) {

				super( $cart );

				this.$price_text = this.$cart.find( '.wceb-price-format' );
				
				this.$picker_wrap.hide();
				this.$price_text.hide();

			}

			events() {

				super.events();
				
				this.$reset_dates.on({

					// Reset dates: reset variation price
					click: () => {
						
						if ( typeof this.product.variation !== 'undefined' ) {
							this.updatePrice( this.product.variation.display_price, this.product.variation.display_regular_price );
						}

					}

				});
				
				this.$cart.on({

					// Update variation value: init
					update_variation_values: () => {
						
						this.init();
						
						// Remove any error
						this.$picker_wrap.find( '.wceb_error' ).remove();

					},

					// No variation found: init
					reset_data: () => {

						delete this.product.variation;
						delete this.product.variation_id;

						this.init();
						
						this.$picker_wrap.hide();

					},

					// Variation found: reset pickers with current variation data
					found_variation: ( _e, variation ) => {

						this.product.variation    = variation;
						this.product.variation_id = variation.variation_id;

						this.product.prices_html = "";

						if ( ! variation.is_bookable ) {
							this.$add_to_cart_button.removeClass( 'date-selection-needed' );
						}

						if ( ! variation.is_purchasable || ! variation.is_in_stock || ! variation.variation_is_visible || ! variation.is_bookable ) {

							this.$picker_wrap.hide();
							
						} else {

							this.$picker_wrap.slideDown( 200 );
							
							// Get selected variation booking settings
							this.product.booking_dates    = EASYBOOKING.product_params[variation.variation_id].booking_dates;
							this.product.first_date       = parseInt( EASYBOOKING.product_params[variation.variation_id].first_date );
							this.product.last_date        = parseInt( EASYBOOKING.product_params[variation.variation_id].last_date );
							this.product.min              = parseInt( EASYBOOKING.product_params[variation.variation_id].min );
							this.product.max              = EASYBOOKING.product_params[variation.variation_id].max === '' ? '' : parseInt( EASYBOOKING.product_params[variation.variation_id].max );
							this.product.booking_duration = parseInt( EASYBOOKING.product_params[variation.variation_id].booking_duration );
							this.product.prices_html      = EASYBOOKING.product_params[variation.variation_id].prices_html;

							this.$cart.trigger( 'wceb_update_variation', variation );

							this.product.booking_dates === 'one' ? this.$picker_wrap.find( '.show_if_two_dates' ).hide() : this.$picker_wrap.find( '.show_if_two_dates' ).show();
							
							this.updatePrice( variation.display_price, variation.display_regular_price );
							this.initPickers();

						}

						this.$cart.trigger( 'wceb_found_variation', variation );

						// Hide "/ day" or "/ night" if variation is not bookable
						( ! variation.is_bookable ) ? this.$price_text.hide() : this.$price_text.html( this.product.prices_html ).show();

					}

				});

			}

		}

		return VariableDatepickers;

	}());

	$( function() {

		$('body').find( '.cart.variations_form:not( .bundled_item_cart_content )' ).each( function () {
			const datepickers = new EasyBooking.VariableDatepickers( $(this) );
		});

	});

}(jQuery, window));