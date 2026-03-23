<?php
/**
 * Tests for the edac_get_readability_panel_icon helper.
 *
 * @package Accessibility_Checker
 * @since 1.38.0
 */

/**
 * Tests for edac_get_readability_panel_icon.
 *
 * @covers ::edac_get_readability_panel_icon
 * @since 1.38.0
 */
class GetReadabilityPanelIconTest extends WP_UnitTestCase {

	/**
	 * Tests all icon-name outcomes via the data provider.
	 *
	 * @dataProvider provider_panel_icon_cases
	 *
	 * @param bool   $has_content          Whether the post has enough content.
	 * @param int    $post_grade           Flesch-Kincaid grade of the post.
	 * @param bool   $post_grade_failed    True when post grade > 9.
	 * @param string $summary_text         Simplified summary text.
	 * @param int    $summary_grade        Flesch-Kincaid grade of the summary.
	 * @param bool   $summary_grade_failed True when summary grade > 9.
	 * @param string $expected             Expected icon name.
	 */
	public function test_returns_expected_icon(
		bool $has_content,
		int $post_grade,
		bool $post_grade_failed,
		string $summary_text,
		int $summary_grade,
		bool $summary_grade_failed,
		string $expected
	): void {
		$this->assertSame(
			$expected,
			edac_get_readability_panel_icon(
				$has_content,
				$post_grade,
				$post_grade_failed,
				$summary_text,
				$summary_grade,
				$summary_grade_failed
			)
		);
	}

	/**
	 * Data provider for test_returns_expected_icon.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function provider_panel_icon_cases(): array {
		return [
			'no content returns warning'                  => [
				'has_content'          => false,
				'post_grade'           => 8,
				'post_grade_failed'    => false,
				'summary_text'         => '',
				'summary_grade'        => 0,
				'summary_grade_failed' => false,
				'expected'             => 'warning',
			],
			'post grade is zero returns warning'          => [
				'has_content'          => true,
				'post_grade'           => 0,
				'post_grade_failed'    => false,
				'summary_text'         => '',
				'summary_grade'        => 0,
				'summary_grade_failed' => false,
				'expected'             => 'warning',
			],
			'both no content and zero grade returns warning' => [
				'has_content'          => false,
				'post_grade'           => 0,
				'post_grade_failed'    => false,
				'summary_text'         => '',
				'summary_grade'        => 0,
				'summary_grade_failed' => false,
				'expected'             => 'warning',
			],
			'reading level below 9th grade returns check' => [
				'has_content'          => true,
				'post_grade'           => 7,
				'post_grade_failed'    => false,
				'summary_text'         => '',
				'summary_grade'        => 0,
				'summary_grade_failed' => false,
				'expected'             => 'check',
			],
			'reading level exactly 9th grade (not failed) returns check' => [
				'has_content'          => true,
				'post_grade'           => 9,
				'post_grade_failed'    => false,
				'summary_text'         => '',
				'summary_grade'        => 0,
				'summary_grade_failed' => false,
				'expected'             => 'check',
			],
			'reading level above 9 with no summary returns warning' => [
				'has_content'          => true,
				'post_grade'           => 11,
				'post_grade_failed'    => true,
				'summary_text'         => '',
				'summary_grade'        => 0,
				'summary_grade_failed' => false,
				'expected'             => 'warning',
			],
			'reading level above 9 with passing summary returns check' => [
				'has_content'          => true,
				'post_grade'           => 11,
				'post_grade_failed'    => true,
				'summary_text'         => 'A short and easy summary.',
				'summary_grade'        => 5,
				'summary_grade_failed' => false,
				'expected'             => 'check',
			],
			'reading level above 9 with failing summary returns warning' => [
				'has_content'          => true,
				'post_grade'           => 11,
				'post_grade_failed'    => true,
				'summary_text'         => 'A summary that is written at too high a reading level.',
				'summary_grade'        => 12,
				'summary_grade_failed' => true,
				'expected'             => 'warning',
			],
			'reading level above 9 with summary grade of zero returns warning' => [
				'has_content'          => true,
				'post_grade'           => 11,
				'post_grade_failed'    => true,
				'summary_text'         => 'Summary present but grade uncalculated.',
				'summary_grade'        => 0,
				'summary_grade_failed' => false,
				'expected'             => 'warning',
			],
		];
	}
}
