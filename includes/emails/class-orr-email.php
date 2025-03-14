<?php
/**
 * Email Class
 *
 * Online Restaurant Reservation Email Class which is extended by specific email template classes to add emails to Online Restaurant Reservation.
 *
 * @class    ORR_Email
 * @extends  ORR_Settings_API
 * @version  1.6.0
 * @package  Online_Restaurant_Reservation/Classes/Emails
 * @category Core
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'ORR_Email', false ) ) {
	return;
}

/**
 * ORR_Email Class.
 */
class ORR_Email extends ORR_Settings_API {

	/**
	 * Email method ID.
	 * @var String
	 */
	public $id;

	/**
	 * Email method title.
	 * @var string
	 */
	public $title;

	/**
	 * 'yes' if the method is enabled.
	 * @var string yes, no
	 */
	public $enabled;

	/**
	 * Description for the email.
	 * @var string
	 */
	public $description;

	/**
	 * Default heading.
	 *
	 * Supported for backwards compatibility but we recommend overloading the
	 * get_default_x methods instead so localization can be done when needed.
	 *
	 * @var string
	 */
	public $heading = '';

	/**
	 * Default subject.
	 *
	 * Supported for backwards compatibility but we recommend overloading the
	 * get_default_x methods instead so localization can be done when needed.
	 *
	 * @var string
	 */
	public $subject = '';

	/**
	 * Plain text template path.
	 * @var string
	 */
	public $template_plain;

	/**
	 * HTML template path.
	 * @var string
	 */
	public $template_html;

	/**
	 * Template path.
	 * @var string
	 */
	public $template_base;

	/**
	 * Recipients for the email.
	 * @var string
	 */
	public $recipient;

	/**
	 * Object this email is for, for example a customer, product, or email.
	 * @var object|bool
	 */
	public $object;

	/**
	 * Mime boundary (for multipart emails).
	 * @var string
	 */
	public $mime_boundary;

	/**
	 * Mime boundary header (for multipart emails).
	 * @var string
	 */
	public $mime_boundary_header;

	/**
	 * True when email is being sent.
	 * @var bool
	 */
	public $sending;

	/**
	 * True when the email notification is sent manually only.
	 * @var bool
	 */
	protected $manual = false;

	/**
	 * True when the email notification is sent to customers.
	 * @var bool
	 */
	protected $customer_email = false;

	/**
	 *  List of preg* regular expression patterns to search for,
	 *  used in conjunction with $plain_replace.
	 *  https://raw.github.com/ushahidi/wp-silcc/master/class.html2text.inc
	 *  @var array $plain_search
	 *  @see $plain_replace
	 */
	public $plain_search = array(
		"/\r/",                                          // Non-legal carriage return
		'/&(nbsp|#160);/i',                              // Non-breaking space
		'/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i', // Double quotes
		'/&(apos|rsquo|lsquo|#8216|#8217);/i',           // Single quotes
		'/&gt;/i',                                       // Greater-than
		'/&lt;/i',                                       // Less-than
		'/&#38;/i',                                      // Ampersand
		'/&#038;/i',                                     // Ampersand
		'/&amp;/i',                                      // Ampersand
		'/&(copy|#169);/i',                              // Copyright
		'/&(trade|#8482|#153);/i',                       // Trademark
		'/&(reg|#174);/i',                               // Registered
		'/&(mdash|#151|#8212);/i',                       // mdash
		'/&(ndash|minus|#8211|#8722);/i',                // ndash
		'/&(bull|#149|#8226);/i',                        // Bullet
		'/&(pound|#163);/i',                             // Pound sign
		'/&(euro|#8364);/i',                             // Euro sign
		'/&#36;/',                                       // Dollar sign
		'/&[^&\s;]+;/i',                                 // Unknown/unhandled entities
		'/[ ]{2,}/',                                      // Runs of spaces, post-handling
	);

