<?php

namespace EasyBooking;

/**
 * Build and run booking queries.
 *
 * Easy Booking provides the order bookings source. Extensions can register
 * other sources, such as manual bookings, without changing this class.
 *
 * @version 3.5.1
 */

defined( 'ABSPATH' ) || exit;

class Bookings_Query {

	/**
	 * Query filters.
	 *
	 * @var array
	 */
	private $filters;

	/**
	 * Selected source name.
	 *
	 * @var string
	 */
	private $source;

	/**
	 * Initialize the query.
	 *
	 * @param array  $filters Query filters.
	 * @param string $source  Source name or "all".
	 */
	public function __construct( $filters = array(), $source = 'all' ) {
		$this->filters = is_array( $filters ) ? $filters : array();
		$this->source  = sanitize_key( $source );
	}

	/**
	 * Run the query.
	 *
	 * @return object[]
	 */
	public function get_bookings() {
		global $wpdb;

		$query = $this->get_query();

		if ( 'all' === $this->source ) {
			// Keep the historical filter available for third-party extensions.
			$query = apply_filters( 'easy_booking_query_bookings', $query, $this->filters );
		}

		if ( empty( $query['sql'] ) ) {
			return array();
		}

		$sql = $query['sql'] . $query['sort'];

		$bookings = ! empty( $query['placeholders'] )
			? $wpdb->get_results( $wpdb->prepare( $sql, $query['placeholders'] ) )
			: $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query sources are registered by PHP code.

		return $bookings;
	}

	/**
	 * Build the complete SQL query.
	 *
	 * @return array
	 */
	public function get_query() {

		$sources = $this->get_sources();

		if ( 'all' !== $this->source ) {
			$sources = isset( $sources[ $this->source ] )
				? array( $this->source => $sources[ $this->source ] )
				: array();
		}

		$conditions = $this->get_conditions();
		$args       = $conditions['sql'] ? ' WHERE ' . $conditions['sql'] : '';
		$queries    = array();

		foreach ( $sources as $source ) {

			if ( ! is_array( $source ) || empty( $source['query'] ) ) {
				continue;
			}

			/*
			 * A source can expose different columns when queried alone. This
			 * keeps its public result format while the normalized query keeps
			 * all UNION columns aligned.
			 */
			$sql = 'all' !== $this->source && ! empty( $source['single_query'] )
				? $source['single_query']
				: $source['query'];

			$queries[] = $sql . $args;
		}

		$placeholders = array();

		foreach ( $queries as $query ) {
			$placeholders = array_merge( $placeholders, $conditions['placeholders'] );
		}

		return array(
			'sql'          => implode( ' UNION ', $queries ),
			'args'         => $args,
			'sort'         => $this->get_sort( $sources ),
			'placeholders' => $placeholders,
		);
	}

	/**
	 * Get the registered booking sources.
	 *
	 * @return array
	 */
	private function get_sources() {
		global $wpdb;

		$sources = array(
			'order' => array(
				'query'           => "SELECT order_item_id, product_id, start, end, status, qty, order_id
					FROM {$wpdb->prefix}wceb_order_bookings",
				'order_by'        => array( 'status', 'order_id', 'product_id', 'start', 'end' ),
				'default_orderby' => 'order_id',
			),
		);

		/**
		 * Filter the database sources used to retrieve bookings.
		 *
		 * Each source must provide a normalized "query". It can also provide
		 * "single_query", "order_by" and "default_orderby" values.
		 *
		 * @param array          $sources Registered sources.
		 * @param array          $filters Query filters.
		 * @param Bookings_Query $query   Current query object.
		 */
		return apply_filters( 'easy_booking_bookings_query_sources', $sources, $this->filters, $this );
	}

	/**
	 * Build the shared WHERE conditions.
	 *
	 * @return array
	 */
	private function get_conditions() {

		$conditions     = array();
		$placeholders   = array();
		$valid_statuses = array( 'pending', 'start', 'processing', 'end', 'completed' );

		if ( isset( $this->filters['status'] ) && in_array( $this->filters['status'], $valid_statuses, true ) ) {
			$conditions[]   = 'status = %s';
			$placeholders[] = 'wceb-' . $this->filters['status'];
		} else {
			$conditions[]   = 'status != %s';
			$placeholders[] = 'wceb-completed';
		}

		if ( isset( $this->filters['product_ids'] ) && is_numeric( $this->filters['product_ids'] ) ) {
			$conditions[]   = 'product_id = %d';
			$placeholders[] = absint( $this->filters['product_ids'] );
		}

		if ( isset( $this->filters['start_date'] ) && wceb_is_valid_date( $this->filters['start_date'] ) ) {
			$conditions[]   = 'start = %s';
			$placeholders[] = $this->filters['start_date'];
		}

		if ( isset( $this->filters['end_date'] ) && wceb_is_valid_date( $this->filters['end_date'] ) ) {
			$conditions[]   = 'end = %s';
			$placeholders[] = $this->filters['end_date'];
		}

		return array(
			'sql'          => implode( ' AND ', $conditions ),
			'placeholders' => $placeholders,
		);
	}

	/**
	 * Build the final ORDER BY clause.
	 *
	 * @param array $sources Selected booking sources.
	 * @return string
	 */
	private function get_sort( $sources ) {

		$valid_order_by  = array( 'status', 'order_id', 'product_id', 'start', 'end' );
		$default_orderby = 'order_id';

		if ( 'all' !== $this->source && ! empty( $sources[ $this->source ] ) ) {
			$source = $sources[ $this->source ];

			if ( ! empty( $source['order_by'] ) && is_array( $source['order_by'] ) ) {
				$valid_order_by = $source['order_by'];
			}

			if ( ! empty( $source['default_orderby'] ) ) {
				$default_orderby = $source['default_orderby'];
			}
		}

		$orderby = isset( $this->filters['orderby'] ) && in_array( $this->filters['orderby'], $valid_order_by, true )
			? $this->filters['orderby']
			: $default_orderby;
		$order   = isset( $this->filters['order'] ) && 'asc' === $this->filters['order'] ? 'ASC' : 'DESC';

		return ' ORDER BY ' . $orderby . ' ' . $order;
	}
}
