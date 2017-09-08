<?php

use Zao\QBO_API\Connect;

/**
 * Quickbooks Online Connect UI Settings
 * @version 0.1.0
 * @package Quickbooks Online Connect UI
 */
class Zao_QBO_API_Settings {

	/**
	 * Option key, and option page slug
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $key = 'qbo_connect_ui_settings';

	/**
	 * Options page metabox id
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $metabox_id = 'qbo_connect_ui_settings_metabox';

	/**
	 * Options Page title
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $title = '';

	/**
	 * Connect object
	 *
	 * @var    Connect
	 * @since  0.1.0
	 */
	protected $api;

	/**
	 * Settings page hook
	 * @var string
	 */
	protected $page_hook = '';

	/**
	 * Which admin menu hook to use for displaying the settings page
	 *
	 * @var string
	 */
	protected $admin_menu_hook = 'admin_menu';

	/**
	 * Which plugin action links hook to use for displaying the settings page
	 *
	 * @var string
	 */
	protected $plugin_action_links_hook = 'plugin_action_links_';

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 *
	 * @param string                  $plugin_basename The plugin's basename.
	 * @param Connect $api             The API object.
	 */
	public function __construct( $plugin_basename, Connect $api ) {
		$this->plugin_action_links_hook .= $plugin_basename;
		$this->api   = $api;
		$this->title = __( 'Quickbooks Online Connect', 'qbo-connect-ui' );
	}

	/**
	 * Initiate our hooks
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( $this->admin_menu_hook, array( $this, 'add_settings_page' ) );
		add_filter( $this->plugin_action_links_hook, array( $this, 'settings_link' ) );
		add_action( 'cmb2_admin_init', array( $this, 'register_settings_page_metabox' ) );
	}

	/**
	 * Register our setting to WP
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function admin_init() {
		register_setting( $this->key, $this->key );

		add_action( 'all_admin_notices', array( $this, 'output_notices' ) );
		add_action( "cmb2_save_options-page_fields_{$this->metabox_id}", array( $this, 'settings_notices' ), 10, 2 );
		add_action( 'qbo_connect_ui_settings_output', array( $this, 'settings_title_output' ) );
		add_action( 'qbo_connect_ui_settings_output', array( $this, 'form_output' ) );
		add_action( 'qbo_connect_ui_settings_after_wrap', array( $this, 'connection_status_output' ) );

		if ( isset( $_GET['qb_reset_all'] ) && wp_verify_nonce( $_GET['qb_reset_all'], 'qb_reset_all' ) ) {
 			$this->delete_all_and_redirect();
		}

		if ( $this->api()->initiated ) {
			$this->check_api();
		} else {
			$this->check_for_stored_connection_errors();
		}
	}

	/**
	 * Add menu settings page
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function add_settings_page() {
		$this->page_hook = add_options_page(
			$this->title,
			__( 'QB Online Connect', 'qbo-connect-ui' ),
			'manage_options',
			$this->key,
			array( $this, 'admin_page_display' )
		);

		add_action( "admin_print_styles-{$this->page_hook}", array( __CLASS__, 'enqueue_resources' ) );
	}

	public static function enqueue_resources() {
		wp_enqueue_style(
			'qbo-connect-ui',
			Zao_QBO_API_Connect_UI::url( 'assets/css/style.css' ),
			array(),
			Zao_QBO_API_Connect_UI::VERSION
		);

		// Include CMB CSS in the head to avoid FOUC
		CMB2_hookup::enqueue_cmb_css();
	}

	/**
	 * Add a settings link to the plugin page.
	 *
	 * @since  0.1.0
	 *
	 * @param  array  $links Array of links
	 *
	 * @return array         Modified array of links
	 */
	public function settings_link( $links ) {
		$setting_link = sprintf( '<a href="%s">%s</a>', $this->settings_url(), __( 'Settings', 'qbo-connect-ui' ) );
		array_unshift( $links, $setting_link );

		return $links;
	}

