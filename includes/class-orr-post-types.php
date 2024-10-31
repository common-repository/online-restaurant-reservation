<?php
/**
 * Post Types
 *
 * Registers post types and taxonomies.
 *
 * @class    ORR_Post_Types
 * @version  1.0.0
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Post_Types Class.
 */
class ORR_Post_Types {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
		add_action( 'init', array( __CLASS__, 'register_post_status' ), 9 );
	}

	/**
	 * Register post types.
	 */
	public static function register_post_types() {
		if ( ! is_blog_installed() || post_type_exists( 'table_reservation' ) ) {
			return;
		}

		do_action( 'online_restaurant_reservation_register_post_type' );

		register_post_type( 'table_reservation',
			apply_filters( 'online_restaurant_reservation_register_post_type_table_reservation',
				array(
					'labels'              => array(
							'name'                  => __( 'Reservations', 'online-restaurant-reservation' ),
							'singular_name'         => __( 'Reservation', 'online-restaurant-reservation' ),
							'menu_name'             => _x( 'Reservations', 'Admin menu name', 'online-restaurant-reservation' ),
							'add_new'               => __( 'Add reservation', 'online-restaurant-reservation' ),
							'add_new_item'          => __( 'Add new reservation', 'online-restaurant-reservation' ),
							'edit'                  => __( 'Edit', 'online-restaurant-reservation' ),
							'edit_item'             => __( 'Edit reservation', 'online-restaurant-reservation' ),
							'new_item'              => __( 'New reservation', 'online-restaurant-reservation' ),
							'view'                  => __( 'View reservation', 'online-restaurant-reservation' ),
							'view_item'             => __( 'View reservation', 'online-restaurant-reservation' ),
							'search_items'          => __( 'Search reservations', 'online-restaurant-reservation' ),
							'not_found'             => __( 'No reservations found', 'online-restaurant-reservation' ),
							'not_found_in_trash'    => __( 'No reservations found in trash', 'online-restaurant-reservation' ),
							'parent'                => __( 'Parent reservations', 'online-restaurant-reservation' ),
							'filter_items_list'     => __( 'Filter reservations', 'online-restaurant-reservation' ),
							'items_list_navigation' => __( 'Reservations navigation', 'online-restaurant-reservation' ),
							'items_list'            => __( 'Reservations list', 'online-restaurant-reservation' ),
						),
					'description'         => __( 'This is where table reservations are stored.', 'online-restaurant-reservation' ),
					'public'              => false,
					'show_ui'             => true,
					'capability_type'     => 'table_reservation',
					'map_meta_cap'        => true,
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'show_in_menu'        => current_user_can( 'manage_reservation' ) ? 'reservation' : true,
					'hierarchical'        => false,
					'rewrite'             => false,
					'query_var'           => false,
					'supports'            => array( 'title', 'comments', 'custom-fields' ),
					'show_in_nav_menus'   => false,
					'show_in_admin_bar'   => true,
				)
			)
		);

		do_action( 'online_restaurant_reservation_after_register_post_type' );
	}

	/**
	 * Register our custom post statuses, used for reservation status.
	 */
	public static function register_post_status() {
		$reservation_statuses = apply_filters( 'online_restaurant_reservation_register_post_statuses',
			array(
				'orr-pending'    => array(
					'label'                     => _x( 'Pending reservation', 'Reservation status', 'online-restaurant-reservation' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( 'Pending reservation <span class="count">(%s)</span>', 'Pending reservation <span class="count">(%s)</span>', 'online-restaurant-reservation' ),
				),
				'orr-confirmed'    => array(
					'label'                     => _x( 'Confirmed', 'Reservation status', 'online-restaurant-reservation' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( 'Confirmed <span class="count">(%s)</span>', 'Confirmed <span class="count">(%s)</span>', 'online-restaurant-reservation' ),
				),
				'orr-check-in'    => array(
					'label'                     => _x( 'Check in', 'Reservation status', 'online-restaurant-reservation' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( 'Check in <span class="count">(%s)</span>', 'Check in <span class="count">(%s)</span>', 'online-restaurant-reservation' ),
				),
				'orr-cancelled'    => array(
					'label'                     => _x( 'Cancelled', 'Reservation status', 'online-restaurant-reservation' ),
					'public'                    => false,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'online-restaurant-reservation' ),
				),
			)
		);

		foreach ( $reservation_statuses as $reservation_status => $values ) {
			register_post_status( $reservation_status, $values );
		}
	}
}

ORR_Post_Types::init();
