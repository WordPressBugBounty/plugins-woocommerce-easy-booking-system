(function($) {

	$(document).ready(function() {

		$.extend( $.fn.pickadate.defaults, {
			hiddenName  : true,
			selectYears : true,
  			selectMonths: true
		});

		var format = $.fn.pickadate.defaults.format;

		var item_picker = $('#woocommerce-order-items').on( 'click', 'a.edit-order-item', function() {

			var line            = $(this).parents( 'tr' );
			var datepickerInput = line.find( '.wceb_datepicker' );
			var item_id         = $( '.order_item_id' ).val();

			var $input = datepickerInput.pickadate();

			if ( datepickerInput.length && item_id.length ) {

				var $inputStart = $( '.wceb_datepicker_start--' + item_id ).pickadate();
				var pickerStart = $inputStart.pickadate( 'picker' );
				var setStart    = $( '.wceb_datepicker_start--' + item_id ).data( 'value' );

				if ( $( '.wceb_datepicker_end--' + item_id ).length > 0 ) {
					dateFormat = 'two';
				} else {
					dateFormat = 'one';
				}

				if ( dateFormat === 'two' ) {
					var $inputEnd = $( '.wceb_datepicker_end--' + item_id ).pickadate();
					var pickerEnd = $inputEnd.pickadate( 'picker' );
					var setEnd    = $( '.wceb_datepicker_end--' + item_id ).data( 'value' );
				}

				pickerStart.on({
					set: function(startTime) {

						if ( typeof startTime.clear != 'undefined' && startTime.clear == null ) {

							if ( dateFormat === 'two' ) {
								pickerEnd.set( 'min', false );
							}

						} else if ( startTime.select && typeof startTime.select != 'undefined' && dateFormat === 'two' ) {

							startPickerData = pickerStart.get( 'select' );

							if ( wceb_admin_order.booking_mode === 'days' ) {

								pickerEnd.set(
									'min',
									[startPickerData.year, startPickerData.month, startPickerData.date]
								);

							} else {

								pickerEnd.set(
									'min',
									[startPickerData.year, startPickerData.month, startPickerData.date + 1]
								);

							}

						}
						
					}

				});

				if ( dateFormat === 'two' ) {

					pickerEnd.on({
						set: function( endTime ) {

							if ( typeof endTime.clear != 'undefined' && endTime.clear == null ) {

								pickerStart.set( 'max', false );

							} else if ( endTime.select && typeof endTime.select != 'undefined' ) {

								endPickerData = pickerEnd.get( 'select' );

								if ( wceb_admin_order.booking_mode === 'days' ) {

									pickerStart.set(
										'max',
										[endPickerData.year, endPickerData.month, endPickerData.date]
									);

								} else {

									pickerStart.set(
										'max',
										[endPickerData.year, endPickerData.month, endPickerData.date - 1]
									);

								}

							}
							
						}
					});

				}

				if ( setStart != '' ) {
					pickerStart.set( 'select', setStart, { format: 'yyyy-mm-dd' } );
				}

				if ( dateFormat === 'two' && setEnd != '' ) {
					pickerEnd.set( 'select', setEnd, { format: 'yyyy-mm-dd' } );
				}

			}

		});

	});

})(jQuery);