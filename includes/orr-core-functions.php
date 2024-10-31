<?php
/**
 * Online Restaurant Reservation Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @author   WPEverest
 * @category Core
 * @package  Online_Restaurant_Reservation/Functions
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include core functions (available in both admin and frontend).
require ORR_ABSPATH . 'includes/orr-conditional-functions.php';
require ORR_ABSPATH . 'includes/orr-deprecated-functions.php';
require ORR_ABSPATH . 'includes/orr-formatting-functions.php';
require ORR_ABSPATH . 'includes/orr-reservation-functions.php';

/**
 * Define a constant if it is not already defined.
 *
 * @since 1.0.0
 * @param string $name  Constant name.
 * @param string $value Value.
 */
function orr_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

/**
 * Get template part (for templates like the layout-loop).
 *
 * ORR_TEMPLATE_DEBUG_MODE will prevent overrides in themes from taking priority.
 *
 * @param mixed  $slug Template slug.
 * @param string $name Template name (default: '').
 */
function orr_get_template_part( $slug, $name = '' ) {
	$template = '';

	// Look in yourtheme/slug-name.php and yourtheme/restaurant-reservation/slug-name.php.
	if ( $name && ! ORR_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( "{$slug}-{$name}.php", ORR()->template_path() . "{$slug}-{$name}.php" ) );
	}

	// Get default slug-name.php.
	if ( ! $template && $name && file_exists( ORR()->plugin_path() . "/templates/{$slug}-{$name}.php" ) ) {
		$template = ORR()->plugin_path() . "/templates/{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/restaurant-reservation/slug.php.
	if ( ! $template && ! ORR_TEMPLATE_DEBUG_MODE ) {
		$template = locate_template( array( "{$slug}.php", ORR()->template_path() . "{$slug}.php" ) );
	}

	// Allow 3rd party plugins to filter template file from their plugin.
	$template = apply_filters( 'orr_get_template_part', $template, $slug, $name );

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Get other templates (e.g. layout attributes) passing attributes and including the file.
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 */
function orr_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( ! empty( $args ) && is_array( $args ) ) {
		extract( $args ); // @codingStandardsIgnoreLine
	}

	$located = orr_locate_template( $template_name, $template_path, $default_path );

	if ( ! file_exists( $located ) ) {
		/* translators: %s template */
		orr_doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '1.4.0' );
		return;
	}

	// Allow 3rd party plugin filter template file from their plugin.
	$located = apply_filters( 'orr_get_template', $located, $template_name, $args, $template_path, $default_path );

	do_action( 'online_restaurant_reservation_before_template_part', $template_name, $template_path, $located, $args );

	include $located;

	do_action( 'online_restaurant_reservation_after_template_part', $template_name, $template_path, $located, $args );
}

/**
 * Like orr_get_template, but returns the HTML instead of outputting.
 *
 * @see   orr_get_template
 * @since 1.0.0
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 *
 * @return string
 */
function orr_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	orr_get_template( $template_name, $args, $template_path, $default_path );
	return ob_get_clean();
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 * yourtheme/$template_path/$template_name
 * yourtheme/$template_name
 * $default_path/$template_name
 *
 * @param  string $template_name Template name.
 * @param  string $template_path Template path. (default: '').
 * @param  string $default_path  Default path. (default: '').
 * @return string
 */
function orr_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = ORR()->template_path();
	}

	if ( ! $default_path ) {
		$default_path = ORR()->plugin_path() . '/templates/';
	}

	// Look within passed path within the theme - this is priority.
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		)
	);

	// Get default template/.
	if ( ! $template || ORR_TEMPLATE_DEBUG_MODE ) {
		$template = $default_path . $template_name;
	}

	// Return what we found.
	return apply_filters( 'online_restaurant_reservation_locate_template', $template, $template_name, $template_path );
}

/**
 * Send HTML emails from Online Restaurant Reservation.
 *
 * @since 1.0.0
 *
 * @param mixed  $to          Receiver.
 * @param mixed  $subject     Subject.
 * @param mixed  $message     Message.
 * @param string $headers     Headers. (default: "Content-Type: text/html\r\n").
 * @param string $attachments Attachments. (default: "").
 */
function orr_mail( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = '' ) {
	$mailer = ORR()->mailer();

	$mailer->send( $to, $subject, $message, $headers, $attachments );
}

/**
 * Queue some JavaScript code to be output in the footer.
 *
 * @param string $code Code.
 */
function orr_enqueue_js( $code ) {
	global $orr_queued_js;

	if ( empty( $orr_queued_js ) ) {
		$orr_queued_js = '';
	}

	$orr_queued_js .= "\n" . $code . "\n";
}