	/**
	 * Admin page markup. Mostly handled by CMB2
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function admin_page_display() {
		?>
		<div class="wrap cmb2-options-page qb-connect-settings-page <?php echo $this->key; ?>">
			<?php do_action( 'qbo_connect_ui_settings_output', $this ); ?>
		</div>
		<?php do_action( 'qbo_connect_ui_settings_after_wrap', $this );
	}

	public function settings_title_output() {
		echo $this->get_settings_title_output();
	}

	public function get_settings_title_output() {
		return '<h2>' . esc_html( get_admin_page_title() ) . '</h2>';
	}

	public function form_output() {
		echo $this->get_form_output();
	}

	public function get_form_output() {
		$connected = $this->api()->connected();
		$connect_button = '';
		$button_class = 'button-primary';

		if ( ! $connected ) {
			wp_enqueue_script(
				'qbo-connect-ui',
				Zao_QBO_API_Connect_UI::url( 'assets/js/script.js' ),
				array( 'jquery' ),
				Zao_QBO_API_Connect_UI::VERSION,
				true
			);

			wp_localize_script( 'qbo-connect-ui', 'Zao_QBO_API_Connect_UI', array(
				'l10n' => array(
					'copy'   => esc_html__( 'Copy', 'qbo-connect-ui' ),
					'copied' => esc_html__( 'Copied!', 'qbo-connect-ui' ),
				),
			) );

			if ( $this->get( 'client_id' ) ) {
				$connect_url = $this->api()->get_full_authorization_url();
				if ( ! is_wp_error( $connect_url ) ) {
					$connect_button = '{{CONNECTBUTTON}}';
				}
			} else {
				$button_class = 'qb-button';
			}
		}

		$form_output = cmb2_get_metabox_form( $this->metabox_id, $this->key, array(
			'form_format' => '<form class="cmb-form" method="post" id="%1$s" enctype="multipart/form-data" encoding="multipart/form-data"><input type="hidden" name="object_id" value="%2$s">%3$s' . $connect_button . '<p class="submit"><input type="submit" name="submit-cmb" value="%4$s" class="' . $button_class . '"></p></form>'
		) );

		if ( $connect_button ) {
			$form_output = str_replace(
				'{{CONNECTBUTTON}}',
				'<p><a href="' . esc_url( $connect_url ) . '" class="qb-button">' . __( 'Begin Authorization', 'qbo-connect-ui' ) . '</a></p>',
				$form_output
			);
		}

		return $form_output;
	}

	public function connection_status_output() {
		if ( ! $this->api()->connected() ) {
			return;
		}

		$creds     = $this->api()->get_option( 'token_credentials' );
		$auth_urls = $this->api()->auth_urls;
		?>
		<br>
		<h3 class="qp-connected-title"><?php _e( 'Connected', 'qbo-connect-ui' ); ?>!</h3>
		<hr>
		<div class="extra-detail">
			<h3><?php _e( 'OAuth endpoints', 'qbo-connect-ui' ); ?></h3>
			<dl>
				<dt><?php _e( 'Authorize Endpoint', 'qbo-connect-ui' ); ?></dt>
				<dd><code><?php echo esc_attr( $auth_urls->authorization_endpoint ); ?></code></dd>
				<dt><?php _e( 'Access Token Endpoint', 'qbo-connect-ui' ); ?></dt>
				<dd><code><?php echo esc_attr( $auth_urls->token_endpoint ); ?></code></dd>
			</dl>
			<h3><?php _e( 'OAuth credentials', 'qbo-connect-ui' ); ?></h3>
			<dl>
				<dt><?php _e( 'Client ID', 'qbo-connect-ui' ); ?></dt>
				<dd><code><?php echo esc_attr( $this->api()->client_id ); ?></code></dd>
				<dt><?php _e( 'Client Secret', 'qbo-connect-ui' ); ?></dt>
				<dd><code><?php echo esc_attr( $this->api()->client_secret ); ?></code></dd>
				<dt><?php _e( 'Access Token', 'qbo-connect-ui' ); ?></dt>
				<dd><code><?php echo esc_attr( $creds->access_token ); ?></code></dd>
				<dt><?php _e( 'Refresh Token', 'qbo-connect-ui' ); ?></dt>
				<dd><code><?php echo esc_attr( $creds->refresh_token ); ?></code></dd>
				<dt><?php _e( 'Expires In', 'qbo-connect-ui' ); ?></dt>
				<dd><code><?php echo esc_attr( $creds->expires_in ); ?></code></dd>
			</dl>
		</div>
		<?php
	}

	/**
	 * Register the CMB2 instance and fields to the settings page.
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function register_settings_page_metabox() {
		if ( $this->get( 'client_id' ) || $this->get( 'client_secret' ) ) {
			// Add a "reset" button next to the "save" button.
			add_filter( 'cmb2_get_metabox_form_format', array( $this, 'add_reset_connection_button' ), 10, 2 );
		}

		$cmb = new_cmb2_box( array(
			'id'         => $this->metabox_id,
			'hookup'     => false,
			'cmb_styles' => false,
			'save_fields' => false,
			'show_on'    => array(
				// These are important, don't remove
				'key'   => 'options-page',
				'value' => array( $this->key, )
			),
		) );

		// Save the metabox if it's been submitted
		// check permissions
		$do_save = (
			isset( $_POST['submit-cmb'], $_POST['object_id'] )
			// check nonce
			&& isset( $_POST[ $cmb->nonce() ] )
			&& wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() )
			&& $_POST['object_id'] == $this->key
		);

		if ( $do_save ) {
			// Save fields at the beginning of page-load, not at field-generation time
			add_action( 'cmb2_after_init', array( $this, 'process_fields' ), 11 );
		}

		$cmb->add_field( array(
			'id'         => 'keys-title',
			'name'       => __( 'Application Keys', 'qbo-connect-ui' ),
			'desc'       => __( 'These keys are configured with your App.', 'qbo-connect-ui' ) . ' <a href="https://developer.intuit.com/docs/0100_quickbooks_online/0100_essentials/000300_your_first_request/0100_get_auth_tokens" target="_blank">' . __( 'Learn more here', 'qbo-connect-ui' ) . '</a>',
			'type'       => 'title',
		) );

		$cmb->add_field( array(
			'name'       => __( 'Client ID', 'qbo-connect-ui' ),
			'id'         => 'client_id',
			'type'       => 'text',
			'attributes' => array(
				'required' => 'required',
			),
		) );

		$after_row = ! $this->api()->connected()
			? '<div class="cmb-row cmb-type-title qb-clipboard-redirect-uri-helper"><p>' . __( '<strong>NOTE:</strong> The Redirect URI for the Intuit app registration needs to be the following: ', 'qbo-connect-ui' ) . '</p><p></p><p><input id="clipboard-redirect-uri" type="text" class="large-text" disabled readonly value="' . $this->settings_url() . '"/></p></div>'
			: '';

		$cmb->add_field( array(
			'name' => __( 'Client Secret', 'qbo-connect-ui' ),
			'id'   => 'client_secret',
			'type' => 'text',
			'attributes' => array(
				'required' => 'required',
			),
			'after_row' => $after_row,
		) );

		$cmb->add_field( array(
			'name'       => __( 'Sandbox Mode?', 'qbo-connect-ui' ),
			'desc'       => __( 'Used for testing.', 'qbo-connect-ui' ),
			'id'         => 'sandbox',
			'type'       => 'checkbox',
			'default_cb' => array( $this, 'checked_by_default' ),
		) );
	}

	public function checked_by_default() {
		$all = $this->get( 'all' );
		if ( empty( $all ) ) {
			return true;
		}

		return ! empty( $all['sandbox'] );
	}

	/**
	 * Get the current value from the database or the POSTed data.
	 * Will be sanitized using $sanitizer if collecting from POSTed data.
	 *
	 * @since  0.2.3
	 *
	 * @param  string  $key       option key
	 * @param  string  $sanitizer Sanitizer function
	 *
	 * @return mixed              Value
	 */
	public function get_current_value( $key, $sanitizer ) {
		$value = $this->get( $key );
		if ( ! $value ) {
			$value = ! empty( $_POST[ $key ] )
				? $sanitizer( $_POST[ $key ] )
				: false;
		}

		return $value;
	}

