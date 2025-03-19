"use strict";

( function( $, window ) {

	EasyBooking.BundleDatepickers = ( function() {

		class BundleDatepickers extends EasyBooking.datepickersClass( 'bundle' ) {

			constructor( $cart ) {
				
				super( $cart );

				this.$qty_input = this.$cart.find(`.cart.bundle_data[data-bundle_id='${this.product.id}'] input[name="quantity"]`);

				this.$cart.find('.bundle_price').remove();
				this.$picker_wrap.hide();

				this.bundleEvents();

			}

			bundleEvents() {

				var self = this;

				// Reset dates: reset bundle price
				self.$reset_dates.on( 'click', function () {
					self.updatePrice( self.product.bundle_totals.price, self.product.bundle_totals.regular_price );
				});

				// Valid bundle configuration found: show datepickers
				self.$cart.on( 'woocommerce-product-bundle-show', function() {
					self.$picker_wrap.slideDown( 200 );
				});

				// Invalid bundle configuration: hide datepickers
				self.$cart.on( 'woocommerce-product-bundle-hide', function() {
					self.$picker_wrap.hide();
				});

				// Bundle updated: update selected items
				self.$cart.on(
					'woocommerce-product-bundle-updated',
					function ( e, bundled_item ) {

						// Store previously selected items
						let previouslySelectedIDs = Object.assign( {}, self.product.selectedIDs );

						// Store bundle totals
						self.product.bundle_totals = bundled_item.api.get_bundle_totals();

						// Reset selected IDs
						self.product.selectedIDs = {};

						// Add main bundle product
						self.product.selectedIDs[self.product.id] = self.$qty_input.val() === 'false' ? 1 : parseFloat( self.$qty_input.val() );

						$.each( bundled_item.api.get_bundle_configuration(), function( id, data ) {

							if ( data.product_type === 'variable' && data.variation_id.length !== 0 && data.quantity > 0 ) {

								let variation_data = self.$cart.find( `.cart[data-bundled_item_id="${id}"]` ).data( 'product_variations' );
								let variation      = variation_data.find( item => item.variation_id == data.variation_id && item.is_bookable );

								// Trigger custom "found_variation" event because PB added event.stopPropagation() on variations_found event
								if ( typeof variation !== 'undefined' ) {
									self.$cart.trigger( 'wceb_variation_found', [variation, self.$cart] );
								}
								
								self.product.selectedIDs[data.variation_id] = parseFloat( data.quantity );

							} else if ( data.product_type !== 'variable' && data.quantity > 0 ) { 

								self.product.selectedIDs[data.product_id] = parseFloat( data.quantity );

							}

						});

						self.$cart.trigger( 'wceb_update_bundle_selection' );

						self.handleMultipleProductSelection( previouslySelectedIDs, self.product.bundle_totals.price, self.product.bundle_totals.regular_price );

					}
					
				);
	
			}

		}

		return BundleDatepickers;

	}());

	jQuery( function() {

		$('body').find('.cart.bundle_form').each( function() {
			let $datepickers = new EasyBooking.BundleDatepickers( $(this) );
		});

	});

}(jQuery, window));