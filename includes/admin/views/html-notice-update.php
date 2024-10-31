<?php
/**
 * Admin View: Notice - Update
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div id="message" class="updated online-restaurant-reservation-message orr-connect">
	<p><strong><?php _e( 'Online Restaurant Reservation data update', 'online-restaurant-reservation' ); ?></strong> &#8211; <?php _e( 'We need to update your site\'s database to the latest version.', 'online-restaurant-reservation' ); ?></p>
	<p class="submit"><a href="<?php echo esc_url( add_query_arg( 'do_update_online_restaurant_reservation', 'true', admin_url( 'admin.php?page=orr-settings' ) ) ); ?>" class="orr-update-now button-primary"><?php _e( 'Run the updater', 'online-restaurant-reservation' ); ?></a></p>
</div>
<script type="text/javascript">
	jQuery( '.orr-update-now' ).click( 'click', function() {
		return window.confirm( '<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'online-restaurant-reservation' ) ); ?>' ); // jshint ignore:line
	});
</script>
