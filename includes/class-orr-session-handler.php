<?php
/**
 * Handle data for the current customers session.
 * Implements the ORR_Session abstract class.
 *
 * @package Online_Restaurant_Reservation/Classes
 * @version 1.0.0
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ORR_Session_Handler Class.
 */
class ORR_Session_Handler extends ORR_Session {

	/**
	 * Cookie name used for the session.
	 *
	 * @var string cookie name
	 */
	protected $_cookie;

	/**
	 * Stores session expiry.
	 *
	 * @var string session due to expire timestamp
	 */
	protected $_session_expiring;

	/**
	 * Stores session due to expire timestamp.
	 *
	 * @var string session expiration timestamp
	 */
	protected $_session_expiration;

	/**
	 * True when the cookie exists.
	 *
	 * @var bool Based on whether a cookie exists.
	 */
	protected $_has_cookie = false;

	/**
	 * Table name for session data.
	 *
	 * @var string Custom session table name
	 */
	protected $_table;

	/**
	 * Constructor for the session class.
	 */
	public function __construct() {
		$this->_cookie = apply_filters( 'online_restaurant_reservation_cookie', 'wp_online_restaurant_reservation_session_' . COOKIEHASH );
		$this->_table  = $GLOBALS['wpdb']->prefix . 'orr_sessions';
	}

	/**
	 * Init hooks and session data.
	 */
	public function init() {
		$cookie = $this->get_session_cookie();

		if ( $cookie ) {
			$this->_customer_id        = $cookie[0];
			$this->_session_expiration = $cookie[1];
			$this->_session_expiring   = $cookie[2];
			$this->_has_cookie         = true;

			// Update session if its close to expiring
			if ( time() > $this->_session_expiring ) {
				$this->set_session_expiration();
				$this->update_session_timestamp( $this->_customer_id, $this->_session_expiration );
			}
		} else {
			$this->set_session_expiration();
			$this->_customer_id = $this->generate_customer_id();
		}

		$this->_data = $this->get_session_data();

		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );

