<?php
/**
 * Online Restaurant Reservation Email Settings
 *
 * @class    ORR_Settings_Emails
 * @version  1.0.0
 * @package  Online_Restaurant_Reservation/Admin
 * @category Admin
 * @author   WPEverest
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ORR_Settings_Emails', false ) ) :

/**
 * ORR_Settings_Emails Class.
 */
class ORR_Settings_Emails extends ORR_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'email';
		$this->label = __( 'Emails', 'online-restaurant-reservation' );

		add_action( 'online_restaurant_reservation_admin_field_email_notification', array( $this, 'email_notification_setting' ) );
		parent::__construct();
	}

	/**
	 * Add this page to settings.
	 *
	 * @param  array $pages Existing pages.
	 * @return array|mixed
	 */
	public function add_settings_page( $pages ) {
		return orr_mailer_enabled() ? parent::add_settings_page( $pages ) : $pages;
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		$sections = array(
			'' => __( 'Email options', 'online-restaurant-reservation' ),
		);
		return apply_filters( 'online_restaurant_reservation_get_sections_' . $this->id, $sections );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters( 'online_restaurant_reservation_email_settings', array(

			array( 'title' => __( 'Email notifications', 'online-restaurant-reservation' ),  'desc' => __( 'Email notifications sent from online restaurant reservation are listed below. Click on an email to configure it.', 'online-restaurant-reservation' ), 'type' => 'title', 'id' => 'email_notification_settings' ),

			array( 'type' => 'email_notification' ),

			array( 'type' => 'sectionend', 'id' => 'email_notification_settings' ),

			array( 'type' => 'sectionend', 'id' => 'email_recipient_options' ),

			array( 'title' => __( 'Email sender options', 'online-restaurant-reservation' ), 'type' => 'title', 'desc' => '', 'id' => 'email_options' ),

			array(
				'title'    => __( '"From" name', 'online-restaurant-reservation' ),
				'desc'     => __( 'How the sender name appears in outgoing online restaurant reservation emails.', 'online-restaurant-reservation' ),
				'id'       => 'online_restaurant_reservation_email_from_name',
				'type'     => 'text',
				'css'      => 'min-width:300px;',
				'default'  => esc_attr( get_bloginfo( 'name', 'display' ) ),
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'             => __( '"From" address', 'online-restaurant-reservation' ),
				'desc'              => __( 'How the sender email appears in outgoing online restaurant reservation emails.', 'online-restaurant-reservation' ),
				'id'                => 'online_restaurant_reservation_email_from_address',
				'type'              => 'email',
				'custom_attributes' => array(
					'multiple' => 'multiple',
				),
				'css'               => 'min-width:300px;',
				'default'           => get_option( 'admin_email' ),
				'autoload'          => false,
				'desc_tip'          => true,
			),

			array( 'type' => 'sectionend', 'id' => 'email_options' ),

			array( 'title' => __( 'Email template', 'online-restaurant-reservation' ), 'type' => 'title', 'desc' => sprintf( __( 'This section lets you customize the online restaurant reservation emails. <a href="%s" target="_blank">Click here to preview your email template</a>.', 'online-restaurant-reservation' ), wp_nonce_url( admin_url( '?preview_online_restaurant_reservation_mail=true' ), 'preview-mail' ) ), 'id' => 'email_template_options' ),

			array(
				'title'       => __( 'Header image', 'online-restaurant-reservation' ),
				'desc'        => __( 'URL to an image you want to show in the email header. Upload images using the media uploader (Admin > Media).', 'online-restaurant-reservation' ),
				'id'          => 'online_restaurant_reservation_email_header_image',
				'type'        => 'text',
				'css'         => 'min-width:300px;',
				'placeholder' => __( 'N/A', 'online-restaurant-reservation' ),
				'default'     => '',
				'autoload'    => false,
				'desc_tip'    => true,
			),

			array(
				'title'       => __( 'Footer text', 'online-restaurant-reservation' ),
				'desc'        => sprintf( __( 'The text to appear in the footer of online restaurant reservation emails. Available placeholders: %s', 'online-restaurant-reservation' ), '{site_title}' ),
				'id'          => 'online_restaurant_reservation_email_footer_text',
				'css'         => 'width:300px; height: 75px;',
				'placeholder' => __( 'N/A', 'online-restaurant-reservation' ),
				'type'        => 'textarea',
				'default'     => '{site_title}',
				'autoload'    => false,
				'desc_tip'    => true,
			),

			array(
				'title'    => __( 'Base color', 'online-restaurant-reservation' ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The base color for online restaurant reservation email templates. Default %s.', 'online-restaurant-reservation' ), '<code>#d54e21</code>' ),
				'id'       => 'online_restaurant_reservation_email_base_color',
				'type'     => 'color',
				'css'      => 'width:6em;',
				'default'  => '#d54e21',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Background color', 'online-restaurant-reservation' ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The background color for online restaurant reservation email templates. Default %s.', 'online-restaurant-reservation' ), '<code>#f7f7f7</code>' ),
				'id'       => 'online_restaurant_reservation_email_background_color',
				'type'     => 'color',
				'css'      => 'width:6em;',
				'default'  => '#f7f7f7',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Body background color', 'online-restaurant-reservation' ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The main body background color. Default %s.', 'online-restaurant-reservation' ), '<code>#ffffff</code>' ),
				'id'       => 'online_restaurant_reservation_email_body_background_color',
				'type'     => 'color',
				'css'      => 'width:6em;',
				'default'  => '#ffffff',
				'autoload' => false,
				'desc_tip' => true,
			),

			array(
				'title'    => __( 'Body text color', 'online-restaurant-reservation' ),
				/* translators: %s: default color */
				'desc'     => sprintf( __( 'The main body text color. Default %s.', 'online-restaurant-reservation' ), '<code>#3c3c3c</code>' ),
				'id'       => 'online_restaurant_reservation_email_text_color',
				'type'     => 'color',
				'css'      => 'width:6em;',
				'default'  => '#3c3c3c',
				'autoload' => false,
				'desc_tip' => true,
			),

			array( 'type' => 'sectionend', 'id' => 'email_template_options' ),

		) );

		return apply_filters( 'online_restaurant_reservation_get_settings_' . $this->id, $settings );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		// Define emails that can be customised here.
		$mailer          = ORR()->mailer();
		$email_templates = $mailer->get_emails();

		if ( $current_section ) {
			foreach ( $email_templates as $email_key => $email ) {
				if ( strtolower( $email_key ) == $current_section ) {
					$email->admin_options();
					break;
				}
			}
		} else {
			$settings = $this->get_settings();
			ORR_Admin_Settings::output_fields( $settings );
		}
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		if ( ! $current_section ) {
			ORR_Admin_Settings::save_fields( $this->get_settings() );

		} else {
			$orr_emails = ORR_Emails::instance();

			if ( in_array( $current_section, array_map( 'sanitize_title', array_keys( $orr_emails->get_emails() ) ) ) ) {
				foreach ( $orr_emails->get_emails() as $email_id => $email ) {
					if ( sanitize_title( $email_id ) === $current_section ) {
						do_action( 'online_restaurant_reservation_update_options_' . $this->id . '_' . $email->id );
					}
				}
			} else {
				do_action( 'online_restaurant_reservation_update_options_' . $this->id . '_' . $current_section );
			}
		}
	}

	/**
	 * Output email notification settings.
	 */
	public function email_notification_setting() {
		// Define emails that can be customised here.
		$mailer          = ORR()->mailer();
		$email_templates = $mailer->get_emails();
		?>
		<tr valign="top">
		    <td class="orr_emails_wrapper" colspan="2">
				<table class="orr_emails widefat" cellspacing="0">
					<thead>
						<tr>
							<?php
								$columns = apply_filters( 'online_restaurant_reservation_email_setting_columns', array(
									'status'     => '',
									'name'       => __( 'Email', 'online-restaurant-reservation' ),
									'email_type' => __( 'Content type', 'online-restaurant-reservation' ),
									'recipient'  => __( 'Recipient(s)', 'online-restaurant-reservation' ),
									'actions'    => '',
								) );
								foreach ( $columns as $key => $column ) {
									echo '<th class="orr-email-settings-table-' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>';
								}
							?>
						</tr>
					</thead>
					<tbody>
						<?php
							if ( ! empty( $email_templates ) ) {
								foreach ( $email_templates as $email_key => $email ) {
									echo '<tr>';

									foreach ( $columns as $key => $column ) {

										switch ( $key ) {
											case 'name' :
												echo '<td class="orr-email-settings-table-' . esc_attr( $key ) . '">
													<a href="' . admin_url( 'admin.php?page=orr-settings&tab=email&section=' . strtolower( $email_key ) ) . '">' . $email->get_title() . '</a>
													' . orr_help_tip( $email->get_description() ) . '
												</td>';
											break;
											case 'recipient' :
												echo '<td class="orr-email-settings-table-' . esc_attr( $key ) . '">
													' . esc_html( $email->is_customer_email() ? __( 'Customer', 'online-restaurant-reservation' ) : $email->get_recipient() ) . '
												</td>';
											break;
											case 'status' :
												echo '<td class="orr-email-settings-table-' . esc_attr( $key ) . '">';

												if ( $email->is_manual() ) {
													echo '<span class="status-manual tips" data-tip="' . esc_attr__( 'Manually sent', 'online-restaurant-reservation' ) . '">' . esc_html__( 'Manual', 'online-restaurant-reservation' ) . '</span>';
												} elseif ( $email->is_enabled() ) {
													echo '<span class="status-enabled tips" data-tip="' . esc_attr__( 'Enabled', 'online-restaurant-reservation' ) . '">' . esc_html__( 'Yes', 'online-restaurant-reservation' ) . '</span>';
												} else {
													echo '<span class="status-disabled tips" data-tip="' . esc_attr__( 'Disabled', 'online-restaurant-reservation' ) . '">-</span>';
												}

												echo '</td>';
											break;
											case 'email_type' :
												echo '<td class="orr-email-settings-table-' . esc_attr( $key ) . '">
													' . esc_html( $email->get_content_type() ) . '
												</td>';
											break;
											case 'actions' :
												echo '<td class="orr-email-settings-table-' . esc_attr( $key ) . '">
													<a class="button alignright tips" data-tip="' . esc_attr__( 'Configure', 'online-restaurant-reservation' ) . '" href="' . admin_url( 'admin.php?page=orr-settings&tab=email&section=' . strtolower( $email_key ) ) . '">' . esc_html__( 'Configure', 'online-restaurant-reservation' ) . '</a>
												</td>';
											break;
											default :
												do_action( 'online_restaurant_reservation_email_setting_column_' . $key, $email );
											break;
										}
									}

									echo '</tr>';
								}
							} else { ?>
								<td class="orr-email-blank-state" colspan="5"><?php _e( 'No notificational email has been configured.', 'online-restaurant-reservation' ); ?></td>
							<?php } ?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
	}
}

endif;

return new ORR_Settings_Emails();
