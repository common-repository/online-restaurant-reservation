<?php
/**
 * Plugin Name: Online Restaurant Reservation
 * Plugin URI: https://wpeverest.com/wordpress-plugins/online-restaurant-reservation/
 * Description: Accept online restaurant reservations and table bookings with ease.
 * Version: 1.0.0
 * Author: WPEverest
 * Author URI: https://wpeverest.com
 *
 * Text Domain: online-restaurant-reservation
 * Domain Path: /languages/
 *
 * @package  Online_Restaurant_Reservation
 * @category Core
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define ORR_PLUGIN_FILE.
if ( ! defined( 'ORR_PLUGIN_FILE' ) ) {
	define( 'ORR_PLUGIN_FILE', __FILE__ );
}

// Include the main Online Restaurant Reservation class.
if ( ! class_exists( 'Online_Restaurant_Reservation' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-online-restaurant-reservation.php';
}

/**
 * Main instance of Online Restaurant Reservation.
 *
 * Returns the main instance of ORR to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return Online_Restaurant_Reservation
 */
function orr() {
	return Online_Restaurant_Reservation::get_instance();
}

// Global for backwards compatibility.
$GLOBALS['online_restaurant_reservation'] = orr();
