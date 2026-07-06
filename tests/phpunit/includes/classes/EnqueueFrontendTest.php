<?php
/**
 * Test cases for the Enqueue_Frontend class.
 *
 * @package accessibility-checker
 */

use EDAC\Inc\Enqueue_Frontend;

/**
 * Tests for Enqueue_Frontend behavior.
 */
class EnqueueFrontendTest extends WP_UnitTestCase {

	/**
	 * Stored filter callbacks to be removed in tearDown.
	 *
	 * @var array<string, callable>
	 */
	private array $added_filters = [];

	/**
	 * Set up test state.
	 */
	protected function setUp(): void {
		parent::setUp();

		update_option( 'edac_post_types', [ 'post' ] );

		global $wp_scripts, $wp_styles;
		$wp_scripts = new \WP_Scripts();
		$wp_styles  = new \WP_Styles();
	}

	/**
	 * Clean up test state.
	 */
	protected function tearDown(): void {
		foreach ( $this->added_filters as $hook => $callback ) {
			remove_filter( $hook, $callback );
		}
		$this->added_filters = [];

		delete_option( 'edac_post_types' );

		global $wp_scripts, $wp_styles, $post;
		unset( $wp_scripts, $wp_styles, $post );

		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Ensure the localized scannerBundleUrl includes the plugin version as a query string parameter.
	 */
	public function testScannerBundleUrlIncludesVersionQueryString(): void {
		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		$created_post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );

		global $post;
		$post = $created_post;

		Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		global $wp_scripts;
		$localized_data = $wp_scripts->get_data( 'edac-frontend-highlighter-app', 'data' );

		$this->assertNotEmpty( $localized_data );
		$this->assertStringContainsString( 'scannerBundleUrl', $localized_data );
		$this->assertStringContainsString( 'ver=' . EDAC_VERSION, $localized_data );
	}

	/**
	 * Helper: enqueue the frontend highlighter as an admin and return the localized data string.
	 *
	 * @return string The raw JS localized-data string for edac-frontend-highlighter-app.
	 */
	private function enqueueAndGetLocalizedData(): string {
		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		global $post;
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );

		Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		global $wp_scripts;
		return (string) $wp_scripts->get_data( 'edac-frontend-highlighter-app', 'data' );
	}

	/**
	 * RestUrl is present in the localized data passed to the frontend highlighter script.
	 */
	public function testLocalizedDataIncludesRestUrl(): void {
		$localized_data = $this->enqueueAndGetLocalizedData();

		$this->assertNotEmpty( $localized_data );
		$this->assertStringContainsString( 'restUrl', $localized_data );
	}

	/**
	 * RestUrl uses the accessibility-checker/v1 namespace and matches rest_url().
	 */
	public function testRestUrlMatchesRestUrlFunction(): void {
		$localized_data = $this->enqueueAndGetLocalizedData();
		$expected       = rest_url( 'accessibility-checker/v1' );

		$this->assertStringContainsString( 'accessibility-checker', $localized_data );
		$this->assertStringContainsString( 'v1', $localized_data );
		// The URL must be derived from rest_url(), not hardcoded — verify the host is present.
		$this->assertStringContainsString( (string) wp_parse_url( $expected, PHP_URL_HOST ), $localized_data );
	}

	/**
	 * RestUrl must be an absolute URL, not a root-relative path like /wp-json/...
	 * A root-relative URL on a subdomain multisite would resolve to the main site.
	 */
	public function testRestUrlIsAbsolute(): void {
		$localized_data = $this->enqueueAndGetLocalizedData();

		// The value following "restUrl" must not be a bare /wp-json path.
		$this->assertDoesNotMatchRegularExpression( '/"restUrl"\s*:\s*"\\\\?\/wp-json/', $localized_data );
		// And the scheme must be present.
		$this->assertMatchesRegularExpression( '/"restUrl"\s*:\s*"https?/', $localized_data );
	}

	/**
	 * FixesRestUrl is present in the localized data passed to the frontend highlighter script.
	 */
	public function testLocalizedDataIncludesFixesRestUrl(): void {
		$localized_data = $this->enqueueAndGetLocalizedData();

		$this->assertStringContainsString( 'fixesRestUrl', $localized_data );
	}

	/**
	 * FixesRestUrl uses the edac/v1 namespace and matches rest_url().
	 */
	public function testFixesRestUrlContainsEdacV1Namespace(): void {
		$localized_data = $this->enqueueAndGetLocalizedData();
		$expected       = rest_url( 'edac/v1' );

		$this->assertStringContainsString( 'edac', $localized_data );
		$this->assertStringContainsString( (string) wp_parse_url( $expected, PHP_URL_HOST ), $localized_data );
	}

	/**
	 * FixesRestUrl must be an absolute URL, not a root-relative path.
	 */
	public function testFixesRestUrlIsAbsolute(): void {
		$localized_data = $this->enqueueAndGetLocalizedData();

		$this->assertDoesNotMatchRegularExpression( '/"fixesRestUrl"\s*:\s*"\\\\?\/wp-json/', $localized_data );
		$this->assertMatchesRegularExpression( '/"fixesRestUrl"\s*:\s*"https?/', $localized_data );
	}

	/**
	 * RestUrl must follow a custom REST base prefix set via the rest_url_prefix filter.
	 * Verifies the URL is built with rest_url() rather than a hardcoded /wp-json/ string.
	 * Pretty permalinks are required for the prefix filter to be applied.
	 */
	public function testRestUrlRespectsCustomRestPrefix(): void {
		update_option( 'permalink_structure', '/%postname%/' );
		flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules

		$prefix_callback                        = static fn() => 'custom-api';
		add_filter( 'rest_url_prefix', $prefix_callback );
		$this->added_filters['rest_url_prefix'] = $prefix_callback;

		$localized_data = $this->enqueueAndGetLocalizedData();

		remove_filter( 'rest_url_prefix', $prefix_callback );
		delete_option( 'permalink_structure' );

		$this->assertStringContainsString( 'custom-api', $localized_data );
		$this->assertStringNotContainsString( 'wp-json', $localized_data );
	}

	/**
	 * FixesRestUrl must follow a custom REST base prefix set via the rest_url_prefix filter.
	 */
	public function testFixesRestUrlRespectsCustomRestPrefix(): void {
		update_option( 'permalink_structure', '/%postname%/' );
		flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules

		$prefix_callback                        = static fn() => 'custom-api';
		add_filter( 'rest_url_prefix', $prefix_callback );
		$this->added_filters['rest_url_prefix'] = $prefix_callback;

		$localized_data = $this->enqueueAndGetLocalizedData();

		remove_filter( 'rest_url_prefix', $prefix_callback );
		delete_option( 'permalink_structure' );

		$this->assertStringContainsString( 'custom-api', $localized_data );
		$this->assertStringNotContainsString( 'wp-json', $localized_data );
	}

	/**
	 * Ensure the highlighter uses the filtered post ID when determining scannable post types.
	 */
	public function testFrontendHighlighterUsesFilteredPostIdForScannableType(): void {
		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		$scannable_post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		$global_post    = $this->factory()->post->create_and_get( [ 'post_type' => 'page' ] );

		global $post;
		$post = $global_post;

		$filter_callback = static function () use ( $scannable_post ) {
			return $scannable_post->ID;
		};

		$this->added_filters['edac_filter_frontend_highlight_post_id'] = $filter_callback;
		add_filter( 'edac_filter_frontend_highlight_post_id', $filter_callback );

		Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertTrue( wp_script_is( 'edac-frontend-highlighter-app', 'enqueued' ) );
	}
}
