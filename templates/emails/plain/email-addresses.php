<?php
/**
 * Email Addresses (plain)
 *
 * This template can be overridden by copying it to yourtheme/restaurant-reservation/emails/plain/email-addresses.php.
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

echo "\n" . strtoupper( __( 'Contact details', 'online-restaurant-reservation' ) ) . "\n\n";
echo preg_replace( '#<br\s*/?>#i', "\n", $reservation->get_formatted_customer_full_name() ) . "\n";

if ( $reservation->get_reservation_phone() ) {
	echo $reservation->get_reservation_phone() . "\n";
}

if ( $reservation->get_reservation_email() ) {
	echo $reservation->get_reservation_email() . "\n";
}
