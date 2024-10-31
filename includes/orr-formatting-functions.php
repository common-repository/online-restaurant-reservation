<?php
/**
 * Online Restaurant Reservation Formatting
 *
 * Functions for formatting data.
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
 * Converts a string (e.g. 'yes' or 'no') to a bool.
 *
 * @since  1.0.0
 * @param  string $string String to convert.
 * @return bool
 */
function orr_string_to_bool( $string ) {
	return is_bool( $string ) ? $string : ( 'yes' === $string || 1 === $string || 'true' === $string || '1' === $string );
}

/**
 * Converts a bool to a 'yes' or 'no'.
 *
 * @since  1.0.0
 * @param  bool $bool String to convert.
 * @return string
 */
function orr_bool_to_string( $bool ) {
	if ( ! is_bool( $bool ) ) {
		$bool = orr_string_to_bool( $bool );
	}
	return true === $bool ? 'yes' : 'no';
}

/**
 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
 * Non-scalar values are ignored.
 *
 * @param  string|array $var Data to sanitize.
 * @return string|array
 */
function orr_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'orr_clean', $var );
	} else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}

/**
 * Run orr_clean over posted textarea but maintain line breaks.
 *
 * @since  1.0.0
 * @param  string $var Data to sanitize.
 * @return string
 */
function orr_sanitize_textarea( $var ) {
	return implode( "\n", array_map( 'orr_clean', explode( "\n", $var ) ) );
}

/**
 * Sanitize a string destined to be a tooltip.
 *
 * @since  1.0.0 Tooltips are encoded with htmlspecialchars to prevent XSS. Should not be used in conjunction with esc_attr()
 * @param  string $var Data to sanitize.
 * @return string
 */
function orr_sanitize_tooltip( $var ) {
	return htmlspecialchars( wp_kses( html_entity_decode( $var ), array(
		'br'     => array(),
		'em'     => array(),
		'strong' => array(),
		'small'  => array(),
		'span'   => array(),
		'ul'     => array(),
		'li'     => array(),
		'ol'     => array(),
		'p'      => array(),
	) ) );
}

/**
 * Online Restaurant Reservation Date Format - Allows to change date format for everything.
 *
 * @return string
 */
function orr_date_format() {
	return apply_filters( 'online_restaurant_reservation_date_format', get_option( 'date_format' ) );
}

/**
 * Online Restaurant Reservation Time Format - Allows to change time format for everything.
 *
 * @return string
 */
function orr_time_format() {
	return apply_filters( 'online_restaurant_reservation_time_format', get_option( 'time_format' ) );
}

/**
 * Convert mysql datetime to PHP timestamp, forcing UTC. Wrapper for strtotime.
 *
 * Based on wcs_strtotime_dark_knight() from WC Subscriptions by Prospress.
 *
 * @since 1.0.0
 *
 * @param string $time_string
 * @param int|null $from_timestamp
 *
 * @return int
 */
function orr_string_to_timestamp( $time_string, $from_timestamp = null ) {
	$original_timezone = date_default_timezone_get();

	// @codingStandardsIgnoreStart
	date_default_timezone_set( 'UTC' );

	if ( null === $from_timestamp ) {
		$next_timestamp = strtotime( $time_string );
	} else {
		$next_timestamp = strtotime( $time_string, $from_timestamp );
	}

	date_default_timezone_set( $original_timezone );
	// @codingStandardsIgnoreEnd

	return $next_timestamp;
}

/**
 * Convert a date string to a ORR_DateTime.
 *
 * @since  1.0.0
 * @param  string $time_string
 * @return ORR_DateTime
 */
function orr_string_to_datetime( $time_string ) {
	// Strings are defined in local WP timezone. Convert to UTC.
	if ( 1 === preg_match( '/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|((-|\+)\d{2}:\d{2}))$/', $time_string, $date_bits ) ) {
		$offset    = ! empty( $date_bits[7] ) ? iso8601_timezone_to_offset( $date_bits[7] ) : orr_timezone_offset();
		$timestamp = gmmktime( $date_bits[4], $date_bits[5], $date_bits[6], $date_bits[2], $date_bits[3], $date_bits[1] ) - $offset;
	} else {
		$timestamp = orr_string_to_timestamp( get_gmt_from_date( gmdate( 'Y-m-d H:i:s', orr_string_to_timestamp( $time_string ) ) ) );
	}
	$datetime  = new ORR_DateTime( "@{$timestamp}", new DateTimeZone( 'UTC' ) );

	// Set local timezone or offset.
	if ( get_option( 'timezone_string' ) ) {
		$datetime->setTimezone( new DateTimeZone( orr_timezone_string() ) );
	} else {
		$datetime->set_utc_offset( orr_timezone_offset() );
	}

	return $datetime;
}

/**
 * Online Restaurant Reservation Timezone - helper to retrieve the timezone string for a site until.
 * a WP core method exists (see https://core.trac.wordpress.org/ticket/24730).
 *
 * Adapted from https://secure.php.net/manual/en/function.timezone-name-from-abbr.php#89155.
 *
 * @since  1.0.0
 * @return string PHP timezone string for the site
 */
