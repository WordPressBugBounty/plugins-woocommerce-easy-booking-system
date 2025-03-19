"use strict";

( function( $, window ) {

	EasyBooking.GroupedDatepickers = ( function() {

		class GroupedDatepickers extends EasyBooking.datepickersClass( 'grouped' ) {

			constructor( $cart ) {
				
				super( $cart );
				
				this.$picker_wrap.hide();

			}

			events() {

				super.events();

				var self = this;

				// Reset dates: reset group price
				self.$reset_dates.on({
					click: () => {
						self.updatePrice( self.product.group_totals.price, self.product.group_totals.regular_price );
					}
				});

				// Change product quantity: update selected items
				self.$cart.find( 'input.qty, .wc-grouped-product-add-to-cart-checkbox' ).on({

					change: () => {

						// Store previously selected items
						let previouslySelectedIDs = Object.assign( {}, self.product.selectedIDs );

						// Store group totals
						self.product.group_totals = { price: 0, regular_price: 0 }

						// Reset selected IDs
						self.product.selectedIDs = {};

						$.each( self.product.children, function( index, child ) {

							let $child_input = self.$cart.find( `input[name="quantity[${child}]"]` );
							let quantity     = $child_input.val();
	
							if ( $child_input.is( '.wc-grouped-product-add-to-cart-checkbox' ) ) {
								quantity = $child_input.is( ':checked' ) ? 1 : 0; // Sold individually products
							}
							
							if ( quantity > 0 ) {

								self.product.selectedIDs[child] = parseFloat( quantity );

								self.product.group_totals.price += parseFloat( self.product.prices[child] * quantity );
								self.product.group_totals.regular_price += parseFloat( self.product.regular_prices[child] * quantity );

							}
	
						});

						this.$cart.trigger( 'wceb_update_group_selection' );

						self.handleMultipleProductSelection( previouslySelectedIDs, self.product.group_totals.price, self.product.group_totals.regular_price );

						// Get highest quantity selected and hide date inputs if no quantity is selected
						Math.max.apply( Math, Object.values( self.product.selectedIDs ) ) > 0 ? self.$picker_wrap.slideDown( 200 ) : self.$picker_wrap.hide();

					}

				});

			}

		}

		return GroupedDatepickers;

	})();

	jQuery( function() {

		$('body').find('.cart.grouped_form').each( function() {
			const datepickers = new EasyBooking.GroupedDatepickers( $(this) );
		});

	});

})(jQuery, window);