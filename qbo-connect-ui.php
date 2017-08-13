<?php
/**
 * Plugin Name: Quickbooks Online Connect UI
 * Plugin URI:  http://zao.is
 * Description: Provides UI for connecting to Quickbooks Online over OAuth.
 * Version:     0.2.6
 * Author:      Zao
 * Author URI:  http://zao.is
 * Donate link: http://zao.is
 * License:     GPLv2
 * Text Domain: qbo-connect-ui
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2015 Zao (email : jt@zao.is)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using generator-plugin-wp
 */

// include composer autoloader (make sure you run `composer install`!)
if ( file_exists( Zao_QBO_API_Connect_UI::dir( 'vendor/autoload.php' ) ) ) {
	require_once Zao_QBO_API_Connect_UI::dir( 'vendor/autoload.php' );
}

/**
 * Main initiation class
 *
 * @since  0.1.0
 * @var  string $version  Plugin version
 * @var  string $basename Plugin basename
 * @var  string $url      Plugin URL
 * @var  string $path     Plugin Path
 */
class Zao_QBO_API_Connect_UI {

	/**
	 * Current version
	 *
	 * @var  string
	 * @since  0.1.0
	 */
	const VERSION = '0.2.6';

	/**
	 * Plugin basename
	 *
	 * @var string
	 * @since  0.1.0
	 */
	protected $basename = '';

	/**
	 * URL of plugin directory
	 *
	 * @var string
	 * @since  0.1.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory
	 *
	 * @var string
	 * @since  0.1.0
	 */
	protected $path = '';

	/**
	 * Error message if plugin cannot be activated.
	 *
	 * @var string
	 * @since  0.1.0
	 */
	protected $activation_error = '';

	/**
	 * Whether plugin should operate on the network settings level.
	 * Enabled via the ZAO_QBO_API_NETWORK_SETTINGS constant
	 *
	 * @var bool
	 * @since  0.1.0
	 */
	protected $is_network = false;

	/**
	 * Singleton instance of plugin
	 *
	 * @var Zao_QBO_API_Connect_UI
	 * @since  0.1.0
	 */
	protected static $single_instance = null;

	/**
	 * Instance of Zao_QBO_API_Settings
	 *
	 * @var Zao_QBO_API_Settings
	 */
	protected $settings;

	/**
	 * Instance of Zao_QBO_API_Compatibility, an abstraction layer for Connect
	 *
	 * @var Zao_QBO_API_Compatibility
	 */
	protected $api;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since  0.1.0
	 * @return Zao_QBO_API_Connect_UI A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin
	 *
	 * @since  0.1.0
	 */
	protected function __construct() {
		$this->basename   = plugin_basename( __FILE__ );
		$this->url        = plugin_dir_url( __FILE__ );
		$this->path       = plugin_dir_path( __FILE__ );
		$this->is_network = apply_filters( 'qbo_connect_ui_is_network', defined( 'ZAO_QBO_API_NETWORK_SETTINGS' ) );

		$this->plugin_classes();
	}

	/**
	 * Attach other plugin classes to the base plugin class.
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function plugin_classes() {
		$storage_classes = $this->is_network ? array(
			'options_class' => 'Zao_QBO_API_Storage_Options',
			'transients_class' => 'Zao_QBO_API_Storage_Transients',
		) : array();

		$this->api = new Zao\QBO_API\Connect( $storage_classes );

		$class = $this->is_network ? 'Zao_QBO_API_Network_Settings' : 'Zao_QBO_API_Settings';
		$this->settings = new $class( $this->basename, $this->api );
	} // END OF PLUGIN CLASSES FUNCTION

	/**
	 * Add hooks and filters
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		$this->settings->hooks();
	}

	/**
	 * Init hooks
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function init() {
		if ( $this->check_requirements() ) {
			load_plugin_textdomain( 'qbo-connect-ui', false, dirname( $this->basename ) . '/languages/' );
		}
	}

	/**
	 * Check that all plugin requirements are met
	 *
	 * @since  0.1.0
	 * @return boolean
	 */
	public function meets_requirements() {

		// Plugin requires CMB2
		if ( ! defined( 'CMB2_LOADED' ) ) {
			$this->activation_error = sprintf( __( 'Quickbooks Online Connect UI requires the <a href="https://wordpress.org/plugins/cmb2/">CMB2 plugin</a>, so it has been <a href="%s">deactivated</a>.', 'qbo-connect-ui' ), admin_url( 'plugins.php' ) );

			return false;
		}

		// If network-level, but not network-activated, it fails
		if ( $this->is_network && ! is_plugin_active_for_network( $this->basename ) ) {
			$this->activation_error = sprintf( __( "Quickbooks Online Connect UI has been designated as a network-only plugin (via the <code>'qbo_connect_ui_is_network'</code> filter or the <code>'ZAO_QBO_API_NETWORK_SETTINGS'</code> constant), so it has been <a href=\"%s\">deactivated</a>. Please try network-activating.", 'qbo-connect-ui' ), admin_url( 'plugins.php' ) );

			return false;
		}

		return true;
	}

