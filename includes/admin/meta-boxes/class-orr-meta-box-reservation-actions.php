<?php
/**
 * Reservation Actions
 *
 * Functions for displaying the reservation actions meta box.
 *
 * @version  1.0.0
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Meta_Box_Reservation_Actions Class.
 */
class ORR_Meta_Box_Reservation_Actions {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $thereservation;

		// This is used by some callbacks attached to hooks such as online_restaurant_reservation_actions which rely on the global to determine if actions should be displayed for certain reservations.
		if ( ! is_object( $thereservation ) ) {
			$thereservation = orr_get_reservation( $post->ID );
		}

		$reservation_actions = apply_filters( 'online_restaurant_reservation_actions', array(
			'send_reservation_details'       => __( 'Email reservation details to customer', 'online-restaurant-reservation' ),
			'send_reservation_details_admin' => __( 'Resend new reservation notification', 'online-restaurant-reservation' ),
		) );
		?>
		<ul class="reservation_actions submitbox">

			<?php do_action( 'online_restaurant_reservation_actionsstart', $post->ID ); ?>

			<li class="wide" id="actions">
				<select name="orr_reservation_action">
					<option value=""><?php _e( 'Choose an action...', 'online-restaurant-reservation' ); ?></option>
					<?php foreach ( $reservation_actions as $action => $title ) : ?>
						<option value="<?php echo $action; ?>"><?php echo $title; ?></option>
					<?php endforeach; ?>
				</select>
				<button class="button orr-reload"><span><?php _e( 'Apply', 'online-restaurant-reservation' ); ?></span></button>
			</li>

			<li class="wide">
				<div id="delete-action"><?php

					if ( current_user_can( 'delete_post', $post->ID ) ) {

						if ( ! EMPTY_TRASH_DAYS ) {
							$delete_text = __( 'Delete permanently', 'online-restaurant-reservation' );
						} else {
							$delete_text = __( 'Move to trash', 'online-restaurant-reservation' );
						}
						?><a class="submitdelete deletion" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?>"><?php echo $delete_text; ?></a><?php
					}
				?></div>

				<input type="submit" class="button save_reservation button-primary" name="save" value="<?php echo 'auto-draft' === $post->post_status ? esc_attr__( 'Create', 'online-restaurant-reservation' ) : esc_attr__( 'Update', 'online-restaurant-reservation' ); ?>" />
			</li>

			<?php do_action( 'online_restaurant_reservation_actions_end', $post->ID ); ?>

		</ul>
		<?php
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 */
	public static function save( $post_id, $post ) {
		// Reservation data saved, now get it so we can manipulate status.
		$reservation = orr_get_reservation( $post_id );

		// Handle button actions.
		if ( ! empty( $_POST['orr_reservation_action'] ) ) { // @codingStandardsIgnoreLine

			$action = orr_clean( wp_unslash( $_POST['orr_reservation_action'] ) ); // @codingStandardsIgnoreLine

			if ( 'send_reservation_details' === $action ) {
				do_action( 'online_restaurant_reservation_before_resend_emails', $reservation, 'customer_invoice' );

				// Send the customer invoice email.
				ORR()->mailer()->customer_invoice( $reservation );

				do_action( 'online_restaurant_reservation_after_resend_email', $reservation, 'customer_invoice' );

				// Change the post saved message.
				add_filter( 'redirect_post_location', array( __CLASS__, 'set_email_sent_message' ) );

			} elseif ( 'send_reservation_details_admin' === $action ) {

				do_action( 'online_restaurant_reservation_before_resend_emails', $reservation, 'new_reservation' );

				// Send the new reservation email.
				ORR()->mailer()->emails['ORR_Email_New_Reservation']->trigger( $reservation->get_id(), $reservation );

				do_action( 'online_restaurant_reservation_after_resend_email', $reservation, 'new_reservation' );

				// Change the post saved message.
				add_filter( 'redirect_post_location', array( __CLASS__, 'set_email_sent_message' ) );

			} else {

				if ( ! did_action( 'online_restaurant_reservation_action_' . sanitize_title( $action ) ) ) {
					do_action( 'online_restaurant_reservation_action_' . sanitize_title( $action ), $reservation );
				}
			}
		}
	}

	/**
	 * Set the correct message ID.
	 *
	 * @static
	 * @param  string $location
	 * @return string
	 */
	public static function set_email_sent_message( $location ) {
		return add_query_arg( 'message', 11, $location );
	}
}