	/**
	 *  List of pattern replacements corresponding to patterns searched.
	 *  @var array $plain_replace
	 *  @see $plain_search
	 */
	public $plain_replace = array(
		'',                                             // Non-legal carriage return
		' ',                                            // Non-breaking space
		'"',                                            // Double quotes
		"'",                                            // Single quotes
		'>',                                            // Greater-than
		'<',                                            // Less-than
		'&',                                            // Ampersand
		'&',                                            // Ampersand
		'&',                                            // Ampersand
		'(c)',                                          // Copyright
		'(tm)',                                         // Trademark
		'(R)',                                          // Registered
		'--',                                           // mdash
		'-',                                            // ndash
		'*',                                            // Bullet
		'£',                                            // Pound sign
		'EUR',                                          // Euro sign. € ?
		'$',                                            // Dollar sign
		'',                                             // Unknown/unhandled entities
		' ',                                             // Runs of spaces, post-handling
	);

	/**
	 * Strings to find/replace in subjects/headings.
	 *
	 * @var array
	 */
	protected $placeholders = array();

 	/**
	 * Strings to find in subjects/headings.
	 *
	 * @deprecated 1.6.0 in favour of placeholders
	 * @var array
	 */
	public $find = array();

	/**
	 * Strings to replace in subjects/headings.
	 *
	 * @deprecated 1.6.0 in favour of placeholders
	 * @var array
	 */
	public $replace = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Find/replace.
		if ( empty( $this->placeholders ) ) {
			$this->placeholders = array(
				'{site_title}' => $this->get_blogname(),
			);
		}


		// Init settings.
		$this->init_form_fields();
		$this->init_settings();

		// Default template base if not declared in child constructor.
		if ( is_null( $this->template_base ) ) {
			$this->template_base = ORR()->plugin_path() . '/templates/';
		}

		$this->email_type = $this->get_option( 'email_type' );
		$this->enabled    = $this->get_option( 'enabled' );

