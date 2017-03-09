<?php
use Zao\WP_API\Storage\Transients;

/**
 * WP API Connect UI Storage Transients
 * @version 0.1.0
 * @package WP API Connect UI
 */
class ZAPIC_Storage_Transients extends Transients {
	protected function get_from_db() {
		return call_user_func_array( 'get_site_transient', func_get_args() );
	}

	protected function delete_from_db() {
		return call_user_func_array( 'delete_site_transient', func_get_args() );
	}

	protected function update_db() {
		return call_user_func_array( 'set_site_transient', func_get_args() );
	}
}
