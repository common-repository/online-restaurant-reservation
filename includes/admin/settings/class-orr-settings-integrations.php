<?php
/**
 * Online Restaurant Reservation Integration Settings
 *
 * @class    ORR_Settings_Integrations
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Admin
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ORR_Settings_Integrations', false ) ) :

/**
 * ORR_Settings_Integrations Class.
 */
class ORR_Settings_Integrations extends ORR_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'integration';
		$this->label = __( 'Integration', 'online-restaurant-reservation' );

		if ( isset( ORR()->integrations ) && ORR()->integrations->get_integrations() ) {
			parent::__construct();
		}
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		global $current_section;

		$sections = array();

		if ( ! defined( 'ORR_INSTALLING' ) ) {
			$integrations = ORR()->integrations->get_integrations();

			if ( ! $current_section && ! empty( $integrations ) ) {
				$current_section = current( $integrations )->id;
			}

			if ( sizeof( $integrations ) > 1 ) {
				foreach ( $integrations as $integration ) {
					$title = empty( $integration->method_title ) ? ucfirst( $integration->id ) : $integration->method_title;
					$sections[ strtolower( $integration->id ) ] = esc_html( $title );
				}
			}
		}

		return apply_filters( 'online_restaurant_reservation_get_sections_' . $this->id, $sections );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		$integrations = ORR()->integrations->get_integrations();

		if ( isset( $integrations[ $current_section ] ) ) {
			$integrations[ $current_section ]->admin_options();
		}
	}
}

endif;

return new ORR_Settings_Integrations();
