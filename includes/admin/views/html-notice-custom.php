<?php
/**
 * Admin View: Custom Notices
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated online-restaurant-reservation-message">
	<a class="online-restaurant-reservation-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'orr-hide-notice', $notice ), 'online_restaurant_reservation_hide_notices_nonce', '_orr_notice_nonce' ) ); ?>"><?php _e( 'Dismiss', 'online-restaurant-reservation' ); ?></a>
	<?php echo wp_kses_post( wpautop( $notice_html ) ); ?>
</div>

