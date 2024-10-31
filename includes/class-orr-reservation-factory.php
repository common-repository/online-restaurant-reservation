<?php
/**
 * Reservation Factory Class
 *
 * @class    ORR_Reservation_Factory
 * @version  1.0.0
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Reservation_Factory Class.
 */
class ORR_Reservation_Factory {

	/**
	 * Get reservation.
	 *
	 * @param mixed $reservation_id (default: false) Reservation ID to get.
	 * @return ORR_Reservation|bool
	 */
	public static function get_reservation( $reservation_id = false ) {
		$reservation_id = self::get_reservation_id( $reservation_id );

		if ( ! $reservation_id ) {
			return false;
		}

		try {
			return new ORR_Reservation( $reservation_id );
		} catch ( Exception $e ) {
			orr_caught_exception( $e, __FUNCTION__, func_get_args() );
			return false;
		}
	}

	/**
	 * Get the reservation ID depending on what was passed.
	 *
	 * @param  mixed $reservation Reservation data to convert to an ID.
	 * @return int|bool false on failure
	 */
	public static function get_reservation_id( $reservation ) {
		global $post;

		if ( false === $reservation && is_a( $post, 'WP_Post' ) && 'table_reservation' === get_post_type( $post ) ) {
			return $post->ID;
		} elseif ( is_numeric( $reservation ) ) {
			return $reservation;
		} elseif ( $reservation instanceof ORR_Reservation ) {
			return $reservation->get_id();
		} elseif ( ! empty( $reservation->ID ) ) {
			return $reservation->ID;
		} else {
			return false;
		}
	}
}
