<?php
/**
 * Reservation Data
 *
 * Functions for displaying the reservation data meta box.
 *
 * @version  1.0.0
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Meta_Box_Reservation_Data Class.
 */
class ORR_Meta_Box_Reservation_Data {

	/**
	 * Reservation fields.
	 *
	 * @var array
	 */
	protected static $reservation_fields = array();

	/**
	 * Init reservation fields we display + save.
	 */
	public static function init_reservation_fields() {
		self::$reservation_fields = apply_filters( 'online_table_reservation_admin_fields', array(
			'first_name' => array(
				'label' => __( 'First name', 'online-restaurant-reservation', 'online-restaurant-reservation' ),
				'show'  => false,
			),
			'last_name' => array(
				'label' => __( 'Last name', 'online-restaurant-reservation', 'online-restaurant-reservation' ),
				'show'  => false,
			),
			'party_size' => array(
				'label'  => __( 'Party size', 'online-restaurant-reservation', 'online-restaurant-reservation' ),
				'type'   => 'number',
				'custom_attributes' => array(
					'step' 	=> 1,
					'min'	=> 1,
					'max'   => 100,
				),
			),
			'email' => array(
				'label' => __( 'Email address', 'online-restaurant-reservation' ),
			),
			'phone' => array(
				'label' => __( 'Phone', 'online-restaurant-reservation' ),
			),
		) );
	}

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $thereservation;

		if ( ! is_object( $thereservation ) ) {
			$thereservation = orr_get_reservation( $post->ID );
		}

		$reservation = $thereservation;

		self::init_reservation_fields();

		wp_nonce_field( 'online_restaurant_reservation_save_data', 'online_restaurant_reservation_meta_nonce' );
		?>
		<style type="text/css">
			#post-body-content, #titlediv { display:none }
		</style>
		<div class="panel-wrap online-restaurant-reservation">
			<input name="post_title" type="hidden" value="<?php echo empty( $post->post_title ) ? __( 'Reservation', 'online-restaurant-reservation' ) : esc_attr( $post->post_title ); ?>" />
			<input name="post_status" type="hidden" value="<?php echo esc_attr( $post->post_status ); ?>" />
			<div id="reservation_data" class="panel">
				<h2 class="online-restaurant-reservation-reservation-data__heading"><?php

				/* translators: %s: reservation number */
				printf(
					esc_html__( 'Reservation #%s details', 'online-restaurant-reservation' ),
					esc_html( $reservation->get_reservation_number() )
				);

				?></h2>
				<p class="online-restaurant-reservation-reservation-data__meta reservation_meta"><?php

				$meta_list = array();

				if ( $reservation->get_date_created() ) {
					/* translators: 1: date 2: time */
					$meta_list[] = sprintf(
						__( 'Created on %1$s @ %2$s', 'online-restaurant-reservation' ),
						orr_format_datetime( $reservation->get_date_created() ),
						orr_format_datetime( $reservation->get_date_created(), get_option( 'time_format' ) )
					);
				}

				if ( $ip_address = $reservation->get_customer_ip_address() ) {
					/* translators: %s: IP address */
					$meta_list[] = sprintf(
						__( 'Customer IP: %s', 'online-restaurant-reservation' ),
						'<span class="online-restaurant-reservation-Reservation-customerIP">' . esc_html( $ip_address ) . '</span>'
					);
				}

				echo wp_kses_post( implode( '. ', $meta_list ) ) . '.';

				?>
				<div class="reservation_data_column_container">
					<div class="reservation_data_column">
						<h3><?php _e( 'General Details', 'online-restaurant-reservation' ); ?></h3>

