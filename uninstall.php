<?php
/**
 * Online Restaurant Reservation Uninstall
 *
 * Uninstalls the plugin deletes user roles, tables, and options.
 *
 * @author   WPEverest
 * @category Core
 * @package  Online_Restaurant_Reservation/Uninstaller
 * @version  1.0.0
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

/*
 * Only remove ALL plugin data if ORR_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'ORR_REMOVE_ALL_DATA' ) && true === ORR_REMOVE_ALL_DATA ) {
	// Roles + caps.
	include_once( dirname( __FILE__ ) . '/includes/class-orr-install.php' );
	ORR_Install::remove_roles();

	// Tables.
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}orr_exceptions" );

	// Delete options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'online_restaurant_reservation\_%';" );

	// Delete posts + data.
	$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'table_reservation' );" );
	$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );

	// Clear any cached data that has been removed.
	wp_cache_flush();
}