		add_action( 'phpmailer_init', array( $this, 'handle_multipart' ) );
		add_action( 'online_restaurant_reservation_update_options_email_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Handle multipart mail.
	 *
	 * @param  PHPMailer $mailer
	 * @return PHPMailer
	 */
	public function handle_multipart( $mailer ) {
		if ( $this->sending && 'multipart' === $this->get_email_type() ) {
			$mailer->AltBody = wordwrap( preg_replace( $this->plain_search, $this->plain_replace, strip_tags( $this->get_content_plain() ) ) );
			$this->sending   = false;
		}
		return $mailer;
	}

	/**
	 * Format email string.
	 *
	 * @param  mixed $string Text to replace placeholders in.
	 * @return string
	 */
	public function format_string( $string ) {
		$find    = array_keys( $this->placeholders );
		$replace = array_values( $this->placeholders );

		// If using legacy find replace, add those to our find/replace arrays first. @todo deprecate in 4.0.0.
		$find    = array_merge( (array) $this->find, $find );
		$replace = array_merge( (array) $this->replace, $replace );

		// If using the older style filters for find and replace, ensure the array is associative and then pass through filters. @todo deprecate in 4.0.0.
		if ( has_filter( 'online_restaurant_reservation_email_format_string_replace' ) || has_filter( 'online_restaurant_reservation_email_format_string_find' ) ) {
			$legacy_find    = $this->find;
			$legacy_replace = $this->replace;

			foreach ( $this->placeholders as $find => $replace ) {
				$legacy_key                    = sanitize_title( str_replace( '_', '-', trim( $find, '{}' ) ) );
				$legacy_find[ $legacy_key ]    = $find;
				$legacy_replace[ $legacy_key ] = $replace;
			}

			$string = str_replace( apply_filters( 'online_restaurant_reservation_email_format_string_find', $legacy_find, $this ), apply_filters( 'online_restaurant_reservation_email_format_string_replace', $legacy_replace, $this ), $string );
		}

		return apply_filters( 'online_restaurant_reservation_email_format_string', str_replace( $find, $replace, $string ), $this );
	}

	/**
	 * Set the locale to the store locale for customer emails to make sure emails are in the store language.
	 */
	public function setup_locale() {
		if ( $this->is_customer_email() && apply_filters( 'online_restaurant_reservation_email_setup_locale', true ) ) {
			orr_switch_to_site_locale();
		}
	}

	/**
	 * Restore the locale to the default locale. Use after finished with setup_locale.
	 */
	public function restore_locale() {
		if ( $this->is_customer_email() && apply_filters( 'online_restaurant_reservation_email_restore_locale', true ) ) {
			orr_restore_locale();
		}
	}

	/**
	 * Get email subject.
	 *
	 * @since  1.6.0
	 * @return string
	 */
	public function get_default_subject() {
		return $this->subject;
	}

	/**
	 * Get email heading.
	 *
	 * @since  1.6.0
	 * @return string
	 */
	public function get_default_heading() {
		return $this->heading;
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		return apply_filters( 'online_restaurant_reservation_email_subject_' . $this->id, $this->format_string( $this->get_option( 'subject', $this->get_default_subject() ) ), $this->object );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_heading() {
		return apply_filters( 'online_restaurant_reservation_email_heading_' . $this->id, $this->format_string( $this->get_option( 'heading', $this->get_default_heading() ) ), $this->object );
	}

	/**
	 * Get valid recipients.
	 *
	 * @return string
	 */
	public function get_recipient() {
		$recipient  = apply_filters( 'online_restaurant_reservation_email_recipient_' . $this->id, $this->recipient, $this->object );
		$recipients = array_map( 'trim', explode( ',', $recipient ) );
		$recipients = array_filter( $recipients, 'is_email' );
		return implode( ', ', $recipients );
	}

	/**
	 * Get email headers.
	 *
	 * @return string
	 */
	public function get_headers() {
		$header = "Content-Type: " . $this->get_content_type() . "\r\n";

		return apply_filters( 'online_restaurant_reservation_email_headers', $header, $this->id, $this->object );
	}

	/**
	 * Get email attachments.
	 *
	 * @return string
	 */
	public function get_attachments() {
		return apply_filters( 'online_restaurant_reservation_email_attachments', array(), $this->id, $this->object );
	}

	/**
	 * get_type function.
	 *
	 * @return string
	 */
	public function get_email_type() {
		return $this->email_type && class_exists( 'DOMDocument' ) ? $this->email_type : 'plain';
	}

	/**
	 * Get email content type.
	 *
	 * @return string
	 */
	public function get_content_type() {
		switch ( $this->get_email_type() ) {
			case 'html' :
				return 'text/html';
			case 'multipart' :
				return 'multipart/alternative';
			default :
				return 'text/plain';
		}
	}

	/**
	 * Return the email's title
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'online_restaurant_reservation_email_title', $this->title, $this );
	}

	/**
	 * Return the email's description
	 * @return string
	 */
	public function get_description() {
		return apply_filters( 'online_restaurant_reservation_email_description', $this->description, $this );
	}

	/**
	 * Proxy to parent's get_option and attempt to localize the result using gettext.
	 *
	 * @param  string $key
	 * @param  mixed  $empty_value
	 * @return string
	 */
	public function get_option( $key, $empty_value = null ) {
		$value = parent::get_option( $key, $empty_value );
		return apply_filters( 'online_restaurant_reservation_email_get_option', $value, $this, $value, $key, $empty_value );
	}

	/**
	 * Checks if this email is enabled and will be sent.
	 * @return bool
	 */
	public function is_enabled() {
		return apply_filters( 'online_restaurant_reservation_email_enabled_' . $this->id, 'yes' === $this->enabled, $this->object );
	}

	/**
	 * Checks if this email is manually sent
	 * @return bool
	 */
	public function is_manual() {
		return $this->manual;
	}

	/**
	 * Checks if this email is customer focussed.
	 * @return bool
	 */
	public function is_customer_email() {
		return $this->customer_email;
	}

	/**
	 * Get WordPress blog name.
	 *
	 * @return string
	 */
	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Get email content.
	 *
	 * @return string
	 */
	public function get_content() {
		$this->sending = true;

		if ( 'plain' === $this->get_email_type() ) {
			$email_content = preg_replace( $this->plain_search, $this->plain_replace, strip_tags( $this->get_content_plain() ) );
		} else {
			$email_content = $this->get_content_html();
		}

		return wordwrap( $email_content, 70 );
	}

	/**
	 * Apply inline styles to dynamic content.
	 *
	 * @param string|null $content
	 * @return string
	 */
	public function style_inline( $content ) {
		// Make sure we only inline CSS for html emails.
		if ( in_array( $this->get_content_type(), array( 'text/html', 'multipart/alternative' ) ) && class_exists( 'DOMDocument' ) ) {
			ob_start();
			orr_get_template( 'emails/email-styles.php' );
			$css = apply_filters( 'online_restaurant_reservation_email_styles', ob_get_clean() );

			// Apply CSS styles inline for picky email clients.
			try {
				$emogrifier = new Emogrifier( $content, $css );
				$content    = $emogrifier->emogrify();
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					trigger_error( $e->getMessage(), E_USER_WARNING );
				}
			}
		}
		return $content;
	}

	/**
	 * Get the email content in plain text format.
	 * @return string
	 */
	public function get_content_plain() { return ''; }

	/**
	 * Get the email content in HTML format.
	 * @return string
	 */
	public function get_content_html() { return ''; }

	/**
	 * Get the from name for outgoing emails.
	 * @return string
	 */
	public function get_from_name() {
		$from_name = apply_filters( 'online_restaurant_reservation_email_from_name', get_option( 'online_restaurant_reservation_email_from_name' ), $this );
		return wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES );
	}

