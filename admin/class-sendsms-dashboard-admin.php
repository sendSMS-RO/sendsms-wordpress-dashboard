<?php
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib' . DIRECTORY_SEPARATOR . 'sendsms.class.php';
require_once plugin_dir_path( dirname( __FILE__ ) ) . 'lib' . DIRECTORY_SEPARATOR . 'functions.php';
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Sendsms_Dashboard
 * @subpackage Sendsms_Dashboard/admin
 * @author     sendSMS <support@sendsms.ro>
 */
class Sendsms_Dashboard_Admin {










	/**
	 * The ID of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string    $version    The current version of this plugin.
	 */
	private $version;

	private $functions;
	private $api;
	// this will keep all auth cookies we will need de invalidate
	private $password_auth_tokens = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->functions   = new SendSMSFunctions();
		$this->api         = new SendSMS();
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/sendsms-dashboard-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( $this->plugin_name . '-jBox', plugin_dir_url( __FILE__ ) . 'css/jBox.all.min.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		 wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/sendsms-dashboard-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name . '-font-awesome', plugin_dir_url( __FILE__ ) . 'js/all.min.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( $this->plugin_name . '-jBox', plugin_dir_url( __FILE__ ) . 'js/jBox.all.min.js', array( 'jquery' ), $this->version, false );
		wp_localize_script(
			$this->plugin_name,
			'sendsms_object',
			array(
				'sendsms_ajax_url'                => admin_url( 'admin-ajax.php' ),
				'security'                        => wp_create_nonce( 'sendsms-security-nonce' ),
				'text_message_contains_something' => __( 'The approximate number of messages: ', 'sendsms-dashboard' ),
				'text_message_is_empty'           => __( 'The field is empty', 'sendsms-dashboard' ),
				'text_button_sending'             => __( 'It\'s being sent...', 'sendsms-dashboard' ),
				'text_button_send'                => __( 'Send Message', 'sendsms-dashboard' ),
				'text_invalid_security_nonce'     => __( 'Invalid security token sent.', 'sendsms-dashboard' ),
				'text_internal_error'             => __( 'Internal error.', 'sendsms-dashboard' ),
				'text_invalid_phone_number'       => __( 'Please enter a valid phone number.', 'sendsms-dashboard' ),
				'text_invalid_first_name'         => __( 'Please enter a valid first name.', 'sendsms-dashboard' ),
				'text_invalid_last_name'          => __( 'Please enter a valid last name.', 'sendsms-dashboard' ),
				'text_invalid_date'               => __( 'Please enter a valid date.', 'sendsms-dashboard' ),
				'text_update_subscriber_success'  => __( 'The subscriber has been updated', 'sendsms-dashboard' ),
				'text_invalid_ip_address'         => __( 'Please enter a valid IP Address', 'sendsms-dashboard' ),
				'text_contacts_synced'            => __( 'Contacts synchronized successfully', 'sendsms-dashboard' ),
				'text_empty_fields'               => __( 'Some fields are empty', 'sendsms-dashboard' ),
				'text_cookie_expired'             => __( 'The verification code has expired. Please refresh the page and try again.', 'sendsms-dashboard' ),
			)
		);
	}

	/**
	 * Loads the menu file
	 *
	 * @since 1.0.0
	 */
	public function load_menu() {
		add_menu_page(
			__( 'SendSMS Dashboard', 'sendsms-dashboard' ),
			__( 'SendSMS Dashboard', 'sendsms-dashboard' ),
			'manage_options',
			$this->plugin_name,
			array( $this, 'page_settings' ),
			plugin_dir_url( __FILE__ ) . 'img/sendsms-dashboard-setting.png'
		);
		// this will add a submenu
		add_submenu_page(
			$this->plugin_name,
			__( 'Send a test', 'sendsms-dashboard' ),
			__( 'Send a test SMS', 'sendsms-dashboard' ),
			'manage_options',
			$this->plugin_name . '_send_a_test',
			array( $this, 'page_test' )
		);
		add_submenu_page(
			$this->plugin_name,
			__( 'History', 'sendsms-dashboard' ),
			__( 'History', 'sendsms-dashboard' ),
			'manage_options',
			$this->plugin_name . '_history',
			array( $this, 'page_history' )
		);
		add_submenu_page(
			$this->plugin_name,
			__( 'Subscribers', 'sendsms-dashboard' ),
			__( 'Subscribers', 'sendsms-dashboard' ),
			'manage_options',
			$this->plugin_name . '_subscribers',
			array( $this, 'page_subscribers' )
		);
		add_submenu_page(
			$this->plugin_name,
			__( 'SMS sending', 'sendsms-dashboard' ),
			__( 'SMS sending', 'sendsms-dashboard' ),
			'manage_options',
			$this->plugin_name . '_sms_sending',
			array( $this, 'page_sms_sending' )
		);
	}

