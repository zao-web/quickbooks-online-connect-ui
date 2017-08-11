<?php

class Zao_QBO_API_Compatibility_Test extends WP_UnitTestCase {

	function test_sample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	function test_class_exists() {
		$this->assertTrue( class_exists( 'Zao_QBO_API_Compatibility') );
	}

	function test_class_access() {
		$this->assertTrue( qbo_connect_ui()->compatibility instanceof Zao_QBO_API_Compatibility );
	}
}
