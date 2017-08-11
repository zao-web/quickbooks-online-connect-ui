<?php
use Zao\QBO_API\Storage\Options;

/**
 * Quickbooks Online Connect UI Storage Options
 * @version 0.1.0
 * @package Quickbooks Online Connect UI
 */
class Zao_QBO_API_Storage_Options extends Options {
	protected function get_from_db() {
		return call_user_func_array( 'get_site_option', func_get_args() );
	}

	protected function delete_from_db() {
		return call_user_func_array( 'delete_site_option', func_get_args() );
	}

	protected function update_db() {
		return call_user_func_array( 'update_site_option', func_get_args() );
	}

	protected function add_db() {
		return call_user_func_array( 'add_site_option', func_get_args() );
	}
}