	/**
	 * Check if the plugin meets requirements and
	 * disable it if they are not present.
	 *
	 * @since  0.1.0
	 * @return boolean result of meets_requirements
	 */
	public function check_requirements() {
		if ( ! $this->meets_requirements() ) {

			// Add a dashboard notice
			add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

			return false;
		}

		return true;
	}

	/**
	 * Adds a notice to the dashboard if the plugin requirements are not met
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function requirements_not_met_notice() {
		// Output our error
		echo '<div id="message" class="error">';
		echo '<p>' . $this->activation_error . '</p>';
		echo '</div>';

		// Deactivate our plugin
		deactivate_plugins( $this->basename );
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  0.1.0
	 * @param string $field
	 * @throws Exception Throws an exception if the field is invalid.
	 * @return mixed
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
			case 'settings':
			case 'api':
				return $this->$field;
			default:
				throw new Exception( 'Invalid '. __CLASS__ .' property: ' . $field );
		}
	}

	/**
	 * Include a file from the includes directory
	 *
	 * @since  0.1.0
	 * @param  string  $filename Name of the file to be included
	 * @return bool    Result of include call.
	 */
	public static function include_file( $filename ) {
		$file = self::dir( 'includes/'. $filename .'.php' );
		if ( file_exists( $file ) ) {
			return include_once( $file );
		}
		return false;
	}

	/**
	 * This plugin's directory
	 *
	 * @since  0.1.0
	 * @param  string $path (optional) appended path
	 * @return string       Directory and path
	 */
	public static function dir( $path = '' ) {
		static $dir;
		$dir = $dir ? $dir : trailingslashit( dirname( __FILE__ ) );
		return $dir . $path;
	}

	/**
	 * This plugin's url
	 *
	 * @since  0.1.0
	 * @param  string $path (optional) appended path
	 * @return string       URL and path
	 */
	public static function url( $path = '' ) {
		static $url;
		$url = $url ? $url : trailingslashit( plugin_dir_url( __FILE__ ) );
		return $url . $path;
	}
}

/**
 * Grab the Zao_QBO_API_Connect_UI object and return it.
 * Wrapper for Zao_QBO_API_Connect_UI::get_instance()
 *
 * @since  0.1.0
 * @return Zao_QBO_API_Connect_UI  Singleton instance of plugin class.
 */
function qbo_connect_ui() {
	return Zao_QBO_API_Connect_UI::get_instance();
}

// Kick it off
add_action( 'plugins_loaded', array( qbo_connect_ui(), 'hooks' ) );

/**
 * Wrapper function for Zao_QBO_API_Settings::get()
 *
 * Available options;
 *    'url'
 *    'endpoint'
 *    'api_url'
 *    'client_key'
 *    'client_secret'
 *    'header_key'
 *    'header_token'
 *
 * @since  0.1.0
 *
 * @param  string  $field_id The setting field to retrieve.
 * @param  boolean $default  Optional default value if no value exists.
 *
 * @return mixed             Value for setting.
 */
function qbo_connect_ui_get_setting( $field_id = '', $default = false ) {
	return qbo_connect_ui()->settings->get( $field_id, $default );
}

/**
 * Wrapper function for Zao_QBO_API_Settings::api()
 *
 * @since  0.1.0
 *
 * @return WP_Error|Connect The API object or WP_Error.
 */
function qbo_connect_ui_api_object() {
	$settings = qbo_connect_ui()->settings;
	$api = $settings->api();

	if ( ! $api->initiated ) {
		$error = sprintf( __( 'API connection is not properly authenticated. Authenticate via the <a href="%s">settings page</a>.', 'qbo-connect-ui' ), $settings->settings_url() );

		return new WP_Error( 'qbo_connect_ui_api_fail', $error );
	}

	return $api;
}

/**
 *
 * In your theme or plugin, Instead of checking if the
 * 'qbo_connect_ui_api_object' function exists you can use:
 *
 * `$api = apply_filters( 'qbo_connect_ui_api_object', null );`
 *
 * Then check for Connect or WP_Error value before proceeding:
 * `if ( $api instanceof \Zao\QBO_API\Connect ) { $company = $api->get_company_info(); }`
 *
 */
add_filter( 'qbo_connect_ui_api_object', 'qbo_connect_ui_api_object' );