	/**
	 * Save fields earlier in the load order (cmb2_after_init)
	 *
	 * @since  0.1.0
	 */
	public function process_fields() {
		$presave_id = $this->get( 'client_id' );

		if ( empty( $_POST['client_id'] ) ) {
			$this->api()->delete_option();
		}

		// Save the fields
		$cmb = cmb2_get_metabox( $this->metabox_id );
		$cmb->save_fields( $this->key, $cmb->object_type( 'options-page' ), $_POST );

		// If we' don't have the right stuff, we need to redirect to get authorization
		if ( empty( $presave_id ) && ! empty( $_POST['client_id'] ) ) {
			$this->api()->redirect_to_login();
		}

		// Redirect after saving to prevent refresh-saving
		$this->redirect();
	}

	/**
	 * Displays registered admin notices
	 *
	 * @since  0.1.0
	 * @uses   get_settings_errors()
	 *
	 * @return void
	 */
	public function output_notices() {
		$settings_errors = get_settings_errors( $this->key . '-notices' );

		if ( empty( $settings_errors ) ) {
			return;
		}

		$output = '';
		foreach ( $settings_errors as $key => $details ) {
			$css_id = 'setting-error-' . $details['code'];
			$css_class = $details['type'] . ' settings-error notice';
			$output .= "<div id='$css_id' class='$css_class'> \n";
			$output .= $details['message'];
			$output .= "</div> \n";
		}

		$output = str_replace( 'updated settings-', 'is-dismissible updated settings-', $output );
		echo $output;
	}

