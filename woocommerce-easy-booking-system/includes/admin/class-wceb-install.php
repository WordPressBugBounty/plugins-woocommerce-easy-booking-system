<?php
/**
 *
 * Install Easy Booking.
 *
 * @package Easy_Booking
 * @version 3.5.0
 */

namespace EasyBooking;

defined( 'ABSPATH' ) || exit;

/**
 *
 * Install actions.
 **/
class Install {

	private const TABLES = array( 'order_bookings' );

	/**
	 *
	 * Install plugin tables on admin init.
	 **/
	public static function init() {

		add_action( 'admin_init', array( __CLASS__, 'install' ) );
	}

	/**
	 *
	 * Install plugin tables and manage DB updates.
	 **/
	public static function install() {

		try {

			self::maybe_create_tables();

			// Database updates
			Update_Manager::init();

		} catch ( \Exception $e ) {

			add_action(
				'admin_notices',
				function () use ( $e ) {
					echo '<div class="error"><p>' . wp_kses( $e->getMessage(), array( 'code' => array() ) ) . '</p></div>';
				}
			);

		}
	}

	/**
	 *
	 * Create plugin tables.
	 **/
	public static function maybe_create_tables() {
		global $wpdb;

		$wpdb->show_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		foreach ( self::TABLES as $table ) {

			$table_name    = $wpdb->prefix . 'wceb_' . $table;
			$table_version = constant( 'WCEB_' . strtoupper( $table ) . '_TABLE_VERSION' );

			$callback = 'create_' . $table . '_table';

			// Check if method exists.
			if ( ! method_exists( self::class, $callback ) ) {
				continue;
			}

			// Get SQL request to create table.
			$sql = self::$callback( $table_name, $charset_collate );

			$success = maybe_create_table( $table_name, $sql );

			// Table creation failed.
			if ( ! $success ) {

				// phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
				$error = ! empty( $wpdb->last_error )
					? $wpdb->last_error
					: sprintf(
						// translators: %s is DB user and DB name.
						__( 'Does the %1$s user have CREATE privileges on the %2$s database?', 'woocommerce-easy-booking-system' ),
						'<code>' . DB_USER . '</code>',
						'<code>' . DB_NAME . '</code>'
					);
					
					throw new \Exception(
						sprintf(
							// translators: %s is error message.
							__( 'Easy Booking table creation failed. %s', 'woocommerce-easy-booking-system' ),
							$error
						)
					);
				// phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped

			}

			// Success or table already exists.
			if ( get_option( 'wceb_' . $table . '_table_version' ) !== $table_version ) {

				dbDelta( $sql );
				update_option( 'wceb_' . $table . '_table_version', $table_version );

			}
		}
	}

	/**
	 *
	 * Create wceb_order_bookings table.
	 *
	 * @param string $table_name
	 * @param string $charset_collate
	 *
	 * @return string $sql
	 **/
	private static function create_order_bookings_table( $table_name, $charset_collate ) {

		$sql = "CREATE TABLE {$table_name} (
            order_item_id BIGINT(20) NOT NULL,
            product_id    BIGINT(20) NOT NULL,
            start         DATE NOT NULL,
            end           DATE,
            status        VARCHAR(200) NOT NULL,
            qty           INT(11) NOT NULL,
            order_id      BIGINT(20) NOT NULL,
            PRIMARY KEY   (order_item_id)
        ) {$charset_collate};";

		return $sql;
	}

}

Install::init();
