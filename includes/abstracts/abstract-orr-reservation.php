<?php
/**
 * Abstract Reservation Class
 *
 * The online restaurant reservation handles individual reservation data.
 *
 * @class    ORR_Reservation
 * @version  1.0.0
 * @category Abstract Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Reservation Class.
 */
class ORR_Reservation {

	/**
	 * ID for this object.
	 *
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Set ID.
	 *
	 * @since 1.0.0
	 * @param int $id
	 */
	public function set_id( $id ) {
		$this->id = absint( $id );
	}

	/**
	 * Returns the unique ID for this object.
	 *
	 * @since  1.0.0
	 * @return int
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @var bool|array
	 */
	protected $status_transition = false;

	/**
	 * Get the reservation if ID is passed, otherwise the reservation is new and empty.
	 * This class should NOT be instantiated, but the orr_get_reservation() function
	 * should be used. It is possible, but the orr_get_reservation() is preferred.
	 *
	 * @param int|ORR_Reservation|object $reservation Reservation to init.
	 */
	public function __construct( $reservation = 0 ) {
		if ( is_numeric( $reservation ) && $reservation > 0 ) {
			$this->set_id( $reservation );
		} elseif ( $reservation instanceof self ) {
			$this->set_id( absint( $reservation->get_id() ) );
		} elseif ( ! empty( $reservation->ID ) ) {
			$this->set_id( absint( $reservation->ID ) );
		}
	}

	/**
	 * Get internal type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'table_reservation';
	}

	/**
	 * Gets a prop for a getter method.
	 *
	 * Gets the value from either current pending changes, or the data itself.
	 * Context controls what happens to the value before it's returned.
	 *
	 * @access private
	 * @since  1.0.0
	 *
	 * @param  string $prop Name of prop to get.
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return mixed
	 */
	protected function get_prop( $prop, $context = 'view' ) {
		$post_status = get_post_status( $this->get_id() );

		if ( 'customer_note' == $prop ) {
			return has_excerpt( $this->get_id() ) ? get_the_excerpt( $this->get_id() ) : '';
		} elseif ( 'status' == $prop && $post_status ) {
			return in_array( $post_status, $this->get_valid_statuses() ) ? substr( $post_status, 4 ) : $post_status;
		}

		return get_post_meta( $this->get_id(), '_' . $prop, true );
	}

	/**
	 * Sets a prop for a setter method.
	 *
	 * This stores changes in a special array so we can track what needs saving
	 * the the DB later.
	 *
	 * @access private
	 * @since 1.0.0
	 *
	 * @param string $prop Name of prop to set.
	 * @param mixed  $value Value of the prop.
	 */
	protected function set_prop( $prop, $value ) {
		if ( 'customer_note' == $prop ) {
			return wp_update_post( array( 'ID' => $this->get_id(), 'post_excerpt' => $value ) );
		}

		return update_post_meta( $this->get_id(), '_' . $prop, $value );
	}

	/**
	 * Set a collection of props in one go, collect any errors, and return the result.
	 * Only sets using public methods.
	 *
	 * @access private
	 * @since  1.0.0
	 *
	 * @param  array $props Key value pairs to set. Key is the prop and should map to a setter function name.
	 * @param  string $context
	 *
	 * @return bool|WP_Error
	 */
	public function set_props( $props, $context = 'set' ) {
		$errors = new WP_Error();

		foreach ( $props as $prop => $value ) {
			try {
				if ( 'meta_data' === $prop ) {
					continue;
				}
				$setter = "set_$prop";
				if ( ! is_null( $value ) && is_callable( array( $this, $setter ) ) ) {
					$reflection = new ReflectionMethod( $this, $setter );

					if ( $reflection->isPublic() ) {
						$this->{$setter}( $value );
					}
				}
			} catch ( ORR_Data_Exception $e ) {
				$errors->add( $e->getErrorCode(), $e->getMessage() );
			}
		}

		return sizeof( $errors->get_error_codes() ) ? $errors : true;
	}

