<?php
/**
 * Customer cancelled reservation email (plain)
 *
 * This template can be overridden by copying it to yourtheme/restaurant-reservation/emails/plain/customer-cancelled-reservation.php.
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

echo "= " . $email_heading . " =\n\n";

if ( $party_size_limit ) {
	echo sprintf( __( 'Your reservation on %s has been cancelled because of limited party size. The reservation was as follows:', 'online-restaurant-reservation' ), get_option( 'blogname' ) );
} else {
	echo sprintf( __( 'Your reservation on %s has been cancelled. The reservation was as follows:', 'online-restaurant-reservation' ), get_option( 'blogname' ) );
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Hook for the online_restaurant_reservation_email_reservation_details.
 *
 * @hooked ORR_Emails::reservation_details() Shows the reservation details table.
 */
do_action( 'online_restaurant_reservation_email_reservation_details', $reservation, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

/**
 * Hook for the online_restaurant_reservation_email_customer_details.
 *
 * @hooked ORR_Emails::customer_details() Shows customer details.
 * @hooked ORR_Emails::email_address() Shows email address.
 */
do_action( 'online_restaurant_reservation_email_customer_details', $reservation, $sent_to_admin, $plain_text, $email );

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'online_restaurant_reservation_email_footer_text', get_option( 'online_restaurant_reservation_email_footer_text' ) );
