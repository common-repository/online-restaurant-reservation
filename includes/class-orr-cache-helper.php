<?php
/**
 * Cache Helper Class
 *
 * @class    ORR_Cache_Helper
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Classes
 * @category Class
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Cache_Helper Class.
 */
class ORR_Cache_Helper {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	/**
	 * Get prefix for use with wp_cache_set. Allows all cache in a group to be invalidated at once.
	 *
	 * @param  string $group Group of cache to get.
	 * @return string
	 */
	public static function get_cache_prefix( $group ) {
		$prefix = wp_cache_get( 'orr_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = 1;
			wp_cache_set( 'orr_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'orr_cache_' . $prefix . '_';
	}

	/**
	 * Increment group cache prefix (invalidates cache).
	 *
	 * @param string $group Group of cache to clear.
	 */
	public static function incr_cache_prefix( $group ) {
		wp_cache_incr( 'orr_' . $group . '_cache_prefix', 1, $group );
	}

	/**
	 * Set constants to prevent caching by some plugins.
	 *
	 * @param  mixed $return Value to return. Previously hooked into a filter.
	 * @return mixed
	 */
	public static function set_nocache_constants( $return = true ) {
		orr_maybe_define_constant( 'DONOTCACHEPAGE', true );
		orr_maybe_define_constant( 'DONOTCACHEOBJECT', true );
		orr_maybe_define_constant( 'DONOTCACHEDB', true );
		return $return;
	}

	/**
	 * Notices function.
	 */
	public static function notices() {
		if ( ! function_exists( 'w3tc_pgcache_flush' ) || ! function_exists( 'w3_instance' ) ) {
			return;
		}

		$config   = w3_instance( 'W3_Config' );
		$enabled  = $config->get_integer( 'dbcache.enabled' );
		$settings = array_map( 'trim', $config->get_array( 'dbcache.reject.sql' ) );

		if ( $enabled && ! in_array( '_orr_session_', $settings, true ) ) {
			?>
			<div class="error">
				<p><?php echo wp_kses_post( sprintf( __( 'In order for <strong>database caching</strong> to work with Online Restaurant Reservation you must add %1$s to the "Ignored Query Strings" option in <a href="%2$s">W3 Total Cache settings</a>.', 'online-restaurant-reservation' ), '<code>_orr_session_</code>', esc_url( admin_url( 'admin.php?page=w3tc_dbcache' ) ) ) ); ?></p>
			</div>
			<?php
		}
	}
}

ORR_Cache_Helper::init();