	/**
	 * Sets a date prop whilst handling formatting and datetime objects.
	 *
	 * @access private
	 * @since 1.0.0
	 *
	 * @param string $prop Name of prop to set.
	 * @param string|integer $value Value of the prop.
	 */
	protected function set_date_prop( $prop, $value ) {
		try {
			if ( empty( $value ) ) {
				$this->set_prop( $prop, null );
				return;
			}

			if ( is_a( $value, 'ORR_DateTime' ) ) {
				$datetime = $value;
			} elseif ( is_numeric( $value ) ) {
				// Timestamps are handled as UTC timestamps in all cases.
				$datetime = new ORR_DateTime( "@{$value}", new DateTimeZone( 'UTC' ) );
			} else {
				// Strings are defined in local WP timezone. Convert to UTC.
				if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $value, $date_bits ) ) {
					$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : orr_timezone_offset();
					$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
				} else {
					$timestamp = orr_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', orr_string_to_timestamp( $value ) ) ) );
				}
				$datetime  = new ORR_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );
			}

			// Set local timezone or offset.
			if ( get_option( 'timezone_string' ) ) {
				$datetime->setTimezone( new DateTimeZone( orr_timezone_string() ) );
			} else {
				$datetime->set_utc_offset( orr_timezone_offset() );
			}

			$this->set_prop( $prop, $datetime );
		} catch ( Exception $e ) {}
	}

	/**
	 * When invalid data is found, throw an exception unless reading from the DB.
	 *
	 * @throws ORR_Data_Exception
	 *
	 * @since 1.0.0
	 * @param string $code             Error code.
	 * @param string $message          Error message.
	 * @param int    $http_status_code HTTP status code.
	 * @param array  $data             Extra error data.
	 */
	protected function error( $code, $message, $http_status_code = 400, $data = array() ) {
		throw new ORR_Data_Exception( $code, $message, $http_status_code, $data );
	}

	/*
	|------------------------------------------------------------------------------
	| CRUD methods
	|------------------------------------------------------------------------------
	|
	| Methods which create, read, update and delete reservations from the database.
	| Written in abstract fashion so that the way reservations are stored can be
	| changed more easily in the future.
	|
	| A save method is included for convenience (chooses update or create based
	| on if the reservation exists yet).
	|
	*/

	/**
	 * Method to create a new reservation in the database.
	 *
	 * @param ORR_Reservation $reservation
	 */
	public function create( &$reservation ) {
		$id = wp_insert_post( apply_filters( 'online_table_reservation_new_data', array(
			'post_type'     => $reservation->get_type(),
			'post_status'   => 'orr-' . ( $reservation->get_status() ? $reservation->get_status() : apply_filters( 'online_table_reservation_default_status', 'pending' ) ),
			'ping_status'   => 'closed',
			'post_author'   => 1,
			'post_title'    => $this->get_post_title(),
			'post_excerpt'  => $this->get_post_excerpt( $reservation ),
		) ), true );

		if ( $id && ! is_wp_error( $id ) ) {
			$reservation->set_id( $id );
			$reservation->set_date_created( current_time( 'timestamp', true ) );
			clean_post_cache( $reservation->get_id() );
		}
	}

	/**
	 * Method to update an reservation in the database.
	 *
	 * @param ORR_Reservation $reservation
	 */
	public function update( &$reservation ) {
		$post_data = array(
			'post_status'  => 'orr-' . ( $reservation->get_status() ? $reservation->get_status() : apply_filters( 'online_table_reservation_default_status', 'pending' ) ),
			'post_excerpt' => $this->get_post_excerpt( $reservation ),
		);

		/**
		 * When updating this object, to prevent infinite loops, use $wpdb
		 * to update data, since wp_update_post spawns more calls to the
		 * save_post action.
		 *
		 * This ensures hooks are fired by either WP itself (admin screen save),
		 * or an update purely from CRUD.
		 */
		if ( doing_action( 'save_post' ) ) {
			$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $reservation->get_id() ) );
			clean_post_cache( $reservation->get_id() );
		} else {
			wp_update_post( array_merge( array( 'ID' => $reservation->get_id() ), $post_data ) );
		}

		// Clear the cache.
		clean_post_cache( $reservation->get_id() );
	}

	/**
	 * Save data to the database.
	 *
	 * @since  1.0.0
	 * @return int reservation ID
	 */
	public function save() {
		$this->maybe_set_user_reservation_email();
		if ( $this->get_id() ) {
			$this->update( $this );
		} else {
			$this->create( $this );
		}
		$this->status_transition();
		return $this->get_id();
	}

	/**
	 * Set reservation status.
	 *
	 * @since  1.0.0
	 * @param  string $new_status Status to change the reservation to. No internal orr- prefix is required.
	 * @param  string $note (default: '') Optional note to add.
	 * @param  bool   $manual_update is this a manual reservation status change?
	 * @return array details of change
	 */
	public function set_status( $new_status, $note = '', $manual_update = false ) {
		$old_status = $this->get_status();
		$new_status = 'orr-' === substr( $new_status, 0, 4 ) ? substr( $new_status, 4 ) : $new_status;

		// If setting the status, ensure it's set to a valid status.
		if ( isset( $new_status ) || isset( $old_status ) ) {
			// Only allow valid new status
			if ( ! in_array( 'orr-' . $new_status, $this->get_valid_statuses() ) && 'trash' !== $new_status ) {
				$new_status = 'pending';
			}

			// If the old status is set but unknown (e.g. draft) assume its pending for action usage.
			if ( $old_status && ! in_array( 'orr-' . $old_status, $this->get_valid_statuses() ) && 'trash' !== $old_status ) {
				$old_status = 'pending';
			}
		}

		$post_data = array(
			'post_status' => 'orr-' . $new_status,
		);

		$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $this->get_id() ) );
		clean_post_cache( $this->get_id() );

		$result = array(
			'from' => $old_status,
			'to'   => $new_status,
		);

		// Update status.
		$this->set_prop( 'status', $new_status );

		// Update status transition.
		if ( ! empty( $result['from'] ) && $result['from'] !== $result['to'] ) {
			$this->status_transition = array(
				'from'   => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $result['from'],
				'to'     => $result['to'],
				'note'   => $note,
				'manual' => (bool) $manual_update,
			);

			$this->maybe_set_date_completed();
		}

		return $result;
	}

	/**
	 * Maybe set date completed.
	 *
	 * Sets the date completed variable when transitioning to check-in status.
	 *
	 * @since 1.0.0
	 */
	protected function maybe_set_date_completed() {
		if ( $this->has_status( 'check-in' ) ) {
			$this->set_date_completed( current_time( 'timestamp', true ) );
		}
	}

	/**
	 * Updates status of Reservation immediately. Reservation must exist.
	 *
	 * @uses ORR_Reservation::set_status()
	 *
	 * @param  string $new_status
	 * @param  string $note
	 * @param  bool   $manual
	 * @return bool success
	 */
	public function update_status( $new_status, $note = '', $manual = false ) {
		try {
			if ( ! $this->get_id() ) {
				return false;
			}
			$this->set_status( $new_status, $note, $manual );
			$this->save();
		} catch ( Exception $e ) {
			return false;
		}
		return true;
	}

	/**
	 * Handle the status transition.
	 */
	protected function status_transition() {
		$status_transition = $this->status_transition;

		// Reset status transition variable.
		$this->status_transition = false;

		if ( $status_transition ) {
			do_action( 'online_restaurant_reservation_status_' . $status_transition['to'], $this->get_id(), $this );

			if ( ! empty( $status_transition['from'] ) ) {
				do_action( 'online_restaurant_reservation_status_' . $status_transition['from'] . '_to_' . $status_transition['to'], $this->get_id(), $this );
				do_action( 'online_restaurant_reservation_status_changed', $this->get_id(), $status_transition['from'], $status_transition['to'], $this );
			}
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Additional Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Excerpt for post.
	 *
	 * @param  ORR_Reservation $reservation
	 * @return string
	 */
	protected function get_post_excerpt( $reservation ) {
		return $reservation->get_customer_note();
	}

	/**
	 * Get a title for the new post type.
	 *
	 * @return string
	 */
	protected function get_post_title() {
		// @codingStandardsIgnoreStart
		/* translators: %s: Reservation date */
		return sprintf( __( 'Reservation &ndash; %s', 'online-restaurant-reservation', 'online-restaurant-reservation' ), strftime( _x( '%b %d, %Y @ %I:%M %p', 'Reservation date parsed by strftime', 'online-restaurant-reservation', 'online-restaurant-reservation' ) ) );
		// @codingStandardsIgnoreEnd
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Gets the reservation number for display (by default, reservation ID).
	 *
	 * @return string
	 */
	public function get_reservation_number() {
		return (string) apply_filters( 'online_table_reservation_number', $this->get_id(), $this );
	}

	/**
	 * Get customer_id.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_customer_id( $context = 'view' ) {
		return $this->get_prop( 'customer_user', $context );
	}

	/**
	 * Alias for get_customer_id().
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_user_id( $context = 'view' ) {
		return $this->get_customer_id( $context );
	}

	/**
	 * Get the user associated with the reservation. False for guests.
	 *
	 * @return WP_User|false
	 */
	public function get_user() {
		return $this->get_user_id() ? get_user_by( 'id', $this->get_user_id() ) : false;
	}

	/**
	 * Get current user IP Address.
	 *
	 * @return string
	 */
	public function get_ip_address() {
		if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			return $_SERVER['HTTP_X_REAL_IP'];
		} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			// Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
			// Make sure we always only send through the first IP in the list which should always be the client IP.
			return (string) self::is_ip_address( trim( current( explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) );
		} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			return $_SERVER['REMOTE_ADDR'];
		}
		return '';
	}

	/**
	 * Get date_created.
	 *
	 * @param  string $context
	 * @return ORR_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get date_reserved.
	 *
	 * @param  string $context
	 * @return ORR_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_reserved( $context = 'view' ) {
		return $this->get_prop( 'date_reserved', $context );
	}

	/**
	 * Get date_completed.
	 *
	 * @param  string $context
	 * @return ORR_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_completed( $context = 'view' ) {
		return $this->get_prop( 'date_completed', $context );
	}

	/**
	 * Return the reservation statuses without orr- internal prefix.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		$status = $this->get_prop( 'status', $context );

		if ( empty( $status ) && 'view' === $context ) {
			// In view context, return the default status if no status has been set.
			$status = apply_filters( 'online_table_reservation_default_status', 'pending' );
		}
		return $status;
	}

	/**
	 * Get first_name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_first_name( $context = 'view' ) {
		return $this->get_prop( 'first_name', $context );
	}

	/**
	 * Get last_name.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_last_name( $context = 'view' ) {
		return $this->get_prop( 'last_name', $context );
	}

	/**
	 * Get party_size.
	 *
	 * @param  string $context
	 * @return int
	 */
	public function get_party_size( $context = 'view' ) {
		return $this->get_prop( 'party_size', $context );
	}

	/**
	 * Get reservation_email.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_reservation_email( $context = 'view' ) {
		return $this->get_prop( 'reservation_email', $context );
	}

	/**
	 * Get reservation_phone.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_reservation_phone( $context = 'view' ) {
		return $this->get_prop( 'reservation_phone', $context );
	}

	/**
	 * Get customer_note.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_customer_note( $context = 'view' ) {
		return $this->get_prop( 'customer_note', $context );
	}

	/**
	 * Get customer_ip_address.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_customer_ip_address( $context = 'view' ) {
		return $this->get_prop( 'customer_ip_address', $context );
	}

	/**
	 * Get customer_user_agent.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_customer_user_agent( $context = 'view' ) {
		return $this->get_prop( 'customer_user_agent', $context );
	}

	/**
	 * Get created_via.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_created_via( $context = 'view' ) {
		return $this->get_prop( 'created_via', $context );
	}

	/**
	 * Get a formatted customer full name.
	 *
	 * @return string
	 */
	public function get_formatted_customer_full_name() {
		if ( $this->get_first_name() || $this->get_last_name() ) {
			/* translators: 1: first name 2: last name */
			return trim( sprintf( _x( '%1$s %2$s', 'full name', 'online-restaurant-reservation', 'online-restaurant-reservation' ), $this->get_first_name(), $this->get_last_name() ) );
		}

		return false;
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get all valid statuses for this reservations.
	 *
	 * @since  1.0.0
	 * @return array Internal status keys e.g. 'orr-pending'
	 */
	protected function get_valid_statuses() {
		return array_keys( orr_get_reservation_statuses() );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set customer_id.
	 *
	 * @param int $value
	 * @throws ORR_Data_Exception
	 */
	public function set_customer_id( $value ) {
		$this->set_prop( 'customer_user', absint( $value ) );
	}

	/**
	 * Set date_created.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 * @throws ORR_Data_Exception
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set date_reserved.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_reserved( $date = null ) {
		$this->set_date_prop( 'date_reserved', $date );
	}

	/**
	 * Set date_completed.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 * @throws ORR_Data_Exception
	 */
	public function set_date_completed( $date = null ) {
		$this->set_date_prop( 'date_completed', $date );
	}

	/**
	 * Set first_name.
	 *
	 * @param  string $value
	 * @throws ORR_Data_Exception
	 */
	public function set_first_name( $value ) {
		$this->set_prop( 'first_name', $value );
	}

	/**
	 * Set last_name.
	 *
	 * @param  string $value
	 * @throws ORR_Data_Exception
	 */
	public function set_last_name( $value ) {
		$this->set_prop( 'last_name', $value );
	}

	/**
	 * Set party_size.
	 *
	 * @param  string $value
	 * @return ORR_Data_Exception
	 */
	public function set_party_size( $value ) {
		$this->set_prop( 'party_size', $value );
	}

	/**
	 * Maybe set empty email to that of the user who owns the reservation.
	 */
	protected function maybe_set_user_reservation_email() {
		if ( ! $this->get_reservation_email() && ( $user = $this->get_user() ) ) {
			try {
				$this->set_reservation_email( $user->user_email );
			} catch( ORR_Data_Exception $e ) {
				unset( $e );
			}
		}
	}

	/**
	 * Set reservation_email.
	 *
	 * @param  string $value
	 * @throws ORR_Data_Exception
	 */
	public function set_reservation_email( $value ) {
		if ( $value && ! is_email( $value ) ) {
			$this->error( 'invalid_reservation_email', __( 'Invalid reservation email address', 'online-restaurant-reservation', 'online-restaurant-reservation' ) );
		}
		$this->set_prop( 'reservation_email', sanitize_email( $value ) );
	}

	/**
	 * Set reservation_phone.
	 *
	 * @param  string $value
	 * @throws ORR_Data_Exception
	 */
	public function set_reservation_phone( $value ) {
		$this->set_prop( 'reservation_phone', $value );
	}

	/**
	 * Set customer_note.
	 *
	 * @param string $value
	 * @throws ORR_Data_Exception
	 */
	public function set_customer_note( $value ) {
		$this->set_prop( 'customer_note', $value );
	}

	/**
	 * Set customer_ip_address.
	 *
	 * @param string $value
	 * @throws ORR_Data_Exception
	 */
	public function set_customer_ip_address( $value ) {
		$this->set_prop( 'customer_ip_address', $value );
	}

	/**
	 * Set customer_user_agent.
	 *
	 * @param string $value
	 * @throws ORR_Data_Exception
	 */
	public function set_customer_user_agent( $value ) {
		$this->set_prop( 'customer_user_agent', $value );
	}

	/**
	 * Set created_via.
	 *
	 * @param string $value
	 * @throws ORR_Data_Exception
	 */
	public function set_created_via( $value ) {
		$this->set_prop( 'created_via', $value );
	}

	/*
	|--------------------------------------------------------------------------
	| Conditionals
	|--------------------------------------------------------------------------
	|
	| Checks if a condition is true or false.
	|
	*/

	/**
	 * Checks the reservation status against a passed in status.
	 *
	 * @param array|string $status
	 *
	 * @return bool
	 */
	public function has_status( $status ) {
		return apply_filters( 'online_table_reservation_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status ) ) || $this->get_status() === $status ? true : false, $this, $status );
	}
}
