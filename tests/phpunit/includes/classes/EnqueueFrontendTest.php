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
		unset( $wp_scripts, $wp_styles, $post, $GLOBALS['current_screen'] );

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
	 * Highlighter must NOT load on the "latest posts" homepage when no filter overrides the ID.
	 *
	 * When show_on_front=posts, the global $post is the first blog post from the main query, not
	 * the homepage itself. The fix passes null to the filter so the free plugin bails gracefully.
	 */
	public function testFrontendHighlighterDoesNotLoadOnLatestPostsHomepage(): void {
		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		// Create a post so the main query has results.
		$this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );

		update_option( 'show_on_front', 'posts' );

		// Simulate visiting the homepage so is_home() / is_front_page() return true.
		$this->go_to( '/' );

		Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertFalse( wp_script_is( 'edac-frontend-highlighter-app', 'enqueued' ) );

		delete_option( 'show_on_front' );
	}

	/**
	 * A filter on edac_filter_frontend_highlight_post_id can enable the highlighter on the
	 * "latest posts" homepage by supplying a valid post ID (e.g. a Pro virtual-page ID).
	 */
	public function testFrontendHighlighterLoadsOnLatestPostsHomepageWhenFilterProvidesId(): void {
		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_id );

		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );

		update_option( 'show_on_front', 'posts' );

		$this->go_to( '/' );

		$filter_callback = static function () use ( $post ) {
			return $post->ID;
		};
		$this->added_filters['edac_filter_frontend_highlight_post_id'] = $filter_callback;
		add_filter( 'edac_filter_frontend_highlight_post_id', $filter_callback );

		Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertTrue( wp_script_is( 'edac-frontend-highlighter-app', 'enqueued' ) );

		delete_option( 'show_on_front' );
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
	 * FixesRestUrl uses the accessibility-checker/v1 namespace and matches rest_url().
	 */
	public function testFixesRestUrlUsesAccessibilityCheckerV1Namespace(): void {
		$localized_data = $this->enqueueAndGetLocalizedData();
		$expected       = esc_url_raw( rest_url( 'accessibility-checker/v1' ) );

		$this->assertStringContainsString( '"fixesRestUrl":"' . $expected . '"', str_replace( '\/', '/', $localized_data ) );
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
		$expected       = esc_url_raw( rest_url( 'accessibility-checker/v1' ) );

		remove_filter( 'rest_url_prefix', $prefix_callback );
		delete_option( 'permalink_structure' );

		$this->assertStringContainsString( '"fixesRestUrl":"' . $expected . '"', str_replace( '\/', '/', $localized_data ) );
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

	/**
	 * An editor without the edac_view_frontend_highlighter capability must NOT get the
	 * highlighter, even though editors satisfy edit_post on virtually any post (PRO-1290:
	 * the historical edit_post fallback silently overrode the Permissions tab setting).
	 */
	public function testHighlighterDoesNotLoadForEditorWithoutCapability(): void {
		edac_ignore_capability()->sync_matrix( [] );

		$editor_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		global $post;
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );

		Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertFalse( wp_script_is( 'edac-frontend-highlighter-app', 'enqueued' ) );
	}

	/**
	 * An editor who does hold the capability still gets the highlighter.
	 */
	public function testHighlighterLoadsForEditorWithCapability(): void {
		edac_ignore_capability()->sync_matrix( [ 'edac_view_frontend_highlighter' => [ 'editor' ] ] );

		$editor_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor_id );

		global $post;
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );

		Enqueue_Frontend::maybe_enqueue_frontend_highlighter();

		$this->assertTrue( wp_script_is( 'edac-frontend-highlighter-app', 'enqueued' ) );

		edac_ignore_capability()->sync_matrix( [] );
	}

	/**
	 * Sets the current screen so is_admin() is deterministic for the asset tests.
	 *
	 * Enqueue_Frontend::enqueue_sr_only_styles() bails inside wp-admin, and a
	 * current screen can be left behind by other test files in the suite, so the
	 * screen is set here rather than assumed. WP_Screen::get() is used instead of
	 * set_current_screen() so no screen hooks fire as a side effect.
	 *
	 * @param string $screen Screen to set as the current one.
	 */
	private function set_current_screen_context( string $screen ): void {
		$GLOBALS['current_screen'] = WP_Screen::get( $screen );
	}

	/**
	 * The screen reader only stylesheet is enqueued on the front end.
	 *
	 * @covers \EDAC\Inc\Enqueue_Frontend::enqueue_sr_only_styles
	 */
	public function testSrOnlyStylesEnqueueOnTheFrontEnd(): void {
		global $wp_styles;

		$this->set_current_screen_context( 'front' );

		Enqueue_Frontend::enqueue_sr_only_styles();

		$this->assertTrue( wp_style_is( 'edac-sr-only-format', 'enqueued' ) );
		$this->assertSame( EDAC_PLUGIN_URL . 'build/css/srOnlyFormat.css', $wp_styles->registered['edac-sr-only-format']->src );
		$this->assertSame( EDAC_VERSION, $wp_styles->registered['edac-sr-only-format']->ver );
		$this->assertSame( 'all', $wp_styles->registered['edac-sr-only-format']->args );
	}

	/**
	 * The screen reader only stylesheet is not enqueued inside wp-admin.
	 *
	 * @covers \EDAC\Inc\Enqueue_Frontend::enqueue_sr_only_styles
	 */
	public function testSrOnlyStylesDoNotEnqueueInAdmin(): void {
		$this->set_current_screen_context( 'post' );

		Enqueue_Frontend::enqueue_sr_only_styles();

		$this->assertFalse( wp_style_is( 'edac-sr-only-format', 'enqueued' ) );
	}

	/**
	 * A stored empty colour scheme falls back to the default WordPress scheme colour.
	 *
	 * @covers \EDAC\Inc\Enqueue_Frontend::get_admin_theme_color
	 */
	public function testAdminThemeColorFallsBackWhenTheStoredSchemeIsEmpty(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		update_user_meta( $user_id, 'admin_color', '' );

		$this->assertSame( '#0073aa', Enqueue_Frontend::get_admin_theme_color() );
	}

	/**
	 * Each WordPress admin colour scheme maps to its primary colour.
	 *
	 * The colour tints the front end highlighter, so a stored scheme must never
	 * resolve to an empty value: unknown schemes fall back to the modern colour.
	 *
	 * @dataProvider provideAdminColourSchemes
	 *
	 * @covers \EDAC\Inc\Enqueue_Frontend::get_admin_theme_color
	 *
	 * @param string $scheme   Stored admin_color user option.
	 * @param string $expected Expected hex colour.
	 */
	public function testAdminThemeColorMapsEachColourScheme( string $scheme, string $expected ): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		update_user_meta( $user_id, 'admin_color', $scheme );

		$this->assertSame( $expected, Enqueue_Frontend::get_admin_theme_color() );
	}

	/**
	 * Provides the WordPress admin colour schemes and their primary colours.
	 *
	 * @return array<string, string[]>
	 */
	public function provideAdminColourSchemes(): array {
		return [
			'fresh'          => [ 'fresh', '#0073aa' ],
			'light'          => [ 'light', '#04a4cc' ],
			'modern'         => [ 'modern', '#3858e9' ],
			'blue'           => [ 'blue', '#096484' ],
			'coffee'         => [ 'coffee', '#46403c' ],
			'ectoplasm'      => [ 'ectoplasm', '#523f6d' ],
			'midnight'       => [ 'midnight', '#e14d43' ],
			'ocean'          => [ 'ocean', '#627c83' ],
			'sunrise'        => [ 'sunrise', '#cf4944' ],
			'unknown scheme' => [ 'unknown-scheme', '#3858e9' ],
		];
	}

	/**
	 * Data for the isolated scan request detection.
	 *
	 * @return array<string, array{0: array<string, string>, 1: bool}>
	 */
	public function isolatedScanRequestProvider(): array {
		return [
			'scanner and isolated markers'     => [
				[
					'edac_pageScanner' => '1',
					'edac_isolated'    => '1',
				],
				true,
			],
			'scanner marker only'              => [
				[ 'edac_pageScanner' => '1' ],
				false,
			],
			'isolated marker only'             => [
				[ 'edac_isolated' => '1' ],
				false,
			],
			'neither marker'                   => [
				[],
				false,
			],
			'isolated marker with other value' => [
				[
					'edac_pageScanner' => '1',
					'edac_isolated'    => '0',
				],
				false,
			],
			'scanner marker with other value'  => [
				[
					'edac_pageScanner' => '0',
					'edac_isolated'    => '1',
				],
				false,
			],
		];
	}

	/**
	 * Only requests carrying both the scanner and isolated markers count as isolated scans.
	 *
	 * @dataProvider isolatedScanRequestProvider
	 *
	 * @param array<string, string> $query    The query string values for the request.
	 * @param bool                  $expected Whether the request counts as an isolated scan.
	 */
	public function testIsIsolatedScanRequest( array $query, bool $expected ): void {
		$original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET         = $query;

		$result = Enqueue_Frontend::is_isolated_scan_request();

		$_GET = $original_get;

		$this->assertSame( $expected, $result );
	}

	/**
	 * The isolation header hook is registered on send_headers.
	 */
	public function testIsolationHeaderHookIsRegistered(): void {
		$this->assertNotFalse(
			has_action( 'send_headers', [ 'EDAC\Inc\Enqueue_Frontend', 'maybe_send_scan_isolation_header' ] )
		);
	}

	/**
	 * The header sent to the scan iframe matches the policy core sends on block editor screens.
	 */
	public function testScanIsolationHeaderMatchesCoreEditorPolicy(): void {
		$this->assertSame(
			'Document-Isolation-Policy: isolate-and-credentialless',
			Enqueue_Frontend::SCAN_ISOLATION_HEADER
		);
	}

	/**
	 * Run the header callback for a request and return the headers it would send.
	 *
	 * The test bootstrap has already produced output, so real headers can't be inspected from here.
	 * A subclass records what is passed to send_header() instead.
	 *
	 * @param array<string, string> $query The query string values for the request.
	 *
	 * @return array<int, string> The header lines that were sent.
	 */
	private function get_scan_headers_sent_for( array $query ): array {
		$spy = new class() extends Enqueue_Frontend {

			/**
			 * Header lines recorded instead of being sent.
			 *
			 * @var array<int, string>
			 */
			public static $sent = [];

			/**
			 * Record the header instead of sending it.
			 *
			 * @param string $header The full header line.
			 */
			protected static function send_header( string $header ): void {
				self::$sent[] = $header;
			}
		};

		$spy_class        = get_class( $spy );
		$spy_class::$sent = [];

		$original_get = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET         = $query;

		$spy_class::maybe_send_scan_isolation_header();

		$_GET = $original_get;

		return $spy_class::$sent;
	}

	/**
	 * The isolation header is sent for isolated scan requests and for no other request.
	 *
	 * @dataProvider isolatedScanRequestProvider
	 *
	 * @param array<string, string> $query    The query string values for the request.
	 * @param bool                  $expected Whether the request counts as an isolated scan.
	 */
	public function testIsolationHeaderIsSentOnlyForIsolatedScanRequests( array $query, bool $expected ): void {
		$this->assertSame(
			$expected ? [ Enqueue_Frontend::SCAN_ISOLATION_HEADER ] : [],
			$this->get_scan_headers_sent_for( $query )
		);
	}
}
