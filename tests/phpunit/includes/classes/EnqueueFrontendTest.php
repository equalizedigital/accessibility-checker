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
