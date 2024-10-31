<?php
/**
 * Online Restaurant Reservation Integrations class
 *
 * Loads Integrations into Online Restaurant Reservation.
 *
 * @class    ORR_Integrations
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Classes/Integrations
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Integrations Class.
 */
class ORR_Integrations {

	/**
	 * Array of integrations.
	 *
	 * @var array
	 */
	public $integrations = array();

	/**
	 * Initialize integrations.
	 */
	public function __construct() {

		do_action( 'online_restaurant_reservation_integrations_init' );

		$load_integrations = apply_filters( 'online_restaurant_reservation_integrations', array() );

		// Load integration classes.
		foreach ( $load_integrations as $integration ) {

			$load_integration = new $integration();

			$this->integrations[ $load_integration->id ] = $load_integration;
		}
	}

	/**
	 * Return loaded integrations.
	 *
	 * @return array
	 */
	public function get_integrations() {
		return $this->integrations;
	}
}
