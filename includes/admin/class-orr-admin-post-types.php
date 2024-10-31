<?php
/**
 * Post Types Admin
 *
 * @class    ORR_Admin_Post_Types
 * @version  1.0.0
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ORR_Admin_Post_Types', false ) ) {
	new ORR_Admin_Post_Types();
	return;
}

/**
 * ORR_Admin_Post_Types Class
 *
 * Handles the edit posts views and some functionality on the edit post screen for ORR post types.
 */
class ORR_Admin_Post_Types {

	/**
	 * Constructor.
	 */
	public function __construct() {
		include_once( dirname( __FILE__ ) . '/class-orr-admin-meta-boxes.php' );

		// Load correct list table classes for current screen.
		add_action( 'current_screen', array( $this, 'setup_screen' ) );
		add_action( 'check_ajax_referer', array( $this, 'setup_screen' ) );

		// Admin notices.
		add_filter( 'post_updated_messages', array( $this, 'post_updated_messages' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'bulk_post_updated_messages' ), 10, 2 );

		// Disable Auto Save.
		add_action( 'admin_print_scripts', array( $this, 'disable_autosave' ) );

		// Add a post display state for special ORR pages.
		add_filter( 'display_post_states', array( $this, 'add_display_post_states' ), 10, 2 );
	}

	/**
	 * Looks at the current screen and loads the correct list table handler.
	 *
	 * @since 1.0.0
	 */
	public function setup_screen( $screen_id ) {
		$screen_id = false;

		if ( function_exists( 'get_current_screen' ) ) {
			$screen    = get_current_screen();
			$screen_id = isset( $screen, $screen->id ) ? $screen->id : '';
		}

		if ( ! empty( $_REQUEST['screen'] ) ) { // WPCS: input var ok.
			$screen_id = orr_clean( wp_unslash( $_REQUEST['screen'] ) ); // WPCS: input var ok, sanitization ok.
		}

		if ( 'edit-table_reservation' === $screen_id ) {
			include_once( 'list-tables/class-orr-admin-list-table-reservations.php' );
		}
	}

	/**
	 * Change messages when a post type is updated.
	 *
	 * @param  array $messages Array of messages.
	 * @return array
	 */
	public function post_updated_messages( $messages ) {
		global $post;

		/* translators: Publish box date format, see https://secure.php.net/date */
		$scheduled_date = date_i18n( __( 'M j, Y @ H:i', 'online-restaurant-reservation' ), strtotime( $post->post_date ) );

		$messages['table_reservation'] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => __( 'Reservation updated.', 'online-restaurant-reservation' ),
			2 => __( 'Custom field updated.', 'online-restaurant-reservation' ),
			3 => __( 'Custom field deleted.', 'online-restaurant-reservation' ),
			4 => __( 'Reservation updated.', 'online-restaurant-reservation' ),
			/* translators: %s: date and time of the revision */
			5 => isset( $_GET['revision'] ) ? sprintf( __( 'Reservation restored to revision from %s', 'online-restaurant-reservation' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => __( 'Reservation updated.', 'online-restaurant-reservation' ),
			7 => __( 'Reservation saved.', 'online-restaurant-reservation' ),
			8 => __( 'Reservation submitted.', 'online-restaurant-reservation' ),
			9 => sprintf( __( 'Reservation scheduled for: %s.', 'online-restaurant-reservation' ), '<strong>' . $scheduled_date . '</strong>' ),
			10 => __( 'Reservation draft updated.', 'online-restaurant-reservation' ),
			11 => __( 'Reservation updated and sent.', 'online-restaurant-reservation' ),
		);

		return $messages;
	}

	/**
	 * Specify custom bulk actions messages for different post types.
	 *
	 * @param  array $bulk_messages Array of messages.
	 * @param  array $bulk_counts Array of how many objects were updated.
	 * @return array
	 */
	public function bulk_post_updated_messages( $bulk_messages, $bulk_counts ) {

		$bulk_messages['table_reservation'] = array(
			/* translators: %s: reservation count */
			'updated'   => _n( '%s reservation updated.', '%s reservations updated.', $bulk_counts['updated'], 'online-restaurant-reservation' ),
			/* translators: %s: reservation count */
			'locked'    => _n( '%s reservation not updated, somebody is editing it.', '%s reservations not updated, somebody is editing them.', $bulk_counts['locked'], 'online-restaurant-reservation' ),
			/* translators: %s: reservation count */
			'deleted'   => _n( '%s reservation permanently deleted.', '%s reservations permanently deleted.', $bulk_counts['deleted'], 'online-restaurant-reservation' ),
			/* translators: %s: reservation count */
			'trashed'   => _n( '%s reservation moved to the Trash.', '%s reservations moved to the Trash.', $bulk_counts['trashed'], 'online-restaurant-reservation' ),
			/* translators: %s: reservation count */
			'untrashed' => _n( '%s reservation restored from the Trash.', '%s reservations restored from the Trash.', $bulk_counts['untrashed'], 'online-restaurant-reservation' ),
		);

		return $bulk_messages;
	}

	/**
	 * Disable the auto-save functionality for Reservations.
	 */
	public function disable_autosave() {
		global $post;

		if ( $post && in_array( get_post_type( $post->ID ), array( 'table_reservation' ) ) ) {
			wp_dequeue_script( 'autosave' );
		}
	}

	/**
	 * Add a post display state for special ORR pages in the page list table.
	 *
	 * @param array   $post_states An array of post display states.
	 * @param WP_Post $post        The current post object.
	 */
	public function add_display_post_states( $post_states, $post ) {
		if ( has_shortcode( $post->post_content, 'online_restaurant_reservation' ) ) {
			$post_states['orr_page_for_reservation'] = __( 'Reservation Page', 'online-restaurant-reservation' );
		}

		return $post_states;
	}
}

new ORR_Admin_Post_Types();