	/**
	 * Register setting fields
	 *
	 * @since 1.0.0
	 */
	public function load_settings() {
		register_setting(
			'sendsms_dashboard_plugin_settings',
			'sendsms_dashboard_plugin_settings',
			array( $this, 'sendsms_dashboard_settings_sanitize' )
		);

		add_settings_section(
			'sendsms_dashboard_general',
			"<div class='sendsms-settings-title'>" . __( 'General Settings', 'sendsms-dashboard' ) . '</div>',
			array( $this, 'sendsms_dashboard_section_callback' ),
			'sendsms_dashboard_plugin_general'
		);

		add_settings_field(
			'sendsms_dashboard_username',
			__( 'SendSMS Username', 'sendsms-dashboard' ),
			array( $this, 'sendsms_dashboard_setting_username_callback' ),
			'sendsms_dashboard_plugin_general',
			'sendsms_dashboard_general'
		);

		add_settings_field(
			'sendsms_dashboard_password',
			__( 'SendSMS Password / Api Key', 'sendsms-dashboard' ),
			array( $this, 'sendsms_dashboard_setting_password_callback' ),
			'sendsms_dashboard_plugin_general',
			'sendsms_dashboard_general'
		);

		add_settings_field(
			'sendsms_dashboard_label',
			__( 'SendSMS Label', 'sendsms-dashboard' ),
			array( $this, 'sendsms_dashboard_setting_label_callback' ),
			'sendsms_dashboard_plugin_general',
			'sendsms_dashboard_general'
		);

		add_settings_field(
			'sendsms_dashboard_cc',
			__( 'Country Code', 'sendsms-dashboard' ),
			array( $this, 'sendsms_dashboard_setting_cc_callback' ),
			'sendsms_dashboard_plugin_general',
			'sendsms_dashboard_general'
		);

		add_settings_section(
			'sendsms_dashboard_user',
			"<div class='sendsms-settings-title'>" . __( 'User Settings', 'sendsms-dashboard' ) . '</div>',
			array( $this, 'sendsms_dashboard_section_user_callback' ),
			'sendsms_dashboard_plugin_user'
		);

		add_settings_field(
			'sendsms_dashboard_user_add_phone_field',
			__( 'Add phone number field / Enable 2fa? IMPORTANT:This is designed only with the default wp login form (wp-admin) in mind. It may break if you have another login system in place. Test it in a development environment first.', 'sendsms-dashboard' ),
			array( $this, 'sendsms_dashboard_user_add_phone_field_callback' ),
			'sendsms_dashboard_plugin_user',
			'sendsms_dashboard_user'
		);

		add_settings_field(
			'sendsms_dashboard_user_2fa_roles',
			__( 'Enable 2fa for the following roles', 'sendsms-dashboard' ),
			array( $this, 'sendsms_dashboard_user_2fa_roles_callback' ),
			'sendsms_dashboard_plugin_user',
			'sendsms_dashboard_user'
		);

		add_settings_field(
			'sendsms_dashboard_2fa_verification_message_field',
			__( 'Two-factor authentication verification message', 'sendsms-dashboard' ),
			array( $this, 'sendsms_dashboard_2fa_verification_message_field_callback' ),
			'sendsms_dashboard_plugin_user',
			'sendsms_dashboard_user'
		);

		add_settings_field(
			'sendsms_dashboard_phone_meta',
			__( 'Phone metadata list', 'sendsms-dashboard' ),
			array( $this, 'sendsms_dashboard_phone_meta_field_callback' ),
			'sendsms_dashboard_plugin_user',
			'sendsms_dashboard_user'
		);

		add_settings_section(
			'sendsms_dashboard_subscription',
			"<div class='sendsms-settings-title'>" . __( 'Subscription Settings', 'sendsms-dashboard' ) . '</div>',
			array( $this, 'sendsms_dashboard_section_subscription_callback' ),
			'sendsms_dashboard_plugin_subscription'
		);

		add_settings_field(
			'sendsms_dashboard_subscribe_phone_verification_field',
			__( 'SMS verification?', 'sendsms-dashboard' ),
			array( $this, 'sendsms_dashboard_subscribe_phone_verification_field_callback' ),
			'sendsms_dashboard_plugin_subscription',
			'sendsms_dashboard_subscription'
		);

		add_settings_field(
			'sendsms_dashboard_subscribe_verification_message_field',
			__( 'Verification message', 'sendsms-dashboard' ),
			array( $this, 'sendsms_dashboard_subscribe_verification_message_field_callback' ),
			'sendsms_dashboard_plugin_subscription',
			'sendsms_dashboard_subscription'
		);

		add_settings_field(
			'sendsms_dashboard_ip_limits_field',
			__( 'IP limit', 'sendsms-dashboard' ),
			array( $this, 'sendsms_dashboard_ip_limit_field_callback' ),
			'sendsms_dashboard_plugin_subscription',
			'sendsms_dashboard_subscription'
		);

		add_settings_field(
			'sendsms_dashboard_restricted_ips_field',
			__( 'Restricted IP addresses', 'sendsms-dashboard' ),
			array( $this, 'sendsms_dashboard_restricted_ips_field_callback' ),
			'sendsms_dashboard_plugin_subscription',
			'sendsms_dashboard_subscription'
		);
	}

