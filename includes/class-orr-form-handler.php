<?php
/**
 * Handle frontend forms.
 *
 * @class    ORR_Form_Handler
 * @version  1.0.0
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Form_Handler Class.
 */
class ORR_Form_Handler {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'wp_loaded', array( __CLASS__, 'reservation_action' ), 20 );
	}

	/**
	 * Process the reservation form.
	 */
	public static function reservation_action() {
		if ( isset( $_POST['online_restaurant_reservation_place_reservation'] ) ) {
			nocache_headers();

			orr_maybe_define_constant( 'ORR_RESERVATION', true );

			ORR()->table_reservation()->process_reservation();
		}
	}
}

ORR_Form_Handler::init();
