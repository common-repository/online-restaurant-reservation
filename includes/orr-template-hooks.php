<?php
/**
 * Online Restaurant Reservation Template Hooks
 *
 * Action/filter hooks used for ORR functions/templates.
 *
 * @author   WPEverest
 * @category Core
 * @package  Online_Restaurant_Reservation/Templates
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'body_class', 'orr_body_class' );

/**
 * WP Header.
 *
 * @see orr_generator_tag()
 */
add_action( 'get_the_generator_html', 'orr_generator_tag', 10, 2 );
add_action( 'get_the_generator_xhtml', 'orr_generator_tag', 10, 2 );

/**
 * Footer.
 *
 * @see orr_print_js()
 */
add_action( 'wp_footer', 'orr_print_js', 25 );
