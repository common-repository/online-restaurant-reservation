<?php
/**
 * Online Restaurant Reservation Message Functions
 *
 * Functions for error/message handling and display.
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
 * Get the count of notices added, either for all notices (default) or for one.
 * particular notice type specified by $notice_type.
 *
 * @since  1.0.0
 * @param  string $notice_type The name of the notice type - either error, success or notice. [optional]
 * @return int
 */
function orr_notice_count( $notice_type = '' ) {
	if ( ! did_action( 'online_restaurant_reservation_init' ) ) {
		orr_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before online_restaurant_reservation_init.', 'online-restaurant-reservation' ), '1.5' );
		return;
	}

	$notice_count = 0;
	$all_notices  = ORR()->session->get( 'orr_notices', array() );

	if ( isset( $all_notices[ $notice_type ] ) ) {

		$notice_count = absint( sizeof( $all_notices[ $notice_type ] ) );

	} elseif ( empty( $notice_type ) ) {

		foreach ( $all_notices as $notices ) {
			$notice_count += absint( sizeof( $all_notices ) );
		}
	}

	return $notice_count;
}

/**
 * Check if a notice has already been added.
 *
 * @since  1.0.0
 * @param  string $message The text to display in the notice.
 * @param  string $notice_type The singular name of the notice type - either error, success or notice. [optional]
 * @return bool
 */
function orr_has_notice( $message, $notice_type = 'success' ) {
	if ( ! did_action( 'online_restaurant_reservation_init' ) ) {
		orr_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before online_restaurant_reservation_init.', 'online-restaurant-reservation' ), '1.5' );
		return;
	}

	$notices = ORR()->session->get( 'orr_notices', array() );
	$notices = isset( $notices[ $notice_type ] ) ? $notices[ $notice_type ] : array();
	return array_search( $message, $notices ) !== false;
}

/**
 * Add and store a notice.
 *
 * @since 1.0.0
 * @param string $message The text to display in the notice.
 * @param string $notice_type The singular name of the notice type - either error, success or notice. [optional]
 */
function orr_add_notice( $message, $notice_type = 'success' ) {
	if ( ! did_action( 'online_restaurant_reservation_init' ) ) {
		orr_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before online_restaurant_reservation_init.', 'online-restaurant-reservation' ), '1.5' );
		return;
	}

	$notices = ORR()->session->get( 'orr_notices', array() );

	// Backward compatibility
	if ( 'success' === $notice_type ) {
		$message = apply_filters( 'online_restaurant_reservation_add_message', $message );
	}

	$notices[ $notice_type ][] = apply_filters( 'online_restaurant_reservation_add_' . $notice_type, $message );

	ORR()->session->set( 'orr_notices', $notices );
}

/**
 * Set all notices at once.
 *
 * @since 1.0.0
 * @param mixed $notices
 */
function orr_set_notices( $notices ) {
	if ( ! did_action( 'online_restaurant_reservation_init' ) ) {
		orr_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before online_restaurant_reservation_init.', 'online-restaurant-reservation' ), '1.5' );
		return;
	}
	ORR()->session->set( 'orr_notices', $notices );
}

/**
 * Unset all notices.
 *
 * @since 1.0.0
 */
function orr_clear_notices() {
	if ( ! did_action( 'online_restaurant_reservation_init' ) ) {
		orr_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before online_restaurant_reservation_init.', 'online-restaurant-reservation' ), '1.5' );
		return;
	}
	ORR()->session->set( 'orr_notices', null );
}

/**
 * Prints messages and errors which are stored in the session, then clears them.
 *
 * @since 1.0.0
 */
function orr_print_notices() {
	if ( ! did_action( 'online_restaurant_reservation_init' ) ) {
		orr_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before online_restaurant_reservation_init.', 'online-restaurant-reservation' ), '1.5' );
		return;
	}

	$all_notices  = ORR()->session->get( 'orr_notices', array() );
	$notice_types = apply_filters( 'online_restaurant_reservation_notice_types', array( 'error', 'success', 'notice' ) );

	foreach ( $notice_types as $notice_type ) {
		if ( orr_notice_count( $notice_type ) > 0 ) {
			orr_get_template( "notices/{$notice_type}.php", array(
				'messages' => array_filter( $all_notices[ $notice_type ] ),
			) );
		}
	}

	orr_clear_notices();
}

/**
 * Print a single notice immediately.
 *
 * @since 1.5.0
 * @param string $message The text to display in the notice.
 * @param string $notice_type The singular name of the notice type - either error, success or notice. [optional]
 */
function orr_print_notice( $message, $notice_type = 'success' ) {
	if ( 'success' === $notice_type ) {
		$message = apply_filters( 'online_restaurant_reservation_add_message', $message );
	}

	orr_get_template( "notices/{$notice_type}.php", array(
		'messages' => array( apply_filters( 'online_restaurant_reservation_add_' . $notice_type, $message ) ),
	) );
}

/**
 * Returns all queued notices, optionally filtered by a notice type.
 *
 * @since  1.5.0
 * @param  string $notice_type The singular name of the notice type - either error, success or notice. [optional]
 * @return array|mixed
 */
function orr_get_notices( $notice_type = '' ) {
	if ( ! did_action( 'online_restaurant_reservation_init' ) ) {
		orr_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before online_restaurant_reservation_init.', 'online-restaurant-reservation' ), '1.5' );
		return;
	}

	$all_notices = ORR()->session->get( 'orr_notices', array() );

	if ( empty( $notice_type ) ) {
		$notices = $all_notices;
	} elseif ( isset( $all_notices[ $notice_type ] ) ) {
		$notices = $all_notices[ $notice_type ];
	} else {
		$notices = array();
	}

	return $notices;
}

/**
 * Add notices for WP Errors.
 *
 * @param WP_Error $errors
 */
function orr_add_wp_error_notices( $errors ) {
	if ( is_wp_error( $errors ) && $errors->get_error_messages() ) {
		foreach ( $errors->get_error_messages() as $error ) {
			orr_add_notice( $error, 'error' );
		}
	}
}
