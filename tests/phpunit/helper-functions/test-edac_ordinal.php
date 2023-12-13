<?php
/**
 * Class EDACOrdinal
 *
 * @package Accessibility_Checker
 */

/**
 * Sample test case.
 */
class EDACOrdinal extends WP_UnitTestCase {

	/**
	 * Tests the edac_ordinal function.
	 * 
	 * @dataProvider edac_ordinal_data
	 * 
	 * @param int|string $numeric_value The number we want to convert to ordinal.
	 * @param string     $ordinal_value The ordinal number that should be returned.
	 */
	public function test_edac_ordinal( $numeric_value, $ordinal_value ) {
		$this->assertSame( $ordinal_value, edac_ordinal( $numeric_value ) );
	}

	/**
	 * Data provider for test_edac_ordinal.
	 */
	public function edac_ordinal_data() {
		return array(
			'integer 1'      => array(
				'numeric_value' => 1,
				'ordinal_value' => '1st',
			),
			'string 1'       => array(
				'numeric_value' => '1',
				'ordinal_value' => '1st',
			),
			'integer 2'      => array(
				'numeric_value' => 2,
				'ordinal_value' => '2nd',
			),
			'integer 3'      => array(
				'numeric_value' => 3,
				'ordinal_value' => '3rd',
			),
			'integer 4'      => array(
				'numeric_value' => 4,
				'ordinal_value' => '4th',
			),
			'integer 5'      => array(
				'numeric_value' => 5,
				'ordinal_value' => '5th',
			),
			'integer 101'    => array(
				'numeric_value' => 101,
				'ordinal_value' => '101st',
			),
			'integer 102'    => array(
				'numeric_value' => 102,
				'ordinal_value' => '102nd',
			),
			'integer 103'    => array(
				'numeric_value' => 103,
				'ordinal_value' => '103rd',
			),
			'integer 104'    => array(
				'numeric_value' => 104,
				'ordinal_value' => '104th',
			),
			'integer 99701'  => array(
				'numeric_value' => 99701,
				'ordinal_value' => '99701st',
			),
			'invalid string' => array(
				'numeric_value' => 'foo',
				'ordinal_value' => '0th',
			),
			'float'          => array(
				'numeric_value' => 1.1,
				'ordinal_value' => '1st',
			),
		);
	}
}
