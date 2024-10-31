<?php
/**
 * Admin new reservation email
 *
 * This template can be overridden by copying it to yourtheme/restaurant-reservation/emails/admin-new-reservation.php.
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

/**
 * Executes the e-mail header.
 *
 * @hooked ORR_Emails::email_header() Output the email header
 */
do_action( 'online_restaurant_reservation_email_header', $email_heading, $email ); ?>

<p><?php printf( __( 'You have received a reservation from %s. The reservation is as follows:', 'online-restaurant-reservation' ), $reservation->get_formatted_customer_full_name() ); ?></p>

<?php

/**
 * Hook for the online_restaurant_reservation_email_reservation_details.
 *
 * @hooked ORR_Emails::reservation_details() Shows the reservation details table.
 */
do_action( 'online_restaurant_reservation_email_reservation_details', $reservation, $sent_to_admin, $plain_text, $email );

/**
 * Hook for the online_restaurant_reservation_email_customer_details.
 *
 * @hooked ORR_Emails::customer_details() Shows customer details.
 * @hooked ORR_Emails::email_address() Shows email address.
 */
do_action( 'online_restaurant_reservation_email_customer_details', $reservation, $sent_to_admin, $plain_text, $email );

/**
 * Executes the email footer.
 *
 * @hooked ORR_Emails::email_footer() Output the email footer
 */
do_action( 'online_restaurant_reservation_email_footer', $email );
