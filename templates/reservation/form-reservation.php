<?php
/**
 * Reservation Form
 *
 * This template can be overridden by copying it to yourtheme/restaurant-reservation/reservation/form-reservation.php.
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

orr_print_notices();

do_action( 'online_restaurant_reservation_before_table_reservation_form', $reservation );

?>
<form name="reservation" method="post" class="reservation online-restaurant-reservation" enctype="multipart/form-data">
	<?php do_action( 'online_restaurant_reservation_table_reservation_before_customer_details' ); ?>

	<?php if ( $reservation->get_reservation_fields() ) : ?>

		<?php do_action( 'online_restaurant_reservation_table_reservation_before_customer_details' ); ?>

		<div class="columns" id="reservation_details">
			<?php do_action( 'online_restaurant_reservation_form_fields' ); ?>
		</div>

		<?php do_action( 'online_restaurant_reservation_after_customer_details' ); ?>

	<?php endif; ?>

	<div id="reservation" class="form-row place-reservation">

		<?php do_action( 'online_restaurant_reservation_table_reservation_before_submit' ); ?>

		<?php echo apply_filters( 'online_restaurant_reservation_table_reservation_button_html', '<input type="submit" class="button alt" name="online_restaurant_reservation_place_reservation" id="place_reservation" value="' . esc_attr( $reservation_button_text ) . '" data-value="' . esc_attr( $reservation_button_text ) . '" />' ); ?>

		<?php do_action( 'online_restaurant_reservation_table_reservation_after_submit' ); ?>

		<?php wp_nonce_field( 'online-table-reservation-process_table_reservation' ); ?>

	</div>
</form>

<?php do_action( 'online_restaurant_reservation_after_table_reservation_form', $reservation ); ?>
