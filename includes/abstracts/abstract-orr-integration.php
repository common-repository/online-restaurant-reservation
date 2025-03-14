<?php
/**
 * Abstract Integration Class
 *
 * Extended by individual integrations to offer additional functionality.
 *
 * @class    ORR_Integration
 * @extends  ORR_Settings_API
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Abstracts
 * @category Abstract Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Integration class.
 */
abstract class ORR_Integration extends ORR_Settings_API {

	/**
	 * yes or no based on whether the integration is enabled.
	 *
	 * @var string
	 */
	public $enabled = 'yes';

	/**
	 * Integration title.
	 *
	 * @var string
	 */
	public $method_title = '';

	/**
	 * Integration description.
	 *
	 * @var string
	 */
	public $method_description = '';

	/**
	 * Return the title for admin screens.
	 *
	 * @return string
	 */
	public function get_method_title() {
		return apply_filters( 'online_restaurant_reservation_integration_title', $this->method_title, $this );
	}

	/**
	 * Return the description for admin screens.
	 * @return string
	 */
	public function get_method_description() {
		return apply_filters( 'online_restaurant_reservation_integration_description', $this->method_description, $this );
	}

	/**
	 * Output the gateway settings screen.
	 */
	public function admin_options() {
		echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
		echo wp_kses_post( wpautop( $this->get_method_description() ) );
		echo '<div><input type="hidden" name="section" value="' . esc_attr( $this->id ) . '" /></div>';
		parent::admin_options();
	}

	/**
	 * Init settings for integrations.
	 */
	public function init_settings() {
		parent::init_settings();
		$this->enabled  = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
	}
}