	/**
	 * Get the from address for outgoing emails.
	 * @return string
	 */
	public function get_from_address() {
		$from_address = apply_filters( 'online_restaurant_reservation_email_from_address', get_option( 'online_restaurant_reservation_email_from_address' ), $this );
		return sanitize_email( $from_address );
	}

	/**
	 * Send an email.
	 *
	 * @param  string $to
	 * @param  string $subject
	 * @param  string $message
	 * @param  string $headers
	 * @param  string $attachments
	 * @return bool success
	 */
	public function send( $to, $subject, $message, $headers, $attachments ) {
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

		$message = apply_filters( 'online_restaurant_reservation_mail_content', $this->style_inline( $message ) );
		$return  = wp_mail( $to, $subject, $message, $headers, $attachments );

		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

		return $return;
	}

	/**
	 * Initialise Settings Form Fields - these are generic email options most will use.
	 */
	public function init_form_fields() {
		$this->form_fields    = array(
			'enabled'         => array(
				'title'       => __( 'Enable/Disable', 'online-restaurant-reservation' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable this email notification', 'online-restaurant-reservation' ),
				'default'     => 'yes',
			),
			'subject'         => array(
				'title'       => __( 'Subject', 'online-restaurant-reservation' ),
				'type'        => 'text',
				'desc_tip'      => true,
				/* translators: %s: list of placeholders */
				'description'   => sprintf( __( 'Available placeholders: %s', 'online-restaurant-reservation' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' ),
				'placeholder' => $this->get_default_subject(),
				'default'     => '',
			),
			'heading'         => array(
				'title'       => __( 'Email heading', 'online-restaurant-reservation' ),
				'type'        => 'text',
				'desc_tip'      => true,
				/* translators: %s: list of placeholders */
				'description'   => sprintf( __( 'Available placeholders: %s', 'online-restaurant-reservation' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' ),
				'placeholder' => $this->get_default_heading(),
				'default'     => '',
			),
			'email_type'      => array(
				'title'       => __( 'Email type', 'online-restaurant-reservation' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'online-restaurant-reservation' ),
				'default'     => 'html',
				'class'       => 'email_type orr-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Email type options.
	 * @return array
	 */
	public function get_email_type_options() {
		$types = array( 'plain' => __( 'Plain text', 'online-restaurant-reservation' ) );

		if ( class_exists( 'DOMDocument' ) ) {
			$types['html']      = __( 'HTML', 'online-restaurant-reservation' );
			$types['multipart'] = __( 'Multipart', 'online-restaurant-reservation' );
		}

		return $types;
	}

	/**
	 * Admin Panel Options Processing.
	 */
	public function process_admin_options() {
		// Save regular options
		parent::process_admin_options();

		$post_data = $this->get_post_data();

		// Save templates
		if ( isset( $post_data['template_html_code'] ) ) {
			$this->save_template( $post_data['template_html_code'], $this->template_html );
		}
		if ( isset( $post_data['template_plain_code'] ) ) {
			$this->save_template( $post_data['template_plain_code'], $this->template_plain );
		}
	}

	/**
	 * Get template.
	 *
	 * @param  string $type
	 * @return string
	 */
	public function get_template( $type ) {
		$type = basename( $type );

		if ( 'template_html' === $type ) {
			return $this->template_html;
		} elseif ( 'template_plain' === $type ) {
			return $this->template_plain;
		}
		return '';
	}

	/**
	 * Save the email templates.
	 *
	 * @since 1.6.0
	 * @param string $template_code
	 * @param string $template_path
	 */
	protected function save_template( $template_code, $template_path ) {
		if ( current_user_can( 'edit_themes' ) && ! empty( $template_code ) && ! empty( $template_path ) ) {
			$saved  = false;
			$file   = get_stylesheet_directory() . '/restaurant-reservation/' . $template_path;
			$code   = wp_unslash( $template_code );

			if ( is_writeable( $file ) ) {
				$f = fopen( $file, 'w+' );

				if ( false !== $f ) {
					fwrite( $f, $code );
					fclose( $f );
					$saved = true;
				}
			}

			if ( ! $saved ) {
				$redirect = add_query_arg( 'orr_error', urlencode( __( 'Could not write to template file.', 'online-restaurant-reservation' ) ) );
				wp_safe_redirect( $redirect );
				exit;
			}
		}
	}

	/**
	 * Get the template file in the current theme.
	 *
	 * @param  string $template
	 *
	 * @return string
	 */
	public function get_theme_template_file( $template ) {
		return get_stylesheet_directory() . '/' . apply_filters( 'online_restaurant_reservation_template_directory', 'online_restaurant_reservation', $template ) . '/' . $template;
	}

	/**
	 * Move template action.
	 *
	 * @param string $template_type
	 */
	protected function move_template_action( $template_type ) {
		if ( $template = $this->get_template( $template_type ) ) {
			if ( ! empty( $template ) ) {

				$theme_file = $this->get_theme_template_file( $template );

				if ( wp_mkdir_p( dirname( $theme_file ) ) && ! file_exists( $theme_file ) ) {

					// Locate template file.
					$core_file     = $this->template_base . $template;
					$template_file = apply_filters( 'online_restaurant_reservation_locate_core_template', $core_file, $template, $this->template_base, $this->id );

					// Copy template file.
					copy( $template_file, $theme_file );

					/**
					 * online_restaurant_reservation_copy_email_template action hook.
					 *
					 * @param string $template_type The copied template type
					 * @param string $email The email object
					 */
					do_action( 'online_restaurant_reservation_copy_email_template', $template_type, $this );

					echo '<div class="updated"><p>' . __( 'Template file copied to theme.', 'online-restaurant-reservation' ) . '</p></div>';
				}
			}
		}
	}

	/**
	 * Delete template action.
	 *
	 * @param string $template_type
	 */
	protected function delete_template_action( $template_type ) {
		if ( $template = $this->get_template( $template_type ) ) {

			if ( ! empty( $template ) ) {

				$theme_file = $this->get_theme_template_file( $template );

				if ( file_exists( $theme_file ) ) {
					unlink( $theme_file );

					/**
					 * online_restaurant_reservation_delete_email_template action hook.
					 *
					 * @param string $template The deleted template type
					 * @param string $email The email object
					 */
					do_action( 'online_restaurant_reservation_delete_email_template', $template_type, $this );

					echo '<div class="updated"><p>' . __( 'Template file deleted from theme.', 'online-restaurant-reservation' ) . '</p></div>';
				}
			}
		}
	}

	/**
	 * Admin actions.
	 */
	protected function admin_actions() {
		// Handle any actions
		if (
			( ! empty( $this->template_html ) || ! empty( $this->template_plain ) )
			&& ( ! empty( $_GET['move_template'] ) || ! empty( $_GET['delete_template'] ) )
			&& 'GET' === $_SERVER['REQUEST_METHOD']
		) {
			if ( empty( $_GET['_orr_email_nonce'] ) || ! wp_verify_nonce( $_GET['_orr_email_nonce'], 'online_restaurant_reservation_email_template_nonce' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'online-restaurant-reservation' ) );
			}

			if ( ! current_user_can( 'edit_themes' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'online-restaurant-reservation' ) );
			}

			if ( ! empty( $_GET['move_template'] ) ) {
				$this->move_template_action( $_GET['move_template'] );
			}

			if ( ! empty( $_GET['delete_template'] ) ) {
				$this->delete_template_action( $_GET['delete_template'] );
			}
		}
	}

	/**
	 * Admin Options.
	 *
	 * Setup the email settings screen.
	 * Override this in your email.
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		// Do admin actions.
		$this->admin_actions();
		?>
		<h2><?php echo esc_html( $this->get_title() ); ?> <?php orr_back_link( __( 'Return to emails', 'online-restaurant-reservation' ), admin_url( 'admin.php?page=orr-settings&tab=email' ) ); ?></h2>

		<?php echo wpautop( wp_kses_post( $this->get_description() ) ); ?>

		<?php
			/**
			 * online_restaurant_reservation_email_settings_before action hook.
			 * @param string $email The email object
			 */
			do_action( 'online_restaurant_reservation_email_settings_before', $this );
		?>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>

		<?php
			/**
			 * online_restaurant_reservation_email_settings_after action hook.
			 * @param string $email The email object
			 */
			do_action( 'online_restaurant_reservation_email_settings_after', $this );
		?>

		<?php if ( current_user_can( 'edit_themes' ) && ( ! empty( $this->template_html ) || ! empty( $this->template_plain ) ) ) { ?>
			<div id="template">
			<?php
				$templates = array(
					'template_html'  => __( 'HTML template', 'online-restaurant-reservation' ),
					'template_plain' => __( 'Plain text template', 'online-restaurant-reservation' ),
				);

				foreach ( $templates as $template_type => $title ) :
					$template = $this->get_template( $template_type );

					if ( empty( $template ) ) {
						continue;
					}

					$local_file    = $this->get_theme_template_file( $template );
					$core_file     = $this->template_base . $template;
					$template_file = apply_filters( 'online_restaurant_reservation_locate_core_template', $core_file, $template, $this->template_base, $this->id );
					$template_dir  = apply_filters( 'online_restaurant_reservation_template_directory', 'restaurant-reservation', $template );
					?>
					<div class="template <?php echo $template_type; ?>">

						<h4><?php echo wp_kses_post( $title ); ?></h4>

						<?php if ( file_exists( $local_file ) ) { ?>

							<p>
								<a href="#" class="button toggle_editor"></a>

								<?php if ( is_writable( $local_file ) ) : ?>
									<a href="<?php echo esc_url( wp_nonce_url( remove_query_arg( array( 'move_template', 'saved' ), add_query_arg( 'delete_template', $template_type ) ), 'online_restaurant_reservation_email_template_nonce', '_orr_email_nonce' ) ); ?>" class="delete_template button"><?php _e( 'Delete template file', 'online-restaurant-reservation' ); ?></a>
								<?php endif; ?>

								<?php printf( __( 'This template has been overridden by your theme and can be found in: %s.', 'online-restaurant-reservation' ), '<code>' . trailingslashit( basename( get_stylesheet_directory() ) ) . $template_dir . '/' . $template . '</code>' ); ?>
							</p>

							<div class="editor" style="display:none">
								<textarea class="code" cols="25" rows="20" <?php if ( ! is_writable( $local_file ) ) : ?>readonly="readonly" disabled="disabled"<?php else : ?>data-name="<?php echo $template_type . '_code'; ?>"<?php endif; ?>><?php echo file_get_contents( $local_file ); ?></textarea>
							</div>

						<?php } elseif ( file_exists( $template_file ) ) { ?>

							<p>
								<a href="#" class="button toggle_editor"></a>

								<?php
									$emails_dir    = get_stylesheet_directory() . '/' . $template_dir . '/emails';
									$templates_dir = get_stylesheet_directory() . '/' . $template_dir;
									$theme_dir     = get_stylesheet_directory();

									if ( is_dir( $emails_dir ) ) {
										$target_dir = $emails_dir;
									} elseif ( is_dir( $templates_dir ) ) {
										$target_dir = $templates_dir;
									} else {
										$target_dir = $theme_dir;
									}

									if ( is_writable( $target_dir ) ) {
										?>
										<a href="<?php echo esc_url( wp_nonce_url( remove_query_arg( array( 'delete_template', 'saved' ), add_query_arg( 'move_template', $template_type ) ), 'online_restaurant_reservation_email_template_nonce', '_orr_email_nonce' ) ); ?>" class="button"><?php _e( 'Copy file to theme', 'online-restaurant-reservation' ); ?></a>
										<?php
									}
								?>

								<?php printf( __( 'To override and edit this email template copy %1$s to your theme folder: %2$s.', 'online-restaurant-reservation' ), '<code>' . plugin_basename( $template_file ) . '</code>', '<code>' . trailingslashit( basename( get_stylesheet_directory() ) ) . $template_dir . '/' . $template . '</code>' ); ?>
							</p>

							<div class="editor" style="display:none">
								<textarea class="code" readonly="readonly" disabled="disabled" cols="25" rows="20"><?php echo file_get_contents( $template_file ); ?></textarea>
							</div>

						<?php } else { ?>

							<p><?php _e( 'File was not found.', 'online-restaurant-reservation' ); ?></p>

						<?php } ?>

					</div>
					<?php
				endforeach;
			?>
			</div>
			<?php
			orr_enqueue_js( "
				jQuery( 'select.email_type' ).change( function() {

					var val = jQuery( this ).val();

					jQuery( '.template_plain, .template_html' ).show();

					if ( val != 'multipart' && val != 'html' ) {
						jQuery('.template_html').hide();
					}

					if ( val != 'multipart' && val != 'plain' ) {
						jQuery('.template_plain').hide();
					}

				}).change();

				var view = '" . esc_js( __( 'View template', 'online-restaurant-reservation' ) ) . "';
				var hide = '" . esc_js( __( 'Hide template', 'online-restaurant-reservation' ) ) . "';

				jQuery( 'a.toggle_editor' ).text( view ).toggle( function() {
					jQuery( this ).text( hide ).closest(' .template' ).find( '.editor' ).slideToggle();
					return false;
				}, function() {
					jQuery( this ).text( view ).closest( '.template' ).find( '.editor' ).slideToggle();
					return false;
				} );

				jQuery( 'a.delete_template' ).click( function() {
					if ( window.confirm('" . esc_js( __( 'Are you sure you want to delete this template file?', 'online-restaurant-reservation' ) ) . "') ) {
						return true;
					}

					return false;
				});

				jQuery( '.editor textarea' ).change( function() {
					var name = jQuery( this ).attr( 'data-name' );

					if ( name ) {
						jQuery( this ).attr( 'name', name );
					}
				});
			" );
		}
	}
}
