<?php
/**
 * Removes the plugin's data when it is deleted.
 *
 * @package SalesByStateReportForTutorLMS
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$sbstl_options = array(
	'sbstl_db_version',
	'sbstl_backfill_cursor',
	'sbstl_year_start',
);

foreach ( $sbstl_options as $sbstl_option ) {
	delete_option( $sbstl_option );
}

if ( is_multisite() ) {
	foreach ( $sbstl_options as $sbstl_option ) {
		delete_site_option( $sbstl_option );
	}
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}sbstl_order_state" );

if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'sbstl_backfill_batch', array(), 'sales-by-state-report-for-tutorlms' );
}
