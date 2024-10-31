<?php
/**
 * Online Restaurant Reservation Conditional Functions
 *
 * Functions for determining the current query/page.
 *
 * @author   WPEverest
 * @category Core
 * @package  Online_Restaurant_Reservation/Functions
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'is_reservation_page' ) ) {

	/**
	 * Is_reservation_page - Returns true when viewing the reservation page.
	 * @return bool
	 */
	function is_reservation_page() {
		return orr_post_content_has_shortcode( 'online_restaurant_reservation' ) || apply_filters( 'orr_is_reservation_page', false );
	}
}


if ( ! function_exists( 'is_ajax' ) ) {

	/**
	 * is_ajax - Returns true when the page is loaded via ajax.
	 * @return bool
	 */
	function is_ajax() {
		return defined( 'DOING_AJAX' );
	}
}

/**
 * Checks whether the content passed contains a specific short code.
 *
 * @param  string $tag Shortcode tag to check.
 * @return bool
 */
function orr_post_content_has_shortcode( $tag = '' ) {
	global $post;

	return is_singular() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
}

if ( ! function_exists( 'orr_mailer_enabled' ) ) {

	/**
	 * Is mailer enabled?
	 * @return bool
	 */
	function orr_mailer_enabled() {
		return apply_filters( 'orr_mailer_enabled', ORR()->mailer()->emails ? true : false );
	}
}