	/**
	 * Sanitize the settings before they are saved to the db
	 */
	public function sendsms_dashboard_settings_sanitize( $args ) {
		foreach ( $args as $key => $value ) {
			switch ( $key ) {
				case 'password':
					$args[ $key ] = trim( $value );
					break;
				case 'store_type':
					$args[ $key ] = 1;
					break;
			}
		}
		return $args;
	}


	public function page_history() {
		include plugin_dir_path( __FILE__ ) . 'partials/sendsms-dashboard-history-admin-display.php';
	}

	public function page_subscribers() {
		include plugin_dir_path( __FILE__ ) . 'partials/sendsms-dashboard-subscribers-admin-display.php';
	}

	public function page_test() {
		include plugin_dir_path( __FILE__ ) . 'partials/sendsms-dashboard-test-admin-display.php';
	}

	public function page_sms_sending() {
		include plugin_dir_path( __FILE__ ) . 'partials/sendsms-dashboard-sms-sending-admin-display.php';
	}

	// Ajax handler
	/**
	 * This will synchronize all the subscribers to sendsms.ro
	 *
	 * @since 1.0.0
	 */
	public function synchronize_contacts() {
		if ( ! check_ajax_referer( 'sendsms-security-nonce', 'security', false ) ) {
			wp_send_json_error( 'invalid_security_nonce' );
			wp_die();
		}
		// if there is no assigned group
		$id = get_option( 'sendsms-dashboard-sync-group' );
		if ( ! $id ) {
			$result = $this->api->create_group();
			if ( $result['status'] < 0 ) {
				wp_send_json_error( 'internal_error' );
				wp_die();
			}
			$id = $result['details'];
		}
		// if there is an assigned group, check if it exists
		if ( $id ) {
			$group_list = $this->api->get_groups();
			if ( $group_list['status'] < 0 ) {
				wp_send_json_error( 'internal_error' );
				wp_die();
			}
			$group_list = $group_list['details'];
			$found      = false;
			foreach ( $group_list as $group ) {
				if ( $group['id'] == $id ) {
					$found = true;
				}
			}
			if ( ! $found ) {
				$id = $this->api->create_group()['details'];
			}
		}
		$subscribers = $this->functions->get_subscribers_db();
		foreach ( $subscribers as $subscriber ) {
			if ( ! $found || ( $found && is_null( $subscriber['synced'] ) ) ) {
				$result = $this->api->add_contact( $id, $subscriber['last_name'], $subscriber['first_name'], $subscriber['phone'] );
				if ( $result['status'] < 0 ) {
					wp_send_json_error( 'internal_error' );
					wp_die();
				}
				$this->functions->update_subscriber_sync_db( $subscriber['phone'], $result['details'] );
			}
		}
		update_option( 'sendsms-dashboard-sync-group', $id );
		wp_send_json_success( 'contacts_synced' );
	}
	/**
	 * This will update the subscriber
	 *
	 * @since 1.0.0
	 */
	public function update_a_subscriber() {
		if ( ! check_ajax_referer( 'sendsms-security-nonce', 'security', false ) ) {
			wp_send_json_error( 'invalid_security_nonce' );
			wp_die();
		}
		$old_phone = $this->functions->validate_phone( $_POST['old_phone'] );
		if ( ! isset( $old_phone ) || is_null( $old_phone ) || ! $this->functions->is_subscriber_db( $old_phone ) ) {
			wp_send_json_error( 'internal_error' );
			wp_die();
		}
		$phone     = $this->functions->validate_phone( $_POST['phone'] );
		$validDate = $this->functions->validate_date( str_replace( 'T', ' ', $_POST['date'] ), 'Y-m-d H:i:s' );
		if ( empty( $phone ) ) {
			wp_send_json_error( 'invalid_phone_number' );
			wp_die();
		}

		$first_name = wp_unslash(
			sanitize_text_field( $_POST['first_name'] )
		);
		if ( empty( $first_name ) ) {
			wp_send_json_error( 'invalid_first_name' );
			wp_die();
		}

		$last_name = wp_unslash(
			sanitize_text_field( $_POST['last_name'] )
		);
		if ( empty( $last_name ) ) {
			wp_send_json_error( 'invalid_last_name' );
			wp_die();
		}
		if ( ! $validDate ) {
			wp_send_json_error( 'invalid_date' );
			wp_die();
		}
		$date       = str_replace(
			'T',
			' ',
			sanitize_text_field( $_POST['date'] )
		);
		$ip_address = '';
		if ( isset( $_POST['ip_address'] ) ) {
			$ip_address = rest_is_ip_address( $_POST['ip_address'] );
			if ( ! $ip_address ) {
				wp_send_json_error( 'invalid_ip_address' );
				wp_die();
			}
		}
		$browser = wp_unslash(
			sanitize_text_field( $_POST['browser'] )
		);
		$this->functions->update_subscriber_db( $old_phone, $phone, $first_name, $last_name, $date, $ip_address, $browser );
		wp_send_json_success(
			array(
				'info'     => 'update_subscriber_success',
				'new_data' => array(
					'phone'      => esc_html( $phone ),
					'first_name' => esc_html( $first_name ),
					'last_name'  => esc_html( $last_name ),
					'date'       => esc_html( $date ),
					'ip_address' => esc_html( $ip_address ),
					'browser'    => esc_html( $browser ),
				),
			)
		);
	}
	/**
	 * This will send a test message when an ajax event is called
	 */
	public function send_a_test_sms() {
		if ( ! check_ajax_referer( 'sendsms-security-nonce', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid security token sent.', 'sendsms-dashboard' ) );
			wp_die();
		}
		if ( empty( $_POST['message'] ) ) {
			wp_send_json_error( __( 'The message box is empty', 'sendsms-dashboard' ) );
		}
		$result = $this->api->message_send(
			$_POST['short'] == 'true' ? true : false,
			$_POST['gdpr'] == 'true' ? true : false,
			isset( $_POST['phone_number'] ) ? wp_unslash( sanitize_text_field( $_POST['phone_number'] ) ) : '',
			isset( $_POST['message'] ) ? wp_unslash( sanitize_text_field( $_POST['message'] ) ) : '',
			'TEST'
		);
		if ( $result['status'] > 0 ) {
			wp_send_json_success( __( 'Message sent', 'sendsms-dashboard' ) );
		} else {
			wp_send_json_error( __( 'Status: ', 'sendsms-dashboard' ) . ( isset( $result['status'] ) ? $result['status'] : '' ) . __( '<br>Message: ', 'sendsms-dashboard' ) . ( isset( $result['message'] ) ? $result['message'] : '' ) . __( '<br>Details: ', 'sendsms-dashboard' ) . ( isset( $result['details'] ) ? $result['details'] : '' ) );
		}
	}

	/**
	 * This will send mass SMS
	 */
	public function send_mass_sms() {
		if ( ! check_ajax_referer( 'sendsms-security-nonce', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid security token sent.', 'sendsms-dashboard' ) );
			wp_die();
		}
		if ( empty( $_POST['message'] ) ) {
			wp_send_json_error( __( 'The message box is empty', 'sendsms-dashboard' ) );
		}
		$phones = array();
		if ( $_POST['receivers_type'] === 'subscribers' ) {
			$phonesAux = $this->functions->get_all_phones_subscriber_db();
			foreach ( $phonesAux as $phoneAux ) {
				$phones[] = $phoneAux['phone'];
			}
		} else {
			$args  = array(
				'role'   => $_POST['role'] === 'all' ? '' : (string) ( sanitize_text_field( $_POST['role'] ) ),
				'fields' => array( 'ID' ),
			);
			$users = get_users( $args );
			foreach ( $users as $user ) {
				$phone = $this->functions->get_user_phone( $user->ID );
				if ( ! empty( $phone ) ) {
					$phones[] = $phone;
				}
			}
		}
		$phones = array_unique( $phones );
		if ( empty( $phones ) ) {
			wp_send_json_error( __( 'We were unable to find any phone numbers', 'sendsms-dashboard' ) );
		}
		$result = $this->api->send_batch(
			$phones,
			isset( $_POST['message'] ) ? wp_unslash( sanitize_text_field( $_POST['message'] ) ) : ''
		);
		if ( $result['status'] === 0 ) {
			wp_send_json_success( __( 'Message sent', 'sendsms-dashboard' ) );
		} else {
			if ( is_array( $result ) ) {
				wp_send_json_error( __( 'Status: ', 'sendsms-dashboard' ) . ( isset( $result['status'] ) ? $result['status'] : '' ) . __( '<br>Message: ', 'sendsms-dashboard' ) . ( isset( $result['message'] ) ? $result['message'] : '' ) . __( '<br>Details: ', 'sendsms-dashboard' ) . ( isset( $result['details'] ) ? $result['details'] : '' ) );
			} else {
				wp_send_json_error( $result );
			}
		}
	}

	/**
	 * Add a new contact from admin view interface
	 *
	 * @since 1.0.0
	 */
	public function add_new_subscriber() {
		if ( ! check_ajax_referer( 'sendsms-security-nonce', 'security', false ) ) {
			wp_send_json_error( __( 'Invalid security token sent.', 'sendsms-dashboard' ) );
			wp_die();
		}
	}

	public function page_settings() {
		include plugin_dir_path( __FILE__ ) . 'partials/sendsms-dashboard-settings-admin-display.php';
	}

	/**
	 * These are the callbacks to each section
	 */
	public function sendsms_dashboard_section_callback( $args ) {
	}

	public function sendsms_dashboard_section_user_callback( $args ) {
	}

	public function sendsms_dashboard_section_subscription_callback( $args ) {
	}
	// Field creators
	/**
	 * There functions will just display the fields of the settings page
	 */
	public function sendsms_dashboard_setting_username_callback( $args ) {
		$setting = $this->functions->get_setting_esc( 'username' );
		?>
		<input type="text" name="sendsms_dashboard_plugin_settings[username]" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
		<?php
	}

	public function sendsms_dashboard_setting_password_callback( $args ) {
		$setting = $this->functions->get_setting_esc( 'password' );
		?>
		<input type="password" name="sendsms_dashboard_plugin_settings[password]" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
		<?php
	}

	public function sendsms_dashboard_setting_label_callback( $args ) {
		$setting = $this->functions->get_setting_esc( 'label', '1898' );
		?>
		<input type="text" name="sendsms_dashboard_plugin_settings[label]" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
		<?php
	}

	public function sendsms_dashboard_setting_cc_callback( $args ) {
		$setting = $this->functions->get_setting_esc( 'cc', 'INT' );
		?>
		<select type="checkbox" name="sendsms_dashboard_plugin_settings[cc]">
			<option value="INT">International</option>
			<?php
			foreach ( $this->functions->country_codes as $key => $value ) {
				echo '<option value="' . esc_attr( $key ) . ( $setting == $key ? '" selected>' : '">' ) . esc_html( $key ) . '(+' . esc_html( $value ) . ')</option>';
			}
			?>
		</select>
		<?php
	}

	public function sendsms_dashboard_user_add_phone_field_callback( $args ) {
		$setting = $this->functions->get_setting_esc( 'add_phone_field', false );
		?>
		<input type="checkbox" name="sendsms_dashboard_plugin_settings[add_phone_field]" value="1" <?php echo $setting ? 'checked' : ''; ?>>
		<p class="sendsms-dashboard-subscript"><?php echo __( 'Add a phone number field in the user editing form and activate the 2fa feature. You can disable the 2fa feature by unchecking every role, but you cannot use 2fa without this setting. A user must have a phone number, or they will be required to add one.', 'sendsms-dashboard' ); ?></p>
		<?php
	}

	public function sendsms_dashboard_user_2fa_roles_callback( $args ) {
		$setting = $this->functions->get_setting( '2fa_roles', array() );
		$roles   = get_editable_roles();
		foreach ( $roles as $key => $value ) {
			?>
			<div style="display: block;">
				<label>
					<input style="margin-top: 0px; margin-right: 5px;" type="checkbox" name="sendsms_dashboard_plugin_settings[2fa_roles][<?php echo esc_attr( $key ); ?>]" value="1" <?php echo ( array_key_exists( $key, $setting ) && $setting[ $key ] ) == '1' ? 'checked' : ''; ?>>
					<?php echo esc_html( $value['name'] ); ?>
				</label>
			</div>
			<?php
		}
	}

	public function sendsms_dashboard_ip_limit_field_callback( $args ) {
		$setting = $this->functions->get_setting_esc( 'ip_limit', '' );
		?>
		<input type="text" name="sendsms_dashboard_plugin_settings[ip_limit]" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
		<p class="sendsms-dashboard-subscript"><?php echo __( 'The maximum number of subscriptions/unsubscriptions an IP address can make per minute. This is used as follows: maximum_ip_addresses/minutes (eg: 5/10 - 5 maximum registrations every 10 minutes). You can use -1 for no restrictions (eg: 5/-1 - 5 maximum registrations on that ip). No restriction will be applied if the field is empty or if it has invalid characters.', 'sendsms-dashboard' ); ?></p>
		<?php
	}

	public function sendsms_dashboard_restricted_ips_field_callback( $args ) {
		$setting = $this->functions->get_setting_esc( 'restricted_ips', '' );
		?>
		<textarea cols="30" rows="5" name="sendsms_dashboard_plugin_settings[restricted_ips]"><?php echo isset( $setting ) ? esc_textarea( $setting ) : ''; ?></textarea>
		<p class="sendsms-dashboard-subscript"><?php echo __( 'These ip addresses will not be able to register subscribe/unsubscribe. Put every IP address on a separated line.', 'sendsms-dashboard' ); ?></p>
		<?php
	}

	public function sendsms_dashboard_phone_meta_field_callback( $args ) {
		$setting = $this->functions->get_setting_esc( 'phone_meta', '' );
		?>
		<textarea cols="30" rows="5" name="sendsms_dashboard_plugin_settings[phone_meta]"><?php echo isset( $setting ) ? esc_textarea( $setting ) : ''; ?></textarea>
		<p class="sendsms-dashboard-subscript"><?php echo __( 'Here you are able to add phone number meta key from "userdata" table. The default is set to sendsms_phone_number but you can change it if you want to use another phone number field. Any input here, will override the default value. If you want to use multiple phone fields, add them on a separated line and we will query for the first valid phone number.', 'sendsms-dashboard' ); ?></p>
		<?php
	}

	public function sendsms_dashboard_subscribe_verification_message_field_callback( $args ) {
		$setting = $this->functions->get_setting_esc( 'subscribe_verification_message', '' );
		?>
		<textarea cols="30" rows="5" name="sendsms_dashboard_plugin_settings[subscribe_verification_message]"><?php echo isset( $setting ) ? esc_textarea( $setting ) : ''; ?></textarea>
		<p class="sendsms-dashboard-subscript"><?php echo __( 'You must specify the {code} key message. The {code} key will be automatically replaced with the unique validation code. If the {code} key is not specified, the validation code will be placed at the end of the message', 'sendsms-dashboard' ); ?></p>
		<?php
	}

	public function sendsms_dashboard_2fa_verification_message_field_callback( $args ) {
		$setting = $this->functions->get_setting_esc( '2fa_verification_message', '' );
		?>
		<textarea cols="30" rows="5" name="sendsms_dashboard_plugin_settings[2fa_verification_message]"><?php echo isset( $setting ) ? esc_textarea( $setting ) : ''; ?></textarea>
		<p class="sendsms-dashboard-subscript"><?php echo __( 'You must specify the {code} key message. The {code} key will be automatically replaced with the unique validation code. If the {code} key is not specified, the validation code will be placed at the end of the message', 'sendsms-dashboard' ); ?></p>
		<?php
	}

	public function sendsms_dashboard_subscribe_phone_verification_field_callback( $args ) {
		$setting = $this->functions->get_setting_esc( 'subscribe_phone_verification', false );
		?>
		<input type="checkbox" name="sendsms_dashboard_plugin_settings[subscribe_phone_verification]" value="1" <?php echo $setting ? 'checked' : ''; ?>>
		<p class="sendsms-dashboard-subscript"><?php echo __( 'This will send a verification code when someone subscribe/unsubscribe', 'sendsms-dashboard' ); ?></p>
		<?php
	}

	// EO SETTINGS PAGE

	/**
	 * Add the phone number field inside the add new user
	 *
	 * @since 1.0.0
	 */
	public function add_new_user_field() {
		include plugin_dir_path( __FILE__ ) . 'partials/user/sendsms-dashboard-mobile-field.php';
	}

	/**
	 * Save the phone number to db
	 *
	 * @since 1.0.0
	 */
	public function user_register_metadata( $user_id ) {
		if ( isset( $_POST['sendsms_phone_number'] ) ) {
			update_user_meta( $user_id, 'sendsms_phone_number', sanitize_text_field( $_POST['sendsms_phone_number'] ) );
		}
	}

	/**
	 * Show the phone number field in the editing page of an user
	 *
	 * @since 1.0.0
	 */
	public function add_new_user_field_to_edit_form( $args ) {
		$fields['sendsms_phone_number'] = __( 'Phone number (required for sendSMS 2fa)', 'sendsms-dashboard' );
		return $fields;
	}

	/**
	 * Add a field to the register form aka wp-login.php
	 */
	// public function add_register_field() {
	// include plugin_dir_path( __FILE__ ) . 'partials/user/sendsms-dashboard-mobile-field-register.php';
	// }

	/**
	 * Set registration errors + show continuation form if everything is ok
	 */
	// public function set_registration_errors( WP_Error $errors, $sanitized_user_login, $user_email ) {
	// $phone = isset( $_POST['sendsms_phone_number'] ) ? $this->functions->validate_phone( $_POST['sendsms_phone_number'] ) : '';
	// if ( $phone === '' ) {
	// $errors->add( 'invalid_phone', __( '<strong>Error</strong>: Your phone number is empty or not valid', 'sendsms-dashboard' ) );
	// }
	// if ( ! isset( $_POST['register_nonce'] ) ) {
	// $errors->add( 'internal_error', __( '<strong>Error</strong>: You should not be here', 'sendsms-dashboard' ) );
	// }
	// if ( ! empty( $errors->errors ) ) {
	// return $errors;
	// }
	// setcookie( 'sanitized_user_login', maybe_serialize( $sanitized_user_login ) );
	// setcookie( 'user_email', maybe_serialize( $user_email ) );
	// setcookie( 'sendsms_phone', maybe_serialize( $phone ) );
	// $content = $this->functions->get_setting( '2fa_verification_message', '' );
	// $this->api->message_send( false, false, $phone, $content, 'code', '_2fa_register' ); //TODO add refresh check
	// if ( ! headers_sent() ) {
	// header( 'Content-Type: text/html; charset=utf-8' );
	// }
	// include plugin_dir_path( __FILE__ ) . 'partials/sendsms-dashboard-register-2fa-form.php'; // popup here, but I need to stop the reg process
	// exit();
	// }

	// public function register_post_sendsms_validate() {
	// }
	/**
	 * This function will copy all auth tokens we will later invalidate
	 *
	 * @since 1.0.0
	 */
	public function collect_auth_cookie_tokens( $cookie ) {
		$parsed = wp_parse_auth_cookie( $cookie );

		if ( ! empty( $parsed['token'] ) ) {
			$this->password_auth_tokens[] = $parsed['token'];
		}
	}

	/**
	 * This function will invalidate all cookies and show the 2fa form if need
	 *
	 * @since 1.0.0
	 */
	public function twofa_processing( $user_login, $user ) {
		if ( ! $this->functions->have_2fa_activated( $user->ID ) ) {
			return;
		}
		// destroy the session
		$this->functions->destroy_current_session_for_user( $user, $this->password_auth_tokens );

		$this->get_2fa_login_form( $user );
	}

	function get_2fa_login_form( $user ) {
		if ( ! $user ) {
			$user = wp_get_current_user();
		}

		$login_nonce = $this->functions->create_login_nonce( $user->ID );
		if ( ! $login_nonce ) {
			wp_die( esc_html__( 'Failed to create a login nonce.', 'sendsms-dashboard' ) );
		}

		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : admin_url();

		$hasPhone = true;
		if ( empty( trim( $this->functions->get_user_phone( $user->ID ) ) ) ) {
			$hasPhone = false;
		}

		include plugin_dir_path( __FILE__ ) . 'partials/sendsms-dashboard-2fa-form.php';
		sendsms_dashboard_printHTML2fa( $user, $login_nonce['key'], $redirect_to, $this, '', true, $hasPhone );
		exit;
	}

	public function generate_auth_code( $user, $phone_no ) {
		$phone   = $phone_no == '' ? $this->functions->get_user_phone( $user->ID ) : $phone_no;
		$content = $this->functions->get_setting( '2fa_verification_message', '' ); // TODO add a specific field
		$this->api->message_send( false, false, $phone, $content, 'code', '_2fa' );
	}

	public function login_form_sendsms_validate() {
		$wp_auth_id  = filter_input( INPUT_POST, 'wp-auth-id', FILTER_SANITIZE_NUMBER_INT );
		$nonce       = isset( $_POST['wp-auth-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wp-auth-nonce'] ) ) : '';
		$posted_phone = isset( $_POST['wp-auth-phone'] ) ? sanitize_text_field( wp_unslash( $_POST['wp-auth-phone'] ) ) : '';
		$phone       = $posted_phone !== ''
			? $posted_phone
			: $this->functions->get_user_phone( $wp_auth_id );
		if ( ! $wp_auth_id || ! $nonce ) {
			return;
		}

		$user = get_userdata( $wp_auth_id );
		if ( ! $user ) {
			return;
		}

		if ( ! $this->functions->verify_login_nonce( $user->ID, $nonce ) ) {
			wp_safe_redirect( get_bloginfo( 'url' ) );
			exit;
		}

		if ( ! isset( $_COOKIE['sendsms_subscribe_check_2fa'] ) || ! $this->functions->verifyVerificationCode( $phone, '_2fa' ) ) { // TODO expired cookie msg
			do_action( 'wp_login_failed', $user->user_login );

			$login_nonce = $this->functions->create_login_nonce( $user->ID );
			if ( ! $login_nonce ) {
				wp_die( esc_html__( 'Failed to create a login nonce.', 'sendsms-dashboard' ) );
			}

			include plugin_dir_path( __FILE__ ) . 'partials/sendsms-dashboard-2fa-form.php';
			if ( ! isset( $_COOKIE['sendsms_subscribe_check_2fa'] ) ) {
				sendsms_dashboard_printHTML2fa( $user, $login_nonce['key'], $_REQUEST['redirect_to'], $this, esc_html__( 'ERROR: The verification code has expired. Please refresh the page and try again.', 'sendsms-dashboard' ), false );
			} else {
				sendsms_dashboard_printHTML2fa( $user, $login_nonce['key'], $_REQUEST['redirect_to'], $this, esc_html__( 'ERROR: Invalid code, please submit the code again.', 'sendsms-dashboard' ), false );
			}
			exit;
		}

		$this->functions->delete_login_nonce( $user->ID );

		$rememberme = false;
		if ( isset( $_REQUEST['rememberme'] ) && $_REQUEST['rememberme'] ) {
			$rememberme = true;
		}

		wp_set_auth_cookie( $user->ID, $rememberme );

		global $interim_login;
		$interim_login = isset( $_REQUEST['interim-login'] );

		if ( $interim_login ) {
			$customize_login = isset( $_REQUEST['customize-login'] );
			if ( $customize_login ) {
				wp_enqueue_script( 'customize-base' );
			}
			$message       = '<p class="message">' . __( 'You have logged in successfully.', 'sendsms-dashboard' ) . '</p>';
			$interim_login = 'success';
				include_once SENDSMS_DASHBOARD_PLUGIN_DIRECTORY . 'includes/class-wp-login.php';
				sendsms_dashboard_login_header( '', $message );
			?>
			</div>
			<?php
			do_action( 'login_footer' );
			?>
			<?php if ( $customize_login ) : ?>
				<script type="text/javascript">
					setTimeout(function() {
						new wp.customize.Messenger({
							url: '<?php echo esc_url( wp_customize_url() ); ?>',
							channel: 'login'
						}).send('login')
					}, 1000);
				</script>
			<?php endif; ?>
			</body>

			</html>
			<?php
			exit;
		}
		if ( empty( trim( $this->functions->get_user_phone( $wp_auth_id ) ) ) ) {
			update_user_meta( $user->ID, 'sendsms_phone_number', $phone );
		}
		$redirect_to = apply_filters( 'login_redirect', $_REQUEST['redirect_to'], $_REQUEST['redirect_to'], $user );
		wp_safe_redirect( $redirect_to );

		exit;
	}

	public function login_form_sendsms_send_code() {
		$wp_auth_id = filter_input( INPUT_POST, 'wp-auth-id', FILTER_SANITIZE_NUMBER_INT );
		$nonce      = isset( $_POST['wp-auth-nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wp-auth-nonce'] ) ) : '';

		if ( ! $wp_auth_id || ! $nonce ) {
			return;
		}

		$user = get_userdata( $wp_auth_id );
		if ( ! $user ) {
			return;
		}

		if ( ! $this->functions->verify_login_nonce( $user->ID, $nonce ) ) {
			wp_safe_redirect( get_bloginfo( 'url' ) );
			exit;
		}

		if ( empty( trim( $this->functions->get_user_phone( $user->ID ) ) ) ) { // in case he did something shady
			$phone = wp_unslash( sanitize_text_field( $_POST['phone'] ) );
		} else {
			$phone = trim( $this->functions->get_user_phone( $user->ID ) );
		}

		if ( $this->functions->validate_phone( $phone ) == '' ) { // TODO expired cookie msg
			do_action( 'wp_login_failed', $user->user_login );

			$login_nonce = $this->functions->create_login_nonce( $user->ID );
			if ( ! $login_nonce ) {
				wp_die( esc_html__( 'Failed to create a login nonce.', 'sendsms-dashboard' ) );
			}

			include plugin_dir_path( __FILE__ ) . 'partials/sendsms-dashboard-2fa-form.php';
			sendsms_dashboard_printHTML2fa( $user, $login_nonce['key'], $_REQUEST['redirect_to'], $this, esc_html__( 'ERROR: Invalid phone number', 'sendsms-dashboard' ), false, false, '' );
			exit;
		} else {
			$login_nonce = $this->functions->create_login_nonce( $user->ID );
			if ( ! $login_nonce ) {
				wp_die( esc_html__( 'Failed to create a login nonce.', 'sendsms-dashboard' ) );
			}

			include plugin_dir_path( __FILE__ ) . 'partials/sendsms-dashboard-2fa-form.php';
			sendsms_dashboard_printHTML2fa( $user, $login_nonce['key'], $_REQUEST['redirect_to'], $this, '', true, true, $phone );
			exit;
		}

		exit;
	}
}
