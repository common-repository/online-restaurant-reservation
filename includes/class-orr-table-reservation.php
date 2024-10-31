<?php
/**
 * Main table reservation class.
 *
 * Handles the reservation process, collecting user data and processing the reservation.
 *
 * @class    ORR_Table_Reservation
 * @version  1.0.0
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Table_Reservation Class.
 */
class ORR_Table_Reservation {

	/**
	 * The single instance of the class.
	 *
	 * @var ORR_Table_Reservation|null
	 */
	protected static $instance = null;

	/**
	 * Reservation fields are stored here.
	 *
	 * @var array|null
	 */
	protected $fields = null;

	/**
	 * Gets the main ORR_Table_Reservation Instance.
	 *
	 * @static
	 * @since  1.0.0
	 * @return ORR_Table_Reservation Main instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			// Hook in actions once.
			add_action( 'online_restaurant_reservation_form_fields', array( self::$instance, 'reservation_form_details'	) );

			// online_restaurant_reservation_init action is ran once when the class is first constructed.
			do_action( 'online_restaurant_reservation_init', self::$instance );
		}

		return self::$instance;
	}

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		orr_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'online-restaurant-reservation' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		orr_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'online-restaurant-reservation' ), '1.0' );
	}

	/**
	 * Get an array of reservation fields.
	 *
	 * @param  string $fieldset to get.
	 *
	 * @return array
	 */
	public function get_reservation_fields( $fieldset = '' ) {
		if ( is_null( $this->fields ) ) {
			$this->fields = array(
				'details'     => array(
					'first_name'        => array(
						'label'        => __( 'First name', 'online-restaurant-reservation' ),
						'required'     => true,
						'class'        => array( 'form-row-first' ),
						'autocomplete' => 'given-name',
						'autofocus'    => true,
						'priority'     => 10,
					),
					'last_name'         => array(
						'label'        => __( 'Last name', 'online-restaurant-reservation' ),
						'required'     => true,
						'class'        => array( 'form-row-last' ),
						'autocomplete' => 'family-name',
						'priority'     => 20,
					),
					'reservation_date'  => array(
						'label'             => __( 'Date', 'online-restaurant-reservation' ),
						'placeholder'       => esc_attr_x( 'yyyy-mm-dd', 'Date format placeholder', 'online-restaurant-reservation' ),
						'required'          => true,
						'class'             => array( 'form-row-first' ),
						'input_class'       => array( 'date-picker-field' ),
						'validate'          => array( 'date' ),
						'priority'          => 30,
						'custom_attributes' => array( 'readonly' => 'readonly' )
					),
					'reservation_time'  => array(
						'label'    => __( 'Available time slot', 'online-restaurant-reservation' ),
						'required' => true,
						'class'    => array( 'form-row-last', 'orr-available-time' ),
						'type'     => 'select',
						'validate' => array( 'time' ),
						'options'  => array(
							'' => __( 'Please select date.', 'online-restaurant-reservation' ),
						),
						'priority' => 40,
					),
					'party_size' => array(
						'label'        => __( 'Party size', 'online-restaurant-reservation' ),
						'required'     => true,
						'type'         => 'number',
						'class'        => array( 'form-row-wide' ),
						'priority'     => 50,
						'default'      => get_option( 'online_restaurant_reservation_min_party_size', 1 ),
						'custom_attributes' => array(
							'step' 	=> 1,
							'min'	=> get_option( 'online_restaurant_reservation_min_party_size', 1 ),
							'max'   => get_option( 'online_restaurant_reservation_max_party_size', 100 ),
						),
					),
					'reservation_phone' => array(
						'label'        => __( 'Phone', 'online-restaurant-reservation' ),
						'required'     => true,
						'type'         => 'tel',
						'class'        => array( 'form-row-first' ),
						'validate'     => array( 'phone' ),
						'autocomplete' => 'tel',
						'priority'     => 50,
					),
					'reservation_email' => array(
						'label'        => __( 'Email address', 'online-restaurant-reservation' ),
						'required'     => true,
						'type'         => 'email',
						'class'        => array( 'form-row-last' ),
						'validate'     => array( 'email' ),
						'autocomplete' => 'email',
						'priority'     => 60,
					),
				),
				'reservation' => array(),
			);

			if ( apply_filters( 'online_restaurant_reservation_enable_additional_notes_field', 'yes' === get_option( 'online_restaurant_reservation_enable_comments', 'yes' ) ) ) {
				$this->fields['reservation']['reservation_comments'] = array(
					'type'        => 'textarea',
					'class'       => array( 'reservation-notes' ),
					'label'       => __( 'Reservation notes', 'online-restaurant-reservation' ),
					'placeholder' => esc_attr__( 'Notes about your table, e.g. special notes for reservation.', 'online-restaurant-reservation' ),
				);
			}

			$this->fields = apply_filters( 'online_restaurant_reservation_fields', $this->fields );
		}
		if ( $fieldset ) {
			return $this->fields[ $fieldset ];
		} else {
			return $this->fields;
		}
	}

