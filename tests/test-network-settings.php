<?php

class ZAPIC_Network_Settings_Test extends WP_UnitTestCase {

	function test_sample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	function test_class_exists() {
		$this->assertTrue( class_exists( 'ZAPIC_Network_Settings') );
	}

	function test_class_access() {
		$this->assertTrue( wp_api_connect_ui()->network-settings instanceof ZAPIC_Network_Settings );
	}
}
