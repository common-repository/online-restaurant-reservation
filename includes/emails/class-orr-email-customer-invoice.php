<?php
/**
 * Customer Invoice
 *
 * An email sent to the customer via admin.
 *
 * @class    ORR_Email_Customer_Invoice
 * @extends  ORR_Email
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Classes/Emails
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ORR_Email_Customer_Invoice', false ) ) :

/**
 * ORR_Email_Customer_Invoice Class.
 */
class ORR_Email_Customer_Invoice extends ORR_Email {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'customer_invoice';
		$this->customer_email = true;
		$this->title          = __( 'Customer invoice', 'online-restaurant-reservation' );
		$this->description    = __( 'Customer invoice emails can be sent to customers containing their reservation information.', 'online-restaurant-reservation' );
		$this->template_html  = 'emails/customer-invoice.php';
		$this->template_plain = 'emails/plain/customer-invoice.php';
		$this->placeholders   = array(
			'{site_title}'         => $this->get_blogname(),
			'{reservation_date}'   => '',
			'{reservation_number}' => '',
		);

		// Call parent constructor.
		parent::__construct();

		$this->manual         = true;
	}

	/**
	 * Get email subject.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_default_subject( $reserved = false ) {
		if ( $reserved ) {
			return __( 'Your {site_title} reservation from {reservation_date}', 'online-restaurant-reservation' );
		} else {
			return __( 'Invoice for reservation {reservation_number}', 'online-restaurant-reservation' );
		}
	}

	/**
	 * Get email heading.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_default_heading( $paid = false ) {
		if ( $paid ) {
			return __( 'Your reservation details', 'online-restaurant-reservation' );
		} else {
			return __( 'Invoice for reservation {reservation_number}', 'online-restaurant-reservation' );
		}
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		if ( $this->object->has_status( orr_get_is_reserved_statuses() ) ) {
			$subject = $this->get_option( 'subject_reserved', $this->get_default_subject( true ) );
			$action  = 'online_restaurant_reservation_email_subject_customer_invoice_reserved';
		} else {
			$subject = $this->get_option( 'subject', $this->get_default_subject() );
			$action  = 'online_restaurant_reservation_email_subject_customer_invoice';
		}
		return apply_filters( $action, $this->format_string( $subject ), $this->object );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_heading() {
		if ( $this->object->has_status( orr_get_is_reserved_statuses() ) ) {
			$heading = $this->get_option( 'heading_reserved', $this->get_default_heading( true ) );
			$action  = 'online_restaurant_reservation_email_heading_customer_invoice_reserved';
		} else {
			$heading = $this->get_option( 'heading', $this->get_default_heading() );
			$action  = 'online_restaurant_reservation_email_heading_customer_invoice';
		}
		return apply_filters( $action, $this->format_string( $heading ), $this->object );
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
			$this->recipient                            = $this->object->get_reservation_email();
			$this->placeholders['{reservation_date}']   = orr_format_datetime( $this->object->get_date_created() );
			$this->placeholders['{reservation_number}'] = $this->object->get_reservation_number();
		}

		if ( $this->get_recipient() ) {
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
			'sent_to_admin' => false,
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
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'			=> $this,
		) );
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
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
			'subject_reserved' => array(
				'title'         => __( 'Subject (reserved)', 'online-restaurant-reservation' ),
				'type'          => 'text',
				'desc_tip'      => true,
				/* translators: %s: list of placeholders */
				'description'   => sprintf( __( 'Available placeholders: %s', 'online-restaurant-reservation' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' ),
				'placeholder'   => $this->get_default_subject( true ),
				'default'       => '',
			),
			'heading_reserved' => array(
				'title'         => __( 'Email heading (reserved)', 'online-restaurant-reservation' ),
				'type'          => 'text',
				'desc_tip'      => true,
				/* translators: %s: list of placeholders */
				'description'   => sprintf( __( 'Available placeholders: %s', 'online-restaurant-reservation' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' ),
				'placeholder'   => $this->get_default_heading( true ),
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

return new ORR_Email_Customer_Invoice();
