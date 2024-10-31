<?php
/**
 * Online Restaurant Reservation Admin Assets
 *
 * Load Admin Assets.
 *
 * @class    ORR_Admin_Assets
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Admin
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ORR_Admin_Assets', false ) ) :

/**
 * ORR_Admin_Assets Class
 */
class ORR_Admin_Assets {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Enqueue styles.
	 */
	public function admin_styles() {
		global $wp_scripts;

		$screen         = get_current_screen();
		$screen_id      = $screen ? $screen->id : '';
		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.12.1';

		// Register admin styles
		wp_register_style( 'online-restaurant-reservation-admin', ORR()->plugin_url() . '/assets/css/admin.css', array(), ORR_VERSION );
		wp_register_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array(), $jquery_version );

		// Add RTL support for admin styles
		wp_style_add_data( 'online-restaurant-reservation-admin', 'rtl', 'replace' );

		// Admin styles for ORR pages only
		if ( in_array( $screen_id, orr_get_screen_ids() ) ) {
			wp_enqueue_style( 'online-restaurant-reservation-admin' );
			wp_enqueue_style( 'jquery-ui-style' );
			wp_enqueue_style( 'wp-color-picker' );
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register Scripts.
		wp_register_script( 'online-restaurant-reservation-admin', ORR()->plugin_url() . '/assets/js/admin/admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), ORR_VERSION );
		wp_register_script( 'jquery-blockui', ORR()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'jquery-tiptip', ORR()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), ORR_VERSION, true );
		wp_register_script( 'orr-backbone-modal', ORR()->plugin_url() . '/assets/js/admin/backbone-modal' . $suffix . '.js', array( 'underscore', 'backbone', 'wp-util' ), ORR_VERSION );
		wp_register_script( 'orr-reservation-schedules', ORR()->plugin_url() . '/assets/js/admin/orr-reservation-schedules' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker' ), ORR_VERSION );
		wp_register_script( 'orr-reservation-exceptions', ORR()->plugin_url() . '/assets/js/admin/orr-reservation-exceptions' . $suffix . '.js', array( 'jquery', 'wp-util', 'underscore', 'backbone' ), ORR_VERSION );
		wp_register_script( 'selectWoo', ORR()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.2' );
		wp_register_script( 'orr-enhanced-select', ORR()->plugin_url() . '/assets/js/admin/enhanced-select' . $suffix . '.js', array( 'jquery', 'selectWoo' ), ORR_VERSION );
		wp_localize_script( 'orr-enhanced-select', 'orr_enhanced_select_params', array(
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'online-restaurant-reservation' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'online-restaurant-reservation' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'online-restaurant-reservation' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'online-restaurant-reservation' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'online-restaurant-reservation' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'online-restaurant-reservation' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'online-restaurant-reservation' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'online-restaurant-reservation' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'online-restaurant-reservation' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'online-restaurant-reservation' ),
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'search_customers_nonce'    => wp_create_nonce( 'search-customers' ),
		) );

		wp_register_script( 'orr-reservations', ORR()->plugin_url() . '/assets/js/admin/orr-reservations' . $suffix . '.js', array( 'jquery', 'wp-util', 'underscore', 'backbone', 'jquery-blockui' ), ORR_VERSION );
		wp_localize_script( 'orr-reservations', 'orr_reservations_params', array(
			'ajax_url'      => admin_url( 'admin-ajax.php' ),
			'preview_nonce' => wp_create_nonce( 'online-restaurant-preview-reservation' ),
		) );

		// Online Restaurant Reservation admin pages.
		if ( in_array( $screen_id, orr_get_screen_ids() ) ) {
			wp_enqueue_script( 'iris' );
			wp_enqueue_script( 'online-restaurant-reservation-admin' );
			wp_enqueue_script( 'orr-enhanced-select' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );
		}

		// Meta boxes
		if ( in_array( str_replace( 'edit-', '', $screen_id ), array( 'table_reservation' ) ) ) {
			wp_enqueue_script( 'online-restaurant-reservation-meta-boxes', ORR()->plugin_url() . '/assets/js/admin/meta-boxes-reservation' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker', 'orr-backbone-modal' ), ORR_VERSION );
		}
	}
}

endif;

return new ORR_Admin_Assets();
