<?php
/**
 * Customer Cancelled Reservation Email
 *
 * An email sent to the customer when a reservation is cancelled.
 *
 * @class    ORR_Email_Customer_Cancelled_Reservation
 * @extends  ORR_Email
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Classes/Emails
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ORR_Email_Customer_Cancelled_Reservation', false ) ) :

/**
 * ORR_Email_Customer_Cancelled_Reservation Class.
 */
class ORR_Email_Customer_Cancelled_Reservation extends ORR_Email {

	/**
	 * Is party size limit?
	 *
	 * @var bool
	 */
	public $party_size_limit;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id             = 'customer_cancelled_reservation';
		$this->customer_email = true;
		$this->title          = __( 'Cancelled reservation', 'online-restaurant-reservation' );
		$this->description    = __( 'This is an reservation notification sent to customers containing details after reservation.', 'online-restaurant-reservation' );
		$this->template_html  = 'emails/customer-cancelled-reservation.php';
		$this->template_plain = 'emails/plain/customer-cancelled-reservation.php';
		$this->placeholders   = array(
			'{site_title}'         => $this->get_blogname(),
			'{reservation_date}'   => '',
			'{reservation_number}' => '',
		);

		// Triggers for this email.
		add_action( 'online_restaurant_reservation_status_pending_to_cancelled_notification', array( $this, 'trigger_pending' ), 10, 2 );
		add_action( 'online_restaurant_reservation_status_confirmed_to_cancelled_notification', array( $this, 'trigger_confirmed' ), 10, 2 );

		// Call parent constructor.
		parent::__construct();
	}

	/**
	 * Get email subject.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( '[{site_title}] Cancelled reservation ({reservation_number})', 'online-restaurant-reservation' );
	}

	/**
	 * Get email heading.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Cancelled reservation', 'online-restaurant-reservation' );
	}

	/**
	 * Pending to cancelled notification.
	 *
	 * @param int $reservation_id The reservation ID.
	 * @param ORR_Reservation $reservation Reservation object.
	 */
	public function trigger_pending( $reservation_id, $reservation = false ) {
		$this->trigger( $reservation_id, $reservation, true );
	}

	/**
	 * Confirmed to cancelled notification.
	 *
	 * @param int $reservation_id The reservation ID.
	 * @param ORR_Reservation $reservation Reservation object.
	 */
	public function trigger_confirmed( $reservation_id, $reservation = false ) {
		$this->trigger( $reservation_id, $reservation, false );
	}

	/**
	 * Trigger the sending of this email.
	 *
	 * @param int $reservation_id The reservation ID.
	 * @param ORR_Reservation $reservation Reservation object.
	 * @param bool $party_size_limit Is party size limit?
	 */
	public function trigger( $reservation_id, $reservation = false, $party_size_limit = false ) {
		$this->setup_locale();
		$this->party_size_limit = $party_size_limit;
		$this->id               = $this->party_size_limit ? 'customer_limit_cancelled_reservation' : 'customer_cancelled_reservation';

		if ( $reservation_id && ! is_a( $reservation, 'ORR_Reservation' ) ) {
			$reservation = orr_get_reservation( $reservation_id );
		}

		if ( is_a( $reservation, 'ORR_Reservation' ) ) {
			$this->object                               = $reservation;
			$this->recipient                            = $this->object->get_reservation_email();
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
			'reservation'      => $this->object,
			'party_size_limit' => $this->party_size_limit,
			'email_heading'    => $this->get_heading(),
			'sent_to_admin'    => false,
			'plain_text'       => false,
			'email'            => $this,
		) );
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		return orr_get_template_html( $this->template_plain, array(
			'reservation'      => $this->object,
			'party_size_limit' => $this->party_size_limit,
			'email_heading'    => $this->get_heading(),
			'sent_to_admin'    => false,
			'plain_text'       => true,
			'email'			   => $this,
		) );
	}
}

endif;

return new ORR_Email_Customer_Cancelled_Reservation();
