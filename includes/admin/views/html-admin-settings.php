<?php
/**
 * Admin View: Settings
 *
 * @package Online_Restaurant_Reservation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tab_exists        = isset( $tabs[ $current_tab ] ) || has_action( 'online_restaurant_reservation_sections_' . $current_tab ) || has_action( 'online_restaurant_reservation_settings_' . $current_tab );
$current_tab_label = isset( $tabs[ $current_tab ] ) ? $tabs[ $current_tab ] : '';

if ( ! $tab_exists ) {
	wp_safe_redirect( admin_url( 'admin.php?page=orr-settings' ) );
	exit;
}

?>
<div class="wrap online-restaurant-reservation">
	<form method="<?php echo esc_attr( apply_filters( 'online_restaurant_reservation_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">
		<nav class="nav-tab-wrapper orr-nav-tab-wrapper">
			<?php
				foreach ( $tabs as $slug => $label ) {
					echo '<a href="' . esc_html( admin_url( 'admin.php?page=orr-settings&tab=' . esc_attr( $slug ) ) ) . '" class="nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '">' . esc_html( $label ) . '</a>';
				}

				do_action( 'online_restaurant_reservation_settings_tabs' );
			?>
		</nav>
		<h1 class="screen-reader-text"><?php echo esc_html( $current_tab_label  ); ?></h1>
		<?php
			do_action( 'online_restaurant_reservation_sections_' . $current_tab );

			self::show_messages();

			do_action( 'online_restaurant_reservation_settings_' . $current_tab );
		?>
		<p class="submit">
			<?php if ( ! isset( $GLOBALS['hide_save_button'] ) ) : ?>
				<button name="save" class="button-primary online-restaurant-reservation-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'online-restaurant-reservation' ); ?>"><?php esc_html_e( 'Save changes', 'online-restaurant-reservation' ); ?></button>
			<?php endif; ?>
			<input type="hidden" name="subtab" id="last_tab" />
			<?php wp_nonce_field( 'online-restaurant-reservation-settings' ); ?>
		</p>
	</form>
</div>
