<?php

namespace EasyBooking;

/**
 * Rebuild order bookings from WooCommerce orders.
 *
 * @version 3.5.1
 */

defined( 'ABSPATH' ) || exit;

class Order_Bookings_Tool {

	/**
	 * Number of orders processed during one AJAX request.
	 */
	private const ORDER_BATCH_SIZE = 10;

	/**
	 * Number of booking rows checked during one cleanup request.
	 */
	private const CLEANUP_BATCH_SIZE = 50;

	/**
	 * Transient used to prevent unrelated rebuilds from running together.
	 */
	private const STATE_TRANSIENT = 'wceb_rebuild_order_bookings_state';

	/**
	 * Keep an interrupted run available for fifteen minutes.
	 */
	private const STATE_EXPIRATION = 15 * MINUTE_IN_SECONDS;

	public function __construct() {
		add_action( 'wp_ajax_wceb_rebuild_order_bookings', array( $this, 'rebuild_order_bookings' ) );
	}

	/**
	 * Process the next rebuild batch.
	 */
	public function rebuild_order_bookings() {

		check_ajax_referer( 'wceb-rebuild-order-bookings', 'security' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'You do not have permission to do this.', 'woocommerce-easy-booking-system' ) ),
				403
			);
		}

		$token = isset( $_POST['token'] )
			? sanitize_text_field( wp_unslash( $_POST['token'] ) )
			: '';

		try {

			$state = $this->get_state( $token );

			if ( is_wp_error( $state ) ) {
				wp_send_json_error(
					array( 'message' => esc_html( $state->get_error_message() ) ),
					409
				);
			}

			if ( 'orders' === $state['phase'] ) {
				$state = $this->process_orders( $state );
			} else {
				$state = $this->cleanup_bookings( $state );
			}

			if ( 'complete' === $state['phase'] ) {

				delete_transient( self::STATE_TRANSIENT );

				// The database now reflects the current WooCommerce orders.
				delete_option( 'wceb_order_bookings_rebuild_required' );

				wp_send_json_success(
					array(
						'complete' => true,
						'message'  => sprintf(
							/* translators: 1: orders processed, 2: bookings synchronized, 3: obsolete bookings removed. */
							esc_html__( 'Order bookings rebuilt successfully. %1$d orders processed, %2$d bookings synchronized and %3$d obsolete bookings removed.', 'woocommerce-easy-booking-system' ),
							$state['orders_processed'],
							$state['bookings_synchronized'],
							$state['bookings_removed']
						),
					)
				);
			}

			// Refresh the expiration after every successful batch.
			set_transient( self::STATE_TRANSIENT, $state, self::STATE_EXPIRATION );

			wp_send_json_success(
				array(
					'complete' => false,
					'token'    => $state['token'],
					'message'  => $this->get_progress_message( $state ),
					'progress' => $this->get_progress_key( $state ),
				)
			);

		} catch ( \Throwable $error ) {

			/*
			 * Do not leave a failed run locked. Order synchronization is
			 * idempotent, and cleanup only starts after every order batch.
			 */
			delete_transient( self::STATE_TRANSIENT );

			wc_get_logger()->error(
				sprintf( 'Order bookings rebuild failed: %s', $error->getMessage() ),
				array( 'source' => 'easy-booking' )
			);

			wp_send_json_error(
				array(
					'message' => esc_html__( 'The order bookings rebuild failed. Please check the WooCommerce logs and try again.', 'woocommerce-easy-booking-system' ),
				),
				500
			);
		}
	}

	/**
	 * Start a run or retrieve its current state.
	 *
	 * A page reload can resume a run started by the same user. A different
	 * administrator must wait until the current run finishes or expires.
	 *
	 * @param string $token Run token received by the browser.
	 * @return array|\WP_Error
	 */
	private function get_state( $token ) {

		$state   = get_transient( self::STATE_TRANSIENT );
		$user_id = get_current_user_id();

		if ( is_array( $state ) ) {

			if ( (int) $state['user_id'] !== $user_id ) {
				return new \WP_Error(
					'easy_booking_rebuild_in_progress',
					__( 'Another administrator is already rebuilding order bookings.', 'woocommerce-easy-booking-system' )
				);
			}

			if ( $token && ! hash_equals( $state['token'], $token ) ) {
				return new \WP_Error(
					'easy_booking_invalid_rebuild_token',
					__( 'This order bookings rebuild can no longer be resumed. Please reload the page.', 'woocommerce-easy-booking-system' )
				);
			}

			/*
			 * Runs started before these progress values were introduced can
			 * continue safely after the page is reloaded.
			 */
			$state = wp_parse_args(
				$state,
				array(
					'cleanup_total'    => null,
					'bookings_checked' => 0,
				)
			);

			return $state;
		}

		// Make sure the custom table exists before the first batch is processed.
		Install::maybe_create_tables();

		$state = array(
			'token'                 => wp_generate_uuid4(),
			'user_id'               => $user_id,
			'phase'                 => 'orders',
			'page'                  => 1,
			'total_pages'           => null,
			'cleanup_cursor'        => 0,
			'cleanup_total'         => null,
			'bookings_checked'      => 0,
			'orders_processed'      => 0,
			'bookings_synchronized' => 0,
			'bookings_removed'      => 0,
		);

		/*
		 * Save the state before processing the first batch so another request
		 * cannot start a separate rebuild while this request is running.
		 */
		set_transient( self::STATE_TRANSIENT, $state, self::STATE_EXPIRATION );

		return $state;
	}

	/**
	 * Synchronize one page of WooCommerce orders.
	 *
	 * @param array $state Current run state.
	 * @return array
	 * @throws \RuntimeException When a valid booking cannot be saved.
	 */
	private function process_orders( $state ) {

		$results = wc_get_orders(
			array(
				'status'   => wceb_get_valid_order_statuses(),
				'limit'    => self::ORDER_BATCH_SIZE,
				'page'     => $state['page'],
				'paginate' => true,
				'orderby'  => 'ID',
				'order'    => 'ASC',
			)
		);

		if ( ! is_object( $results ) || ! isset( $results->orders, $results->max_num_pages ) ) {
			throw new \RuntimeException( 'WooCommerce returned an invalid orders result.' );
		}

		if ( is_null( $state['total_pages'] ) ) {
			$state['total_pages'] = absint( $results->max_num_pages );
		}

		foreach ( $results->orders as $order ) {

			if ( ! is_a( $order, 'WC_Order' ) ) {
				continue;
			}

			foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
				$state = $this->synchronize_order_item( $state, $item_id, $item, $order );
			}

			++$state['orders_processed'];
		}

		if ( $state['page'] >= $state['total_pages'] ) {
			$state['phase'] = 'cleanup';
		} else {
			++$state['page'];
		}

		return $state;
	}

	/**
	 * Synchronize one order item with the bookings table.
	 *
	 * @param array                 $state   Current run state.
	 * @param int                   $item_id Order item ID.
	 * @param \WC_Order_Item_Product $item   Order item.
	 * @param \WC_Order             $order   Parent order.
	 * @return array
	 * @throws \RuntimeException When a database operation fails.
	 */
	private function synchronize_order_item( $state, $item_id, $item, $order ) {

		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return $state;
		}

		$start_date  = $item->get_meta( '_booking_start_date' );
		$product_id  = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
		$quantity    = (float) $item->get_quantity();
		$refunded    = abs( (float) $order->get_qty_refunded_for_item( $item_id ) );
		$has_booking = wceb_is_valid_date( $start_date )
			&& $product_id
			&& wc_get_product( $product_id )
			&& $quantity > $refunded;

		if ( ! $has_booking ) {

			if ( $this->delete_existing_booking( $item_id ) ) {
				++$state['bookings_removed'];
			}

			return $state;
		}

		$booking_status = $item->get_meta( '_booking_status' );
		$valid_statuses = array( 'wceb-pending', 'wceb-start', 'wceb-processing', 'wceb-end', 'wceb-completed' );

		if ( ! in_array( $booking_status, $valid_statuses, true ) ) {

			$end_date       = $item->get_meta( '_booking_end_date' );
			$end_date       = wceb_is_valid_date( $end_date ) ? $end_date : null;
			$booking_status = wceb_get_booking_status( $start_date, $end_date );

			/*
			 * Older orders may not contain the status metadata. Store it on
			 * the order item as well as in the rebuilt bookings table.
			 */
			$item->update_meta_data( '_booking_status', $booking_status );
			$item->save_meta_data();
		}

		$result = wceb_create_or_update_order_booking( $item_id, $item, $order );

		if ( false === $result ) {
			throw new \RuntimeException( sprintf( 'Could not synchronize order item %d.', $item_id ) );
		}

		++$state['bookings_synchronized'];

		return $state;
	}

	/**
	 * Check one batch of existing rows and remove rows without a valid source.
	 *
	 * Cleanup runs only after the order scan completes, so an interrupted scan
	 * cannot remove bookings that have not been visited yet.
	 *
	 * @param array $state Current run state.
	 * @return array
	 * @throws \RuntimeException When the cleanup query fails.
	 */
	private function cleanup_bookings( $state ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'wceb_order_bookings';

		if ( is_null( $state['cleanup_total'] ) ) {
			$state['cleanup_total'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table name is generated by WordPress.
					"SELECT COUNT(*)
					FROM {$table_name}
					WHERE order_item_id > %d",
					$state['cleanup_cursor']
				)
			);
		}

		$bookings = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table name is generated by WordPress.
				"SELECT *
				FROM {$table_name}
				WHERE order_item_id > %d
				ORDER BY order_item_id ASC
				LIMIT %d",
				$state['cleanup_cursor'],
				self::CLEANUP_BATCH_SIZE
			)
		);

		if ( $wpdb->last_error ) {
			throw new \RuntimeException( $wpdb->last_error );
		}

		$obsolete_bookings = array();

		foreach ( $bookings as $booking ) {

			$item_id                 = absint( $booking->order_item_id );
			$state['cleanup_cursor'] = $item_id;

			if ( $this->is_valid_order_booking_source( $item_id ) ) {
				continue;
			}

			$obsolete_bookings[] = $booking;
		}

		$state['bookings_checked'] += count( $bookings );

		if ( $obsolete_bookings ) {

			$obsolete_item_ids = wp_list_pluck( $obsolete_bookings, 'order_item_id' );
			$placeholders      = wceb_create_sql_placeholders( $obsolete_item_ids, '%d' );

			/*
			 * Delete the obsolete rows together. Calling the regular
			 * single-booking deletion hook thousands of times would trigger
			 * an expensive PRO availability recalculation for every row.
			 */
			$deleted = $wpdb->query(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The table name and placeholders are generated by PHP.
					"DELETE FROM {$table_name}
					WHERE order_item_id IN ( {$placeholders} )",
					$obsolete_item_ids
				)
			);

			if ( false === $deleted ) {
				throw new \RuntimeException( $wpdb->last_error );
			}

			$state['bookings_removed'] += $deleted;

			/*
			 * Extensions receive all deleted rows at once and can deduplicate
			 * their follow-up work, such as availability recalculations.
			 */
			do_action( 'easy_booking_order_bookings_cleanup_deleted', $deleted, 0, $obsolete_bookings );
		}

		if ( count( $bookings ) < self::CLEANUP_BATCH_SIZE ) {
			$state['phase'] = 'complete';
		}

		return $state;
	}

	/**
	 * Check whether a booking row still has a valid WooCommerce order item.
	 *
	 * @param int $item_id Order item ID.
	 * @return bool
	 */
	private function is_valid_order_booking_source( $item_id ) {

		$item = \WC_Order_Factory::get_order_item( $item_id );

		if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
			return false;
		}

		$order = $item->get_order();

		if ( ! is_a( $order, 'WC_Order' )
			|| ! in_array( 'wc-' . $order->get_status(), wceb_get_valid_order_statuses(), true )
			|| ! wceb_is_valid_date( $item->get_meta( '_booking_start_date' ) )
		) {
			return false;
		}

		$product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
		$quantity   = (float) $item->get_quantity();
		$refunded   = abs( (float) $order->get_qty_refunded_for_item( $item_id ) );

		return $product_id && wc_get_product( $product_id ) && $quantity > $refunded;
	}

	/**
	 * Delete a booking only when its row currently exists.
	 *
	 * @param int $item_id Order item ID.
	 * @return bool Whether a row was deleted.
	 * @throws \RuntimeException When the database deletion fails.
	 */
	private function delete_existing_booking( $item_id ) {
		global $wpdb;

		/*
		 * Check the raw row instead of hydrating an Order_Booking object.
		 * Historical rows can reference a deleted product or order and are
		 * therefore intentionally invalid, but they must still be removable.
		 */
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT order_item_id
				FROM {$wpdb->prefix}wceb_order_bookings
				WHERE order_item_id = %d",
				$item_id
			)
		);

		if ( is_null( $exists ) ) {
			return false;
		}

		$deleted = wceb_delete_order_booking( $item_id );

		if ( false === $deleted ) {
			throw new \RuntimeException( sprintf( 'Could not delete booking for order item %d.', $item_id ) );
		}

		return (bool) $deleted;
	}

	/**
	 * Get the progress text displayed below the tool button.
	 *
	 * @param array $state Current run state.
	 * @return string
	 */
	private function get_progress_message( $state ) {

		if ( 'cleanup' === $state['phase'] ) {
			return sprintf(
				/* translators: 1: bookings checked, 2: total bookings to check, 3: obsolete bookings removed. */
				esc_html__( 'Checking existing bookings: %1$d of %2$d checked, %3$d obsolete bookings removed.', 'woocommerce-easy-booking-system' ),
				$state['bookings_checked'],
				$state['cleanup_total'],
				$state['bookings_removed']
			);
		}

		return sprintf(
			/* translators: 1: current page, 2: total pages, 3: orders processed, 4: bookings synchronized. */
			esc_html__( 'Batch %1$d of %2$d: %3$d orders processed and %4$d bookings synchronized.', 'woocommerce-easy-booking-system' ),
			min( $state['page'] - 1, $state['total_pages'] ),
			$state['total_pages'],
			$state['orders_processed'],
			$state['bookings_synchronized']
		);
	}

	/**
	 * Get a value that must change after every successful batch.
	 *
	 * @param array $state Current run state.
	 * @return string
	 */
	private function get_progress_key( $state ) {

		if ( 'cleanup' === $state['phase'] ) {
			return 'cleanup:' . $state['cleanup_cursor'];
		}

		return 'orders:' . $state['page'];
	}
}

new Order_Bookings_Tool();
