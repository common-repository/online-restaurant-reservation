<?php
/**
 * Setup menus in WP admin.
 *
 * @class    ORR_Admin_Menus
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Admin
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ORR_Admin_Menus', false ) ) :

	/**
	 * ORR_Admin_Menus Class.
	 */
	class ORR_Admin_Menus {

		/**
		 * Hook in tabs.
		 */
		public function __construct() {
			// Add menus.
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
			add_action( 'admin_menu', array( $this, 'settings_menu' ), 50 );

			if ( apply_filters( 'online_restaurant_reservation_show_extensions_page', false ) ) {
				add_action( 'admin_menu', array( $this, 'extensions_menu' ), 70 );
			}

			add_action( 'admin_head', array( $this, 'menu_reservation_count' ) );
			add_filter( 'menu_order', array( $this, 'menu_order' ) );
			add_filter( 'custom_menu_order', array( $this, 'custom_menu_order' ) );
		}

		/**
		 * Add menu items.
		 */
		public function admin_menu() {
			global $menu;

			if ( current_user_can( 'manage_reservation' ) ) {
				$menu[] = array( '', 'read', 'separator-reservation', '', 'wp-menu-separator online-restaurant-reservation' ); // WPCS: override ok.
			}

			add_menu_page( __( 'Online Restaurant Reservation', 'online-restaurant-reservation' ), __( 'Reservations', 'online-restaurant-reservation' ), 'manage_reservation', 'reservation', null, 'dashicons-clock', '58.5' );
		}

		/**
		 * Add menu item.
		 */
		public function settings_menu() {
			$settings_page = add_submenu_page( 'reservation', __( 'Online Restaurant Reservation Settings', 'online-restaurant-reservation' ),  __( 'Settings', 'online-restaurant-reservation' ) , 'manage_reservation', 'orr-settings', array( $this, 'settings_page' ) );

			add_action( 'load-' . $settings_page, array( $this, 'settings_page_init' ) );
		}

		/**
		 * Loads settings page.
		 */
		public function settings_page_init() {
			global $current_tab, $current_section;

			// Include settings pages.
			ORR_Admin_Settings::get_settings_pages();

			// Get current tab/section.
			$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) ); // WPCS: input var okay, CSRF ok.
			$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) ); // WPCS: input var okay, CSRF ok.

			// Save settings if data has been posted.
			if ( apply_filters( '' !== $current_section ? "online_restaurant_reservation_save_settings_{$current_tab}_{$current_section}" : "online_restaurant_reservation_save_settings_{$current_tab}", ! empty( $_POST ) ) ) { // WPCS: input var okay, CSRF ok.
				ORR_Admin_Settings::save();
			}

			// Add any posted messages.
			if ( ! empty( $_GET['orr_error'] ) ) { // WPCS: input var okay, CSRF ok.
				ORR_Admin_Settings::add_error( wp_kses_post( wp_unslash( $_GET['orr_error'] ) ) ); // WPCS: input var okay, CSRF ok.
			}

			if ( ! empty( $_GET['orr_message'] ) ) { // WPCS: input var okay, CSRF ok.
				ORR_Admin_Settings::add_message( wp_kses_post( wp_unslash( $_GET['orr_message'] ) ) ); // WPCS: input var okay, CSRF ok.
			}
		}

		/**
		 * Extensions menu item.
		 */
		public function extensions_menu() {
			add_submenu_page( 'reservation', __( 'Online Restaurant Reservation extensions', 'online-restaurant-reservation' ),  __( 'Extensions', 'online-restaurant-reservation' ) , 'manage_reservation', 'orr-extensions', null );
		}

		/**
		 * Adds the reservation pending count to the menu.
		 */
		public function menu_reservation_count() {
			global $submenu;

			if ( isset( $submenu['reservation'] ) ) {
				// Remove 'Reservation' sub menu item.
				unset( $submenu['reservation'][0] );

				// Add count if user has access.
				if ( apply_filters( 'online_restaurant_reservation_include_pending_reservation_count_in_menu', true ) && current_user_can( 'manage_reservation' ) && ( $reservation_count = orr_pending_reservation_count() ) ) {
					foreach ( $submenu['reservation'] as $key => $menu_item ) {
						if ( 0 === strpos( $menu_item[0], _x( 'Reservations', 'Admin menu name', 'online-restaurant-reservation' ) ) ) {
							$submenu['reservation'][ $key ][0] .= ' <span class="awaiting-mod update-plugins count-' . $reservation_count . '"><span class="pending-count">' . number_format_i18n( $reservation_count ) . '</span></span>';
							break;
						}
					}
				}
			}
		}

		/**
		 * Reorder the ORR menu items in admin.
		 *
		 * @param  int $menu_order Menu Order.
		 * @return array
		 */
		public function menu_order( $menu_order ) {
			// Initialize our custom order array.
			$orr_menu_order = array();

			// Get the index of our custom separator.
			$orr_separator = array_search( 'separator-reservation', $menu_order, true );

			// Get index of food menu.
			$orr_reservation_menu = array_search( 'edit.php?post_type=table_reservation', $menu_order, true );

			// Loop through menu order and do some rearranging.
			foreach ( $menu_order as $index => $item ) {

				if ( 'reservation' === $item ) {
					$orr_menu_order[] = 'separator-reservation';
					$orr_menu_order[] = $item;
					$orr_menu_order[] = 'edit.php?post_type=table_reservation';
					unset( $menu_order[ $orr_separator ] );
					unset( $menu_order[ $orr_reservation_menu ] );
				} elseif ( ! in_array( $item, array( 'separator-reservation' ), true ) ) {
					$orr_menu_order[] = $item;
				}
			}

			// Return order.
			return $orr_menu_order;
		}

		/**
		 * Custom menu order.
		 *
		 * @return bool
		 */
		public function custom_menu_order() {
			return current_user_can( 'manage_reservation' );
		}

		/**
		 * Init the settings page.
		 */
		public function settings_page() {
			ORR_Admin_Settings::output();
		}
	}

endif;

return new ORR_Admin_Menus();
