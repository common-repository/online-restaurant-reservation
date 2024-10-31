<?php
/**
 * Online Restaurant Reservation Shortcodes
 *
 * @class    ORR_Shortcodes
 * @version  1.0.0
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Shortcodes Class.
 */
class ORR_Shortcodes {

	/**
	 * Init Shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
			'online_restaurant_reservation' => __CLASS__ . '::reservation',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @param string[] $function Callback function.
	 * @param array    $atts     Attributes. Default to empty array.
	 * @param array    $wrapper  Customer wrapper data.
	 *
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class'  => 'online-restaurant-reservation',
			'before' => null,
			'after'  => null,
		)
	) {
		ob_start();

		// @codingStandardsIgnoreStart
		echo empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
		call_user_func( $function, $atts );
		echo empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];
		// @codingStandardsIgnoreEnd

		return ob_get_clean();
	}

	/**
	 * Reservation page shortcode.
	 *
	 * @param  array $atts Attributes.
	 * @return string
	 */
	public static function reservation( $atts ) {
		return self::shortcode_wrapper( array( __CLASS__, 'output' ), $atts );
	}

	/**
	 * Output the shortcode.
	 *
	 * @param array $atts
	 */
	public static function output( $atts ) {

		orr_print_notices();

		// Get reservation object.
		$reservation = ORR()->table_reservation();

		orr_get_template( 'reservation/form-reservation.php', array(
			'reservation'             => $reservation,
			'reservation_button_text' => apply_filters( 'online_restaurant_reservation_button_text', __( 'Place reservation', 'online-restaurant-reservation' ) ),
		) );
	}
}
