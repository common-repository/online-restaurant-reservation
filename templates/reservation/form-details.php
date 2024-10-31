<?php
/**
 * Reservation customer information form
 *
 * This template can be overridden by copying it to yourtheme/restaurant-reservation/reservation/form-details.php.
 *
 * HOWEVER, on occasion Online Restaurant Reservation will need to update template files and you
 * and you (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.wpeverest.com/docs/online-restaurant-reservation/template-structure/
 * @author  WPEverest
 * @package Online_Restaurant_Reservation/Templates
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="online-restaurant-reservation-fields">
	<h3><?php _e( 'Reservation details', 'online-restaurant-reservation' ); ?></h3>

	<?php do_action( 'online_restaurant_reservation_before_customer_details_form', $reservation ); ?>

	<div class="online-restaurant-reservation-fields__field-wrapper">
		<?php
			$fields = $reservation->get_reservation_fields( 'details' );

			foreach ( $fields as $key => $field ) {
				online_restaurant_reservation_form_field( $key, $field, $reservation->get_value( $key ) );
			}
		?>
	</div>

	<?php do_action( 'online_restaurant_reservation_after_customer_details_form', $reservation ); ?>
</div>
<div class="online-restaurant-reservation-additional-fields">
	<?php do_action( 'online_restaurant_reservation_before_additional_notes', $reservation ); ?>

	<?php if ( apply_filters( 'online_restaurant_reservation_enable_table_reservation_notes_field', 'yes' === get_option( 'online_restaurant_reservation_enable_table_reservation_comments', 'yes' ) ) ) : ?>

		<div class="online-restaurant-reservation-additional-fields__field-wrapper">
			<a class="reservation-notes-toggle" href="#"><?php esc_html_e( 'Add a reservation notes', 'online-restaurant-reservation' ); ?></a>
			<?php foreach ( $reservation->get_reservation_fields( 'reservation' ) as $key => $field ) : ?>
				<?php online_restaurant_reservation_form_field( $key, $field, $reservation->get_value( $key ) ); ?>
			<?php endforeach; ?>
		</div>

	<?php endif; ?>

	<?php do_action( 'online_restaurant_reservation_after_additional_notes', $reservation ); ?>
</div>