						<p class="form-field form-field-wide"><label for="reservation_date"><?php _e( 'Reservation date:', 'online-restaurant-reservation' ) ?></label>
							<input type="text" class="date-picker" name="reservation_date" id="reservation_date" maxlength="10" value="<?php echo orr_format_datetime( $reservation->get_date_reserved(), 'Y-m-d' ); ?>" pattern="<?php echo esc_attr( apply_filters( 'online_restaurant_reservation_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])' ) ); ?>" />@&lrm;<input type="number" class="hour" placeholder="<?php esc_attr_e( 'h', 'online-restaurant-reservation' ) ?>" name="reservation_date_hour" id="reservation_date_hour" min="0" max="23" step="1" value="<?php echo orr_format_datetime( $reservation->get_date_reserved(), 'H' ); ?>" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:<input type="number" class="minute" placeholder="<?php esc_attr_e( 'm', 'online-restaurant-reservation' ) ?>" name="reservation_date_minute" id="reservation_date_minute" min="0" max="59" step="1" value="<?php echo orr_format_datetime( $reservation->get_date_reserved(), 'i' ); ?>" pattern="[0-5]{1}[0-9]{1}" />&lrm;
						</p>

						<p class="form-field form-field-wide orr-reservation-status">
							<label for="reservation_status"><?php _e( 'Reservation status:', 'online-restaurant-reservation' ) ?></label>
							<select id="reservation_status" name="reservation_status" class="orr-enhanced-select">
								<?php
									$statuses = orr_get_reservation_statuses();
									foreach ( $statuses as $status => $status_name ) {
										echo '<option value="' . esc_attr( $status ) . '" ' . selected( $status, 'orr-' . $reservation->get_status(), false ) . '>' . esc_html( $status_name ) . '</option>';
									}
								?>
							</select>
						</p>

