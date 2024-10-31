<?php
/**
 * Reservation details table shown in emails
 *
 * This template can be overridden by copying it to yourtheme/restaurant-reservation/emails/email-reservation-details.php.
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

do_action( 'online_restaurant_reservation_email_before_reservation_table', $reservation, $sent_to_admin, $plain_text, $email ); ?>

<?php if ( ! $sent_to_admin ) : ?>
	<h2><?php printf( __( 'Reservation #%s', 'online-restaurant-reservation' ), $reservation->get_reservation_number() ); ?> (<?php printf( '<time datetime="%s">%s</time>', $reservation->get_date_created()->format( 'c' ), orr_format_datetime( $reservation->get_date_created() ) ); ?>)</h2>
<?php else : ?>
	<h2><a class="link" href="<?php echo esc_url( admin_url( 'post.php?post=' . $reservation->get_id() . '&action=edit' ) ); ?>"><?php printf( __( 'Reservation #%s', 'online-restaurant-reservation' ), $reservation->get_reservation_number() ); ?></a> (<?php printf( '<time datetime="%s">%s</time>', $reservation->get_date_created()->format( 'c' ), orr_format_datetime( $reservation->get_date_created() ) ); ?>)</h2>
<?php endif; ?>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
		<thead>
			<tr>
				<th class="td" scope="col" style="text-align:<?php echo $text_align; ?>;"><?php _e( 'Reservation Date', 'online-restaurant-reservation' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo $text_align; ?>;"><?php _e( 'Reservation Time', 'online-restaurant-reservation' ); ?></th>
				<th class="td" scope="col" style="text-align:<?php echo $text_align; ?>;"><?php _e( 'Party size', 'online-restaurant-reservation' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="td" style="text-align:<?php echo $text_align; ?>; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"><?php echo orr_format_datetime( $reservation->get_date_reserved(), get_option( 'date_format' ) ); ?></td>
				<td class="td" style="text-align:<?php echo $text_align; ?>; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"><?php echo orr_format_datetime( $reservation->get_date_reserved(), get_option( 'time_format' ) ); ?></td>
				<td class="td" style="text-align:<?php echo $text_align; ?>; vertical-align:middle; border: 1px solid #eee; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;"><?php echo $reservation->get_party_size(); ?></td>
			</tr>
		</tbody>
		<tfoot>
			<?php
				if ( $reservation->get_customer_note() ) {
					?><tr>
						<th class="td" scope="row" style="text-align:<?php echo $text_align; ?>;"><?php _e( 'Note:', 'online-restaurant-reservation' ); ?></th>
						<td class="td" colspan="2" style="text-align:<?php echo $text_align; ?>;"><?php echo wptexturize( $reservation->get_customer_note() ); ?></td>
					</tr><?php
				}
			?>
		</tfoot>
	</table>
</div>

<?php do_action( 'online_restaurant_reservation_email_after_reservation_table', $reservation, $sent_to_admin, $plain_text, $email ); ?>