	/**
	 * Output the details form.
	 */
	public function reservation_form_details() {
		orr_get_template( 'reservation/form-details.php', array( 'reservation' => $this ) );
	}

	/**
	 * Create an reservation.
	 *
	 * @param  $data Posted data.
	 *
	 * @return int|WP_ERROR
	 */
	public function create_reservation( $data ) {
		// Give plugins the opportunity to create an reservation themselves.
		if ( $reservation_id = apply_filters( 'orr_create_reservation', null, $data ) ) {
			return $reservation_id;
		}

		try {
			$reservation = new ORR_Reservation();

			// Action hook to adjust reservation before save.
			do_action( 'online_restaurant_reservation_create_table_reservation', $reservation, $data );

			// Save the reservation.
			$reservation_id = $reservation->save();

			foreach ( $data as $key => $value ) {
				if ( is_callable( array( $reservation, "set_{$key}" ) ) ) {
					$reservation->{"set_{$key}"}( $value );
				}
			}

			$reservation->set_created_via( 'reservation' );
			$reservation->set_customer_id( apply_filters( 'online_restaurant_reservation_customer_id', get_current_user_id() ) );
			$reservation->set_customer_ip_address( $reservation->get_ip_address() );
			$reservation->set_customer_user_agent( orr_get_user_agent() );
			$reservation->set_customer_note( isset( $data['reservation_comments'] ) ? $data['reservation_comments'] : '' );
			$reservation->set_date_reserved( gmdate( 'Y-m-d h:i:s a', strtotime( $data['reservation_date'] . ' ' . $data['reservation_time'] ) ) );

			do_action( 'online_restaurant_reservation_update_table_reservation_meta', $reservation_id, $data );

			return $reservation_id;
		}
		catch ( Exception $e ) {
			return new WP_Error( 'reservation-error', $e->getMessage() );
		}
	}

	/**
	 * Get posted data from the reservation form.
	 *
	 * @since  1.0.0
	 * @return array of data.
	 */
	public function get_posted_data() {
		$data = array();

		foreach ( $this->get_reservation_fields() as $fieldset_key => $fieldset ) {
			foreach ( $fieldset as $key => $field ) {
				$type = sanitize_title( isset( $field['type'] ) ? $field['type'] : 'text' );

				switch ( $type ) {
					case 'checkbox' :
						$value = isset( $_POST[ $key ] ) ? 1 : '';
						break;
					case 'multiselect' :
						$value = isset( $_POST[ $key ] ) ? implode( ', ', orr_clean( $_POST[ $key ] ) ) : '';
						break;
					case 'textarea' :
						$value = isset( $_POST[ $key ] ) ? orr_sanitize_textarea( $_POST[ $key ] ) : '';
						break;
					default :
						$value = isset( $_POST[ $key ] ) ? orr_clean( $_POST[ $key ] ) : '';
						break;
				}

				$data[ $key ] = apply_filters( 'online_restaurant_reservation_process_' . $type . '_field', apply_filters( 'online_restaurant_reservation_process_reservation_field_' . $key, $value ) );
			}
		}

		return apply_filters( 'online_restaurant_reservation_posted_data', $data );
	}

