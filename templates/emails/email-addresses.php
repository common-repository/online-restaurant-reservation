<?php
/**
 * Email Addresses
 *
 * This template can be overridden by copying it to yourtheme/restaurant-reservation/emails/email-addresses.php.
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

$text_align = is_rtl() ? 'right' : 'left';

?><table id="addresses" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top; margin-bottom: 40px; padding:0;" border="0">
	<tr>
		<td style="text-align:<?php echo $text_align; ?>; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; border:0; padding:0;" valign="top" width="50%">
			<h2><?php _e( 'Contact details', 'online-restaurant-reservation' ); ?></h2>

			<address class="address">
				<?php echo ( $address = $reservation->get_formatted_customer_full_name() ) ? $address : __( 'N/A', 'online-restaurant-reservation' ); ?>
				<?php if ( $reservation->get_reservation_phone() ) : ?>
					<br/><?php echo esc_html( $reservation->get_reservation_phone() ); ?>
				<?php endif; ?>
				<?php if ( $reservation->get_reservation_email() ) : ?>
					<p><?php echo esc_html( $reservation->get_reservation_email() ); ?></p>
				<?php endif; ?>
			</address>
		</td>
	</tr>
</table>