	/**
	 * Register settings notices for display
	 *
	 * @since  0.1.0
	 *
	 * @param  int    $object_id Option key
	 * @param  array  $updated   Array of updated fields
	 *
	 * @return void
	 */
	public function settings_notices( $object_id, $updated ) {
		if ( $object_id !== $this->key || empty( $updated ) ) {
			return;
		}

		// Setup our save notice
		$this->register_notice( __( 'Settings updated.', 'qbo-connect-ui' ), false, '' );
		$this->output_notices();

		// Delete stored errors
		$this->api()->delete_stored_error();

		// Add redirect to re-check credentials
		echo '
		<script type="text/javascript">
		window.location.href = "' . esc_url_raw( add_query_arg( 'check_credentials', 1 ) ) . '";
		</script>
		';
	}

	/**
	 * API checks
	 *
	 * @since  0.1.0
	 *
	 * @return void
	 */
	public function check_api() {
		// Setup reauth if requested.
		if ( isset( $_GET['qb_reauth'] ) && wp_verify_nonce( $_GET['qb_reauth'], 'qb_reauth' ) ) {
			return $this->reauth_and_redirect();
		}

		// Check auth credentials if requested.
		if ( isset( $_GET['check_credentials'] ) && $this->verify_api_connection_successful() ) {
			return;
		}

		// Refresh token if requested.
		if ( isset( $_GET['refresh_token'] ) ) {
			return $this->refresh_token_and_redirect();
		}

		// Dismiss authentication errors if requested.
		if ( isset( $_GET['dismiss_errrors'] ) ) {
			return $this->dismiss_errrors_and_redirect();
		}

		// Output any connection errors that may exist.
		if ( $this->check_for_stored_connection_errors() ) {
			return;
		}

		if ( $this->get( 'client_id' ) ) {
			// Add a "check credentials" button next to the "save" button.
			add_filter( 'cmb2_get_metabox_form_format', array( $this, 'add_check_connection_button' ), 10, 2 );
		}
	}

