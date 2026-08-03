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

				if ( ! response.success ) {
					alert( response.data.message );
					$parent.stop(true).css( 'opacity', '1' );
					return;
				}

				if ( fullUpdate ) {
					alert( response.data.message );	// Tools page
				} else {
					$parent.empty().append( $( '<p>' ).text( response.data.message ) );
				}

				$parent.stop(true).css( 'opacity', '1' );

			});
			
		});

		// Initialize booking statuses.
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

		// Rebuild order bookings in small AJAX batches.
		$( '.wceb-rebuild-order-bookings' ).on( 'click', function(e) {
			e.preventDefault();

			if ( ! window.confirm( wceb_admin.rebuild_bookings_confirm ) ) {
				return;
			}

			const $button   = $(this);
			const $progress = $button.siblings( '.wceb-rebuild-order-bookings-progress' );

			let token        = $button.data( 'rebuild-token' ) || '';
			let lastProgress = '';

			const runBatch = function() {

				$button.prop( 'disabled', true );

				$.ajax({
					url : wceb_admin.ajax_url,
					data: {
						action  : 'wceb_rebuild_order_bookings',
						security: wceb_admin.rebuild_bookings_nonce,
						token   : token
					},
					type: 'POST'
				}).done(function( response ) {

					if ( ! response.success ) {
						$progress.text( response.data.message || wceb_admin.rebuild_bookings_error );
						$button.prop( 'disabled', false );
						return;
					}

					$progress.text( response.data.message );

					if ( response.data.complete ) {
						$button.removeData( 'rebuild-token' ).prop( 'disabled', false );
						return;
					}

					if ( response.data.progress === lastProgress ) {
						$progress.text( wceb_admin.rebuild_bookings_stalled );
						$button.prop( 'disabled', false );
						return;
					}

					token = response.data.token;
					lastProgress = response.data.progress;
					$button.data( 'rebuild-token', token );
					runBatch();

				}).fail(function( response ) {

					const message = response.responseJSON && response.responseJSON.data
						? response.responseJSON.data.message
						: wceb_admin.rebuild_bookings_error;

					$progress.text( message );
					$button.prop( 'disabled', false );
				});
			};

			runBatch();
		});
		
	});
})(jQuery);
