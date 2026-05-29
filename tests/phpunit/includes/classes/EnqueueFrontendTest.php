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