	/**
	 * Deletes all settings and connection settings.
	 *
	 * @since  0.2.0
	 */
	public function delete_all_and_redirect() {
		$this->api()->reset_connection();
		delete_option( $this->key );
		$this->redirect();
	}

	/**
	 * Deletes stored API connection data and redirects to setup reauthentication
	 *
	 * @since  0.1.0
	 *
	 * @return void
	 */
	public function reauth_and_redirect() {
		$this->api()->delete_option( 'token_credentials' );
		$this->api()->delete_stored_error();
		$this->redirect( array( 'check_credentials' => 1 ) );
	}

	/**
	 * Deletes stored API connection errors and redirects, removing any query params.
	 *
	 * @since  0.1.0
	 *
	 * @return void
	 */
	public function dismiss_errrors_and_redirect() {
		$this->api()->delete_stored_error();
		$this->redirect();
	}

	/**
	 * Deletes stored API connection errors and redirects, removing any query params.
	 *
	 * @since  0.1.0
	 *
	 * @return void
	 */
	public function refresh_token_and_redirect() {
		$this->api()->request_refresh_token();
		$this->redirect();
	}

	/**
	 * Determines if API connection credentials provide a successful connection.
	 *
	 * @since  0.1.0
	 *
	 * @return bool  Whether API conneciton is successful.
	 */
	public function verify_api_connection_successful() {
		if ( ! $this->api()->connected() ) {
			$this->api()->redirect_to_login();
		}

		$company = $this->get_company_info();

		if ( $company ) {
			return $this->success_message( $company );
		}
	}

	/**
	 * Get's authorized user. Useful for testing authenticated connection.
	 *
	 * @since  0.2.0
	 *
	 * @return mixed  User object or WP_Error object.
	 */
	public function get_company_info() {
		$company = $this->api()->get_company_info();

		if ( is_wp_error( $company ) ) {

			if ( 'wp_rest_api_not_authorized' == $company->get_error_code() ) {
				return $this->need_to_authenticate_message( $company );
			}

			return $this->oops_error_message( $company );
		}

		return $company;
	}

	/**
	 * Register a notice for any connection errors that may exist.
	 *
	 * @since  0.1.0
	 *
	 * @return bool  Whether stored connection errors exist.
	 */
	public function check_for_stored_connection_errors() {
		$errors = $this->api()->get_stored_error();

		if ( ! $errors || ! isset( $errors['message'] ) || ! $errors['message'] ) {
			return false;
		}

		$message = '
		<h4>'. $errors['message'] .'</h4>
		<xmp>request args: '. print_r( $errors['request_args'], true ) .'</xmp>
		<p><a class="button-secondary" href="'. add_query_arg( 'dismiss_errrors', 1 ) .'">' . __( 'Dismiss Errors', 'qbo-connect-ui' ) . '</a></p>
		';
		$this->register_notice( $message );

		return true;
	}

	/**
	 * Register a notice for a successful API connection, and display API data.
	 *
	 * @since  0.1.0
	 *
	 * @return bool  Successful connection.
	 */
	public function success_message( $company ) {

		$props = array();
		foreach ( get_object_vars( $company ) as $prop_name => $prop_value ) {
			$props[] = '<tr><td>'. print_r( $prop_name, true ) .'</td><td>'. print_r( $prop_value, true ) .'</td></tr>';
		}

		$message = '
		<br>
		<table class="wp-list-table widefat">
			<thead>
				<tr>
					<th>' . __( 'Connected Company Name', 'qbo-connect-ui' ) . '</th>
					<th>' . __( 'Connected Company ID', 'qbo-connect-ui' ) . '</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>'. esc_html( $company->CompanyName ) .'</td>
					<td>'. esc_html( $company->Id ) .'</td>
				</tr>
			</tbody>
		</table>
		<br>
		<table class="wp-list-table widefat">
			<thead>
				<tr>
					<th>'. __( 'Company Property:', 'qbo-connect-ui' ) .'</th>
					<th>'. __( 'Company Property Value:', 'qbo-connect-ui' ) .'</th>
				</tr>
			</thead>
			<tbody>
				'. implode( "\n", $props ) .'
			</tbody>
		</table>
		<br>
		<p><a class="button-secondary" href="'. $this->settings_url() .'">' . __( 'Dismiss', 'qbo-connect-ui' ) . '</a>&nbsp;&nbsp;<a class="button-secondary" href="'. $this->reauth_url() .'">' . __( 'Re-authenticate', 'qbo-connect-ui' ) . '</a></p>
		';

		$this->register_notice( $message, false );

		return true;
	}

