<?php
/**
 * Class SampleTest
 *
 * @package Accessibility_Checker
 */

/**
 * Sample test case.
 */
class CompareStringsTest extends WP_UnitTestCase {

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
		return [
			'mixed upper/lower letters'            => [
				'string1'  => 'random string',
				'string2'  => 'RanDom StrIng',
				'expected' => true,
			],
			'different casing with same html tags' => [
				'string1'  => '<p>random string</p>',
				'string2'  => '<p>RanDom StrIng</p>',
				'expected' => true,
			],
			'with different html tags'             => [
				'string1'  => '<p>random string</p>',
				'string2'  => '<div>random string</div>',
				'expected' => true,
			],
			'containing "permalink of/to" strings' => [
				'string1'  => 'permalink of random string',
				'string2'  => 'permalink to random string',
				'expected' => true,
			],
			'containing "&nbsp;" strings'          => [
				'string1'  => 'random&nbsp; string',
				'string2'  => 'random string',
				'expected' => true,
			],
			'different strings'                    => [
				'string1'  => 'random string',
				'string2'  => 'different string',
				'expected' => false,
			],
		];
	}
}
