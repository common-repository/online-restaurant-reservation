<?php
/**
 * Online Restaurant Reservation General Settings
 *
 * @class    ORR_Settings_General
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Admin
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ORR_Settings_General', false ) ) :

/**
 * ORR_Settings_General Class
 */
class ORR_Settings_General extends ORR_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'general';
		$this->label = __( 'General', 'online-restaurant-reservation' );

		parent::__construct();
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		$settings = $this->get_settings( $current_section );

		ORR_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings( $current_section );
		ORR_Admin_Settings::save_fields( $settings );
	}

	/**
	 * Get settings array.
	 *
	 * @param string $current_section
	 *
	 * @return array
	 */
	public function get_settings( $current_section = '' ) {
		$settings = apply_filters( 'online_restaurant_reservation_general_settings', array(

			array( 'title' => __( 'Reservation options', 'online-restaurant-reservation' ), 'type' => 'title', 'desc' => '', 'id' => 'reservation_options' ),

			array(
				'title'    => __( 'Min party size', 'online-restaurant-reservation' ),
				'desc'     => __( 'This sets the number of minimum allowed party size for reservation.', 'online-restaurant-reservation' ),
				'id'       => 'online_restaurant_reservation_min_party_size',
				'css'      => 'width:75px;',
				'default'  => '1',
				'desc_tip' => true,
				'type'     => 'number',
				'custom_attributes' => array(
					'min'  => 1,
					'step' => 1,
				),
			),

			array(
				'title'    => __( 'Max party size', 'online-restaurant-reservation' ),
				'desc'     => __( 'This sets the number of maximum allowed party size for reservation.', 'online-restaurant-reservation' ),
				'id'       => 'online_restaurant_reservation_max_party_size',
				'css'      => 'width:75px;',
				'default'  => '100',
				'desc_tip' => true,
				'type'     => 'number',
				'custom_attributes' => array(
					'min'  => get_option( 'online_restaurant_reservation_min_party_size', '1' ),
					'step' => 1,
				),
			),

			array(
				'title'    => __( 'Time range steps', 'online-restaurant-reservation' ),
				'desc'     => __( 'This controls how time range are listed for reservation.', 'online-restaurant-reservation' ),
				'id'       => 'online_restaurant_reservation_time_range_steps',
				'default'  => '30min',
				'type'     => 'select',
				'class'    => 'orr-enhanced-select',
				'desc_tip' => true,
				'options'  => array(
					15 => __( '15 Minutes', 'online-restaurant-reservation' ),
					30 => __( '30 Minutes', 'online-restaurant-reservation' ),
					45 => __( '45 Minutes', 'online-restaurant-reservation' ),
					60 => __( '60 Minutes', 'online-restaurant-reservation' ),
				),
			),

			array( 'type' => 'sectionend', 'id' => 'reservation_options' ),

		) );

		return apply_filters( 'online_restaurant_reservation_get_settings_' . $this->id, $settings, $current_section );
	}
}

endif;

return new ORR_Settings_General();