	/**
	 * Register a notice when re-authentication is required.
	 *
	 * @since  0.1.0
	 *
	 * @param  WP_Error $request WP_Error object
	 *
	 * @return bool              Failed connection.
	 */
	public function need_to_authenticate_message( $request ) {

		$url = $this->api()->get_authorization_url();
		if ( is_wp_error( $url ) ) {
			return false;
		}

		$authenticate = '<p><a class="button-secondary" href="'. esc_url( $url ) .'">' . __( 'Click here to authenticate', 'qbo-connect-ui' ) . '</a></p>';

		$this->register_notice( $authenticate, false, __( "You're almost there.", 'qbo-connect-ui' ) );

		return false;
	}

	/**
	 * Register a notice when authentication failed.
	 *
	 * @since  0.1.0
	 *
	 * @param  WP_Error $request WP_Error object
	 *
	 * @return bool              Failed connection.
	 */
	public function oops_error_message( $request ) {
		$message = '
		<h4>'. $request->get_error_message() .'</h4>
		<h4>'. $request->get_error_code() .'</h4>
		<xmp>Error Data: '. print_r( $request->get_error_data(), true ) .'</xmp>
		<p><a class="button-secondary" href="'. add_query_arg( 'dismiss_errrors', 1 ) .'">' . __( 'Dismiss Errors', 'qbo-connect-ui' ) . '</a></p>
		';

		$this->register_notice( $message );

		return false;
	}

	/**
	 * Add a "check credentials" button next to the "save" button.
	 *
	 * @since 0.1.0
	 *
	 * @param string  $format    Form format
	 * @param string  $object_id CMB2 object ID
	 */
	public function add_check_connection_button( $format, $object_id ) {
		if ( $object_id != $this->key || ! $this->api()->connected() ) {
			return $format;
		}

		$url = str_replace( '%', '%%', esc_url( add_query_arg( 'check_credentials', 1 ) ) );

		$check_button = '<a class="qb-action-link" href="'. $url .'">' . __( 'Check API Connection', 'qbo-connect-ui' ) . '</a>';
		$check_button .= '' . $this->get_refresh_token_button() . '</p></form>';

		$format = str_replace(
			'</p></form>',
			$check_button,
			$format
		);

		return $format;
	}

	/**
	 * Add a "refresh token" button next to the "check credentials" button.
	 *
	 * @since  0.1.0
	 * @return string
	 */
	public function get_refresh_token_button() {
		$url = str_replace( '%', '%%', esc_url( add_query_arg( 'refresh_token', 1, remove_query_arg( 'check_credentials' ) ) ) );

		return '<a class="qb-action-link" href="'. $url .'">' . __( 'Refresh Authentication Token', 'qbo-connect-ui' ) . '</a>';
	}

	/**
	 * Add a "reset" button next to the "save" button.
	 *
	 * @since 0.1.0
	 *
	 * @param string  $format    Form format
	 * @param string  $object_id CMB2 object ID
	 */
	public function add_reset_connection_button( $format, $object_id ) {
		if ( $object_id != $this->key ) {
			return $format;
		}

		$reset_url = str_replace( '%', '%%', esc_url( $this->reset_url() ) );

		$reset_button = '<a class="button-secondary" href="'. $reset_url .'">' . __( 'Reset All Settings', 'qbo-connect-ui' ) . '</a></p></form>';
		// Add a check-api button to the form
		$format = str_replace(
			'</p></form>',
			$reset_button,
			$format
		);

		return $format;
	}

