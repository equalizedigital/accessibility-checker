<?php
/**
 * Tests for the edac_normalize_fk_grade helper.
 *
 * @package Accessibility_Checker
 * @since x.x.x
 */

/**
 * Tests for edac_normalize_fk_grade.
 *
 * @covers ::edac_normalize_fk_grade
 * @since x.x.x
 */
class NormalizeFkGradeTest extends WP_UnitTestCase {

	/**
	 * Verifies FK grade normalization for the given input/expected pair.
	 *
	 * @dataProvider data_normalize_fk_grade
	 *
	 * @param float|bool|null $input    Raw FK grade value (float, or false/null from the library on empty content).
	 * @param int             $expected Expected normalized integer grade.
	 */
	public function test_normalize_fk_grade( $input, int $expected ) {
		$this->assertSame( $expected, edac_normalize_fk_grade( $input ) );
	}

	/**
	 * Data provider for test_normalize_fk_grade.
	 *
	 * @return array<string, array{mixed, int}>
	 */
	public static function data_normalize_fk_grade(): array {
		return [
			'zero stays zero'            => [ 0.0, 0 ],
			'negative stays zero'        => [ -1.5, 0 ],
			'fractional above zero is 1' => [ 0.01, 1 ],
			'mid-fraction is 1'          => [ 0.5, 1 ],
			'just below 1.0 is 1'        => [ 0.99, 1 ],
			'exactly 1.0 is 1'           => [ 1.0, 1 ],
			'1.9 floors to 1'            => [ 1.9, 1 ],
			'9.0 is 9'                   => [ 9.0, 9 ],
			'9.9 floors to 9'            => [ 9.9, 9 ],
			'10.0 is 10'                 => [ 10.0, 10 ],
			'whole grade passes through' => [ 5.0, 5 ],
			'false returns zero'         => [ false, 0 ],
			'null returns zero'          => [ null, 0 ],
		];
	}
}
