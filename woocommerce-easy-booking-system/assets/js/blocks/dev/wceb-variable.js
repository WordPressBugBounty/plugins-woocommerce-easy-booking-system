import { getConfig, getContext, getElement, store } from '@wordpress/interactivity';

// Each form keeps its own calendars and pending variation update.
const forms = new WeakMap();

store( 'easy-booking/variable-product', {
	callbacks: {
		syncVariation() {
			const { ref } = getElement();
			const form = ref.closest( 'form' );
			const context = getContext( 'woocommerce/add-to-cart-with-options' );

			if ( ! form || ! context || ! window.EasyBooking?.VariableDatepickers ) {
				return;
			}

			const productId = form.querySelector( '[name="add-to-cart"]' )?.value;
			const variations = getConfig().variations?.[ productId ];

			if ( ! variations ) {
				return;
			}

			// Reading this context makes WordPress call us again when options change.
			const selection = JSON.stringify( context.selectedAttributes || [] );
			let current = forms.get( form );

			if ( ! current ) {
				current = {
					datepickers: new window.EasyBooking.VariableDatepickers( window.jQuery( form ) ),
					selection: null,
					frame: null,
				};
				forms.set( form, current );
			}

			if ( current.selection === selection ) {
				return;
			}

			current.selection = selection;
			current.datepickers.resetVariation();

			if ( current.frame !== null ) {
				window.cancelAnimationFrame( current.frame );
			}

			// Let WooCommerce finish updating its existing hidden variation input.
			current.frame = window.requestAnimationFrame( () => {
				current.frame = null;

				if ( ! form.isConnected ) {
					return;
				}

				// A cleared or incomplete selection may leave the previous hidden value in the DOM.
				if ( ! context.selectedAttributes.length || context.validationErrors.some( error => error.code === 'variableProductMissingAttributes' ) ) {
					return;
				}

				const variationId = form.querySelector( '[name="variation_id"]' )?.value;
				const variation = variations[ variationId ];

				if ( variation ) {
					current.datepickers.setVariation( variation );
				}
			} );
		},
	},
} );
