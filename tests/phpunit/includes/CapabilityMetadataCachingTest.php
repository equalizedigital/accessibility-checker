<?php
/**
 * Tests for edac_capability_metadata()'s static request cache.
 *
 * @package accessibility-checker
 */

/**
 * Edac_capability_metadata() runs the edac_capabilities filter and a
 * normalization pass, and is called from several places per request
 * (options page, Permissions page, sync bundle). It caches its result in a
 * static variable so that work happens at most once per request rather than
 * on every call site.
 */
class CapabilityMetadataCachingTest extends WP_UnitTestCase {

	/**
	 * A capability registered via the edac_capabilities filter AFTER the
	 * registry has already been read once must not appear in a later read
	 * within the same request - the cache serves the first computed value.
	 *
	 * @return void
	 */
	public function test_metadata_is_cached_after_first_call() {
		// Warm the cache (a no-op if an earlier test already warmed it).
		edac_capability_metadata();

		$late_slug = 'edac_test_late_registered_capability';
		$add_late  = function ( $capabilities ) use ( $late_slug ) {
			$capabilities[ $late_slug ] = [
				'label'         => 'Late capability',
				'description'   => '',
				'group'         => '',
				'owner'         => 'test',
				'pro'           => false,
				'floor'         => '',
				'default_roles' => [],
			];
			return $capabilities;
		};

		add_filter( 'edac_capabilities', $add_late );
		$metadata = edac_capability_metadata();
		remove_filter( 'edac_capabilities', $add_late );

		$this->assertArrayNotHasKey(
			$late_slug,
			$metadata,
			'edac_capability_metadata() should return the cached result, not re-run the edac_capabilities filter, on a later call within the same request.'
		);
	}

	/**
	 * Two calls in the same request must return an identical registry -
	 * the normalization pass is deterministic and only needs to run once.
	 *
	 * @return void
	 */
	public function test_repeated_calls_return_identical_metadata() {
		$first  = edac_capability_metadata();
		$second = edac_capability_metadata();

		$this->assertSame( $first, $second );
	}
}
