<?php
/**
 * Handle frontend scripts.
 *
 * @class    ORR_Frontend_Scripts
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Classes
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Frontend_Scripts Class.
 */
class ORR_Frontend_Scripts {

	/**
	 * Contains an array of script handles registered by ORR.
	 *
	 * @var array
	 */
	private static $scripts = array();

	/**
	 * Contains an array of script handles registered by ORR.
	 *
	 * @var array
	 */
	private static $styles = array();

	/**
	 * Contains an array of script handles localized by ORR.
	 *
	 * @var array
	 */
	private static $wp_localize_scripts = array();

	/**
	 * Hooks in methods.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_scripts' ) );
		add_action( 'wp_print_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5 );
	}

	/**
	 * Get styles for the frontend.
	 *
	 * @access private
	 * @return array
	 */
	public static function get_styles() {
		return apply_filters( 'online_restaurant_reservation_enqueue_styles', array(
			'online-restaurant-reservation-layout' => array(
				'src'     => self::get_asset_url( 'assets/css/online-restaurant-reservation-layout.css' ),
				'deps'    => '',
				'version' => ORR_VERSION,
				'media'   => 'all',
				'has_rtl' => true,
			),
			'online-restaurant-reservation-smallscreen' => array(
				'src'     => self::get_asset_url( 'assets/css/online-restaurant-reservation-smallscreen.css' ),
				'deps'    => 'online-restaurant-reservation-layout',
				'version' => ORR_VERSION,
				'media'   => 'only screen and (max-width: ' . apply_filters( 'online_restaurant_reservation_style_smallscreen_breakpoint', $breakpoint = '768px' ) . ')',
				'has_rtl' => true,
			),
			'online-restaurant-reservation-general' => array(
				'src'     => self::get_asset_url( 'assets/css/online-restaurant-reservation.css' ),
				'deps'    => '',
				'version' => ORR_VERSION,
				'media'   => 'all',
				'has_rtl' => true,
			),
		) );
	}

	/**
	 * Return asset URL.
	 *
	 * @param  string $path
	 * @return string
	 */
	private static function get_asset_url( $path ) {
		return apply_filters( 'online_restaurant_reservation_get_asset_url', plugins_url( $path, ORR_PLUGIN_FILE ), $path );
	}

	/**
	 * Register a script for use.
	 *
	 * @uses   wp_register_script()
	 * @access private
	 * @param  string   $handle
	 * @param  string   $path
	 * @param  string[] $deps
	 * @param  string   $version
	 * @param  boolean  $in_footer
	 */
	private static function register_script( $handle, $path, $deps = array( 'jquery' ), $version = ORR_VERSION, $in_footer = true ) {
		self::$scripts[] = $handle;
		wp_register_script( $handle, $path, $deps, $version, $in_footer );
	}

	/**
	 * Register and enqueue a script for use.
	 *
	 * @uses   wp_enqueue_script()
	 * @access private
	 * @param  string   $handle
	 * @param  string   $path
	 * @param  string[] $deps
	 * @param  string   $version
	 * @param  boolean  $in_footer
	 */
	private static function enqueue_script( $handle, $path = '', $deps = array( 'jquery' ), $version = ORR_VERSION, $in_footer = true ) {
		if ( ! in_array( $handle, self::$scripts ) && $path ) {
			self::register_script( $handle, $path, $deps, $version, $in_footer );
		}
		wp_enqueue_script( $handle );
	}

	/**
	 * Register a style for use.
	 *
	 * @uses   wp_register_style()
	 * @access private
	 * @param  string   $handle
	 * @param  string   $path
	 * @param  string[] $deps
	 * @param  string   $version
	 * @param  string   $media
	 * @param  boolean  $has_rtl
	 */
	private static function register_style( $handle, $path, $deps = array(), $version = ORR_VERSION, $media = 'all', $has_rtl = false ) {
		self::$styles[] = $handle;
		wp_register_style( $handle, $path, $deps, $version, $media );

		if ( $has_rtl ) {
			wp_style_add_data( $handle, 'rtl', 'replace' );
		}
	}

	/**
	 * Register and enqueue a styles for use.
	 *
	 * @uses   wp_enqueue_style()
	 * @access private
	 * @param  string   $handle
	 * @param  string   $path
	 * @param  string[] $deps
	 * @param  string   $version
	 * @param  string   $media
	 * @param  boolean  $has_rtl
	 */
	private static function enqueue_style( $handle, $path = '', $deps = array(), $version = ORR_VERSION, $media = 'all', $has_rtl = false ) {
		if ( ! in_array( $handle, self::$styles ) && $path ) {
			self::register_style( $handle, $path, $deps, $version, $media, $has_rtl );
		}
		wp_enqueue_style( $handle );
	}

