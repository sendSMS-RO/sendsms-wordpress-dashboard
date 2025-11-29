<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Sendsms_Dashboard
 * @subpackage Sendsms_Dashboard/includes
 * @author     sendSMS <support@sendsms.ro>
 */
class Sendsms_Dashboard {


	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    Sendsms_Dashboard_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $sendsms_dashboard    The string used to uniquely identify this plugin.
	 */
	protected $sendsms_dashboard;

	/**
	 * The current version of the plugin.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $version    The current version of the plugin.
	 */
	protected $version;
    
    /**
	 * Plugin name.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var    string    $plugin_name    The plugin name.
	 */
    protected $plugin_name;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( defined( 'SENDSMS_DASHBOARD_VERSION' ) ) {
			$this->version = SENDSMS_DASHBOARD_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'sendsms-dashboard';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Sendsms_Dashboard_Loader. Orchestrates the hooks of the plugin.
	 * - Sendsms_Dashboard_i18n. Defines internationalization functionality.
	 * - Sendsms_Dashboard_Admin. Defines all hooks for the admin area.
	 * - Sendsms_Dashboard_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sendsms-dashboard-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-sendsms-dashboard-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-sendsms-dashboard-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		include_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-sendsms-dashboard-public.php';

		$this->loader = new Sendsms_Dashboard_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Sendsms_Dashboard_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function set_locale() {
		$plugin_i18n = new Sendsms_Dashboard_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Sendsms_Dashboard_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		// load settings
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'load_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'load_settings' );
		// ajax
		$this->loader->add_action( 'wp_ajax_send_a_test_sms', $plugin_admin, 'send_a_test_sms' );
		$this->loader->add_action( 'wp_ajax_send_mass_sms', $plugin_admin, 'send_mass_sms' );
		$this->loader->add_action( 'wp_ajax_update_a_subscriber', $plugin_admin, 'update_a_subscriber' );    // this will update a subscriber
		$this->loader->add_action( 'wp_ajax_synchronize_contacts', $plugin_admin, 'synchronize_contacts' ); // this will sync the contacts to sendsms
		// load phone fields
		if ( $this->get_setting( 'add_phone_field', false ) ) {
			$this->loader->add_action( 'user_new_form', $plugin_admin, 'add_new_user_field' );
			$this->loader->add_action( 'user_register', $plugin_admin, 'user_register_metadata' );
			$this->loader->add_filter( 'user_contactmethods', $plugin_admin, 'add_new_user_field_to_edit_form' );
			// this is for 2fa. We keep a list of all cookies we need to invalidate before second auth step
			$this->loader->add_action( 'set_auth_cookie', $plugin_admin, 'collect_auth_cookie_tokens' );
			$this->loader->add_action( 'set_logged_in_cookie', $plugin_admin, 'collect_auth_cookie_tokens' );
			// this invalidates the cookies and show the correct verification form if needed
			$this->loader->add_action( 'wp_login', $plugin_admin, 'twofa_processing', 10, 2 ); // comment this to deactivate 2fa
			$this->loader->add_action( 'login_form_sendsms_validate', $plugin_admin, 'login_form_sendsms_validate' );
			$this->loader->add_action( 'login_form_sendsms_send_code', $plugin_admin, 'login_form_sendsms_send_code' );
			// register #there hooks are deactivated due to WordPress not letting your check both email and phone
			// $this->loader->add_action( 'register_form', $plugin_admin, 'add_register_field' );
			// $this->loader->add_filter( 'registration_errors', $plugin_admin, 'set_registration_errors', 100, 3 );
			// $this->loader->add_action( 'register_post_sendsms_validate', $plugin_admin, 'register_post_sendsms_validate' );
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since  1.0.0
	 * @access private
	 */
	private function define_public_hooks() {
		$plugin_public = new Sendsms_Dashboard_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		// widget
		$this->loader->add_action( 'widgets_init', $plugin_public, 'widget_initialization' );

		// ajax
		// subscribe to newsletter
		$this->loader->add_action( 'wp_ajax_nopriv_subscribe_to_newsletter', $plugin_public, 'subscribe_to_newsletter' );
		$this->loader->add_action( 'wp_ajax_subscribe_to_newsletter', $plugin_public, 'subscribe_to_newsletter' );
		$this->loader->add_action( 'wp_ajax_nopriv_subscribe_verify_code', $plugin_public, 'subscribe_verify_code' );
		$this->loader->add_action( 'wp_ajax_subscribe_verify_code', $plugin_public, 'subscribe_verify_code' );
		// unsubscribe from newsletter
		$this->loader->add_action( 'wp_ajax_nopriv_unsubscribe_from_newsletter', $plugin_public, 'unsubscribe_from_newsletter' );
		$this->loader->add_action( 'wp_ajax_unsubscribe_from_newsletter', $plugin_public, 'unsubscribe_from_newsletter' );
		$this->loader->add_action( 'wp_ajax_nopriv_unsubscribe_verify_code', $plugin_public, 'unsubscribe_verify_code' );
		$this->loader->add_action( 'wp_ajax_unsubscribe_verify_code', $plugin_public, 'unsubscribe_verify_code' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since 1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since  1.0.0
	 * @return string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since  1.0.0
	 * @return Sendsms_Dashboard_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since  1.0.0
	 * @return string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Get a setting
	 *
	 * @since 1.0.0
	 */
	public function get_setting( $setting, $default = '' ) {
		return isset( get_option( 'sendsms_dashboard_plugin_settings' )[ "$setting" ] ) ? get_option( 'sendsms_dashboard_plugin_settings' )[ "$setting" ] : $default;
	}
}
