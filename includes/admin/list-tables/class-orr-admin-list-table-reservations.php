<?php
/**
 * List tables: reservations.
 *
 * @author   WPEverest
 * @category Admin
 * @package  Online_Restaurant_Reservation/Admin
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ORR_Admin_List_Table_Reservations', false ) ) {
	new ORR_Admin_List_Table_Reservations();
	return;
}

if ( ! class_exists( 'ORR_Admin_List_Table', false ) ) {
	include_once( 'abstract-class-orr-admin-list-table.php' );
}

/**
 * ORR_Admin_List_Table_Reservations Class.
 */
class ORR_Admin_List_Table_Reservations extends ORR_Admin_List_Table {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $list_table_type = 'table_reservation';

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'admin_notices', array( $this, 'bulk_admin_notices' ) );
		add_action( 'admin_footer', array( $this, 'reservation_preview_template' ) );
		add_filter( 'get_search_query', array( $this, 'search_label' ) );
		add_filter( 'query_vars', array( $this, 'add_custom_query_var' ) );
		add_action( 'parse_query', array( $this, 'search_custom_fields' ) );
	}

	/**
	 * Render blank state.
	 */
	protected function render_blank_state() {
		echo '<div class="online-restaurant-reservation-BlankState">';
		echo '<h2 class="online-restaurant-reservation-BlankState-message">' . esc_html__( 'When you receive a new reservation, it will appear here.', 'online-restaurant-reservation' ) . '</h2>';
		echo '<a class="online-restaurant-reservation-BlankState-cta button-primary button" href="https://docs.wpeverest.com/docs/online-restaurant-reservation/managing-reservations/">' . esc_html__( 'Learn more about reservations', 'online-restaurant-reservation' ) . '</a>';
		echo '</div>';
	}

	/**
	 * Define primary column.
	 *
	 * @return array
	 */
	protected function get_primary_column() {
		return 'reservation_number';
	}

	/**
	 * Get row actions to show in the list table.
	 *
	 * @param array   $actions Array of actions.
	 * @param WP_Post $post Current post object.
	 * @return array
	 */
	protected function get_row_actions( $actions, $post ) {
		return array();
	}

	/**
	 * Define which columns are sortable.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_sortable_columns( $columns ) {
		$custom = array(
			'reservation_number' => 'ID',
			'reservation_date'   => 'date',
			'reservation_size'   => 'reservation_size',
		);
		unset( $columns['comments'] );

		return wp_parse_args( $custom, $columns );
	}

	/**
	 * Define which columns to show on this screen.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function define_columns( $columns ) {
		$show_columns                        = array();
		$show_columns['cb']                  = $columns['cb'];
		$show_columns['reservation_number']  = __( 'Reservation', 'online-restaurant-reservation' );
		$show_columns['customer_details']    = __( 'Customer Details', 'online-restaurant-reservation' );
		$show_columns['reservation_date']    = __( 'Date', 'online-restaurant-reservation' );
		$show_columns['reservation_time']    = __( 'Time', 'online-restaurant-reservation' );
		$show_columns['reservation_status']  = __( 'Status', 'online-restaurant-reservation' );
		$show_columns['reservation_size']    = __( 'Size', 'online-restaurant-reservation' );

		if ( has_action( 'online_restaurant_reservation_admin_actions_start' ) || has_action( 'online_restaurant_reservation_admin_actions_end' ) || has_filter( 'online_restaurant_reservation_admin_actions' ) ) {
			$show_columns['reservation_actions'] = __( 'Actions', 'online-restaurant-reservation' );
		}

		wp_enqueue_script( 'orr-reservations' );

		return $show_columns;
	}

	/**
	 * Define bulk actions.
	 *
	 * @param array $actions Existing actions.
	 * @return array
	 */
	public function define_bulk_actions( $actions ) {
		if ( isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}

		$actions['mark_confirmed'] = __( 'Mark confirmed', 'online-restaurant-reservation' );
		$actions['mark_check-in']  = __( 'Mark check-in', 'online-restaurant-reservation' );
		$actions['mark_cancelled'] = __( 'Mark cancelled', 'online-restaurant-reservation' );

		return $actions;
	}

	/**
	 * Pre-fetch any data for the row each column has access to it. the_reservation global is there for bw compat.
	 *
	 * @param int $post_id Post ID being shown.
	 */
	protected function prepare_row_data( $post_id ) {
		global $the_reservation;

		if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
			$this->object = $the_reservation = orr_get_reservation( $post_id );
		}
	}

	/**
	 * Render columm: reservation_number.
	 */
	protected function render_reservation_number_column() {
		$buyer = '';

		if ( $this->object->get_first_name() || $this->object->get_last_name() ) {
			/* translators: 1: first name 2: last name */
			$buyer = trim( sprintf( _x( '%1$s %2$s', 'full name', 'online-restaurant-reservation' ), $this->object->get_first_name(), $this->object->get_last_name() ) );
		} elseif ( $this->object->get_customer_id() ) {
			$user  = get_user_by( 'id', $this->object->get_customer_id() );
			$buyer = ucwords( $user->display_name );
		}

		if ( $this->object->get_status() === 'trash' ) {
			echo '<strong>#' . esc_attr( $this->object->get_reservation_number() ) . ' ' . esc_html( $buyer ) . '</strong>';
		} else {
			echo '<a href="#" class="reservation-preview" data-reservation-id="' . absint( $this->object->get_id() ) . '" title="' . esc_attr( __( 'Preview', 'online-restaurant-reservation' ) ) . '">' . esc_html( __( 'Preview', 'online-restaurant-reservation' ) ) . '</a>';
			echo '<a href="' . esc_url( admin_url( 'post.php?post=' . absint( $this->object->get_id() ) ) . '&action=edit' ) . '" class="reservation-view"><strong>#' . esc_attr( $this->object->get_reservation_number() ) . ' ' . esc_html( $buyer ) . '</strong></a>';
		}
	}

	/**
	 * Render columm: reservation_status.
	 */
	protected function render_reservation_status_column() {
		$tooltip = $this->object->get_customer_note();

		if ( $tooltip ) {
			printf( '<mark class="reservation-status %s tips" data-tip="%s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $this->object->get_status() ) ), wp_kses_post( $tooltip ), esc_html( orr_get_reservation_status_name( $this->object->get_status() ) ) );
		} else {
			printf( '<mark class="reservation-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $this->object->get_status() ) ), esc_html( orr_get_reservation_status_name( $this->object->get_status() ) ) );
		}
	}

	/**
	 * Render columm: customer_details.
	 */
	protected function render_customer_details_column() {
		if ( $address = $this->object->get_formatted_customer_full_name() ) {
			echo esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) );
		} else {
			echo '&ndash;';
		}

		if ( $this->object->get_reservation_phone() ) {
			echo '<small class="meta">' . __( 'Phone:', 'online-restaurant-reservation' ) . ' ' . esc_html( $this->object->get_reservation_phone() ) . '</small>';
		}
	}

	/**
	 * Render columm: reservation_date.
	 */
	protected function render_reservation_date_column() {
		printf(
			'<time datetime="%1$s" title="%2$s">%3$s</time>',
			esc_attr( $this->object->get_date_reserved()->date( 'c' ) ),
			esc_html( $this->object->get_date_reserved()->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
			esc_html( $this->object->get_date_created()->date_i18n( apply_filters( 'online_restaurant_reservation_admin_date_format', get_option( 'date_format' ) ) ) )
		);
	}

	/**
	 * Render columm: reservation_time.
	 */
	protected function render_reservation_time_column() {
		if ( $this->object->get_date_reserved() ) {
			echo '<span class="meta">' . esc_html( $this->object->get_date_reserved()->date_i18n( get_option( 'time_format' ) ) ) . '</span>';
		} else {
			echo '<span class="na">&ndash;</span>';
		}
	}

	/**
	 * Render columm: reservation_size.
	 */
	protected function render_reservation_size_column() {
		if ( $this->object->get_party_size() ) {
			echo '<span class="meta">' . esc_html( $this->object->get_party_size() ) . '</span>';
		} else {
			echo '<span class="na">&ndash;</span>';
		}
	}

	/**
	 * Render columm: reservation_actions.
	 */
	protected function render_reservation_actions_column() {
		echo '<p>';

		do_action( 'online_restaurant_reservation_admin_actions_start', $this->object );

		$actions = array();

		if ( $this->object->has_status( array( 'pending' ) ) ) {
			$actions['confirmed'] = array(
				'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=online_restaurant_reservation_mark_reservation_status&status=confirmed&reservation_id=' . $this->object->get_id() ), 'online-restaurant-reservation-mark-reservation-status' ),
				'name'      => __( 'Confirmed', 'online-restaurant-reservation' ),
				'action'    => 'confirmed',
			);
		}

		if ( $this->object->has_status( array( 'pending', 'confirmed' ) ) ) {
			$actions['check-in'] = array(
				'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=online_restaurant_reservation_mark_reservation_status&status=check-in&reservation_id=' . $this->object->get_id() ), 'online-restaurant-reservation-mark-reservation-status' ),
				'name'      => __( 'Check-in', 'online-restaurant-reservation' ),
				'action'    => 'check-in',
			);
		}

		$actions = apply_filters( 'online_restaurant_reservation_admin_actions', $actions, $this->object );

		foreach ( $actions as $action ) {
			printf( '<a class="button tips %s" href="%s" data-tip="%s">%s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( $action['name'] ), esc_attr( $action['name'] ) );
		}

		do_action( 'online_restaurant_reservation_admin_actions_end', $this->object );

		echo '</p>';
	}

	/**
	 * Template for reservation preview.
	 */
	public function reservation_preview_template() {
		?>
		<script type="text/template" id="tmpl-orr-modal-view-reservation">
			<div class="orr-backbone-modal orr-reservation-preview">
				<div class="orr-backbone-modal-content">
					<section class="orr-backbone-modal-main" role="main">
						<header class="orr-backbone-modal-header">
							<h1><?php echo esc_html( sprintf( __( 'Reservation #%s', 'online-restaurant-reservation' ), '{{ data.reservation_number }}' ) ); ?></h1>
							<button class="modal-close modal-close-link dashicons dashicons-no-alt">
								<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'online-restaurant-reservation' ); ?></span>
							</button>
						</header>
						<article>
							<?php do_action( 'online_restaurant_reservation_admin_preview_start' ); ?>

							{{{ data.item_html }}}

							<div class="orr-reservation-preview-details">
								<div class="orr-reservation-preview-detail">
									<h2><?php esc_html_e( 'Contact details', 'online-restaurant-reservation' ); ?></h2>
									{{{ data.formatted_full_name }}}

									<# if ( data.reservation_email ) { #>
										<strong><?php esc_html_e( 'Email', 'online-restaurant-reservation' ); ?></strong>
										<a href="mailto:{{ data.reservation_email }}">{{ data.reservation_email }}</a>
									<# } #>

									<# if ( data.reservation_phone ) { #>
										<strong><?php esc_html_e( 'Phone', 'online-restaurant-reservation' ); ?></strong>
										<a href="tel:{{ data.reservation_phone }}">{{ data.reservation_phone }}</a>
									<# } #>
								</div>
								<# if ( data.customer_note ) { #>
									<div class="orr-reservation-preview-detail">
										<h2><?php esc_html_e( 'Customer note', 'online-restaurant-reservation' ); ?></h2>
										{{ data.customer_note }}
									</div>
								<# } #>
							</div>
							<?php do_action( 'online_restaurant_reservation_admin_preview_end' ); ?>
						</article>
						<footer>
							<div class="inner">
								<a class="button button-primary button-large" href="<?php echo esc_url( admin_url( 'post.php?action=edit' ) ); ?>&post={{ data.reservation_number }}"><?php esc_html_e( 'Edit reservation', 'online-restaurant-reservation' ); ?></a>
							</div>
						</footer>
					</section>
				</div>
			</div>
			<div class="orr-backbone-modal-backdrop modal-close"></div>
		</script>
		<?php
	}

	/**
	 * Handle bulk actions.
	 *
	 * @param  string $redirect_to URL to redirect to.
	 * @param  string $action      Action name.
	 * @param  array  $ids         List of ids.
	 * @return string
	 */
	public function handle_bulk_actions( $redirect_to, $action, $ids ) {
		// Bail out if this is not a status-changing action.
		if ( false === strpos( $action, 'mark_' ) ) {
			return $redirect_to;
		}

		$reservation_statuses = orr_get_reservation_statuses();
		$new_status           = substr( $action, 5 ); // Get the status name from action.
		$report_action        = 'marked_' . $new_status;

		// Sanity check: bail out if this is actually not a status, or is
		// not a registered status.
		if ( ! isset( $reservation_statuses[ 'orr-' . $new_status ] ) ) {
			return $redirect_to;
		}

		$changed = 0;
		$ids = array_map( 'absint', $ids );

		foreach ( $ids as $id ) {
			$reservation = orr_get_reservation( $id );
			$reservation->update_status( $new_status, __( 'Reservation status changed by bulk edit:', 'online-restaurant-reservation' ), true );
			do_action( 'online_restaurant_reservation_edit_status', $id, $new_status );
			$changed++;
		}

		$redirect_to = add_query_arg( array(
			'post_type'    => $this->list_table_type,
			$report_action => true,
			'changed'      => $changed,
			'ids'          => join( ',', $ids ),
		), $redirect_to );

		return esc_url_raw( $redirect_to );
	}

	/**
	 * Show confirmation message that reservation status changed for number of reservations.
	 */
	public function bulk_admin_notices() {
		global $post_type, $pagenow;

		// Bail out if not on table reservation list page.
		if ( 'edit.php' !== $pagenow || 'table_reservation' !== $post_type ) {
			return;
		}

		$reservation_statuses = orr_get_reservation_statuses();

		// Check if any status changes happened.
		foreach ( $reservation_statuses as $slug => $name ) {
			if ( isset( $_REQUEST[ 'marked_' . str_replace( 'orr-', '', $slug ) ] ) ) {  // WPCS: input var ok.

				$number = isset( $_REQUEST['changed'] ) ? absint( $_REQUEST['changed'] ) : 0; // WPCS: input var ok.
				/* translators: %s: orders count */
				$message = sprintf( _n( '%d reservation status changed.', '%d reservation statuses changed.', $number, 'online-restaurant-reservation' ), number_format_i18n( $number ) );
				echo '<div class="updated"><p>' . esc_html( $message ) . '</p></div>';
				break;
			}
		}
	}

	/**
	 * Render any custom filters and search inputs for the list table.
	 */
	protected function render_filters() {
		$user_string = '';
		$user_id     = '';

		if ( ! empty( $_GET['_customer_user'] ) ) { // WPCS: input var ok.
			$user_id     = absint( $_GET['_customer_user'] ); // WPCS: input var ok, sanitization ok.
			$user        = get_user_by( 'id', $user_id );
			/* translators: 1: user display name 2: user ID 3: user email */
			$user_string = sprintf(
				esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'online-restaurant-reservation' ),
				$user->display_name,
				absint( $user->ID ),
				$user->user_email
			);
		}
		?>
		<select class="orr-customer-search" name="_customer_user" data-placeholder="<?php esc_attr_e( 'Search for a customer&hellip;', 'online-restaurant-reservation' ); ?>" data-allow_clear="true">
			<option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo wp_kses_post( $user_string ); ?><option>
		</select>
		<?php
	}

	/**
	 * Handle any custom filters.
	 *
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	protected function query_filters( $query_vars ) {
		global $wp_post_statuses;

		// Filter the orders by the posted customer.
		if ( ! empty( $_GET['_customer_user'] ) ) { // WPCS: input var ok.
			// @codingStandardsIgnoreStart
			$query_vars['meta_query'] = array(
				array(
					'key'   => '_customer_user',
					'value' => (int) $_GET['_customer_user'], // WPCS: input var ok, sanitization ok.
					'compare' => '=',
				),
			);
			// @codingStandardsIgnoreEnd
		}

		// Sorting.
		if ( isset( $query_vars['orderby'] ) ) {
			if ( 'reservation_size' === $query_vars['orderby'] ) {
				// @codingStandardsIgnoreStart
				$query_vars = array_merge( $query_vars, array(
					'meta_key'  => '_party_size',
					'orderby'   => 'meta_value_num',
				) );
				// @codingStandardsIgnoreEnd
			}
		}

		// Status.
		if ( ! isset( $query_vars['post_status'] ) ) {
			$post_statuses = orr_get_reservation_statuses();

			foreach ( $post_statuses as $status => $value ) {
				if ( isset( $wp_post_statuses[ $status ] ) && false === $wp_post_statuses[ $status ]->show_in_admin_all_list ) {
					unset( $post_statuses[ $status ] );
				}
			}

			$query_vars['post_status'] = array_keys( $post_statuses );
		}
		return $query_vars;
	}

	/**
	 * Change the label when searching orders.
	 *
	 * @param mixed $query Current search query.
	 * @return string
	 */
	public function search_label( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' !== $pagenow || 'table_reservation' !== $typenow || ! get_query_var( 'table_reservation_search' ) || ! isset( $_GET['s'] ) ) { // WPCS: input var ok.
			return $query;
		}

		return orr_clean( wp_unslash( $_GET['s'] ) ); // WPCS: input var ok, sanitization ok.
	}

	/**
	 * Query vars for custom searches.
	 *
	 * @param mixed $public_query_vars Array of query vars.
	 * @return array
	 */
	public function add_custom_query_var( $public_query_vars ) {
		$public_query_vars[] = 'table_reservation_search';
		return $public_query_vars;
	}

	/**
	 * Search custom fields as well as content.
	 *
	 * @param WP_Query $wp Query object.
	 */
	public function search_custom_fields( $wp ) {
		global $pagenow;

		if ( 'edit.php' !== $pagenow || empty( $wp->query_vars['s'] ) || 'table_reservation' !== $wp->query_vars['post_type'] || ! isset( $_GET['s'] ) ) { // WPCS: input var ok.
			return;
		}

		$post_ids = orr_reservation_search( orr_clean( wp_unslash( $_GET['s'] ) ) ); // WPCS: input var ok, sanitization ok.

		if ( ! empty( $post_ids ) ) {
			// Remove "s" - we don't want to search reservation name.
			unset( $wp->query_vars['s'] );

			// so we know we're doing this.
			$wp->query_vars['table_reservation_search'] = true;

			// Search by found posts.
			$wp->query_vars['post__in'] = array_merge( $post_ids, array( 0 ) );
		}
	}
}

new ORR_Admin_List_Table_Reservations();
