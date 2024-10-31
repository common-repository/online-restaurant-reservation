<?php
/**
 * Online Restaurant Reservation Settings
 *
 * @class    ORR_Settings_Reservation
 * @version  1.5.1
 * @package  Online_Restaurant_Reservation/Admin
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ORR_Settings_Reservation', false ) ) :

/**
 * ORR_Settings_Reservation Class.
 */
class ORR_Settings_Reservation extends ORR_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'reservation';
		$this->label = __( 'Reservation', 'online-restaurant-reservation' );

		add_action( 'online_restaurant_reservation_admin_field_reservation_schedule', array( $this, 'reservation_schedule_setting' ) );
		add_action( 'online_restaurant_reservation_admin_field_reservation_exceptions', array( $this, 'reservation_exceptions_setting' ) );
		parent::__construct();
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			''           => __( 'Reservation schedule', 'online-restaurant-reservation' ),
			'exceptions' => __( 'Reservation exceptions', 'online-restaurant-reservation' ),
		);
		return apply_filters( 'online_restaurant_reservation_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters( 'online_restaurant_reservation_settings', array(

			array( 'title' => __( 'Reservation schedule', 'online-restaurant-reservation' ),  'desc' => __( 'Lists of weekly schedule during which you accept reservations.', 'online-restaurant-reservation' ), 'type' => 'title', 'id' => 'reservation_schedule_settings' ),

			array( 'type' => 'reservation_schedule' ),

			array( 'type' => 'sectionend', 'id' => 'reservation_schedule_settings' ),

		) );

		return apply_filters( 'online_restaurant_get_settings_' . $this->id, $settings );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section, $hide_save_button;

		wp_enqueue_script( 'orr-reservation-schedules' );

		if ( '' === $current_section ) {
			$settings = $this->get_settings();
			ORR_Admin_Settings::output_fields( $settings );
		} elseif ( 'exceptions' === $current_section ) {
			$hide_save_button = true;
			$this->output_reservation_exception_screen();
		}
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		if ( ! $current_section ) {
			$settings = $this->get_settings();
			ORR_Admin_Settings::save_fields( $settings );

			if ( ! empty( $_POST['reservation_schedule'] ) ) {
				$this->save_reservation_schedule();
			}
		}
	}

	/**
	 * Output reservation schedule settings.
	 */
	public function reservation_schedule_setting() {
		global $wp_locale;

		$time_range           = orr_get_time_range();
		$reservation_schedule = get_option( 'online_restaurant_reservation_schedule', array() );

		?>
		<tr valign="top">
			<td class="orr_schedules_wrapper" colspan="2">
				<table class="orr-reservation-schedules widefat" cellspacing="0">
					<thead>
						<tr>
							<?php
								$columns = apply_filters( 'online_restaurant_reservation_schedule_setting_columns', array(
									'sort'         => '',
									'name'         => __( 'Day', 'online-restaurant-reservation' ),
									'closed'       => __( 'Closed', 'online-restaurant-reservation' ),
									'opening_time' => __( 'Start time', 'online-restaurant-reservation' ),
									'seperator'    => '',
									'closing_time' => __( 'End time', 'online-restaurant-reservation' ),
								) );

								foreach ( $columns as $key => $column ) {
									echo '<th class="orr-reservation-schedule-table-' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>';
								}
							?>
						</tr>
					</thead>
					<tbody class="orr-reservation-schedule-rows">
						<?php
							for ( $day_index = 0; $day_index <= 6; $day_index++ ) {

								// Default value.
								if ( ! isset( $reservation_schedule[ $day_index ] ) ) {
									$reservation_schedule[ $day_index ] = array(
										'closed'   => 'no',
										'schedule' => array(
											'start_time' => array(
												'00:00:00'
											),
											'end_time' => array(
												'23:30:00'
											)
										),
									);
								}

								// ...and is closed.
								$is_closed = 'yes' === $reservation_schedule[ $day_index ]['closed'] ? 'enabled' : 'disabled';

								echo '<tr class="orr-reservation-schedule">';

								foreach ( $columns as $key => $column ) {

									switch ( $key ) {
										case 'sort' :
											echo '<td width="1%" class="orr-reservation-schedule"></td>';
											break;

										case 'name':
											echo '<td class="orr-reservation-schedule-table-' . esc_attr( $key ) . '">' . esc_html( $wp_locale->get_weekday( $day_index ) ) . '</td>';
											break;

										case 'closed':
											echo '<td width="5%" class="orr-reservation-schedule-table-' . esc_attr( $key ) . '"><a href="#">
												<span class="online-restaurant-reservation-input-toggle online-restaurant-reservation-input-toggle--' . $is_closed . '">
													<input id="reservation_schedule[' . $day_index . '][closed]" type="checkbox" name="reservation_schedule[' . $day_index . '][closed]" value="yes" ' . checked( $reservation_schedule[ $day_index ]['closed'], 'yes', false ) . ' class="orr-reservation-schedule-closed" />
												</span>
											</a></td>';
											break;

										case 'opening_time':
											echo '<td class="orr-reservation-schedule-table-' . esc_attr( $key ) . '">
												<input type="hidden" name="_reservation_schedule[' . $day_index . '][schedule][start_time][]" value="' . $reservation_schedule[ $day_index ]['schedule']['start_time'][0] . '" />
												<div class="edit">';
													echo '<select id="reservation_schedule_start_time" name="reservation_schedule[' . $day_index . '][schedule][start_time][]" class="orr-enhanced-select" data-placeholder="' . __( 'Start time', 'online-restaurant-reservation' ) . '">';
													foreach ( $time_range as $time_key => $time ) {
														echo '<option value="' . esc_attr( $time_key ) . '" ' . selected( $time_key, $reservation_schedule[ $day_index ]['schedule']['start_time'][0], false ) . '>' . esc_html( $time ) . '</option>';
													}
													echo '</select>
												</div>
											</td>';
											break;

										case 'closing_time':
											echo '<td class="orr-reservation-schedule-table-' . esc_attr( $key ) . '">
												<input type="hidden" name="_reservation_schedule[' . $day_index . '][schedule][end_time][]" value="' . $reservation_schedule[ $day_index ]['schedule']['end_time'][0] . '" />
												<div class="edit">';
													echo '<select id="reservation_schedule_end_time" name="reservation_schedule[' . $day_index . '][schedule][end_time][]" class="orr-enhanced-select" data-placeholder="' . __( 'End time', 'online-restaurant-reservation' ) . '">';
													foreach ( $time_range as $time_key => $time ) {
														echo '<option value="' . esc_attr( $time_key ) . '" ' . selected( $time_key, $reservation_schedule[ $day_index ]['schedule']['end_time'][0], false ) . '>' . esc_html( $time ) . '</option>';
													}
													echo '</select>
												</div>
											</td>';
											break;


										case 'seperator':
											echo '<td width="1%">&mdash;</td>';
											break;

										default :
											do_action( 'online_restaurant_reservation_schedules_setting_column_' . $key, $day_index );
											break;
									}
								}

								echo '</tr>';
							}
						?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
	}

	/**
	 * Handle output of the reservation exception settings screen.
	 */
	protected function output_reservation_exception_screen() {
		wp_localize_script( 'orr-reservation-exceptions', 'reservationExceptionsLocalizeScript', array(
			'exceptions'  => ORR_Reservation_Exceptions::get_exceptions(),
			'default_reservation_exception' => array(
				'exception_id' => 0,
				'is_closed'    => 'no',
				'name'         => '',
				'start_date'   => '',
				'end_date'     => '',
				'start_time'   => '00:00:00',
				'end_time'     => '23:30:00',
			),
			'orr_reservation_exceptions_nonce' => wp_create_nonce( 'orr_reservation_exceptions_nonce' ),
			'strings'       => array(
				'unload_confirmation_msg' => __( 'Your changed data will be lost if you leave this page without saving.', 'online-restaurant-reservation' ),
				'save_failed'             => __( 'Your changes were not saved. Please retry.', 'online-restaurant-reservation' ),
				'yes'                     => __( 'Yes', 'online-restaurant-reservation' ),
				'no'                      => __( 'No', 'online-restaurant-reservation' ),
			),
		) );
		wp_enqueue_script( 'orr-reservation-exceptions' );

		include_once( dirname( __FILE__ ) . '/views/html-admin-page-reservation-exceptions.php' );
	}

	/**
	 * Save reservation schedule.
	 */
	public function save_reservation_schedule() {
		global $wp_locale;

		for ( $day_index = 0; $day_index <= 6; $day_index++ ) {

			// When the schedule is empty set detault to no.
			if ( ! isset( $_POST['reservation_schedule'][ $day_index ]['closed'] ) ) {
				$_POST['reservation_schedule'][ $day_index ]['closed'] = 'no';
			}

			// ...and if select2 is disable then fetch data from hidden field.
			if ( ! isset( $_POST['reservation_schedule'][ $day_index ]['schedule'] ) ) {
				$_POST['reservation_schedule'][ $day_index ]['schedule'] = $_POST['_reservation_schedule'][ $day_index ]['schedule'];
			}
		}

		update_option( 'online_restaurant_reservation_schedule', $_POST['reservation_schedule'] );
	}
}

endif;

return new ORR_Settings_Reservation();
