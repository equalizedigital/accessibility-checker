<?php
/**
 * Tests for the scannable post types setting and the scannable post count.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Settings;

/**
 * Tests for Settings::get_scannable_post_types() and Settings::get_scannable_posts_count().
 *
 * @covers \EDAC\Admin\Settings::get_scannable_post_types
 * @covers \EDAC\Admin\Settings::get_scannable_posts_count
 */
class ScannablePostTypesTest extends WP_UnitTestCase {

	/**
	 * The post type registered by the tests that need a non-builtin public post type.
	 *
	 * @var string
	 */
	private const CUSTOM_POST_TYPE = 'edac_test_public';

	/**
	 * The callback registered on the post status filter by the current test, if any.
	 *
	 * @var callable|null
	 */
	private $status_filter_callback;

	/**
	 * Starts every test from a known option state.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		delete_option( 'edac_post_types' );
	}

	/**
	 * Removes anything a test registered.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if ( $this->status_filter_callback ) {
			remove_filter( 'edac_scannable_post_statuses', $this->status_filter_callback );
			$this->status_filter_callback = null;
		}

		if ( post_type_exists( self::CUSTOM_POST_TYPE ) ) {
			unregister_post_type( self::CUSTOM_POST_TYPE );
		}

		parent::tearDown();
	}

	/**
	 * Tests that nothing is scannable before the setting has been saved.
	 *
	 * @return void
	 */
	public function test_post_types_are_empty_when_the_option_is_not_set(): void {
		$this->assertSame( [], Settings::get_scannable_post_types() );
	}

	/**
	 * Tests that a non-array stored value is treated as nothing selected.
	 *
	 * @return void
	 */
	public function test_post_types_are_empty_when_the_option_is_not_an_array(): void {
		update_option( 'edac_post_types', 'post' );

		$this->assertSame( [], Settings::get_scannable_post_types() );
	}

	/**
	 * Tests that an empty selection returns an empty list.
	 *
	 * @return void
	 */
	public function test_post_types_are_empty_when_the_option_is_an_empty_array(): void {
		update_option( 'edac_post_types', [] );

		$this->assertSame( [], Settings::get_scannable_post_types() );
	}

	/**
	 * Tests the list returned for a valid selection.
	 *
	 * @return void
	 */
	public function test_returns_the_selected_post_types(): void {
		update_option( 'edac_post_types', [ 'post', 'page' ] );

		$this->assertSame( [ 'post', 'page' ], Settings::get_scannable_post_types() );
	}

	/**
	 * Tests that a post type selected twice is only returned once.
	 *
	 * @return void
	 */
	public function test_removes_duplicate_post_types(): void {
		update_option( 'edac_post_types', [ 'post', 'page', 'post' ] );

		$this->assertSame( [ 'post', 'page' ], Settings::get_scannable_post_types() );
	}

	/**
	 * Tests that post types which are not scannable are dropped from the selection.
	 *
	 * @param string[] $selected The stored selection.
	 * @param string[] $expected The expected list of scannable post types.
	 *
	 * @dataProvider provider_invalid_post_types
	 *
	 * @return void
	 */
	public function test_removes_post_types_that_are_not_scannable( array $selected, array $expected ): void {
		update_option( 'edac_post_types', $selected );

		$this->assertSame( $expected, Settings::get_scannable_post_types() );
	}

	/**
	 * Provides selections that contain post types which must not be returned.
	 *
	 * @return array<string, array<int, string[]>>
	 */
	public function provider_invalid_post_types(): array {
		return [
			'unregistered'         => [ [ 'post', 'edac_not_a_post_type' ], [ 'post' ] ],
			'attachment'           => [ [ 'post', 'attachment' ], [ 'post' ] ],
			'a non-public builtin' => [ [ 'post', 'nav_menu_item' ], [ 'post' ] ],
			'all invalid'          => [ [ 'edac_nope', 'edac_also_nope' ], [] ],
		];
	}

	/**
	 * Tests that a registered public post type which is not a builtin is dropped.
	 *
	 * The query behind the setting is limited to public builtin post types, so a
	 * plugin-registered post type is never scannable even though it exists.
	 *
	 * @return void
	 */
	public function test_removes_registered_post_types_that_are_not_builtin(): void {
		register_post_type(
			self::CUSTOM_POST_TYPE,
			[
				'label'  => 'EDAC Test Type',
				'public' => true,
			]
		);
		$this->assertTrue( post_type_exists( self::CUSTOM_POST_TYPE ) );

		update_option( 'edac_post_types', [ self::CUSTOM_POST_TYPE, 'post' ] );

		$this->assertSame( [ 1 => 'post' ], Settings::get_scannable_post_types() );
	}

	/**
	 * Tests that the remaining post types keep their original keys.
	 *
	 * The invalid entry is unset in place, so keys are not reindexed. Callers
	 * treat the return value as a list, but the keys are asserted here so a change
	 * to that behaviour is visible.
	 *
	 * @return void
	 */
	public function test_preserves_the_keys_of_the_remaining_post_types(): void {
		update_option( 'edac_post_types', [ 'post', 'edac_nope', 'page' ] );

		$this->assertSame(
			[
				0 => 'post',
				2 => 'page',
			],
			Settings::get_scannable_post_types()
		);
	}