/**
 * Output any queued javascript code in the footer.
 */
function orr_print_js() {
	global $orr_queued_js;

	if ( ! empty( $orr_queued_js ) ) {
		// Sanitize.
		$orr_queued_js = wp_check_invalid_utf8( $orr_queued_js );
		$orr_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $orr_queued_js );
		$orr_queued_js = str_replace( "\r", '', $orr_queued_js );

		$js = "<!-- Online Restaurant Reservation JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) { $orr_queued_js });\n</script>\n";

		/**
		 * Queued jsfilter.
		 *
		 * @param string $js JavaScript code.
		 */
		echo apply_filters( 'online_restaurant_reservation_queued_js', $js ); // WPCS: XSS ok.

		unset( $orr_queued_js );
	}
}

/**
 * Set a cookie - wrapper for setcookie using WP constants.
 *
 * @since 1.0.0
 *
 * @param string  $name   Name of the cookie being set.
 * @param string  $value  Value of the cookie.
 * @param integer $expire Expiry of the cookie.
 * @param bool    $secure Whether the cookie should be served only over https.
 */
function orr_setcookie( $name, $value, $expire = 0, $secure = false ) {
	if ( ! headers_sent() ) {
		setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, apply_filters( 'online_restaurant_reservation_cookie_httponly', false, $name, $value, $expire, $secure ) );
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		headers_sent( $file, $line );
		trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE ); // WPCS: XSS ok.
	}
}

/**
 * Get user agent string.
 *
 * @since  1.0.0
 * @return string
 */
function orr_get_user_agent() {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( orr_clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
}

/**
 * Outputs a "back" link so admin screens can easily jump back a page.
 *
 * @param string $label Title of the page to return to.
 * @param string $url   URL of the page to return to.
 */
function orr_back_link( $label, $url ) {
	echo '<small class="orr-admin-breadcrumb"><a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $label ) . '">&#x2934;</a></small>';
}

/**
 * Display a Online Restaurant Reservation help tip.
 *
 * @param  string $tip        Help tip text.
 * @param  bool   $allow_html Allow sanitized HTML if true or escape.
 * @return string
 */
function orr_help_tip( $tip, $allow_html = false ) {
	if ( $allow_html ) {
		$tip = orr_sanitize_tooltip( $tip );
	} else {
		$tip = esc_attr( $tip );
	}

	return '<span class="online-restaurant-reservation-help-tip" data-tip="' . $tip . '"></span>';
}

/**
 * Wrapper for set_time_limit to see if it is enabled.
 *
 * @since 1.0.0
 * @param int $limit Time limit.
 */
function orr_set_time_limit( $limit = 0 ) {
	if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) {
		@set_time_limit( $limit ); // @codingStandardsIgnoreLine
	}
}

/**
 * Wrapper for nocache_headers which also disables page caching.
 *
 * @since 1.0.0
 */
function orr_nocache_headers() {
	ORR_Cache_Helper::set_nocache_constants();
	nocache_headers();
}

/**
 * Prints human-readable information about a variable.
 *
 * Some server environments blacklist some debugging functions. This function provides a safe way to
 * turn an expression into a printable, readable form without calling blacklisted functions.
 *
 * @since 1.0
 *
 * @param mixed $expression The expression to be printed.
 * @param bool  $return Optional. Default false. Set to true to return the human-readable string.
 * @return string|bool False if expression could not be printed. True if the expression was printed.
 *     If $return is true, a string representation will be returned.
 */
function orr_print_r( $expression, $return = false ) {
	$alternatives = array(
		array(
			'func' => 'print_r',
			'args' => array( $expression, true ),
		),
		array(
			'func' => 'var_export',
			'args' => array( $expression, true ),
		),
		array(
			'func' => 'json_encode',
			'args' => array( $expression ),
		),
		array(
			'func' => 'serialize',
			'args' => array( $expression ),
		),
	);

	$alternatives = apply_filters( 'online_restaurant_reservation_print_r_alternatives', $alternatives, $expression );

	foreach ( $alternatives as $alternative ) {
		if ( function_exists( $alternative['func'] ) ) {
			$res = call_user_func_array( $alternative['func'], $alternative['args'] );
			if ( $return ) {
				return $res;
			} else {
				echo $res; // WPCS: XSS ok.
				return true;
			}
		}
	}

	return false;
}

/**
 * Switch Online Restaurant Reservation to site language.
 *
 * @since 1.0.0
 */
function orr_switch_to_site_locale() {
	if ( function_exists( 'switch_to_locale' ) ) {
		switch_to_locale( get_locale() );

		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		add_filter( 'plugin_locale', 'get_locale' );

		// Init ORR locale.
		ORR()->load_plugin_textdomain();
	}
}