function orr_timezone_string() {

	// if site timezone string exists, return it
	if ( $timezone = get_option( 'timezone_string' ) ) {
		return $timezone;
	}

	// get UTC offset, if it isn't set then return UTC
	if ( 0 === ( $utc_offset = intval( get_option( 'gmt_offset', 0 ) ) ) ) {
		return 'UTC';
	}

	// adjust UTC offset from hours to seconds
	$utc_offset *= 3600;

	// attempt to guess the timezone string from the UTC offset
	if ( $timezone = timezone_name_from_abbr( '', $utc_offset ) ) {
		return $timezone;
	}

	// last try, guess timezone string manually
	foreach ( timezone_abbreviations_list() as $abbr ) {
		foreach ( $abbr as $city ) {
			if ( (bool) date( 'I' ) === (bool) $city['dst'] && $city['timezone_id'] && intval( $city['offset'] ) === $utc_offset ) {
				return $city['timezone_id'];
			}
		}
	}

	// fallback to UTC
	return 'UTC';
}

/**
 * Get timezone offset in seconds.
 *
 * @since  1.5.0
 * @return float
 */
function orr_timezone_offset() {
	if ( $timezone = get_option( 'timezone_string' ) ) {
		$timezone_object = new DateTimeZone( $timezone );
		return $timezone_object->getOffset( new DateTime( 'now' ) );
	} else {
		return floatval( get_option( 'gmt_offset', 0 ) ) * HOUR_IN_SECONDS;
	}
}

if ( ! function_exists( 'orr_rgb_from_hex' ) ) {

	/**
	 * Convert RGB to HEX.
	 *
	 * @param  mixed $color Color.
	 * @return array
	 */
	function orr_rgb_from_hex( $color ) {
		$color = str_replace( '#', '', $color );
		// Convert shorthand colors to full format, e.g. "FFF" -> "FFFFFF".
		$color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color );

		$rgb      = array();
		$rgb['R'] = hexdec( $color{0} . $color{1} );
		$rgb['G'] = hexdec( $color{2} . $color{3} );
		$rgb['B'] = hexdec( $color{4} . $color{5} );

		return $rgb;
	}
}

if ( ! function_exists( 'orr_hex_darker' ) ) {

	/**
	 * Make HEX color darker.
	 *
	 * @param mixed $color  Color.
	 * @param int   $factor Darker factor.
	 *                      Defaults to 30.
	 * @return string
	 */
	function orr_hex_darker( $color, $factor = 30 ) {
		$base  = orr_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {
			$amount      = $v / 100;
			$amount      = round( $amount * $factor );
			$new_decimal = $v - $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) {
				$new_hex_component = '0' . $new_hex_component;
			}
			$color .= $new_hex_component;
		}

		return $color;
	}
}

if ( ! function_exists( 'orr_hex_lighter' ) ) {

	/**
	 * Make HEX color lighter.
	 *
	 * @param mixed $color  Color.
	 * @param int   $factor Lighter factor.
	 *                      Defaults to 30.
	 * @return string
	 */
	function orr_hex_lighter( $color, $factor = 30 ) {
		$base  = orr_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {
			$amount      = 255 - $v;
			$amount      = $amount / 100;
			$amount      = round( $amount * $factor );
			$new_decimal = $v + $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) {
				$new_hex_component = '0' . $new_hex_component;
			}
			$color .= $new_hex_component;
		}

		return $color;
	}
}

if ( ! function_exists( 'orr_hex_is_light' ) ) {

	/**
	 * Determine whether a hex color is light.
	 *
	 * @param mixed $color Color.
	 * @return bool True if a light color.
	 */
	function orr_hex_is_light( $color ) {
		$hex = str_replace( '#', '', $color );

		$c_r = hexdec( substr( $hex, 0, 2 ) );
		$c_g = hexdec( substr( $hex, 2, 2 ) );
		$c_b = hexdec( substr( $hex, 4, 2 ) );

		$brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;

		return $brightness > 155;
	}
}

if ( ! function_exists( 'orr_light_or_dark' ) ) {

	/**
	 * Detect if we should use a light or dark color on a background color.
	 *
	 * @param mixed  $color Color.
	 * @param string $dark  Darkest reference.
	 *                      Defaults to '#000000'.
	 * @param string $light Lightest reference.
	 *                      Defaults to '#FFFFFF'.
	 * @return string
	 */
	function orr_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
		return orr_hex_is_light( $color ) ? $dark : $light;
	}
}

if ( ! function_exists( 'orr_format_hex' ) ) {

	/**
	 * Format string as hex.
	 *
	 * @param string $hex HEX color.
	 * @return string|null
	 */
	function orr_format_hex( $hex ) {
		$hex = trim( str_replace( '#', '', $hex ) );

		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return $hex ? '#' . $hex : null;
	}
}

/**
 * Format phone numbers.
 *
 * @param  string $phone Phone number.
 * @return string
 */
function orr_format_phone_number( $phone ) {
	return str_replace( '.', '-', $phone );
}

/**
 * Format a date for output.
 *
 * @since  1.0.0
 * @param  ORR_DateTime $date
 * @param  string       $format Defaults to the orr_date_format function if not set.
 * @return string
 */
function orr_format_datetime( $date, $format = '' ) {
	if ( ! $format ) {
		$format = orr_date_format();
	}
	if ( ! is_a( $date, 'ORR_DateTime' ) ) {
		return date_i18n( $format );
	}
	return $date->date_i18n( $format );
}