	/**
	 * Tests that the skip filtering argument does not change the free plugin result.
	 *
	 * The argument is only forwarded to the Pro settings class.
	 *
	 * @return void
	 */
	public function test_skip_filtering_does_not_change_the_result(): void {
		update_option( 'edac_post_types', [ 'post', 'page' ] );

		$this->assertSame( [ 'post', 'page' ], Settings::get_scannable_post_types( true ) );
	}

	/**
	 * Tests that the Pro settings class is used when it is available.
	 *
	 * Runs in its own process so the stubbed Pro class does not leak into any
	 * other test in the run.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 *
	 * @return void
	 */
	public function test_delegates_to_the_pro_settings_class_when_available(): void {
		// phpcs:ignore Squiz.PHP.Eval.Discouraged -- Stubbing the Pro plugin class for this test only.
		eval(
			'
			namespace EqualizeDigital\AccessibilityCheckerPro\Admin;
			class Settings {
				public static function get_scannable_post_types( $skip_filtering = false ) {
					return [ $skip_filtering ? "skip-filtering" : "with-filtering" ];
				}
			}
			'
		);

		$this->assertSame( [ 'with-filtering' ], Settings::get_scannable_post_types() );
		$this->assertSame( [ 'skip-filtering' ], Settings::get_scannable_post_types( true ) );
	}

	/**
	 * Tests that the count is zero when no post type is scannable.
	 *
	 * @return void
	 */
	public function test_count_is_zero_without_scannable_post_types(): void {
		$this->assertSame( 0, Settings::get_scannable_posts_count() );
	}

	/**
	 * Tests that the count is zero when every selected post type is invalid.
	 *
	 * @return void
	 */
	public function test_count_is_zero_when_all_selected_post_types_are_invalid(): void {
		update_option( 'edac_post_types', [ 'edac_not_a_post_type' ] );

		$this->assertSame( 0, Settings::get_scannable_posts_count() );
	}

	/**
	 * Tests that the count is zero when no post status is scannable.
	 *
	 * @return void
	 */
	public function test_count_is_zero_without_scannable_post_statuses(): void {
		update_option( 'edac_post_types', [ 'post' ] );

		$this->status_filter_callback = static function () {
			return [];
		};
		add_filter( 'edac_scannable_post_statuses', $this->status_filter_callback );

		$this->assertSame( 0, Settings::get_scannable_posts_count() );
	}

	/**
	 * Tests that only posts of a scannable type and status are counted.
	 *
	 * The count is compared against a baseline taken before the fixtures are
	 * created because the test suite ships its own content.
	 *
	 * @return void
	 */
	public function test_counts_only_scannable_post_types_and_statuses(): void {
		update_option( 'edac_post_types', [ 'post', 'page' ] );

		$baseline = (int) Settings::get_scannable_posts_count();

		// Counted: the default statuses are publish, future, draft, pending and private.
		$this->create_post( 'post', 'publish' );
		$this->create_post( 'post', 'publish' );
		$this->create_post( 'page', 'publish' );
		$this->create_post( 'post', 'draft' );
		$this->create_post( 'post', 'pending' );
		$this->create_post( 'post', 'private' );

		// Not counted: trashed posts are never scanned.
		$this->create_post( 'post', 'trash' );

		// Not counted: a registered public post type is not a builtin one.
		register_post_type(
			self::CUSTOM_POST_TYPE,
			[
				'label'  => 'EDAC Test Type',
				'public' => true,
			]
		);
		$this->assertTrue( post_type_exists( self::CUSTOM_POST_TYPE ) );
		$this->create_post( self::CUSTOM_POST_TYPE, 'publish' );

		// Not counted: attachments are removed from the list of valid post types.
		$this->create_post( 'attachment', 'inherit' );

		$this->assertSame( $baseline + 6, (int) Settings::get_scannable_posts_count() );
	}

	/**
	 * Tests that a filtered list of post statuses is what gets counted.
	 *
	 * @return void
	 */
	public function test_respects_the_filtered_post_status_list(): void {
		update_option( 'edac_post_types', [ 'post', 'page' ] );

		$this->status_filter_callback = static function () {
			return [ 'publish' ];
		};
		add_filter( 'edac_scannable_post_statuses', $this->status_filter_callback );

		$baseline = (int) Settings::get_scannable_posts_count();

		$this->create_post( 'post', 'publish' );
		$this->create_post( 'page', 'publish' );
		$this->create_post( 'post', 'draft' );
		$this->create_post( 'post', 'pending' );
		$this->create_post( 'post', 'private' );

		$this->assertSame( $baseline + 2, (int) Settings::get_scannable_posts_count() );
	}

	/**
	 * Creates a post of the given type and status.
	 *
	 * @param string $post_type   The post type.
	 * @param string $post_status The post status.
	 *
	 * @return int The created post ID.
	 */
	private function create_post( string $post_type, string $post_status ): int {
		return $this->factory()->post->create(
			[
				'post_type'   => $post_type,
				'post_status' => $post_status,
			]
		);
	}
}
