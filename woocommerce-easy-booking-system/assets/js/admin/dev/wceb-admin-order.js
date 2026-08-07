(function( $ ) {

	'use strict';

	$( function() {

		$.extend( $.fn.pickadate.defaults, {
			hiddenName   : true,
			selectYears  : true,
			selectMonths : true
		} );

		$( '#woocommerce-order-items' ).on( 'click', 'a.edit-order-item', function() {

			let $line       = $( this ).closest( 'tr' );
			let $startInput = $line.find( '.wceb_datepicker[class*="wceb_datepicker_start--"]' );
			let $endInput   = $line.find( '.wceb_datepicker[class*="wceb_datepicker_end--"]' );

			if ( ! $startInput.length || $startInput.data( 'wceb-order-picker-initialized' ) ) {
				return;
			}

			$startInput.data( 'wceb-order-picker-initialized', true );
			$startInput.pickadate();

			let pickerStart = $startInput.pickadate( 'picker' );
			let setStart    = $startInput.data( 'value' );

			if ( ! $endInput.length ) {
				if ( setStart ) {
					pickerStart.set( 'select', setStart, { format: 'yyyy-mm-dd' } );
				}

				return;
			}

			$endInput.pickadate();

			let pickerEnd = $endInput.pickadate( 'picker' );
			let setEnd    = $endInput.data( 'value' );
			let dayOffset = wceb_admin_order.booking_mode === 'days' ? 0 : 1;

			function getLimit( picker, offset ) {
				let selected = picker.get( 'select' );

				return selected ? [selected.year, selected.month, selected.date + offset] : false;
			}

			pickerStart.on( 'set', function( event ) {
				if ( Object.prototype.hasOwnProperty.call( event, 'clear' ) ) {
					pickerEnd.set( 'min', false );
				} else if ( Object.prototype.hasOwnProperty.call( event, 'select' ) ) {
					pickerEnd.set( 'min', getLimit( pickerStart, dayOffset ) );
				}
			} );

			pickerEnd.on( 'set', function( event ) {
				if ( Object.prototype.hasOwnProperty.call( event, 'clear' ) ) {
					pickerStart.set( 'max', false );
				} else if ( Object.prototype.hasOwnProperty.call( event, 'select' ) ) {
					pickerStart.set( 'max', getLimit( pickerEnd, -dayOffset ) );
				}
			} );

			if ( setStart ) {
				pickerStart.set( 'select', setStart, { format: 'yyyy-mm-dd' } );
			}

			if ( setEnd ) {
				pickerEnd.set( 'select', setEnd, { format: 'yyyy-mm-dd' } );
			}

			pickerEnd.set( 'min', getLimit( pickerStart, dayOffset ) );
			pickerStart.set( 'max', getLimit( pickerEnd, -dayOffset ) );
		} );
	} );

})( jQuery );
