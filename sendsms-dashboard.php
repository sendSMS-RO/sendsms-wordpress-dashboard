<?php

/**
 * Plugin Name: SendSMS Dashboard
 * Description: Use this plugin to communicate with everyone on your site
 * Version: 1.0.1
 * Author: sendSMS
 * Author URI: https://www.sendsms.ro/en/
 * License: GPLv2
 * Requires PHP: 7.2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SENDSMS_DASHBOARD_VERSION', '1.0.2' );
/**
 * Currently DB version
 */
define( 'SENDSMS_DASHBOARD_DB_VERSION', '1.0.0' );
/**
 * Plugin directory
 */
define( 'SENDSMS_DASHBOARD_PLUGIN_DIRECTORY', plugin_dir_path( __FILE__ ) ); // TODO this should be used more in future versions


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-sendsms-dashboard-activator.php
 */
function activate_sendsms_dashboard() {
	 include_once plugin_dir_path( __FILE__ ) . 'includes/class-sendsms-dashboard-activator.php';
	Sendsms_Dashboard_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-sendsms-dashboard-deactivator.php
 */
function deactivate_sendsms_dashboard() {
	include_once plugin_dir_path( __FILE__ ) . 'includes/class-sendsms-dashboard-deactivator.php';
	Sendsms_Dashboard_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_sendsms_dashboard' );
register_deactivation_hook( __FILE__, 'deactivate_sendsms_dashboard' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-sendsms-dashboard.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function run_sendsms_dashboard() {
	$plugin = new Sendsms_Dashboard();
	$plugin->run();
}
run_sendsms_dashboard();
