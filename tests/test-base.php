<?php

class BaseTest extends WP_UnitTestCase {

	function test_class_exists() {
		$this->assertTrue( class_exists( 'Zao_QBO_API_Connect_UI') );
	}

	function test_get_instance() {
		$this->assertTrue( qbo_connect_ui() instanceof Zao_QBO_API_Connect_UI );
	}
}
