<?php
/**
 * Site Health status checks for Accessibility Checker.
 *
 * @since 1.29.0
 * @package Accessibility_Checker
 */

namespace EDAC\Admin\SiteHealth;

use EDAC\Admin\Scans_Stats;

/**
 * Adds Site Health tests for Accessibility Checker.
 *
 * @since 1.29.0
 */
class Checks {

	/**
	 * Init hooks.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_filter( 'site_status_tests', [ $this, 'register_tests' ] );
	}

	/**
	 * Register our tests with Site Health.
	 *
	 * @param array $tests Existing tests.
	 * @return array
	 */
	public function register_tests( array $tests ): array {
		$tests['direct']['edac_issues'] = [
			'label' => __( 'Accessibility issues', 'accessibility-checker' ),
			'test'  => [ $this, 'test_for_issues' ],
		];

		$tests['direct']['edac_scanned'] = [
			'label' => __( 'Content checked for accessibility', 'accessibility-checker' ),
			'test'  => [ $this, 'test_content_scanned' ],
		];

		return $tests;
	}

	/**
	 * Returns the badge for Accessibility Checker site health tests.
	 *
	 * @param string $color The color for the badge (e.g., 'blue', 'orange', 'red', 'green').
	 * @return array
	 */
	protected function get_accessibility_badge( string $color = 'blue' ): array {
		return [
			'label' => __( 'Accessibility', 'accessibility-checker' ),
			'color' => $color,
		];
	}

	/**
	 * Test if there are accessibility issues on the site.
	 *
	 * @return array
	 */
	public function test_for_issues(): array {
		$stats    = ( new Scans_Stats( 60 * 5 ) )->summary();
		$errors   = absint( $stats['errors'] ?? 0 );
		$warnings = absint( $stats['warnings'] ?? 0 );

		if ( $errors > 0 || $warnings > 0 ) {
			return [
				'status'      => 'recommended',
				'label'       => __( 'Accessibility issues detected', 'accessibility-checker' ),
				'description' => sprintf(
				// translators: 1: error count, 2: warning count.
					__( 'Accessibility Checker has detected %1$d errors and %2$d warnings.', 'accessibility-checker' ),
					$errors,
					$warnings
				),
				'actions'     => sprintf(
					'<p><a href="%1$s" class="button button-primary">%2$s</a></p>',
					esc_url( admin_url( 'admin.php?page=accessibility_checker_issues' ) ),
					esc_html__( 'View issues', 'accessibility-checker' )
				),
				'test'        => 'edac_issues',
				'badge'       => $this->get_accessibility_badge(),
			];
		}

		return [
			'status'      => 'good',
			'label'       => __( 'No accessibility issues detected', 'accessibility-checker' ),
			'description' => __( 'Accessibility Checker has not found any issues in scanned content.', 'accessibility-checker' ),
			'test'        => 'edac_issues',
			'badge'       => $this->get_accessibility_badge(),
		];
	}

	/**
	 * Test if any posts have been scanned.
	 *
	 * @return array
	 */
	public function test_content_scanned(): array {
		$stats   = ( new Scans_Stats( 60 * 5 ) )->summary();
		$scanned = absint( $stats['posts_scanned'] ?? 0 );

		if ( 0 === $scanned ) {
			return [
				'status'      => 'recommended',
				'label'       => __( 'Content has not been checked', 'accessibility-checker' ),
				'description' => __( 'No posts have been scanned yet. Run a full site scan to begin checking your content for accessibility issues.', 'accessibility-checker' ),
				'actions'     => sprintf(
					'<p><a href="%1$s" class="button button-primary">%2$s</a></p>',
					esc_url( admin_url( 'admin.php?page=accessibility_checker_full_site_scan' ) ),
					esc_html__( 'Start full site scan', 'accessibility-checker' )
				),
				'test'        => 'edac_scanned',
				'badge'       => $this->get_accessibility_badge(),
			];
		}

		return [
			'status'      => 'good',
			'label'       => __( 'Content is being checked', 'accessibility-checker' ),
			'description' => sprintf(
				_n( 'Accessibility Checker has scanned %d post.', 'Accessibility Checker has scanned %d posts.', $scanned, 'accessibility-checker' ),
				$scanned
			),
			'test'        => 'edac_scanned',
			'badge'       => $this->get_accessibility_badge(),
		];
	}
}
