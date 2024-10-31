<?php
/**
 * Online Restaurant Reservation Settings Page/Tab
 *
 * @class    ORR_Settings_Page
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Admin
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ORR_Settings_Page', false ) ) :

/**
 * ORR_Settings_Page Abstract
 */
abstract class ORR_Settings_Page {

	/**
	 * Setting page id.
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Setting page label.
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'online_restaurant_reservation_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'online_restaurant_reservation_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'online_restaurant_reservation_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'online_restaurant_reservation_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get settings page ID.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get settings page label.
	 * @since  1.0.0
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Add this page to settings.
	 *
	 * @param array $pages
	 * @return mixed
	 */
	public function add_settings_page( $pages ) {
		$pages[ $this->id ] = $this->label;

		return $pages;
	}

	/**
	 * Get settings
	 * @return array
	 */
	public function get_settings() {
		return apply_filters( 'online_restaurant_reservation_get_settings_' . $this->id, array() );
	}

	/**
	 * Get sections
	 * @return array
	 */
	public function get_sections() {
		return apply_filters( 'online_restaurant_reservation_get_sections_' . $this->id, array() );
	}

	/**
	 * Output sections
	 */
	public function output_sections() {
		global $current_section;

		$sections = $this->get_sections();

		if ( empty( $sections ) || 1 === sizeof( $sections ) ) {
			return;
		}

		echo '<ul class="subsubsub">';

		$array_keys = array_keys( $sections );

		foreach ( $sections as $id => $label ) {
			echo '<li><a href="' . admin_url( 'admin.php?page=orr-settings&tab=' . $this->id . '&section=' . sanitize_title( $id ) ) . '" class="' . ( $current_section == $id ? 'current' : '' ) . '">' . $label . '</a> ' . ( end( $array_keys ) == $id ? '' : '|' ) . ' </li>';
		}

		echo '</ul><br class="clear" />';
	}

	/**
	 * Output the settings
	 */
	public function output() {
		$settings = $this->get_settings();

		ORR_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings();
		ORR_Admin_Settings::save_fields( $settings );

		if ( $current_section ) {
			do_action( 'online_restaurant_reservation_update_options_' . $this->id . '_' . $current_section );
		}
	}
}

endif;
