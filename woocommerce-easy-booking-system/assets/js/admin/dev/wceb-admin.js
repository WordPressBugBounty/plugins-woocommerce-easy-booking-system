(function($) {
	$(document).ready(function() {

		$('.easy-booking-notice-close').on('click', function(e) {
			e.preventDefault();
			
			let $this = $(this),
				notice = $this.data('notice');

			let data = {
				action: 'wceb_hide_admin_notice',
				security: wceb_admin.hide_notice_nonce,
				notice: notice
			};

			$.ajax({
				url :  wceb_admin.ajax_url,
				data: data,
				type: 'POST',
				success: function( response ) {
					$this.parents('.easy-booking-notice').hide();
				}
			});
			
		});

		// Script to update database.
		$( '.wceb-db-update' ).on( 'click', function(e) {
			e.preventDefault();
			
			let fullUpdate = $(this).next('input[name="wceb-full-db-update"]').val();
			let $parent    = $(this).parents('.run-tool, .easy-booking-notice');

			let data = {
				action     : 'wceb_update_database',
				security   : wceb_admin.hide_notice_nonce,
				full_update: fullUpdate
			};

			$parent.fadeTo( '400', '0.6' );

			$.post( wceb_admin.ajax_url, data, function( response ) {

				if ( fullUpdate ) {
					alert( response.data.message );	// Tools page
				} else {
					$parent.html( '<p>' + wceb_admin.db_update_text + '</p>');
				}

				$parent.stop(true).css( 'opacity', '1' );

			});
			
		});

		// Migrate add-ons to PRO version.
		$( '.wceb-init-booking-statuses' ).on( 'click', function(e) {
			e.preventDefault();
			
			let $this = $(this);

			let data = {
				action  : 'wceb_init_booking_statuses',
				security: wceb_admin.hide_notice_nonce
			};

			$this.fadeTo( '400', '0.6' );

			$.post( wceb_admin.ajax_url, data, function( response ) {

				if ( response !== "" ) {
					alert( response );
				}

				$this.stop(true).css( 'opacity', '1' );

			});
			
		});
		
	});
})(jQuery);