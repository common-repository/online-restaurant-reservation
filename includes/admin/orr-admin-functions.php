<?php
/**
 * Online Restaurant Reservation Admin Functions
 *
 * @author   WPEverest
 * @category Core
 * @package  Online_Restaurant_Reservation/Admin/Functions
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get all Online Restaurant Reservation screen ids.
 *
 * @return array
 */
function orr_get_screen_ids() {
	$orr_screen_id = sanitize_title( __( 'Reservations', 'online-restaurant-reservation' ) );
	$screen_ids   = array(
		'toplevel_page_' . $orr_screen_id,
		$orr_screen_id . '_page_orr-settings',
		$orr_screen_id . '_page_orr-extensions',
		'edit-table_reservation',
		'table_reservation'
	);

	return apply_filters( 'online_restaurant_reservation_screen_ids', $screen_ids );
}

/**
 * Output admin fields.
 *
 * Loops though the online restaurant reservation options array and outputs each field.
 *
 * @param array $options Opens array to output.
 */
function online_restaurant_reservation_admin_fields( $options ) {

	if ( ! class_exists( 'ORR_Admin_Settings', false ) ) {
		include( dirname( __FILE__ ) . '/class-orr-admin-settings.php' );
	}

	ORR_Admin_Settings::output_fields( $options );
}

/**
 * Update all settings which are passed.
 *
 * @param array $options
 * @param array $data
 */
function online_restaurant_reservation_update_options( $options, $data = null ) {

	if ( ! class_exists( 'ORR_Admin_Settings', false ) ) {
		include( dirname( __FILE__ ) . '/class-orr-admin-settings.php' );
	}

	ORR_Admin_Settings::save_fields( $options, $data );
}

/**
 * Get a setting from the settings API.
 *
 * @param mixed $option_name
 * @param mixed $default
 * @return string
 */
function online_restaurant_reservation_settings_get_option( $option_name, $default = '' ) {

	if ( ! class_exists( 'ORR_Admin_Settings', false ) ) {
		include( dirname( __FILE__ ) . '/class-orr-admin-settings.php' );
	}

	return ORR_Admin_Settings::get_option( $option_name, $default );
}
