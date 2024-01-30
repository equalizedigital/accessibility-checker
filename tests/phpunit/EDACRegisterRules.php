<?php
/**
 * Class SampleTest
 *
 * @package Accessibility_Checker
 */

/**
 * Sample test case.
 */
class EDACRegisterRules extends WP_UnitTestCase {

	/**
	 * Tests the edac_register_rules function.
	 */
	public function test_edac_register_rules() {
		$rules = edac_register_rules();
		$this->assertIsArray( $rules );
		$this->assertNotEmpty( $rules );
	}
}
