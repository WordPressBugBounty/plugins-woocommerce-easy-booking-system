"use strict";

( function( $, window ) {

	EasyBooking.GroupedDatepickers = ( function() {

		class GroupedDatepickers extends EasyBooking.datepickersClass( 'grouped' ) {

			constructor( $cart ) {
				
				super( $cart );
				
				this.$picker_wrap.hide();
				this.$booking_price.hide();

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

				// Block forms synchronize through the Interactivity API instead of DOM changes.
				if ( ! self.$cart.is( '.wp-block-woocommerce-add-to-cart-with-options' ) ) {
					self.$cart.find( 'input.qty, .wc-grouped-product-add-to-cart-checkbox' ).on( 'change', () => {
						self.updateSelection();
					} );
				}

			}

			/**
			 * Update selected children from classic inputs or block quantities.
			 * @param {object} quantities Quantities supplied by the block context, when available.
			 */
			updateSelection( quantities ) {
				const previouslySelectedIDs = { ...this.product.selectedIDs };
				this.product.group_totals = { price: 0, regular_price: 0 };
				this.product.selectedIDs = {};

				this.product.children.forEach( ( child ) => {
					let quantity;

					if ( quantities ) {
						quantity = quantities[child];
					} else {
						const $input = this.$cart.find( 'input[name="quantity[' + child + ']"]' );
						quantity = $input.is( '.wc-grouped-product-add-to-cart-checkbox' )
							? ( $input.is( ':checked' ) ? 1 : 0 )
							: $input.val();
					}

					quantity = parseFloat( quantity );
					if ( Number.isFinite( quantity ) && quantity > 0 ) {
						this.product.selectedIDs[child] = quantity;
						this.product.group_totals.price += parseFloat( this.product.prices[child] * quantity );
						this.product.group_totals.regular_price += parseFloat( this.product.regular_prices[child] * quantity );
					}
				} );

				if ( JSON.stringify( previouslySelectedIDs ) !== JSON.stringify( this.product.selectedIDs ) ) {
					this.groupSelectionRevision = ( this.groupSelectionRevision || 0 ) + 1;
				}

				this.$cart.trigger( 'wceb_update_group_selection' );
				this.handleMultipleProductSelection( previouslySelectedIDs, this.product.group_totals.price, this.product.group_totals.regular_price );

				if ( Object.keys( this.product.selectedIDs ).length ) {
					this.$picker_wrap.stop( true, true ).slideDown( 200 );
					this.$booking_price.stop( true, true ).slideDown( 200 );
					this.$cart.trigger( 'wceb_after_update_group_selection' );
				} else {
					this.$picker_wrap.stop( true, true ).hide();
					this.$booking_price.stop( true, true ).hide();
				}
			}

		}

		return GroupedDatepickers;

	})();

	jQuery( function() {

		$('body').find( '.cart.grouped_form:not( .wp-block-woocommerce-add-to-cart-with-options )' ).each( function() {
			const datepickers = new EasyBooking.GroupedDatepickers( $(this) );
		});

	});

})(jQuery, window);