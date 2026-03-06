<?php
/**
 * Class IssuesQueryTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Issues_Query;

/**
 * Test cases for EDAC\Admin\Issues_Query.
 */
class IssuesQueryTest extends WP_UnitTestCase {

	/**
	 * Ensure color contrast rule slug is not duplicated when already present.
	 */
	public function test_color_contrast_rule_slug_not_duplicated_when_first() {
		$query = new Issues_Query(
			[
				'rule_types' => [ Issues_Query::RULETYPE_COLOR_CONTRAST ],
				'rule_slugs' => [ 'color_contrast_failure' ],
			],
			100000,
			Issues_Query::FLAG_INCLUDE_ALL_POST_TYPES
		);

		$sql = $query->get_sql();

		$this->assertSame(
			1,
			substr_count( $sql, 'color_contrast_failure' ),
			'Expected color_contrast_failure to appear once in the SQL.'
		);
	}
}
