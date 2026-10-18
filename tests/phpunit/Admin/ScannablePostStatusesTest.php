<?php
/**
 * Tests for the scannable post statuses setting.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Settings;

/**
 * Tests for Settings::get_scannable_post_statuses().
 *
 * @covers \EDAC\Admin\Settings::get_scannable_post_statuses
 */
class ScannablePostStatusesTest extends WP_UnitTestCase {

	/**
	 * The post statuses the plugin scans by default.
	 *
	 * Kept as a literal so a change to the default list in
	 * admin/class-settings.php fails here instead of silently passing.
	 *
	 * @var string[]
	 */
	private const DEFAULT_STATUSES = [ 'publish', 'future', 'draft', 'pending', 'private' ];

	/**
	 * The callback registered on the filter by the current test, if any.
	 *
	 * @var callable|null
	 */
	private $filter_callback;

	/**
	 * Removes any filter a test registered.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if ( $this->filter_callback ) {
			remove_filter( 'edac_scannable_post_statuses', $this->filter_callback );
			$this->filter_callback = null;
		}

		parent::tearDown();
	}

	/**
	 * Tests the default returned list and its order.
	 *
	 * @return void
	 */
	public function test_returns_the_default_scannable_statuses(): void {
		$this->assertSame( self::DEFAULT_STATUSES, Settings::get_scannable_post_statuses() );
	}

	/**
	 * Tests statuses that must never be treated as scannable.
	 *
	 * @param string $status Status that is not scannable.
	 *
	 * @dataProvider provider_not_scannable_statuses
	 *
	 * @return void
	 */
	public function test_excludes_statuses_that_are_not_scannable( string $status ): void {
		$this->assertNotContains( $status, Settings::get_scannable_post_statuses() );
	}

	/**
	 * Provides statuses that are not scannable.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_not_scannable_statuses(): array {
		return [
			'trash'      => [ 'trash' ],
			'auto-draft' => [ 'auto-draft' ],
			'inherit'    => [ 'inherit' ],
		];
	}

	/**
	 * Tests the filter can replace the list of scannable statuses.
	 *
	 * @return void
	 */
	public function test_filter_can_replace_the_status_list(): void {
		$this->filter_callback = static function () {
			return [ 'publish' ];
		};

		add_filter( 'edac_scannable_post_statuses', $this->filter_callback );

		$this->assertSame( [ 'publish' ], Settings::get_scannable_post_statuses() );
	}

	/**
	 * Tests the filter receives the default list as its first argument.
	 *
	 * @return void
	 */
	public function test_filter_receives_the_default_statuses(): void {
		$received = null;

		$this->filter_callback = function ( $statuses ) use ( &$received ) {
			$received = $statuses;

			return $statuses;
		};

		add_filter( 'edac_scannable_post_statuses', $this->filter_callback );

		$this->assertSame( self::DEFAULT_STATUSES, Settings::get_scannable_post_statuses() );
		$this->assertSame( self::DEFAULT_STATUSES, $received );
	}

	/**
	 * Tests the filter can add a status without dropping the defaults.
	 *
	 * @return void
	 */
	public function test_filter_can_extend_the_status_list(): void {
		$this->filter_callback = static function ( $statuses ) {
			$statuses[] = 'trash';

			return $statuses;
		};

		add_filter( 'edac_scannable_post_statuses', $this->filter_callback );

		$expected   = self::DEFAULT_STATUSES;
		$expected[] = 'trash';

		$this->assertSame( $expected, Settings::get_scannable_post_statuses() );
	}
}
