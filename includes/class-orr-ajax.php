<?php
/**
 * Online Restaurant Reservation ORR_AJAX
 *
 * AJAX Event Handler
 *
 * @class    ORR_AJAX
 * @version  1.0.0
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_AJAX Class.
 */
class ORR_AJAX {

	/**
	 * Hooks in ajax handlers
	 */
	public static function init() {
		self::add_ajax_events();
	}

	/**
	 * Hook in methods - uses WordPress ajax handlers (admin-ajax)
	 */
	public static function add_ajax_events() {
		// online_restaurant_reservation_EVENT => nopriv
		$ajax_events = array(
			'mark_reservation_status' => false,
			'get_reservation_details' => false,
			'exceptions_save_changes' => false,
			'on_date_select'          => true,
			'json_search_customers'   => false,
			'json_search_categories'  => false,
			'rated'                   => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_online_restaurant_reservation_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_online_restaurant_reservation_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	/**
	 * Mark an reservation with a status.
	 */
	public static function mark_reservation_status() {
		if ( current_user_can( 'manage_reservation' ) && check_admin_referer( 'online-restaurant-reservation-mark-reservation-status' ) ) {
			$status      = sanitize_text_field( $_GET['status'] );
			$reservation = orr_get_reservation( absint( $_GET['reservation_id'] ) );

			if ( orr_is_reservation_status( 'orr-' . $status ) && $reservation ) {
				$reservation->update_status( $status, '', true );
				do_action( 'online_restaurant_reservation_edit_status', $reservation->get_id(), $status );
			}
		}

		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php?post_type=table_reservation' ) );
		exit;
	}

	/**
	 * Get reservation details.
	 */
	public static function get_reservation_details() {
		check_admin_referer( 'online-restaurant-preview-reservation', 'security' );

		if ( ! current_user_can( 'manage_reservation' ) ) {
			wp_die( - 1 );
		}

		if ( $reservation = orr_get_reservation( absint( $_GET['reservation_id'] ) ) ) {

			ob_start();

			?>
			<div class="orr-reservation-preview__table-wrapper">
				<table cellspacing="0" class="orr-reservation-preview__table">
					<tr>
						<th><?php _e( 'Date', 'online-restaurant-reservation' ); ?></th>
						<th><?php _e( 'Time', 'online-restaurant-reservation' ); ?></th>
						<th><?php _e( 'Size', 'online-restaurant-reservation' ); ?></th>
					</tr>
					<tr class="reservation_item">
						<td><?php echo esc_html( $reservation->get_date_reserved()->date_i18n( apply_filters( 'online_restaurant_reservation_admin_date_format', get_option( 'date_format' ) ) ) ); ?></td>
						<td><?php echo esc_html( $reservation->get_date_reserved()->date_i18n( apply_filters( 'online_restaurant_reservation_admin_time_format', get_option( 'time_format' ) ) ) ); ?></td>
						<td><?php echo esc_html( $reservation->get_party_size() ) ?></td>
					</tr>
				</table>
			</div>
			<?php
			$item_html = ob_get_clean();

			wp_send_json_success( apply_filters( 'online_restaurant_reservation_ajax_get_reservation_details', array(
				'item_html'           => $item_html,
				'customer_note'       => $reservation->get_customer_note(),
				'reservation_email'   => $reservation->get_reservation_email(),
				'reservation_phone'   => $reservation->get_reservation_phone(),
				'reservation_number'  => $reservation->get_reservation_number(),
				'formatted_full_name' => ( $full_name = $reservation->get_formatted_customer_full_name() ) ? $full_name : __( 'N/A', 'online-restaurant-reservation' ),
			), $reservation ) );
		}
		exit;
	}

	/**
	 * Handle submissions from assets/js/orr-reservation-exceptions.js Backbone model.
	 */
	public static function exceptions_save_changes() {
		if ( ! isset( $_POST['orr_reservation_exceptions_nonce'], $_POST['changes'] ) ) {
			wp_send_json_error( 'missing_fields' );
			exit;
		}

		if ( ! wp_verify_nonce( $_POST['orr_reservation_exceptions_nonce'], 'orr_reservation_exceptions_nonce' ) ) {
			wp_send_json_error( 'bad_nonce' );
			exit;
		}

		if ( ! current_user_can( 'manage_reservation' ) ) {
			wp_send_json_error( 'missing_capabilities' );
			exit;
		}

		global $wpdb;

		$changes = $_POST['changes'];

		foreach ( $changes as $exception_id => $data ) {
			if ( isset( $data['deleted'] ) ) {
				if ( isset( $data['newRow'] ) ) {
					// So the user added and deleted a new row.
					// That's fine, it's not in the database anyways. NEXT!
					continue;
				}
				ORR_Reservation_Exceptions::_delete_exception( $exception_id );
				continue;
			}

			$exception_data = array_intersect_key( $data, array(
				'exception_id'    => 1,
				'exception_name'  => 1,
				'exception_order' => 1,
				'is_closed'       => 1,
				'start_date'      => 1,
				'end_date'        => 1,
				'start_time'      => 1,
				'end_time'        => 1,
			) );

			// If end date is empty then clone start date.
			if ( isset( $data['end_date'] ) && ! empty( $exception_data['start_date'] ) && ( empty( $exception_data['end_date'] ) || '0000:00:00' == $exception_data['end_date'] ) ) {
				$exception_data['end_date'] = $exception_data['start_date'];
			}

			// If end date is empty no need to update database.
			if ( isset ( $data['end_date'] ) && empty( $exception_data['end_date'] ) ) {
				$start_date = ORR_Reservation_Exceptions::get_start_date( $exception_id );

				if ( isset( $start_date['start_date'] ) ) {
					$exception_data['end_date'] = $start_date['start_date'];
				} else {
					unset( $exception_data['end_date'] );
				}
			}

			if ( isset( $data['newRow'] ) ) {
				if ( empty( $exception_data['start_date'] ) ) {
					// If start date don't exist then don't update.
					continue;
				}
				$exception_id = ORR_Reservation_Exceptions::_insert_exception( $exception_data );
			} elseif ( ! empty( $exception_data ) ) {
				ORR_Reservation_Exceptions::_update_exception( $exception_id, $exception_data );
			}
		}

		wp_send_json_success( array(
			'reservation_exceptions' => ORR_Reservation_Exceptions::get_exceptions()
		) );
	}

	/**
	 * Get time slots on date select.
	 */
	public static function on_date_select() {
		check_ajax_referer( 'orr_date_changed', 'security' );

		$selected_date = isset( $_POST['date'] ) ? $_POST['date'] : '';

		if ( ! preg_match( "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $selected_date ) ) {
			wp_die( - 1 );
		}

		$time_slot = ORR_Reservation_Exceptions::get_reservation_open_time_slot( $selected_date );

		wp_send_json_success( array(
			'time_slot' => $time_slot
		) );
	}

	/**
	 * Search for customers and return json.
	 */
	public static function json_search_customers() {
		ob_start();

		check_ajax_referer( 'search-customers', 'security' );

		if ( ! current_user_can( 'manage_reservation' ) ) {
			wp_die( -1 );
		}

		$term    = orr_clean( stripslashes( $_GET['term'] ) );
		$exclude = array();

		if ( empty( $term ) ) {
			wp_die();
		}

		if ( ! empty( $_GET['exclude'] ) ) {
			$exclude = array_map( 'intval', explode( ',', $_GET['exclude'] ) );
		}

		$found_customers = array();

		add_action( 'pre_user_query', array( __CLASS__, 'json_search_customer_name' ) );

		$customers_query = new WP_User_Query( apply_filters( 'online_restaurant_reservation_json_search_customers_query', array(
			'fields'         => 'all',
			'orderby'        => 'display_name',
			'search'         => '*' . $term . '*',
			'search_columns' => array( 'ID', 'user_login', 'user_email', 'user_nicename' ),
		) ) );

		remove_action( 'pre_user_query', array( __CLASS__, 'json_search_customer_name' ) );

		$customers = $customers_query->get_results();

		if ( ! empty( $customers ) ) {
			foreach ( $customers as $customer ) {
				if ( ! in_array( $customer->ID, $exclude ) ) {
					/* translators: 1: user display name 2: user ID 3: user email */
					$found_customers[ $customer->ID ] = sprintf(
						esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'online-restaurant-reservation' ),
						$customer->display_name,
						$customer->ID,
						sanitize_email( $customer->user_email )
					);
				}
			}
		}

		$found_customers = apply_filters( 'online_restaurant_reservation_json_search_found_customers', $found_customers );

		wp_send_json( $found_customers );
	}

	/**
	 * When searching using the WP_User_Query, search names (user meta) too.
	 *
	 * @param  object $query
	 * @return object
	 */
	public static function json_search_customer_name( $query ) {
		global $wpdb;

		$term = orr_clean( stripslashes( $_GET['term'] ) );
		if ( method_exists( $wpdb, 'esc_like' ) ) {
			$term = $wpdb->esc_like( $term );
		} else {
			$term = like_escape( $term );
		}

		$query->query_from  .= " INNER JOIN {$wpdb->usermeta} AS user_name ON {$wpdb->users}.ID = user_name.user_id AND ( user_name.meta_key = 'first_name' OR user_name.meta_key = 'last_name' ) ";
		$query->query_where .= $wpdb->prepare( ' OR user_name.meta_value LIKE %s ', '%' . $term . '%' );
	}

	/**
	 * Triggered when clicking the rating footer.
	 */
	public static function rated() {
		if ( ! current_user_can( 'manage_reservation' ) ) {
			wp_die( -1 );
		}
		update_option( 'online_restaurant_reservation_admin_footer_text_rated', 1 );
		wp_die();
	}
}

ORR_AJAX::init();