/**
 * Switch Online Restaurant Reservation language to original.
 *
 * @since 1.0.0
 */
function orr_restore_locale() {
	if ( function_exists( 'restore_previous_locale' ) ) {
		restore_previous_locale();

		// Remove filter.
		remove_filter( 'plugin_locale', 'get_locale' );

		// Init ORR locale.
		ORR()->load_plugin_textdomain();
	}
}

/**
 * Convert plaintext phone number to clickable phone number.
 *
 * Remove formatting and allow "+".
 * Example and specs: https://developer.mozilla.org/en/docs/Web/HTML/Element/a#Creating_a_phone_link
 *
 * @since 1.0.0
 *
 * @param  string $phone Content to convert phone number.
 * @return string Content with converted phone number.
 */
function orr_make_phone_clickable( $phone ) {
	$number = trim( preg_replace( '/[^\d|\+]/', '', $phone ) );

	return '<a href="tel:' . esc_attr( $number ) . '">' . esc_html( $phone ) . '</a>';
}

/**
 * Read in WooCommerce headers when reading plugin headers.
 *
 * @since  1.0.0
 * @param  array $headers Headers.
 * @return array
 */
function orr_enable_orr_plugin_headers( $headers ) {
	if ( ! class_exists( 'ORR_Plugin_Updates' ) ) {
		include_once dirname( __FILE__ ) . '/admin/plugin-updates/class-orr-plugin-updates.php';
	}

	$headers['ORRRequires'] = ORR_Plugin_Updates::VERSION_REQUIRED_HEADER;
	$headers['ORRTested']   = ORR_Plugin_Updates::VERSION_TESTED_HEADER;
	return $headers;
}
add_filter( 'extra_plugin_headers', 'orr_enable_orr_plugin_headers' );

/**
 * Delete expired transients.
 *
 * Deletes all expired transients. The multi-table delete syntax is used.
 * to delete the transient record from table a, and the corresponding.
 * transient_timeout record from table b.
 *
 * Based on code inside core's upgrade_network() function.
 *
 * @since  1.4.0
 * @return int Number of transients that were cleared.
 */
function orr_delete_expired_transients() {
	global $wpdb;

	$sql  = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
		WHERE a.option_name LIKE %s
		AND a.option_name NOT LIKE %s
		AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
		AND b.option_value < %d";
	$rows = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) ); // WPCS: unprepared SQL ok.

	$sql   = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
		WHERE a.option_name LIKE %s
		AND a.option_name NOT LIKE %s
		AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
		AND b.option_value < %d";
	$rows2 = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_site_transient_' ) . '%', $wpdb->esc_like( '_site_transient_timeout_' ) . '%', time() ) ); // WPCS: unprepared SQL ok.

	return absint( $rows + $rows2 );
}
add_action( 'online_restaurant_reservation_installed', 'orr_delete_expired_transients' );

/**
 * Make a URL relative, if possible.
 *
 * @since  1.0.0
 * @param  string $url URL to make relative.
 * @return string
 */
function orr_get_relative_url( $url ) {
	return orr_is_external_resource( $url ) ? $url : str_replace( array( 'http://', 'https://' ), '//', $url );
}

/**
 * See if a resource is remote.
 *
 * @since  1.0.0
 * @param  string $url URL to check.
 * @return bool
 */
function orr_is_external_resource( $url ) {
	$wp_base = str_replace( array( 'http://', 'https://' ), '//', get_home_url( null, '/', 'http' ) );
	return strstr( $url, '://' ) && strstr( $wp_base, $url );
}

/**
 * See if theme/s is activate or not.
 *
 * @since  1.0.0
 * @param  string|array $theme Theme name or array of theme names to check.
 * @return boolean
 */
function orr_is_active_theme( $theme ) {
	return is_array( $theme ) ? in_array( get_template(), $theme, true ) : get_template() === $theme;
}

/**
 * Add support for searching by display_name.
 *
 * @since  1.0.0
 *
 * @param  array $search_columns Column names.
 *
 * @return array
 */
function orr_user_search_columns( $search_columns ) {
	$search_columns[] = 'display_name';

	return $search_columns;
}

add_filter( 'user_search_columns', 'orr_user_search_columns' );

/**
 * Cleans up session data - cron callback.
 */
function orr_cleanup_session_data() {
	$session_class = apply_filters( 'online_restaurant_reservation_session_handler', 'ORR_Session_Handler' );
	$session       = new $session_class();

	if ( is_callable( array( $session, 'cleanup_sessions' ) ) ) {
		$session->cleanup_sessions();
	}
}
add_action( 'online_restaurant_reservation_cleanup_sessions', 'orr_cleanup_session_data' );
