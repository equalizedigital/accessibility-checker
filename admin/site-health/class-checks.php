<?php
/**
 * Site Health status checks for Accessibility Checker.
 *
 * @since 1.29.0
 * @package Accessibility_Checker
 */

namespace EDAC\Admin\SiteHealth;

use EDAC\Admin\Scans_Stats;
use EDAC\Admin\Settings;

/**
 * Adds Site Health tests for Accessibility Checker.
 *
 * @since 1.29.0
 */
class Checks {
	/**
	 * Cached scan stats for this request.
	 *
	 * @var array|null
	 */
	private ?array $stats = null;

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

		$tests['direct']['edac_post_types'] = [
			'label' => __( 'Post types configured for accessibility checks', 'accessibility-checker' ),
			'test'  => [ $this, 'test_post_types_configured' ],
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
	 * Get scan stats, caching for this request.
	 *
	 * @return array
	 */
	private function get_stats(): array {
		if ( null === $this->stats ) {
			$this->stats = ( new Scans_Stats() )->summary();
		}
		return $this->stats;
	}

	/**
	 * Get the appropriate URL for viewing issues based on available features.
	 *
	 * @return array Array with 'url' and 'text' keys.
	 */
	private function get_issues_link(): array {
		// Check if Pro version is available and has the issues page.
		if ( defined( 'EDACP_VERSION' ) && defined( 'EDAC_KEY_VALID' ) && EDAC_KEY_VALID ) {
			return [
				'url'  => admin_url( 'admin.php?page=accessibility_checker_issues' ),
				'text' => __( 'View Issues', 'accessibility-checker' ),
			];
		}

		// Fallback to the main welcome page for free version.
		return [
			'url'  => admin_url( 'admin.php?page=accessibility_checker' ),
			'text' => __( 'View Accessibility Checker', 'accessibility-checker' ),
		];
	}

	/**
	 * Test if there are accessibility issues on the site.
	 *
	 * @return array
	 */
	public function test_for_issues(): array {
		$stats    = $this->get_stats();
		$errors   = absint( $stats['errors'] ?? 0 );
		$warnings = absint( $stats['warnings'] ?? 0 );

		if ( $errors > 0 || $warnings > 0 ) {
			$issues_link = $this->get_issues_link();
			
			return [
				'status'      => 'recommended',
				'label'       => __( 'Accessibility issues detected', 'accessibility-checker' ),
				'description' => sprintf(
				// translators: 1: error count, 2: warning count.
					__( 'Accessibility Checker has detected %1$s errors and %2$s warnings.', 'accessibility-checker' ),
					number_format_i18n( $errors ),
					number_format_i18n( $warnings )
				),
				'actions'     => sprintf(
					'<p><a href="%1$s" class="button button-primary">%2$s</a></p>',
					esc_url( $issues_link['url'] ),
					esc_html( $issues_link['text'] )
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
		$stats   = $this->get_stats();
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
			'label'       => __( 'Content is being checked for accessibility', 'accessibility-checker' ),
			'description' => sprintf(
				// translators: %s is the number of posts scanned.
				_n( 'Accessibility Checker has scanned %s post for accessibility issues.', 'Accessibility Checker has scanned %s posts for accessibility issues.', $scanned, 'accessibility-checker' ),
				number_format_i18n( $scanned )
			),
			'test'        => 'edac_scanned',
			'badge'       => $this->get_accessibility_badge(),
		];
	}

	/**
	 * Test if post types are configured for scanning.
	 *
	 * @return array
	 */
	public function test_post_types_configured(): array {
		$scannable_post_types = Settings::get_scannable_post_types();

		if ( empty( $scannable_post_types ) ) {
			return [
				'status'      => 'critical',
				'label'       => __( 'No post types selected for accessibility checking', 'accessibility-checker' ),
				'description' => __( 'Accessibility Checker cannot scan any content because no post types have been selected. Without configured post types, no accessibility issues will be detected.', 'accessibility-checker' ),
				'actions'     => sprintf(
					'<p><a href="%1$s" class="button button-primary">%2$s</a></p>',
					esc_url( admin_url( 'admin.php?page=accessibility_checker_settings' ) ),
					esc_html__( 'Configure post types', 'accessibility-checker' )
				),
				'test'        => 'edac_post_types',
				'badge'       => $this->get_accessibility_badge( 'red' ),
			];
		}

		$post_type_count = count( $scannable_post_types );
		$post_type_names = implode( ', ', $scannable_post_types );

		return [
			'status'      => 'good',
			'label'       => __( 'Post types are configured for accessibility checking', 'accessibility-checker' ),
			'description' => sprintf(
				// translators: 1: number of post types, 2: comma-separated list of post type names.
				_n( 'Accessibility Checker is configured to scan %1$s post type: %2$s.', 'Accessibility Checker is configured to scan %1$s post types: %2$s.', $post_type_count, 'accessibility-checker' ),
				number_format_i18n( $post_type_count ),
				$post_type_names
			),
			'test'        => 'edac_post_types',
			'badge'       => $this->get_accessibility_badge(),
		];
	}
}
