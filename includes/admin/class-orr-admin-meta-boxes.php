<?php
/**
 * Online Restaurant Reservation Meta Boxes
 *
 * Sets up the write panel used by table reservations (custom post types).
 *
 * @class    ORR_Admin_Meta_Boxes
 * @version  1.0.0
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Admin_Meta_Boxes Class.
 */
class ORR_Admin_Meta_Boxes {

	/**
	 * Is meta boxes saved once?
	 *
	 * @var boolean
	 */
	private static $saved_meta_boxes = false;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 10 );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );

		/**
		 * Save Reservation Meta Boxes.
		 *
		 * In reservation:
		 *      Save reservation data - also updates status and sends out admin emails if needed. Last to show latest data.
		 *      Save actions - sends out other emails. Last to show latest data.
		 */
		add_action( 'online_table_reservation_process_table_reservation_meta', 'ORR_Meta_Box_Reservation_Data::save', 20, 2 );
		add_action( 'online_table_reservation_process_table_reservation_meta', 'ORR_Meta_Box_Reservation_Actions::save', 30, 2 );

		// Include required meta boxes classes.
		include_once( dirname( __FILE__ ) . '/meta-boxes/class-orr-meta-box-reservation-data.php' );
		include_once( dirname( __FILE__ ) . '/meta-boxes/class-orr-meta-box-reservation-actions.php' );
	}

	/**
	 * Remove bloat.
	 */
	public function remove_meta_boxes() {
		remove_meta_box( 'commentsdiv', 'table_reservation', 'normal' );
		remove_meta_box( 'commentstatusdiv', 'table_reservation', 'normal' );
		remove_meta_box( 'slugdiv', 'table_reservation', 'normal' );
		remove_meta_box( 'submitdiv', 'table_reservation', 'side' );
	}

	/**
	 * Add ORR Meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box( 'online-restaurant-reservation-data', __( 'Reservation data', 'online-restaurant-reservation' ), 'ORR_Meta_Box_Reservation_Data::output', 'table_reservation', 'normal', 'high' );
		add_meta_box( 'online-restaurant-reservation-actions', __( 'Reservation actions', 'online-restaurant-reservation' ), 'ORR_Meta_Box_Reservation_Actions::output', 'table_reservation', 'side', 'high' );
	}

	/**
	 * Check if we're saving, the trigger an action based on the post type.
	 *
	 * @param  int $post_id
	 * @param  object $post
	 */
	public function save_meta_boxes( $post_id, $post ) {
		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
			return;
		}

		// Dont' save meta boxes for revisions or autosaves
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

		// Check the nonce
		if ( empty( $_POST['online_restaurant_reservation_meta_nonce'] ) || ! wp_verify_nonce( $_POST['online_restaurant_reservation_meta_nonce'], 'online_restaurant_reservation_save_data' ) ) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events
		if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
			return;
		}

		// Check user has permission to edit
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// We need this save event to run once to avoid potential endless loops. This would have been perfect:
		self::$saved_meta_boxes = true;

		// Check the post type
		if ( in_array( $post->post_type, array( 'table_reservation' ) ) ) {
			update_post_meta( 76, 'angry', 'post' );
			do_action( 'online_table_reservation_process_' . $post->post_type . '_meta', $post_id, $post );
		}
	}
}

new ORR_Admin_Meta_Boxes();
