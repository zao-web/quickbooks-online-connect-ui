<?php

class ZAPIC_Compatibility_Test extends WP_UnitTestCase {

	function test_sample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	function test_class_exists() {
		$this->assertTrue( class_exists( 'ZAPIC_Compatibility') );
	}

	function test_class_access() {
		$this->assertTrue( wp_api_connect_ui()->compatibility instanceof ZAPIC_Compatibility );
	}
}
