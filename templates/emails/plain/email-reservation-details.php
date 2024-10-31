<?php
/**
 * Reservation details table shown in emails (plain)
 *
 * This template can be overridden by copying it to yourtheme/restaurant-reservation/emails/plain/email-reservation-details.php.
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

do_action( 'online_restaurant_reservation_email_before_reservation_table', $reservation, $sent_to_admin, $plain_text, $email );

echo strtoupper( sprintf( __( 'Reservation number: %s', 'online-restaurant-reservation' ), $reservation->get_reservation_number() ) ) . "\n";
echo orr_format_datetime( $reservation->get_date_created() ) . "\n\n";

echo __( 'Reservation Date:', 'online-restaurant-reservation' ) . "\t " . orr_format_datetime( $reservation->get_date_reserved(), get_option( 'date_format' ) ) . "\n";
echo __( 'Reservation Time:', 'online-restaurant-reservation' ) . "\t " . orr_format_datetime( $reservation->get_date_reserved(), get_option( 'time_format' ) ) . "\n";
echo __( 'Party size:', 'online-restaurant-reservation' ) . "\t " . $reservation->get_party_size() . "\n";

echo "===========\n\n";

if ( $reservation->get_customer_note() ) {
	echo __( 'Note:', 'online-restaurant-reservation' ) . "\t " . wptexturize( $reservation->get_customer_note() ) . "\n";
}

if ( $sent_to_admin ) {
	echo "\n" . sprintf( __( 'View reservation: %s', 'online-restaurant-reservation' ), admin_url( 'post.php?post=' . $reservation->get_id() . '&action=edit' ) ) . "\n";
}

do_action( 'online_restaurant_reservation_email_after_reservation_table', $reservation, $sent_to_admin, $plain_text, $email );
