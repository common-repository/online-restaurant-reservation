<?php
/**
 * Online Restaurant Reservation setup
 *
 * @author   WPEverest
 * @category Core
 * @package  Online_Restaurant_Reservation
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main Online Restaurant Reservation Class.
 *
 * @class   Online_Restaurant_Reservation
 * @version 1.0.0
 */
final class Online_Restaurant_Reservation {

	/**
	 * Plugin Version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * The single instance of the class.
	 *
	 * @var object
	 */
	protected static $_instance = null;

	/**
	 * Session instance.
	 *
	 * @var ORR_Session|ORR_Session_Handler
	 */
	public $session = null;

	/**
	 * Reservation factory instance.
	 *
	 * @var ORR_Reservation_Factory
	 */
	public $reservation_factory = null;

	/**
	 * Integrations instance.
	 *
	 * @var ORR_Integrations
	 */
	public $integrations = null;

	/**
	 * Array of deprecated hook handlers.
	 *
	 * @var array of ORR_Deprecated_Hooks
	 */
	public $deprecated_hook_handlers = array();

	/**
	 * Main Online Restaurant Reservation Instance.
	 *
	 * Ensure only one instance of Online Restaurant Reservation is loaded or can be loaded.
	 *
	 * @static
	 * @see    ORR()
	 * @return Online Restaurant Reservation - Main instance.
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0
	 */
	public function __clone() {
		orr_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'online-restaurant-reservation' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		orr_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'online-restaurant-reservation' ), '1.0' );
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 *
	 * @param  mixed $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'mailer', 'table_reservation' ), true ) ) {
			return $this->$key();
		}
	}

	/**
	 * Online Restaurant Reservation Constructor.
	 */
	private function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();

		do_action( 'online_restaurant_reservation_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		register_activation_hook( ORR_PLUGIN_FILE, array( 'ORR_Install', 'install' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( 'ORR_Shortcodes', 'init' ) );
		add_action( 'init', array( 'ORR_Emails', 'init_notificational_emails' ) );
	}

	/**
	 * Define ORR Constants.
	 */
	private function define_constants() {
		$this->define( 'ORR_ABSPATH', dirname( ORR_PLUGIN_FILE ) . '/' );
		$this->define( 'ORR_PLUGIN_BASENAME', plugin_basename( ORR_PLUGIN_FILE ) );
		$this->define( 'ORR_VERSION', $this->version );
		$this->define( 'ORR_SESSION_CACHE_GROUP', 'orr_session_id' );
		$this->define( 'ORR_TEMPLATE_DEBUG_MODE', false );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * What type of request is this?
	 *
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
		}
	}

	/**
	 * Includes the required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		include_once( ORR_ABSPATH . 'includes/class-orr-autoloader.php' );

		/**
		 * Abstract classes.
		 */
		include_once( ORR_ABSPATH . 'includes/abstracts/abstract-orr-reservation.php' ); // Reservations.
		include_once( ORR_ABSPATH . 'includes/abstracts/abstract-orr-settings-api.php' ); // Settings API (for mailer, and integrations).
		include_once( ORR_ABSPATH . 'includes/abstracts/abstract-orr-integration.php' ); // An integration with a service.
		include_once( ORR_ABSPATH . 'includes/abstracts/abstract-orr-deprecated-hooks.php' );
		include_once( ORR_ABSPATH . 'includes/abstracts/abstract-orr-session.php' );

		/**
		 * Core classes.
		 */
		include_once( ORR_ABSPATH . 'includes/orr-core-functions.php' );
		include_once( ORR_ABSPATH . 'includes/class-orr-datetime.php' );
		include_once( ORR_ABSPATH . 'includes/class-orr-post-types.php' ); // Registers post types.
		include_once( ORR_ABSPATH . 'includes/class-orr-install.php' );
		include_once( ORR_ABSPATH . 'includes/class-orr-ajax.php' );
		include_once( ORR_ABSPATH . 'includes/class-orr-emails.php' );
		include_once( ORR_ABSPATH . 'includes/class-orr-data-exception.php' );
		include_once( ORR_ABSPATH . 'includes/class-orr-reservation-factory.php' ); // Reservation factory.
		include_once( ORR_ABSPATH . 'includes/class-orr-integrations.php' ); // Loads integrations.
		include_once( ORR_ABSPATH . 'includes/class-orr-cache-helper.php' ); // Cache Helper.
		include_once( ORR_ABSPATH . 'includes/class-orr-deprecated-action-hooks.php' );
		include_once( ORR_ABSPATH . 'includes/class-orr-deprecated-filter-hooks.php' );
		include_once( ORR_ABSPATH . 'includes/class-orr-background-emailer.php' );

		if ( $this->is_request( 'admin' ) ) {
			include_once( ORR_ABSPATH . 'includes/admin/class-orr-admin.php' );
		}

		if ( $this->is_request( 'frontend' ) ) {
			$this->frontend_includes();
		}

		if ( $this->is_request( 'frontend' ) || $this->is_request( 'cron' ) ) {
			include_once( ORR_ABSPATH . 'includes/class-orr-session-handler.php' );
		}
	}

	/**
	 * Include required frontend files.
	 */
	public function frontend_includes() {
		include_once( ORR_ABSPATH . 'includes/orr-notice-functions.php' );
		include_once( ORR_ABSPATH . 'includes/orr-template-hooks.php' );
		include_once( ORR_ABSPATH . 'includes/class-orr-frontend-scripts.php' ); // Frontend Scripts.
		include_once( ORR_ABSPATH . 'includes/class-orr-form-handler.php' );     // Form Handlers.
		include_once( ORR_ABSPATH . 'includes/class-orr-shortcodes.php' );       // Shortcodes class.
	}

	/**
	 * Function used to Init ORR Template Functions - This makes them pluggable by plugins and themes.
	 */
	public function include_template_functions() {
		include_once( ORR_ABSPATH . 'includes/orr-template-functions.php' );
	}

	/**
	 * Init Online Restaurant Reservation when WordPress Initialises.
	 */
	public function init() {
		// Before init action.
		do_action( 'before_online_restaurant_reservation_init' );

		// Set up localisation.
		$this->load_plugin_textdomain();

		// Load class instances.
		$this->reservation_factory                 = new ORR_Reservation_Factory(); // Reservation Factory to create new reservation instances.
		$this->integrations                        = new ORR_Integrations(); // Integrations class.
		$this->deprecated_hook_handlers['actions'] = new ORR_Deprecated_Action_Hooks();
		$this->deprecated_hook_handlers['filters'] = new ORR_Deprecated_Filter_Hooks();

		// Classes/actions loaded for the frontend and for ajax requests.
		if ( $this->is_request( 'frontend' ) ) {
			// Session class, handles session data for users - can be overwritten if custom handler is needed.
			$session_class  = apply_filters( 'online_restaurant_reservation_session_handler', 'ORR_Session_Handler' );
			$this->session  = new $session_class();
			$this->session->init();
		}

		// Init action.
		do_action( 'online_restaurant_reservation_init' );
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/online-restaurant-reservation/online-restaurant-reservation-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/online-restaurant-reservation-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'online-restaurant-reservation' );

		unload_textdomain( 'online-restaurant-reservation' );
		load_textdomain( 'online-restaurant-reservation', WP_LANG_DIR . '/online-restaurant-reservation/online-restaurant-reservation-' . $locale . '.mo' );
		load_plugin_textdomain( 'online-restaurant-reservation', false, plugin_basename( dirname( ORR_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', ORR_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( ORR_PLUGIN_FILE ) );
	}

	/**
	 * Get the template path.
	 *
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'online_restaurant_reservation_template_path', 'restaurant-reservation/' );
	}

	/**
	 * Get Ajax URL.
	 *
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	/**
	 * Email Class.
	 *
	 * @return ORR_Emails
	 */
	public function mailer() {
		return ORR_Emails::instance();
	}

	/**
	 * Get Table Reservation Class.
	 *
	 * @return ORR_Table_Reservation
	 */
	public function table_reservation() {
		return ORR_Table_Reservation::instance();
	}
}
