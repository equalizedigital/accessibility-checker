<?php
/**
 * Tests for the landmark helper functions.
 *
 * @package Accessibility_Checker
 */

/**
 * Tests for edac_get_landmark_types() and edac_get_landmark_filter_options().
 *
 * @covers ::edac_get_landmark_types
 * @covers ::edac_get_landmark_filter_options
 */
class LandmarkTypesTest extends WP_UnitTestCase {

	/**
	 * Removes filters added by a test.
	 */
	public function tearDown(): void {
		remove_all_filters( 'edac_landmark_types' );

		parent::tearDown();
	}

	/**
	 * Replaces the landmark types with the given array.
	 *
	 * @param array $types Landmark types the filter should return.
	 */
	private function filter_landmark_types( array $types ): void {
		add_filter(
			'edac_landmark_types',
			static function () use ( $types ) {
				return $types;
			}
		);
	}

	/**
	 * The defaults are the four expected key sets.
	 */
	public function test_get_landmark_types_returns_expected_defaults(): void {
		$this->assertSame(
			[
				'tags'             => [ 'MAIN', 'HEADER', 'FOOTER', 'NAV', 'ASIDE' ],
				'roles'            => [ 'main', 'navigation', 'banner', 'contentinfo', 'complementary' ],
				'conditionalTags'  => [ 'SECTION', 'ARTICLE', 'FORM' ],
				'conditionalRoles' => [ 'region', 'article', 'form' ],
			],
			edac_get_landmark_types()
		);
	}

	/**
	 * A filtered array is returned in place of the defaults.
	 */
	public function test_get_landmark_types_returns_filtered_array(): void {
		$types           = edac_get_landmark_types();
		$types['tags'][] = 'DIALOG';

		$this->filter_landmark_types( $types );

		$this->assertSame( [ 'MAIN', 'HEADER', 'FOOTER', 'NAV', 'ASIDE', 'DIALOG' ], edac_get_landmark_types()['tags'] );
	}

	/**
	 * A filter that returns something other than an array is ignored.
	 */
	public function test_get_landmark_types_falls_back_when_filter_is_not_an_array(): void {
		add_filter(
			'edac_landmark_types',
			static function () {
				return 'MAIN';
			}
		);

		$this->assertSame( [ 'MAIN', 'HEADER', 'FOOTER', 'NAV', 'ASIDE' ], edac_get_landmark_types()['tags'] );
	}

	/**
	 * Options are lowercased { value, label } pairs, deduplicated across all four sets.
	 */
	public function test_get_landmark_filter_options_returns_deduped_value_label_pairs(): void {
		$this->assertSame(
			[
				[
					'value' => 'main',
					'label' => 'Main',
				],
				[
					'value' => 'header',
					'label' => 'Header',
				],
				[
					'value' => 'footer',
					'label' => 'Footer',
				],
				[
					'value' => 'nav',
					'label' => 'Nav',
				],
				[
					'value' => 'aside',
					'label' => 'Aside',
				],
				[
					'value' => 'navigation',
					'label' => 'Navigation',
				],
				[
					'value' => 'banner',
					'label' => 'Banner',
				],
				[
					'value' => 'contentinfo',
					'label' => 'Contentinfo',
				],
				[
					'value' => 'complementary',
					'label' => 'Complementary',
				],
				[
					'value' => 'section',
					'label' => 'Section',
				],
				[
					'value' => 'article',
					'label' => 'Article',
				],
				[
					'value' => 'form',
					'label' => 'Form',
				],
				[
					'value' => 'region',
					'label' => 'Region',
				],
			],
			edac_get_landmark_filter_options()
		);
	}

	/**
	 * Values added by the filter are lowercased, deduplicated and labelled.
	 */
	public function test_get_landmark_filter_options_follows_the_filter(): void {
		$this->filter_landmark_types(
			[
				'tags'             => [ 'MAIN', 'DIALOG' ],
				'roles'            => [ 'main' ],
				'conditionalTags'  => [ 'section' ],
				'conditionalRoles' => [ 'figure' ],
			]
		);

		$this->assertSame(
			[
				[
					'value' => 'main',
					'label' => 'Main',
				],
				[
					'value' => 'dialog',
					'label' => 'Dialog',
				],
				[
					'value' => 'section',
					'label' => 'Section',
				],
				[
					'value' => 'figure',
					'label' => 'Figure',
				],
			],
			edac_get_landmark_filter_options()
		);
	}

	/**
	 * Missing keys are tolerated rather than raising a warning.
	 */
	public function test_get_landmark_filter_options_tolerates_missing_keys(): void {
		$this->filter_landmark_types( [ 'roles' => [ 'COMPLEMENTARY' ] ] );

		$this->assertSame(
			[
				[
					'value' => 'complementary',
					'label' => 'Complementary',
				],
			],
			edac_get_landmark_filter_options()
		);
	}
}
