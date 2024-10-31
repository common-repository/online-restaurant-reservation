<?php
/**
 * Admin View: Notice - Updating
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated online-restaurant-reservation-message orr-connect">
	<p><strong><?php _e( 'Online Restaurant Reservation data update', 'online-restaurant-reservation' ); ?></strong> &#8211; <?php _e( 'Your database is being updated in the background.', 'online-restaurant-reservation' ); ?> <a href="<?php echo esc_url( add_query_arg( 'force_update_online_restaurant_reservation', 'true', admin_url( 'admin.php?page=orr-settings' ) ) ); ?>"><?php _e( 'Taking a while? Click here to run it now.', 'online-restaurant-reservation' ); ?></a></p>
</div>