	/**
	 * Registers a notice to be output later.
	 *
	 * @since  0.1.0
	 * @uses   add_settings_error()
	 *
	 * @param  string      $message Text of output notice
	 * @param  boolean     $error   Whether notice is an error notice
	 * @param  string|null $title   Optional title
	 *
	 * @return void
	 */
	public function register_notice( $message, $error = true, $title = null ) {
		if ( is_null( $title ) ) {
			$title = $error
				? __( 'ERROR:', 'qbo-connect-ui' )
				: __( 'SUCCESS:', 'qbo-connect-ui' );
		}

		$type  = $error ? 'error' : 'updated';

		if ( $title ) {
			$title = $title ? '<h3>' . $title . '</h3>' : '';
			$message = $title . $message;
		}

		add_settings_error( $this->key . '-notices', $this->key, $message, $type );
	}

	/**
	 * Redirects to our settings page w/ any specified query args
	 *
	 * @since  0.1.0
	 * @uses   wp_redirect()
	 *
	 * @param  array   $args Optional array of query args
	 *
	 * @return void
	 */
	public function redirect( $args = array() ) {
		wp_redirect( $this->settings_url( $args ) );
		exit();
	}

	/**
	 * This settings page's URL with any specified query args
	 *
	 * @since  0.1.0
	 *
	 * @param  array   $args Optional array of query args
	 *
	 * @return string        Settings page URL.
	 */
	public function settings_url( $args = array() ) {
		$args['page'] = $this->key;
		return esc_url_raw( add_query_arg( $args, admin_url( 'options-general.php' ) ) );
	}

	/**
	 * This settings page's URL with a reset query arg
	 *
	 * @since  0.2.0
	 */
	public function reset_url() {
		return wp_nonce_url( $this->settings_url(), 'qb_reset_all', 'qb_reset_all' );
	}

	/**
	 * This settings page's URL with a qb_reauth query arg
	 *
	 * @since  0.2.0
	 */
	public function reauth_url() {
		return wp_nonce_url( $this->settings_url(), 'qb_reauth', 'qb_reauth' );
	}

	/**
	 * Get a setting from the stored settings values.
	 *
	 * @since  0.1.0
	 * @see    get_option()
	 * @see    cmb2_get_option()
	 *
	 * @param  string  $field_id Specifies the setting to retrieve.
	 *
	 * @return mixed             Setting value.
	 */
	public function get( $field_id = '', $default = false ) {
		if ( function_exists( 'cmb2_get_option' ) ) {
			$value = cmb2_get_option( $this->key, $field_id, $default );
		} else {

			$opts = get_option( $this->key );
			$value = $default;

			if ( 'all' == $field_id ) {
				$value = $opts;
			} elseif ( array_key_exists( $field_id, $opts ) ) {
				$value = false !== $opts[ $field_id ] ? $opts[ $field_id ] : $default;
			}
		}

		if ( $value && 'api_url' == $field_id ) {
			$value = trailingslashit( $value );
		}

		return $value;
	}

	/**
	 * Return (and initiate) API object.
	 *
	 * @return Zao\QBO_API\Connect
	 */
	public function api() {
		if ( $this->api->initiated && $this->api->client_id ) {
			// Has already been initated
			return $this->api;
		}

		$all = $this->get( 'all' );
		$all = is_array( $all ) ? array_filter( $all ) : false;

		// Make sure we have the bare minimums saved for making a connection.
		if ( empty( $all ) ) {
			return $this->api;
		}

		$args['client_id']     = $this->get( 'client_id' );
		$args['client_secret'] = $this->get( 'client_secret' );
		$args['sandbox']       = !! $this->get( 'sandbox' );
		$args['callback_uri']  = $this->settings_url();
		$args['autoredirect_authoriziation']  = false;

		// Initate the API.
		$this->api->init( $args );
		$this->api->api_url;

		if ( $this->api->is_authorizing() ) {
			$this->redirect( array( 'check_credentials' => 1 ) );
		}

		return $this->api;
	}
}
