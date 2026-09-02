<?php
/**
 * Locates Tutor LMS orders.
 *
 * @package SalesByStateReportForTutorLMS
 */

namespace SBSTL\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Reads order IDs from Tutor LMS's native ecommerce table.
 *
 * Trash records are skipped. Table names are written as literals so every
 * identifier in the SQL is fixed.
 */
class OrderSource {

	/**
	 * Whether the Tutor LMS orders table exists.
	 *
	 * @return bool
	 */
	public static function table_exists() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'tutor_orders' )
		);
	}

	/**
	 * Total number of orders on the site.
	 *
	 * @return int
	 */
	public static function count() {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return 0;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}tutor_orders
			 WHERE order_status != 'trash'"
		);
	}

	/**
	 * Number of orders above a cursor.
	 *
	 * @param int $cursor Highest order ID already processed.
	 * @return int
	 */
	public static function count_after( $cursor ) {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return 0;
		}

		$cursor = max( 0, (int) $cursor );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}tutor_orders
				 WHERE order_status != 'trash'
				   AND id > %d",
				$cursor
			)
		);
	}

	/**
	 * The next batch of order IDs after a cursor.
	 *
	 * @param int $cursor Highest order ID already processed.
	 * @param int $limit  Batch size.
	 * @return int[]
	 */
	public static function ids_after( $cursor, $limit ) {
		global $wpdb;

		if ( ! self::table_exists() ) {
			return array();
		}

		$cursor = max( 0, (int) $cursor );
		$limit  = max( 1, (int) $limit );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}tutor_orders
				 WHERE order_status != 'trash'
				   AND id > %d
				 ORDER BY id ASC
				 LIMIT %d",
				$cursor,
				$limit
			)
		);

		return array_map( 'intval', (array) $ids );
	}
}
