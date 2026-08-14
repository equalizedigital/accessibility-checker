<?php
/**
 * Regression test for the EDAC_CAPABILITY_MIGRATION_VERSION / EDAC_VERSION coupling.
 *
 * @package accessibility-checker
 */

/**
 * EDAC_CAPABILITY_MIGRATION_VERSION gates the one-time capability-bundle
 * migration in SyncCapability::reconcile() (see includes/options-page.php).
 * The doc comment above its definition requires the shipping plugin version
 * to be >= this value, so a site upgrading straight to the shipping release
 * still crosses the migration boundary. This drifted once already (bumped to
 * 1.49.0 while the actual next release was 1.48.0), which would have skipped
 * the ignore -> dismiss migration entirely for that release.
 */
class CapabilityVersionCouplingTest extends WP_UnitTestCase {

	/**
	 * The shipping plugin version (EDAC_VERSION) must never be older than the
	 * capability migration boundary, or upgraders would never cross it and
	 * would be left on the pre-migration capability slugs/grants.
	 *
	 * @return void
	 */
	public function test_plugin_version_is_not_older_than_the_migration_version() {
		$this->assertTrue(
			version_compare( EDAC_VERSION, EDAC_CAPABILITY_MIGRATION_VERSION, '>=' ),
			sprintf(
				'EDAC_VERSION (%s) must be >= EDAC_CAPABILITY_MIGRATION_VERSION (%s), or the capability migration boundary is unreachable by the shipping release.',
				EDAC_VERSION,
				EDAC_CAPABILITY_MIGRATION_VERSION
			)
		);
	}
}
