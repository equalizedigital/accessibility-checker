<?php
/**
 * Class SampleTest
 *
 * @package Accessibility_Checker
 */

/**
 * Sample test case.
 */
class EDACCompareStrings extends WP_UnitTestCase {

	/**
	 * Tests the edac_compare_strings function.
	 * 
	 * @dataProvider edac_compare_strings_data
	 * 
	 * @param string $string1  The 1st string to compare.
	 * @param string $string2  The 2nd string to compare.
	 * @param string $expected The expected result of the comparison.
	 */
	public function test_edac_compare_strings( $string1, $string2, $expected ) {
		$this->assertSame(
			$expected, 
			edac_compare_strings( $string1, $string2 ) 
		);
	}

	/**
	 * Data provider for test_edac_compare_strings.
	 */
	public function edac_compare_strings_data() {
		return array(
			'mixed upper/lower letters'            => array(
				'string1'  => 'random string',
				'string2'  => 'RanDom StrIng',
				'expected' => true,
			),
			'different casing with same html tags' => array(
				'string1'  => '<p>random string</p>',
				'string2'  => '<p>RanDom StrIng</p>',
				'expected' => true,
			),
			'with different html tags'             => array(
				'string1'  => '<p>random string</p>',
				'string2'  => '<div>random string</div>',
				'expected' => true,
			),
			'containing "permalink of/to" strings' => array(
				'string1'  => 'permalink of random string',
				'string2'  => 'permalink to random string',
				'expected' => true,
			),
			'containing "&nbsp;" strings'          => array(
				'string1'  => 'random&nbsp; string',
				'string2'  => 'random string',
				'expected' => true,
			),
			'different strings'                    => array(
				'string1'  => 'random string',
				'string2'  => 'different string',
				'expected' => false,
			),
		);
	}
}
