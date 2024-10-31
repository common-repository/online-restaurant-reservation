<?php
/**
 * Handles storage and retrival of reservation exceptions.
 *
 * @class    ORR_Reservation_Exceptions
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Classes
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Reservation_Exceptions Class.
 */
class ORR_Reservation_Exceptions {

	/**
	 * Get reservation exceptions date from the database.
	 *
	 * @return array of arrays
	 */
	public static function get_closed_exception_date() {

		global $wpdb;

		$exceptions = $wpdb->get_results( "SELECT start_date, end_date FROM {$wpdb->prefix}orr_exceptions where end_date >=date(now()) and is_closed = 1", ARRAY_A );

		return $exceptions;
	}

	/**
	 * Get reservation exceptions date from the database.
	 *
	 * @return array of arrays
	 */
	public static function get_opened_exception_date() {

		global $wpdb;

		$exceptions = $wpdb->get_results( "SELECT start_date, end_date FROM {$wpdb->prefix}orr_exceptions where end_date >=date(now()) and is_closed = 0", ARRAY_A );

		return $exceptions;
	}

	/**
	 * Get reservation exceptions from the database.
	 *
	 * @return array of arrays
	 */
	public static function get_exceptions() {
		global $wpdb;

		$raw_exceptions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}orr_exceptions order by exception_order ASC, exception_id ASC;" );
		$exceptions     = array();

		foreach ( $raw_exceptions as $key => $raw_exception ) {
			$exceptions[ $key ]                           = $raw_exception;
			$exceptions[ $key ]->exception_order          = absint( $raw_exception->exception_order );
			$exceptions[ $key ]->is_closed                = orr_bool_to_string( $raw_exception->is_closed );
			$exceptions[ $key ]->formatted_exception_name = self::get_formatted_name( $raw_exception->exception_name );
		}

		return $exceptions;
	}

	/**
	 * Get array of reservation closed days.
	 *
	 * @since  1.0.0
	 * @return array of strings
	 */
	public static function get_closed_days() {
		return wp_list_filter( self::get_exceptions(), array( 'is_closed' => 'yes' ) );
	}

	/**
	 * Get array of reservation exceptions name.
	 *
	 * @since  1.0.0
	 * @return array of strings
	 */
	public static function get_exception_names() {
		return wp_list_pluck( self::get_exceptions(), 'exception_name' );
	}

	/**
	 * Format reservation exception name.
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	private static function get_formatted_name( $name ) {
		return $name ? $name : __( '(No exception name)', 'online-restaurant-reservation' );
	}

	/**
	 * Prepare and format exception data for DB insertion.
	 *
	 * @param  array $exception_data
	 *
	 * @return array
	 */
	private static function prepare_exception_data( $exception_data ) {
		foreach ( $exception_data as $key => $value ) {
			if ( method_exists( __CLASS__, 'format_' . $key ) ) {
				$exception_data[ $key ] = call_user_func( array( __CLASS__, 'format_' . $key ), $value );
			}
		}

		return $exception_data;
	}

	/**
	 * @param $exception_id
	 *
	 * @return array|null|object
	 */
	public static function get_start_date( $exception_id ) {
		$exception_id = absint( $exception_id );
		global $wpdb;

		$start_date = $wpdb->get_results( "SELECT start_date FROM {$wpdb->prefix}orr_exceptions where exception_id = {$exception_id}", ARRAY_A );

		return isset( $start_date[0] ) ? $start_date[0] : array();
	}

	/**
	 * Format the reservation exception name.
	 *
	 * @param  string $name
	 *
	 * @return string
	 */
	private static function format_exception_name( $name ) {
		return orr_clean( $name );
	}

	/**
	 * Format the reservation exception closed status.
	 *
	 * @param  string $status
	 *
	 * @return bool
	 */
	private static function format_is_closed( $status ) {
		return orr_string_to_bool( $status );
	}


	/**
	 * Insert a new reservation exception.
	 *
	 * Internal use only.
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @param  array $exception_data
	 *
	 * @return int   Reservation exception ID.
	 */
	public static function _insert_exception( $exception_data ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'orr_exceptions', self::prepare_exception_data( $exception_data ) );

		return $wpdb->insert_id;
	}

	/**
	 * Update a reservation exception.
	 *
	 * Internal use only.
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @param int   $exception_id
	 * @param array $exception_data
	 */
	public static function _update_exception( $exception_id, $exception_data ) {
		global $wpdb;

		$exception_id = absint( $exception_id );

		$wpdb->update(
			$wpdb->prefix . "orr_exceptions",
			self::prepare_exception_data( $exception_data ),
			array(
				'exception_id' => $exception_id
			)
		);
	}

	/**
	 * Delete a reservation exception from the database.
	 *
	 * Internal use only.
	 *
	 * @since  1.0.0
	 * @access private
	 *
	 * @param int $exception_id
	 */
	public static function _delete_exception( $exception_id ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}orr_exceptions WHERE exception_id = %d;", $exception_id ) );
	}

	/**
	 * Get reservation time slot for certain date.
	 *
	 * @return array of arrays
	 */

	public static function get_reservation_open_time_slot( $date, $is_merge = true ) {
		global $wpdb;

		$time_slot  = array();
		$time_range = array();

		if ( $is_merge ) {
			$query = "SELECT start_time, end_time FROM {$wpdb->prefix}orr_exceptions WHERE '{$date}' BETWEEN start_date and end_date and is_closed = 0 order by exception_order";
		} else {
			$query = "SELECT start_time, end_time FROM {$wpdb->prefix}orr_exceptions WHERE '{$date}' BETWEEN start_date and end_date and is_closed = 0 order by exception_order desc limit 1";
		}

		$open_exceptions = $wpdb->get_results( $query, ARRAY_A );

		if ( count( $open_exceptions ) > 0 ) {
			foreach ( $open_exceptions as $time_key => $time_value ) {

				$time = array(
					'start_time' => $time_value['start_time'],
					'end_time'   => $time_value['end_time']
				);
				array_push( $time_range, $time );
			}
		} else {
			$open_day_index = orr_get_open_day();
			$dayofweek = date( 'w', strtotime( $date ) );

			if ( isset( $open_day_index[ $dayofweek ] ) ) {
				$schedule = isset( $open_day_index[ $dayofweek ]['schedule'] ) ? $open_day_index[ $dayofweek ]['schedule'] : array();

				if ( isset( $schedule['start_time'] ) ) {
					foreach ( $schedule['start_time'] as $time_key => $start_time ) {
						$time = array(
							'start_time' => $start_time,
							'end_time'   => $schedule['end_time'][ $time_key ]
						);

						array_push( $time_range, $time );
					}
				}
			}
		}

		foreach ( $time_range as $range ) {
			$start_time = orr_time_into_second( $range['start_time'] );
			$end_time   = orr_time_into_second( $range['end_time'] );

			$args            = array(
				'start_time' => $start_time,
				'end_time'   => $end_time,
			);
			$time_range_data = orr_get_time_range( $args );
			$time_slot       = array_merge( $time_slot, $time_range_data );

		}

		ksort( $time_slot );

		return $time_slot;
	}
}