		if ( ! is_user_logged_in() ) {
			add_filter( 'nonce_user_logged_out', array( $this, 'nonce_user_logged_out' ) );
		}
	}

	/**
	 * Return true if the current user has an active session, i.e. a cookie to retrieve values.
	 *
	 * @return bool
	 */
	public function has_session() {
		return isset( $_COOKIE[ $this->_cookie ] ) || $this->_has_cookie || is_user_logged_in(); // @codingStandardsIgnoreLine.
	}

	/**
	 * Set session expiration.
	 */
	public function set_session_expiration() {
		$this->_session_expiring   = time() + intval( apply_filters( 'orr_session_expiring', 60 * 60 * 47 ) ); // 47 Hours.
		$this->_session_expiration = time() + intval( apply_filters( 'orr_session_expiration', 60 * 60 * 48 ) ); // 48 Hours.
	}

	/**
	 * Generate a unique customer ID for guests, or return user ID if logged in.
	 *
	 * Uses Portable PHP password hashing framework to generate a unique cryptographically strong ID.
	 *
	 * @return int|string
	 */
	public function generate_customer_id() {
		if ( is_user_logged_in() ) {
			return get_current_user_id();
		} else {
			require_once ABSPATH . 'wp-includes/class-phpass.php';
			$hasher = new PasswordHash( 8, false );
			return md5( $hasher->get_random_bytes( 32 ) );
		}
	}

	/**
	 * Get session cookie.
	 *
	 * @return bool|array
	 */
	public function get_session_cookie() {
		$cookie_value = isset( $_COOKIE[ $this->_cookie ] ) ? wp_unslash( $_COOKIE[ $this->_cookie ] ) : false; // @codingStandardsIgnoreLine.

		if ( empty( $cookie_value ) || ! is_string( $cookie_value ) ) {
			return false;
		}

		list( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) = explode( '||', $cookie_value );

		// Validate hash.
		$to_hash = $customer_id . '|' . $session_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		if ( empty( $cookie_hash ) || ! hash_equals( $hash, $cookie_hash ) ) {
			return false;
		}

		return array( $customer_id, $session_expiration, $session_expiring, $cookie_hash );
	}

	/**
	 * Get session data.
	 *
	 * @return array
	 */
	public function get_session_data() {
		return $this->has_session() ? (array) $this->get_session( $this->_customer_id, array() ) : array();
	}

	/**
	 * Gets a cache prefix. This is used in session names so the entire cache can be invalidated with 1 function call.
	 *
	 * @return string
	 */
	private function get_cache_prefix() {
		return ORR_Cache_Helper::get_cache_prefix( ORR_SESSION_CACHE_GROUP );
	}

	/**
	 * Save data.
	 */
	public function save_data() {
		// Dirty if something changed - prevents saving nothing new.
		if ( $this->_dirty && $this->has_session() ) {
			global $wpdb;

			$wpdb->replace( // @codingStandardsIgnoreLine.
				$this->_table,
				array(
					'session_key' => $this->_customer_id,
					'session_value' => maybe_serialize( $this->_data ),
					'session_expiry' => $this->_session_expiration,
				),
				array(
					'%s',
					'%s',
					'%d',
				)
			);

			wp_cache_set( $this->get_cache_prefix() . $this->_customer_id, $this->_data, ORR_SESSION_CACHE_GROUP, $this->_session_expiration - time() );
			$this->_dirty = false;
		}
	}

	/**
	 * Destroy all session data.
	 */
	public function destroy_session() {
		orr_setcookie( $this->_cookie, '', time() - YEAR_IN_SECONDS, apply_filters( 'orr_session_use_secure_cookie', false ) );

		$this->delete_session( $this->_customer_id );

		$this->_data        = array();
		$this->_dirty       = false;
		$this->_customer_id = $this->generate_customer_id();
	}

	/**
	 * When a user is logged out, ensure they have a unique nonce by using the customer/session ID.
	 *
	 * @param int $uid User ID.
	 * @return string
	 */
	public function nonce_user_logged_out( $uid ) {
		return $this->has_session() && $this->_customer_id ? $this->_customer_id : $uid;
	}

	/**
	 * Cleanup session data from the database and clear caches.
	 */
	public function cleanup_sessions() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $this->_table WHERE session_expiry < %d", time() ) ); // @codingStandardsIgnoreLine.

		if ( class_exists( 'ORR_Cache_Helper' ) ) {
			ORR_Cache_Helper::incr_cache_prefix( ORR_SESSION_CACHE_GROUP );
		}
	}

	/**
	 * Returns the session.
	 *
	 * @param  string $customer_id Custo ID.
	 * @param  mixed  $default Default session value.
	 * @return string|array
	 */
	public function get_session( $customer_id, $default = false ) {
		global $wpdb;

		if ( defined( 'WP_SETUP_CONFIG' ) ) {
			return false;
		}

		// Try to get it from the cache, it will return false if not present or if object cache not in use.
		$value = wp_cache_get( $this->get_cache_prefix() . $customer_id, ORR_SESSION_CACHE_GROUP );

		if ( false === $value ) {
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $customer_id ) ); // @codingStandardsIgnoreLine.

			if ( is_null( $value ) ) {
				$value = $default;
			}

			wp_cache_add( $this->get_cache_prefix() . $customer_id, $value, ORR_SESSION_CACHE_GROUP, $this->_session_expiration - time() );
		}

		return maybe_unserialize( $value );
	}

	/**
	 * Delete the session from the cache and database.
	 *
	 * @param int $customer_id Customer ID.
	 */
	public function delete_session( $customer_id ) {
		global $wpdb;

		wp_cache_delete( $this->get_cache_prefix() . $customer_id, ORR_SESSION_CACHE_GROUP );

		$wpdb->delete( // @codingStandardsIgnoreLine.
			$this->_table,
			array(
				'session_key' => $customer_id,
			)
		);
	}

	/**
	 * Update the session expiry timestamp.
	 *
	 * @param string $customer_id Customer ID.
	 * @param int    $timestamp Timestamp to expire the cookie.
	 */
	public function update_session_timestamp( $customer_id, $timestamp ) {
		global $wpdb;

		// @codingStandardsIgnoreStart.
		$wpdb->update(
			$this->_table,
			array(
				'session_expiry' => $timestamp,
			),
			array(
				'session_key' => $customer_id,
			),
			array(
				'%d'
			)
		);
		// @codingStandardsIgnoreEnd.
	}
}
