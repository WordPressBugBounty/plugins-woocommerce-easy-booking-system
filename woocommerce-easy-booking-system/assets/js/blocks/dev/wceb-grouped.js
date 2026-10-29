import { getContext, getElement, store } from '@wordpress/interactivity';

const forms = new WeakMap();

store( 'easy-booking/grouped-product', {
	callbacks: {
		syncSelection() {
			const { ref } = getElement();
			const form = ref.closest( 'form' );
			const context = getContext( 'woocommerce/add-to-cart-with-options' );

			if ( ! form || ! context?.quantity || ! window.EasyBooking?.GroupedDatepickers ) {
				return;
			}

			// Observe quantities directly: stepper events can precede the input's DOM update.
			const selection = JSON.stringify( context.quantity );
			let current = forms.get( form );

			if ( ! current ) {
				current = {
					datepickers: new window.EasyBooking.GroupedDatepickers( window.jQuery( form ) ),
					selection: null,
				};
				forms.set( form, current );
			}

			if ( current.selection === selection ) {
				return;
			}

			// Set this first because updating calendars can dispatch other form events.
			current.selection = selection;
			current.datepickers.updateSelection( { ...context.quantity } );
		},
	},
} );
