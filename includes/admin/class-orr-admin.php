<?php
/**
 * Online Restaurant Reservation Admin.
 *
 * @class    ORR_Admin
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Admin
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Admin Class
 */
class ORR_Admin {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'admin_init', array( $this, 'buffer' ), 1 );
		add_action( 'admin_init', array( $this, 'preview_emails' ) );
		add_action( 'admin_footer', 'orr_print_js', 25 );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
	}

	/**
	 * Output buffering allows admin screens to make redirects later on.
	 */
	public function buffer() {
		ob_start();
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once( dirname( __FILE__ ) . '/orr-admin-functions.php' );
		include_once( dirname( __FILE__ ) . '/orr-meta-box-functions.php' );
		include_once( dirname( __FILE__ ) . '/class-orr-admin-post-types.php' );
		include_once( dirname( __FILE__ ) . '/class-orr-admin-menus.php' );
		include_once( dirname( __FILE__ ) . '/class-orr-admin-notices.php' );
		include_once( dirname( __FILE__ ) . '/class-orr-admin-assets.php' );
	}

	/**
	 * Preview email template.
	 */
	public function preview_emails() {
		if ( isset( $_GET['preview_online_restaurant_reservation_mail'] ) ) {
			if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'preview-mail' ) ) {
				die( 'Security check' );
			}

			// Load the mailer class.
			$mailer = ORR()->mailer();

			// Get the preview email subject.
			$email_heading = __( 'HTML email template', 'online-restaurant-reservation' );

			// Get the preview email content.
			ob_start();
			include( 'views/html-email-template-preview.php' );
			$message       = ob_get_clean();

			// Create a new email.
			$email         = new ORR_Email();

			// Wrap the content with the email template and then add styles.
			$message       = apply_filters( 'online_restaurant_reservation_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $message ) ) );

			// Print the preview email.
			echo $message;
			exit;
		}
	}

	/**
	 * Change the admin footer text on Online Restaurant Reservation admin pages.
	 *
	 * @param  string $footer_text
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		if ( ! current_user_can( 'manage_reservation' ) ) {
			return;
		}
		$current_screen = get_current_screen();
		$orr_pages      = orr_get_screen_ids();

		// Check to make sure we're on a Online Restaurant Reservation admin page
		if ( isset( $current_screen->id ) && apply_filters( 'online_restaurant_reservation_display_admin_footer_text', in_array( $current_screen->id, $orr_pages ) ) ) {
			// Change the footer text
			if ( ! get_option( 'online_restaurant_reservation_admin_footer_text_rated' ) ) {
				$footer_text = sprintf(
					/* translators: 1: Online Restaurant Reservation 2:: five stars */
					__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'online-restaurant-reservation' ),
					sprintf( '<strong>%s</strong>', esc_html__( 'Online Restaurant Reservation', 'online-restaurant-reservation' ) ),
					'<a href="https://wordpress.org/support/plugin/online-restaurant-reservation/reviews?rate=5#new-post" target="_blank" class="orr-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'online-restaurant-reservation' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
				);
				orr_enqueue_js( "
					jQuery( 'a.orr-rating-link' ).click( function() {
						jQuery.post( '" . ORR()->ajax_url() . "', { action: 'online_restaurant_reservation_rated' } );
						jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
					});
				" );
			} else {
				$footer_text = __( 'Thank you for creating with Online Restaurant Reservation.', 'online-restaurant-reservation' );
			}
		}

		return $footer_text;
	}
}

new ORR_Admin();