	/**
	 * Validates the posted reservation data based on field properties.
	 *
	 * @since 1.0.0
	 *
	 * @param array    $data An array of posted data.
	 * @param WP_Error $errors
	 */
	protected function validate_posted_data( &$data, &$errors ) {
		foreach ( $this->get_reservation_fields() as $fieldset_key => $fieldset ) {
			foreach ( $fieldset as $key => $field ) {
				if ( ! isset( $data[ $key ] ) ) {
					continue;
				}
				$required    = ! empty( $field['required'] );
				$format      = array_filter( isset( $field['validate'] ) ? (array) $field['validate'] : array() );
				$field_label = isset( $field['label'] ) ? $field['label'] : '';

				if ( in_array( 'phone', $format ) ) {
					$data[ $key ] = orr_format_phone_number( $data[ $key ] );

					if ( '' !== $data[ $key ] && ! ORR_Validation::is_phone( $data[ $key ] ) ) {
						/* translators: %s: phone number */
						$errors->add( 'validation', sprintf( __( '%s is not a valid phone number.', 'online-restaurant-reservation' ), '<strong>' . esc_html( $field_label ) . '</strong>' ) );
					}
				}

				if ( in_array( 'email', $format ) && '' !== $data[ $key ] ) {
					$data[ $key ] = sanitize_email( $data[ $key ] );

					if ( ! is_email( $data[ $key ] ) ) {
						/* translators: %s: email address */
						$errors->add( 'validation', sprintf( __( '%s is not a valid email address.', 'online-restaurant-reservation' ), '<strong>' . esc_html( $field_label ) . '</strong>' ) );
						continue;
					}
				}

				if ( $required && '' === $data[ $key ] ) {
					/* translators: %s: field name */
					$errors->add( 'required-field', apply_filters( 'online_restaurant_reservation_required_field_notice', sprintf( __( '%s is a required field.', 'online-restaurant-reservation' ), '<strong>' . esc_html( $field_label ) . '</strong>' ), $field_label ) );
				}
			}
		}
	}

	/**
	 * Validates that the reservation has enough info to proceed.
	 *
	 * @since  3.0.0
	 *
	 * @param  array    $data An array of posted data.
	 * @param  WP_Error $errors
	 */
	protected function validate_reservation( &$data, &$errors ) {
		$this->validate_posted_data( $data, $errors );

		do_action( 'online_restaurant_reservation_after_validation', $data, $errors );
	}

	/**
	 * Process the reservation after the confirm reservation button is pressed.
	 * @throws Exception If the reservation check fails.
	 */
	public function process_reservation() {
		try {
			if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'online-table-reservation-process_table_reservation' ) ) {
				throw new Exception( __( 'We were unable to process your reservation, please try again.', 'online-restaurant-reservation' ) );
			}

			orr_maybe_define_constant( 'ORR_RESERVATION', true );
			orr_set_time_limit( 0 );

			do_action( 'online_restaurant_reservation_process' );

			$errors      = new WP_Error();
			$posted_data = $this->get_posted_data();

			// Validate posted data before proceeding.
			$this->validate_reservation( $posted_data, $errors );

			foreach ( $errors->get_error_messages() as $message ) {
				orr_add_notice( $message, 'error' );
			}

			if ( ! empty( $posted_data ) && 0 === orr_notice_count( 'error' ) ) {
				$reservation_id = $this->create_reservation( $posted_data );
				$reservation    = orr_get_reservation( $reservation_id );

				if ( is_wp_error( $reservation_id ) ) {
					throw new Exception( $reservation_id->get_error_message() );
				}

				// Send the customer invoice email.
				ORR()->mailer()->customer_invoice( $reservation );

				do_action( 'online_restaurant_reservation_processed', $reservation_id, $posted_data, $reservation );

				orr_add_notice( __( 'Thank you. Your reservation has been received.', 'online-restaurant-reservation' ), 'success' );

				// Reset form :)
				unset( $_POST );
			}
		}
		catch ( Exception $e ) {
			orr_add_notice( $e->getMessage(), 'error' );
		}
	}

	/**
	 * Gets the value either from the posted data, or from the users meta data.
	 *
	 * @param string $input
	 *
	 * @return string
	 */
	public function get_value( $input ) {
		if ( ! empty( $_POST[ $input ] ) ) {
			return orr_clean( $_POST[ $input ] );

		} else {

			$value = apply_filters( 'online_restaurant_reservation_get_value', null, $input );

			if ( null !== $value ) {
				return $value;
			}

			return apply_filters( 'default_reservation_' . $input, $value, $input );
		}
	}
}
