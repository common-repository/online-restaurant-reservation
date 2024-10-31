<?php
/**
 * New Reservation Email
 *
 * An email sent to the admin when a new reservation is received/booked for.
 *
 * @class    ORR_Email_New_Reservation
 * @extends  ORR_Email
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Classes/Emails
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ORR_Email_New_Reservation', false ) ) :

/**
 * ORR_Email_New_Reservation Class.
 */
class ORR_Email_New_Reservation extends ORR_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'new_reservation';
		$this->title          = __( 'New reservation', 'online-restaurant-reservation' );
		$this->description    = __( 'New reservation emails are sent to chosen recipient(s) when a new reservation is received.', 'online-restaurant-reservation' );
		$this->template_html  = 'emails/admin-new-reservation.php';
		$this->template_plain = 'emails/plain/admin-new-reservation.php';
		$this->placeholders   = array(
			'{site_title}'         => $this->get_blogname(),
			'{reservation_date}'   => '',
			'{reservation_number}' => '',
		);

		// Triggers for this email.
		add_action( 'online_restaurant_reservation_processed', array( $this, 'trigger' ), 10, 2 );

		// Call parent constructor.
		parent::__construct();

		// Other settings.
		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * Get email subject.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}] New customer reservation ({reservation_number}) - {reservation_date}', 'online-restaurant-reservation' );
	}

	/**
	 * Get email heading.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'New customer reservation', 'online-restaurant-reservation' );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param int $reservation_id The reservation ID.
	 * @param ORR_Reservation $reservation Reservation object.
	 */
	public function trigger( $reservation_id, $reservation = false ) {
		$this->setup_locale();

		if ( $reservation_id && ! is_a( $reservation, 'ORR_Reservation' ) ) {
			$reservation = orr_get_reservation( $reservation_id );
		}

		if ( is_a( $reservation, 'ORR_Reservation' ) ) {
			$this->object                               = $reservation;
			$this->placeholders['{reservation_date}']   = orr_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{reservation_number}'] = $this->object->get_reservation_number();
		}

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		}

		$this->restore_locale();
	}

	/**
	 * Get content html.
	 *
	 * @return string
	 */
	public function get_content_html() {
		return orr_get_template_html( $this->template_html, array(
			'reservation'   => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => true,
			'plain_text'    => false,
			'email'			=> $this,
		) );
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return orr_get_template_html( $this->template_plain, array(
			'reservation'   => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => true,
			'plain_text'    => true,
			'email'			=> $this,
		) );
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'         => __( 'Enable/Disable', 'online-restaurant-reservation' ),
				'type'          => 'checkbox',
				'label'         => __( 'Enable this email notification', 'online-restaurant-reservation' ),
				'default'       => 'yes',
			),
			'recipient' => array(
				'title'         => __( 'Recipient(s)', 'online-restaurant-reservation' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'online-restaurant-reservation' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
				'placeholder'   => '',
				'default'       => '',
				'desc_tip'      => true,
			),
			'subject' => array(
				'title'         => __( 'Subject', 'online-restaurant-reservation' ),
				'type'          => 'text',
				'desc_tip'      => true,
				/* translators: %s: list of placeholders */
				'description'   => sprintf( __( 'Available placeholders: %s', 'online-restaurant-reservation' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' ),
				'placeholder'   => $this->get_default_subject(),
				'default'       => '',
			),
			'heading' => array(
				'title'         => __( 'Email heading', 'online-restaurant-reservation' ),
				'type'          => 'text',
				'desc_tip'      => true,
				/* translators: %s: list of placeholders */
				'description'   => sprintf( __( 'Available placeholders: %s', 'online-restaurant-reservation' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' ),
				'placeholder'   => $this->get_default_heading(),
				'default'       => '',
			),
			'email_type' => array(
				'title'         => __( 'Email type', 'online-restaurant-reservation' ),
				'type'          => 'select',
				'description'   => __( 'Choose which format of email to send.', 'online-restaurant-reservation' ),
				'default'       => 'html',
				'class'         => 'email_type orr-enhanced-select',
				'options'       => $this->get_email_type_options(),
				'desc_tip'      => true,
			),
		);
	}
}

endif;

return new ORR_Email_New_Reservation();
