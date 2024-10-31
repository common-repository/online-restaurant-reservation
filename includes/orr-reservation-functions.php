<?php
/**
 * Online Restaurant Reservation Functions
 *
 * Functions for reservation specific things.
 *
 * @author   WPEverest
 * @category Core
 * @package  Online_Restaurant_Reservation/Functions
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main function for returning reservations, uses the ORR_Reservation_Factory class.
 *
 * @param  mixed $the_reservation Post object or post ID of the reservation.
 * @return bool|ORR_Reservation
 */
function orr_get_reservation( $the_reservation = false ) {
	if ( ! did_action( 'online_restaurant_reservation_after_register_post_type' ) ) {
		orr_doing_it_wrong( __FUNCTION__, 'orr_get_reservation should not be called before post types are registered (online_restaurant_reservation_after_register_post_type action)', '1.0' );
		return false;
	}

	return ORR()->reservation_factory->get_reservation( $the_reservation );
}

/**
 * Search reservations.
 *
 * @param  string $term Term to search.
 * @return array List of reservations ID.
 */
function orr_reservation_search( $term ) {
	global $wpdb;

	$term            = str_replace( 'Reservation #', '', orr_clean( $term ) );
	$reservation_ids = array();

	// Search fields.
	$search_fields = array_map( 'orr_clean', apply_filters( 'online_restaurant_reservation_search_fields', array(
		'_last_name',
		'_first_name',
		'_reservation_email',
	) ) );

	if ( is_numeric( $term ) ) {
		$reservation_ids[] = absint( $term );
	}

	if ( ! empty( $search_fields ) ) {
		$reservation_ids = array_unique( array_merge(
			$reservation_ids,
			$wpdb->get_col(
				$wpdb->prepare( "SELECT DISTINCT p1.post_id FROM {$wpdb->postmeta} p1 WHERE p1.meta_value LIKE '%%%s%%'", $wpdb->esc_like( orr_clean( $term ) ) ) . " AND p1.meta_key IN ('" . implode( "','", array_map( 'esc_sql', $search_fields ) ) . "')"
			)
		) );
	}

	return apply_filters( 'online_restaurant_reservation_search_results', $reservation_ids, $term, $search_fields );
}

/**
 * Return the count of pending reservations.
 *
 * @return int
 */
function orr_pending_reservation_count() {
	return orr_reservations_count( 'pending' );
}

/**
 * Return the reservations count of a specific reservation status.
 *
 * @param  string $status
 * @return int
 */
function orr_reservations_count( $status ) {
	global $wpdb;

	$count                = 0;
	$status               = 'orr-' . $status;
	$reservation_statuses = array_keys( orr_get_reservation_statuses() );

	if ( ! in_array( $status, $reservation_statuses ) ) {
		return 0;
	}

	return absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT( * ) FROM {$wpdb->posts} WHERE post_type = 'table_reservation' AND post_status = %s", $status ) ) );
}

/**
 * Get all reservation statuses.
 *
 * @return array
 */
function orr_get_reservation_statuses() {
	$reservation_statuses = array(
		'orr-pending'   => _x( 'Pending reservation', 'Reservation status', 'online-restaurant-reservation' ),
		'orr-confirmed' => _x( 'Confirmed', 'Reservation status', 'online-restaurant-reservation' ),
		'orr-check-in'  => _x( 'Check in', 'Reservation status', 'online-restaurant-reservation' ),
		'orr-cancelled' => _x( 'Cancelled', 'Reservation status', 'online-restaurant-reservation' ),
	);

	return apply_filters( 'orr_reservation_statuses', $reservation_statuses );
}

/**
 * Get list of statuses which are consider 'reserved'.
 *
 * @return array
 */
function orr_get_is_reserved_statuses() {
	return apply_filters( 'online_table_reservation_is_reserved_statuses', array( 'confirmed', 'check-in' ) );
}

/**
 * See if a string is an reservation status.
 *
 * @param  string $maybe_status Status, including any orr- prefix
 * @return bool
 */
function orr_is_reservation_status( $maybe_status ) {
	$reservation_statuses = orr_get_reservation_statuses();

	return isset( $reservation_statuses[ $maybe_status ] );
}

/**
 * Get the nice name for an reservation status.
 *
 * @param  string $status Reservation status.
 * @return string
 */
function orr_get_reservation_status_name( $status ) {
	$statuses = orr_get_reservation_statuses();
	$status   = 'orr-' === substr( $status, 0, 4 ) ? substr( $status, 4 ) : $status;
	$status   = isset( $statuses[ 'orr-' . $status ] ) ? $statuses[ 'orr-' . $status ] : $status;

	return 'trash' !== $status ? $status : __( 'Trash', 'online-restaurant-reservation' );
}

/**
 * Get list of times range.
 *
 * @param  string $format Time format.
 * @return array
 */
function orr_get_time_range( $range = array(), $format = 'g:i A' ) {
	$times = array();
	$range = wp_parse_args( $range, array(
		'start_time' => 0,
		'end_time'   => 86400,
	) );

	$time_range = (int) get_option( 'online_restaurant_reservation_time_range_steps', 30 ) * MINUTE_IN_SECONDS;

	foreach ( range( $range['start_time'], $range['end_time'], $time_range ) as $timestamp ) {
		$hour_mins = gmdate( 'H:i:s', $timestamp );
		if ( ! empty( $format ) ) {
			$times[ $hour_mins ] = gmdate( $format, $timestamp );
		} else {
			$times[ $hour_mins ] = $hour_mins;
		}
	}

	return $times;
}

/**
 * Get second of time.
 *
 * @param  string $time
 *
 * @return int
 */
function orr_time_into_second( $time ) {
	$parsed  = date_parse( $time );
	$seconds = $parsed['hour'] * 3600 + $parsed['minute'] * 60 + $parsed['second'];

	return $seconds;
}

/**
 * Get list of closed weekday index.
 *
 * @param  string $format Time format.
 * @return array
 */
function orr_get_closed_day_index() {
	return array_keys( wp_list_filter( get_option( 'online_table_reservation_schedule', array() ), array( 'closed' => 'yes' ), 'AND' ) );
}

/**
 * Get list of opened weekday index.
 *
 * @param  string $format Time format.
 *
 * @return array
 */
function orr_get_open_day() {
	$options = get_option( 'online_table_reservation_schedule', array() );
	// If there is no option - set default option.
	if ( count( $options ) < 1 ) {
		$reservation_schedule = array();
		for ( $day_index = 0; $day_index <= 6; $day_index ++ ) {
			// Default value.
			if ( ! isset( $reservation_schedule[ $day_index ] ) ) {
				$reservation_schedule[ $day_index ] = array(
					'closed'   => 'no',
					'schedule' => array(
						'start_time' => array(
							'00:00:00'
						),
						'end_time'   => array(
							'23:30:00'
						)
					),
				);
			}
		}

		return $reservation_schedule;
	}

	return ( wp_list_filter( $options, array( 'closed' => 'no' ), 'AND' ) );
}

/**
 * Get list of closed exceptional weekday array.
 *
 * @param  string $format Time format.
 *
 * @return array
 */
function orr_get_exceptional_closed_date_ranges() {
	return ORR_Reservation_Exceptions::get_closed_exception_date();
}

/**
 * Get list of opened exceptional date array.
 *
 * @return array
 */
function orr_get_exceptional_opened_date_ranges() {
	return ORR_Reservation_Exceptions::get_opened_exception_date();
}
