<?php
/**
 * Class IssueDensityTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_get_issue_density() function.
 */
class IssueDensityTest extends WP_UnitTestCase {

	/**
	 * Tests the edac_get_issue_density function.
	 *
	 * @dataProvider edac_get_issue_density_data
	 *
	 * @param int   $issue_count    Number of issues.
	 * @param int   $element_count  Number of elements.
	 * @param int   $content_length Content length.
	 * @param float $expected       Expected density score.
	 */
	public function test_edac_get_issue_density( $issue_count, $element_count, $content_length, $expected ) {
		$this->assertEquals(
			$expected,
			edac_get_issue_density( $issue_count, $element_count, $content_length )
		);
	}

	/**
	 * Data provider for test_edac_get_issue_density.
	 *
	 * @return array<string, array<string, int|float>>
	 */
	public function edac_get_issue_density_data() {
		return [
			'zero elements returns zero' => [
				'issue_count'    => 5,
				'element_count'  => 0,
				'content_length' => 100,
				'expected'       => 0,
			],
			'zero content returns zero'  => [
				'issue_count'    => 5,
				'element_count'  => 100,
				'content_length' => 0,
				'expected'       => 0,
			],
			'weighted calculation'       => [
				'issue_count'    => 4,
				'element_count'  => 100,
				'content_length' => 200,
				'expected'       => 3.6,
			],
			'rounding to two decimals'   => [
				'issue_count'    => 1,
				'element_count'  => 3,
				'content_length' => 7,
				'expected'       => 29.52,
			],
		];
	}
}
