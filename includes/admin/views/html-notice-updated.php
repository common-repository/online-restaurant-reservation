<?php
/**
 * Admin View: Notice - Updated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated online-restaurant-reservation-message orr-connect online-restaurant-reservation-message--success">
	<a class="online-restaurant-reservation-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'orr-hide-notice', 'update', remove_query_arg( 'do_update_online_restaurant_reservation' ) ), 'online_restaurant_reservation_hide_notices_nonce', '_orr_notice_nonce' ) ); ?>"><?php _e( 'Dismiss', 'online-restaurant-reservation' ); ?></a>

	<p><?php _e( 'Online Restaurant Reservation data update complete. Thank you for updating to the latest version!', 'online-restaurant-reservation' ); ?></p>
</div>