	/**
	 * Register all ORR scripts.
	 */
	private static function register_scripts() {
		$suffix           = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$register_scripts = array(
			'jquery-blockui' => array(
				'src'     => self::get_asset_url( 'assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js' ),
				'deps'    => array( 'jquery' ),
				'version' => '2.70',
			),
			'selectWoo' => array(
				'src'     => self::get_asset_url( 'assets/js/selectWoo/selectWoo.full' . $suffix . '.js' ),
				'deps'    => array( 'jquery' ),
				'version' => '1.0.2',
			),
			'orr-reservation' => array(
				'src'     => self::get_asset_url( 'assets/js/frontend/reservation' . $suffix . '.js' ),
				'deps'    => array( 'jquery', 'jquery-blockui', 'jquery-ui-datepicker' ),
				'version' => ORR_VERSION,
			),
		);
		foreach ( $register_scripts as $name => $props ) {
			self::register_script( $name, $props['src'], $props['deps'], $props['version'] );
		}
	}

	/**
	 * Register all ORR styles.
	 */
	private static function register_styles() {
		$register_styles = array(
			'orr-jquery-ui-datepicker' => array(
				'src'     => self::get_asset_url( 'assets/css/jquery-ui-datepicker/jquery-ui-datepicker.css' ),
				'deps'    => array(),
				'version' => ORR_VERSION,
				'has_rtl' => true,
			),
		);
		foreach ( $register_styles as $name => $props ) {
			self::register_style( $name, $props['src'], $props['deps'], $props['version'], 'all', $props['has_rtl'] );
		}
	}

	/**
	 * Register/enqueue frontend scripts.
	 */
	public static function load_scripts() {
		global $post;

		if ( ! did_action( 'before_online_restaurant_reservation_init' ) ) {
			return;
		}

		self::register_scripts();
		self::register_styles();

		// Load gallery scripts on food pages only if supported.
		if ( is_reservation_page() || ( ! empty( $post->post_content ) && strstr( $post->post_content, '[online_restaurant_reservation' ) ) ) {
			self::enqueue_script( 'orr-reservation' );
			self::enqueue_style( 'orr-jquery-ui-datepicker' );
		}

		// CSS Styles.
		if ( $enqueue_styles = self::get_styles() ) {
			foreach ( $enqueue_styles as $handle => $args ) {
				if ( ! isset( $args['has_rtl'] ) ) {
					$args['has_rtl'] = false;
				}

				self::enqueue_style( $handle, $args['src'], $args['deps'], $args['version'], $args['media'], $args['has_rtl'] );
			}
		}
	}

	/**
	 * Localize a ORR script once.
	 *
	 * @access private
	 * @since  1.0.0 this needs less wp_script_is() calls due to https://core.trac.wordpress.org/ticket/28404 being added in WP 4.0.
	 * @param  string $handle
	 */
	private static function localize_script( $handle ) {
		if ( ! in_array( $handle, self::$wp_localize_scripts ) && wp_script_is( $handle ) && ( $data = self::get_script_data( $handle ) ) ) {
			$name                        = str_replace( '-', '_', $handle ) . '_params';
			self::$wp_localize_scripts[] = $handle;
			wp_localize_script( $handle, $name, apply_filters( $name, $data ) );
		}
	}

	/**
	 * Return data for script handles.
	 *
	 * @access private
	 * @param  string $handle
	 * @return array|bool
	 */
	private static function get_script_data( $handle ) {
		global $wp;

		switch ( $handle ) {
			case 'orr-reservation':
				$params = array(
					'ajax_url'                => ORR()->ajax_url(),
					'debug_mode'              => defined( 'WP_DEBUG' ) && WP_DEBUG,
					'closed_days'             => orr_get_closed_day_index(),
					'exceptional_closed_days' => orr_get_exceptional_closed_date_ranges(),
					'exceptional_opened_days' => orr_get_exceptional_opened_date_ranges(),
					'i18n_reservation_error'  => esc_attr__( 'Error processing reservation. Please try again.', 'online-restaurant-reservation' ),
					'date_changed_nonce'      => wp_create_nonce( 'orr_date_changed' ),
				);
			break;
			default:
				$params = false;
			break;
		}

		return apply_filters( 'online_restaurant_reservation_get_script_data', $params, $handle );
	}

	/**
	 * Localize scripts only when enqueued.
	 */
	public static function localize_printed_scripts() {
		foreach ( self::$scripts as $handle ) {
			self::localize_script( $handle );
		}
	}
}

ORR_Frontend_Scripts::init();