						<p class="form-field form-field-wide orr-customer-user">
							<!--email_off--> <!-- Disable CloudFlare email obfuscation -->
							<label for="customer_user"><?php _e( 'Reserved by:', 'online-restaurant-reservation' ) ?> <?php
								if ( $reservation->get_user_id() ) {
									$args = array(
										'post_status'    => 'all',
										'post_type'      => 'table_reservation',
										'_customer_user' => $reservation->get_user_id( 'edit' ),
									);
									printf( '<a href="%s">%s</a>',
										esc_url( add_query_arg( $args, admin_url( 'edit.php' ) ) ),
										__( 'View other reservations &rarr;', 'online-restaurant-reservation' )
									);
								}
							?></label>
							<?php
							$user_string = '';
							$user_id     = '';
							if ( $reservation->get_user_id() ) {
								$user_id     = absint( $reservation->get_user_id() );
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
							<select class="orr-customer-search" id="customer_user" name="customer_user" data-placeholder="<?php esc_attr_e( 'Guest', 'online-restaurant-reservation' ); ?>" data-allow_clear="true">
								<option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo htmlspecialchars( $user_string ); ?></option>
							</select>
							<!--/email_off-->
						</p>
						<?php do_action( 'online_restaurant_reservation_admin_data_after_reservation_details', $reservation ); ?>
					</div>
					<div class="reservation_data_column">
						<h3>
							<?php _e( 'Customer Details', 'online-restaurant-reservation' ); ?>
							<a href="#" class="edit_details"><?php _e( 'Edit', 'online-restaurant-reservation' ); ?></a>
						</h3>
						<?php
							// Display values.
							echo '<div class="details">';

								if ( $reservation->get_formatted_customer_full_name() ) {
									echo '<p><strong>' . __( 'Name:', 'online-restaurant-reservation' ) . '</strong> ' . wp_kses( $reservation->get_formatted_customer_full_name(), array( 'br' => array() ) ) . '</p>';
								} else {
									echo '<p class="none_set"><strong>' . __( 'Name:', 'online-restaurant-reservation' ) . '</strong> ' . __( 'No name set.', 'online-restaurant-reservation' ) . '</p>';
								}

								foreach ( self::$reservation_fields as $field_name => $field ) {
									if ( isset( $field['show'] ) && false === $field['show'] ) {
										continue;
									}

									if ( in_array( $field_name, array( 'phone', 'email' ) ) ) {
										$field_name = 'reservation_' . $field_name;
									}

									if ( is_callable( array( $reservation, 'get_' . $field_name ) ) ) {
										$field_value = $reservation->{"get_$field_name"}();
									} else {
										$field_value = get_post_meta( $reservation->get_id(), '_' . $field_name, true );
									}

									if ( 'reservation_phone' === $field_name ) {
										$field_value = orr_make_phone_clickable( $field_value );
									} else {
										$field_value = make_clickable( esc_html( $field_value ) );
									}

									echo '<p><strong>' . esc_html( $field['label'] ) . ':</strong> ' . wp_kses_post( $field_value ) . '</p>';
								}

								if ( apply_filters( 'online_restaurant_reservation_enable_table_reservation_notes_field', 'yes' === get_option( 'online_restaurant_reservation_enable_table_reservation_comments', 'yes' ) ) && $post->post_excerpt ) {
									echo '<p><strong>' . __( 'Customer provided note:', 'online-restaurant-reservation' ) . '</strong> ' . nl2br( esc_html( $post->post_excerpt ) ) . '</p>';
								}

							echo '</div>';

							// Display form
							echo '<div class="edit_details">';

								foreach ( self::$reservation_fields as $field_name => $field ) {
									if ( ! isset( $field['type'] ) ) {
										$field['type'] = 'text';
									}
									if ( ! isset( $field['id'] ) ) {
										$field['id'] = '_' . $field_name;

										if ( in_array( $field_name, array( 'phone', 'email' ) ) ) {
											$field['id'] = '_reservation_' . $field_name;
										}
									}
									switch ( $field['type'] ) {
										case 'select' :
											online_restaurant_reservation_wp_select( $field );
										break;
										default :
											online_restaurant_reservation_wp_text_input( $field );
										break;
									}
								}

								if ( apply_filters( 'online_restaurant_reservation_enable_table_reservation_notes_field', 'yes' === get_option( 'online_restaurant_reservation_enable_table_reservation_comments', 'yes' ) )  ) {
									?>
									<p class="form-field form-field-wide"><label for="excerpt"><?php _e( 'Customer provided note', 'online-restaurant-reservation' ) ?>:</label>
									<textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt" placeholder="<?php esc_attr_e( 'Customer notes about the reservation', 'online-restaurant-reservation' ); ?>"><?php echo wp_kses_post( $post->post_excerpt ); ?></textarea></p>
									<?php
								}

							echo '</div>';

							do_action( 'online_restaurant_reservation_admin_data_after_reservation_fields', $reservation );
						?>
					</div>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $reservation_id Reservation ID.
	 */
	public static function save( $reservation_id ) {
		self::init_reservation_fields();

		// Get reservation object.
		$reservation = orr_get_reservation( $reservation_id );
		$props       = array();

		// Update customer.
		$customer_id = isset( $_POST['customer_user'] ) ? absint( $_POST['customer_user'] ) : 0;
		if ( $customer_id !== $reservation->get_customer_id() ) {
			$props['customer_id'] = $customer_id;
		}

		// Update reservation fields.
		if ( ! empty( self::$reservation_fields ) ) {
			foreach ( self::$reservation_fields as $field_name => $field ) {
				if ( ! isset( $field['id'] ) ) {
					$field['id'] = '_' . $field_name;

					if ( in_array( $field_name, array( 'phone', 'email' ) ) ) {
						$field['id'] = '_reservation_' . $field_name;
					}
				}

				$prop_key = substr( $field['id'], 1 );

				if ( ! isset( $_POST[ $field['id'] ] ) ) {
					continue;
				}

				if ( is_callable( array( $reservation, "set_{$prop_key}" ) ) ) {
					$props[ $prop_key ] = orr_clean( $_POST[ $field['id'] ] );
				} else {
					update_post_meta( $reservation_id, $field['id'], orr_clean( $_POST[ $field['id'] ] ), true );
				}
			}
		}

		// Created date.
		if ( ! $reservation->get_date_created() ) {
			$props['date_created'] = current_time( 'timestamp', true );
		}

		// Update date.
		if ( empty( $_POST['reservation_date'] ) ) {
			$date = current_time( 'timestamp', true );
		} else {
			$date = gmdate( 'Y-m-d H:i:s', strtotime( $_POST['reservation_date'] . ' ' . (int) $_POST['reservation_date_hour'] . ':' . (int) $_POST['reservation_date_minute'] . ':00' ) );
		}

		$props['date_reserved'] = $date;

		// Save reservation data.
		$reservation->set_props( $props );
		$reservation->set_status( orr_clean( $_POST['reservation_status'] ), '', true );
		$reservation->save();
	}
}
