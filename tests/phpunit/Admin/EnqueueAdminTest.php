<?php
/**
 * Test cases for the Enqueue_Admin class.
 *
 * @package accessibility-checker
 */

use EDAC\Admin\Enqueue_Admin;

/**
 * Tests for functionality of the Enqueue_Admin class.
 */
class EnqueueAdminTest extends WP_UnitTestCase {

	/**
	 * Holds the instance of the Enqueue_Admin class.
	 *
	 * @var Enqueue_Admin the instance of the Enqueue_Admin class.
	 */
	private $enqueue_admin;

	/**
	 * Holds the test admin user ID.
	 *
	 * @var int
	 */
	private $test_admin_user_id;

	/**
	 * Setup the option, global wp_scripts and the Enqueue_Admin instance.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->test_admin_user_id = self::factory()->user->create(
			[
				'role' => 'administrator',
			]
		);
		wp_set_current_user( $this->test_admin_user_id );

		update_option( 'edac_post_types', [ 'post', 'page' ] );

		global $wp_scripts, $wp_styles;
		$wp_scripts = new \WP_Scripts();
		$wp_styles  = new \WP_Styles();

		$this->enqueue_admin = new Enqueue_Admin();
	}

	/**
	 * Clean up the option, global wp_scripts and the Enqueue_Admin instance.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if ( $this->test_admin_user_id ) {
			delete_user_meta( $this->test_admin_user_id, 'show_sr_text_in_editor' );
		}

		wp_set_current_user( 0 );

		if ( $this->test_admin_user_id ) {
			wp_delete_user( $this->test_admin_user_id );
			$this->test_admin_user_id = 0;
		}

		parent::tearDown();

		delete_option( 'edac_post_types' );

		global $wp_scripts, $wp_styles;
		unset( $wp_scripts, $wp_styles, $GLOBALS['current_screen'] );

		unset( $this->enqueue_admin );
	}

	/**
	 * Test that the base script is enqueued in the admin on non-editor pages.
	 *
	 * @return void
	 */
	public function testEnqueueBaseScriptInAdminNonEditorPage() {
		global $wp_scripts;

		$this->enqueue_admin::maybe_enqueue_admin_and_editor_app_scripts();

		$this->assertTrue( wp_script_is( 'edac', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'edac-editor-app', 'enqueued' ) );

		$localized_data = $wp_scripts->get_data( 'edac', 'data' );
		$this->assertIsString( $localized_data );
		$this->assertStringContainsString( 'utm_content=__name__', $localized_data );
		$this->assertStringNotContainsString( 'utm-content=__name__', $localized_data );
	}

	/**
	 * Test localized pro URL includes the expected underscore UTM content key.
	 *
	 * @return void
	 */
	public function testLocalizedProUrlUsesUnderscoreUtmContentKey() {
		global $wp_scripts;

		$this->enqueue_admin::maybe_enqueue_admin_and_editor_app_scripts();

		$localized_data = (string) $wp_scripts->get_data( 'edac', 'data' );
		$this->assertStringContainsString( 'utm_content=__name__', $localized_data );
		$this->assertStringNotContainsString( 'utm-content=__name__', $localized_data );
	}

	/**
	 * Test that the base script and editor script is enqueued in the editor for an existing page.
	 *
	 * @return void
	 */
	public function testEnqueueBaseAndEditorScriptsInAdminEditorExisting() {

		global $post;
		$post = $this->factory()->post->create_and_get();

		global $pagenow;
		$pagenow = 'post.php';

		$this->enqueue_admin::maybe_enqueue_admin_and_editor_app_scripts();

		$this->assertTrue( wp_script_is( 'edac', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'edac-editor-app', 'enqueued' ) );
	}

	/**
	 * Test that the base script and editor script is enqueued in the editor for a new page.
	 *
	 * @return void
	 */
	public function testEnqueueBaseAndEditorScriptsInAdminEditorNew() {
		global $post;
		$post = $this->factory()->post->create_and_get();

		global $pagenow;
		$pagenow = 'post-new.php';

		$this->enqueue_admin::maybe_enqueue_admin_and_editor_app_scripts();

		$this->assertTrue( wp_script_is( 'edac', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'edac-editor-app', 'enqueued' ) );
	}
	/**
	 * Test that scanUrl uses permalink for frontpage.
	 *
	 * @return void
	 */
	public function testScanUrlUsesPermalinkForFrontpage() {
		global $post, $pagenow, $wp_scripts;

		// Create a post and set it as the frontpage.
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'page' ] );
		update_option( 'page_on_front', $post->ID );
		$pagenow = 'post.php';

		$this->enqueue_admin::maybe_enqueue_admin_and_editor_app_scripts();

		$localized_data = $wp_scripts->get_data( 'edac-editor-app', 'data' );
		$this->assertStringContainsString( 'edac_pageScanner', $localized_data );
		// In WP 6.9 there were changes to the flags passed to wp_json_encode() that made slashes no longer get escaped by default.
		// We should check for both possibilities here to ensure compatibility across versions.
		// See: https://github.com/WordPress/wordpress-develop/pull/9557 for more details.
		if ( version_compare( get_bloginfo( 'version' ), '6.9', '>=' ) ) {
			$this->assertStringContainsString( esc_url_raw( get_permalink( $post->ID ) ), $localized_data );
		} else {
			$this->assertStringContainsString( str_replace( '/', '\\/', esc_url_raw( get_permalink( $post->ID ) ) ), $localized_data );
		}
		$this->assertStringNotContainsString( 'preview=true', $localized_data );

		// Cleanup.
		delete_option( 'page_on_front' );
	}

	/**
	 * Test that scanUrl uses permalink for posts page.
	 *
	 * @return void
	 */
	public function testScanUrlUsesPermalinkForPostsPage() {

		global $post, $pagenow, $wp_scripts;

		// Create a post and set it as the posts page.
		$post = $this->factory()->post->create_and_get( [ 'post_type' => 'page' ] );
		update_option( 'page_for_posts', $post->ID );
		$pagenow = 'post.php';

		$this->enqueue_admin::maybe_enqueue_admin_and_editor_app_scripts();

		$localized_data = $wp_scripts->get_data( 'edac-editor-app', 'data' );
		$this->assertStringContainsString( 'edac_pageScanner', $localized_data );
		// In WP 6.9 there were changes to the flags passed to wp_json_encode() that made slashes no longer get escaped by default.
		// We should check for both possibilities here to ensure compatibility across versions.
		// See: https://github.com/WordPress/wordpress-develop/pull/9557 for more details.
		if ( version_compare( get_bloginfo( 'version' ), '6.9', '>=' ) ) {
			$this->assertStringContainsString( esc_url_raw( get_permalink( $post->ID ) ), $localized_data );
		} else {
			$this->assertStringContainsString( str_replace( '/', '\\/', esc_url_raw( get_permalink( $post->ID ) ) ), $localized_data );
		}
		$this->assertStringNotContainsString( 'preview=true', $localized_data );

		// Cleanup.
		delete_option( 'page_for_posts' );
	}

	/**
	 * Test that scanUrl uses preview link for regular posts.
	 *
	 * @return void
	 */
	public function testScanUrlUsesPreviewLinkForRegularPost() {
		global $post, $pagenow, $wp_scripts;

		// Create a regular post (not frontpage or posts page).
		$post    = $this->factory()->post->create_and_get();
		$pagenow = 'post.php';

		$this->enqueue_admin::maybe_enqueue_admin_and_editor_app_scripts();

		$localized_data = $wp_scripts->get_data( 'edac-editor-app', 'data' );
		$this->assertStringContainsString( 'edac_pageScanner', $localized_data );
		$this->assertStringContainsString( 'preview=true', $localized_data );
	}

	/**
	 * Test that the sidebar assets enqueue only in the block editor for scannable post types.
	 */
	public function testSidebarScriptEnqueuesInBlockEditorForScannablePost() {
		global $post, $pagenow;
		$post    = $this->factory()->post->create_and_get();
		$pagenow = 'post.php';

		$this->set_mock_screen( true );

		Enqueue_Admin::maybe_enqueue_sidebar_script();

		$this->assertTrue( wp_script_is( 'edac-sidebar', 'enqueued' ) );
		$this->assertTrue( wp_style_is( 'edac-sidebar', 'enqueued' ) );
	}

	/**
	 * Test that the sidebar assets do not enqueue when not in the block editor.
	 */
	public function testSidebarScriptDoesNotEnqueueInClassicEditor() {
		global $post, $pagenow;
		$post    = $this->factory()->post->create_and_get();
		$pagenow = 'post.php';

		$this->set_mock_screen( false );

		Enqueue_Admin::maybe_enqueue_sidebar_script();

		$this->assertFalse( wp_script_is( 'edac-sidebar', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'edac-sidebar', 'enqueued' ) );
	}

	/**
	 * Test that the sidebar assets do not enqueue for non-scannable post types.
	 */
	public function testSidebarScriptDoesNotEnqueueForNonScannablePostType() {
		update_option( 'edac_post_types', [ 'page' ] );

		global $post, $pagenow;
		$post    = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		$pagenow = 'post.php';

		$this->set_mock_screen( true );

		Enqueue_Admin::maybe_enqueue_sidebar_script();

		$this->assertFalse( wp_script_is( 'edac-sidebar', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'edac-sidebar', 'enqueued' ) );
	}

	/**
	 * Test that the sr-only format script enqueues on post.php for a scannable post type.
	 */
	public function testSrOnlyFormatEnqueuesOnPostEditor() {
		global $post, $pagenow;
		$post    = $this->factory()->post->create_and_get();
		$pagenow = 'post.php';

		$this->set_mock_screen( true );

		Enqueue_Admin::maybe_enqueue_sr_only_format();

		$this->assertTrue( wp_script_is( 'edac-sr-only-format', 'enqueued' ) );
	}

	/**
	 * Test that the sr-only format script enqueues on post-new.php for a scannable post type.
	 */
	public function testSrOnlyFormatEnqueuesOnPostNewEditor() {
		global $post, $pagenow;
		$post    = $this->factory()->post->create_and_get();
		$pagenow = 'post-new.php';

		$this->set_mock_screen( true );

		Enqueue_Admin::maybe_enqueue_sr_only_format();

		$this->assertTrue( wp_script_is( 'edac-sr-only-format', 'enqueued' ) );
	}

	/**
	 * Test that the sr-only format script does not enqueue on post.php for a non-scannable post type.
	 */
	public function testSrOnlyFormatDoesNotEnqueueForNonScannablePostType() {
		update_option( 'edac_post_types', [ 'page' ] );

		global $post, $pagenow;
		$post    = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		$pagenow = 'post.php';

		$this->set_mock_screen( true );

		Enqueue_Admin::maybe_enqueue_sr_only_format();

		$this->assertFalse( wp_script_is( 'edac-sr-only-format', 'enqueued' ) );
	}

	/**
	 * Test that the sr-only format script enqueues on the Full Site Editor.
	 */
	public function testSrOnlyFormatEnqueuesInFullSiteEditor() {
		global $pagenow;
		$pagenow = 'site-editor.php';

		$this->set_mock_screen( true );

		Enqueue_Admin::maybe_enqueue_sr_only_format();

		$this->assertTrue( wp_script_is( 'edac-sr-only-format', 'enqueued' ) );
	}

	/**
	 * Test that the sr-only format script does not enqueue on site-editor.php when not in block editor.
	 */
	public function testSrOnlyFormatDoesNotEnqueueInFseWhenNotBlockEditor() {
		global $pagenow;
		$pagenow = 'site-editor.php';

		$this->set_mock_screen( false );

		Enqueue_Admin::maybe_enqueue_sr_only_format();

		$this->assertFalse( wp_script_is( 'edac-sr-only-format', 'enqueued' ) );
	}

	/**
	 * Test that the sr-only format script does not enqueue on unrelated admin pages.
	 */
	public function testSrOnlyFormatDoesNotEnqueueOnOtherAdminPages() {
		global $pagenow;
		$pagenow = 'edit.php';

		$this->set_mock_screen( true );

		Enqueue_Admin::maybe_enqueue_sr_only_format();

		$this->assertFalse( wp_script_is( 'edac-sr-only-format', 'enqueued' ) );
	}


	/**
	 * Test that edac_filter_admin_post_id overrides the post ID localized into edac_script_vars.
	 *
	 * @return void
	 */
	public function testAdminPostIdFilterOverridesLocalizedPostId() {
		global $post, $pagenow, $wp_scripts;

		$original_post  = $this->factory()->post->create_and_get();
		$alternate_post = $this->factory()->post->create_and_get();
		$post           = $original_post;
		$pagenow        = 'post.php';

		$filter_callback = static function () use ( $alternate_post ) {
			return $alternate_post->ID;
		};
		add_filter( 'edac_filter_admin_post_id', $filter_callback );

		$this->enqueue_admin::maybe_enqueue_admin_and_editor_app_scripts();

		remove_filter( 'edac_filter_admin_post_id', $filter_callback );

		$localized_data = $wp_scripts->get_data( 'edac', 'data' );
		$this->assertStringContainsString( (string) $alternate_post->ID, $localized_data );
	}

	/**
	 * Test that edac_filter_post_is_latest_posts_home causes the scan URL to use the home URL.
	 *
	 * When show_on_front=posts and page_for_posts=0, the standard WP options cannot identify a
	 * virtual homepage post. The filter lets extensions signal this so we use get_home_url()
	 * for the scanner iframe rather than an invalid preview URL.
	 *
	 * @return void
	 */
	public function testScanUrlUsesHomeUrlWhenLatestPostsHomeFilterReturnsTrue() {
		global $post, $pagenow, $wp_scripts;

		$post    = $this->factory()->post->create_and_get( [ 'post_type' => 'page' ] );
		$pagenow = 'post.php';

		update_option( 'show_on_front', 'posts' );
		update_option( 'page_for_posts', 0 );

		$filter_callback = static function () {
			return true;
		};
		add_filter( 'edac_filter_post_is_latest_posts_home', $filter_callback );

		$this->enqueue_admin::maybe_enqueue_admin_and_editor_app_scripts();

		remove_filter( 'edac_filter_post_is_latest_posts_home', $filter_callback );
		delete_option( 'show_on_front' );
		delete_option( 'page_for_posts' );

		$localized_data = $wp_scripts->get_data( 'edac-editor-app', 'data' );
		$this->assertStringContainsString( 'edac_pageScanner', $localized_data );
		$this->assertStringNotContainsString( 'preview=true', $localized_data );
		// The scan URL should be based on the home URL, not a preview link.
		$this->assertStringContainsString( trailingslashit( get_home_url() ), $localized_data );
	}

	/**
	 * Test that edac_filter_post_is_latest_posts_home causes the scan URL to use the home URL
	 * when show_on_front=page but no static front page is configured (fallback case).
	 *
	 * WordPress falls back to showing latest posts when show_on_front=page but page_on_front
	 * is empty. The $is_latest_posts_home condition must cover this case so extensions can
	 * still signal that get_home_url() should be used as the scan URL.
	 *
	 * @return void
	 */
	public function testScanUrlUsesHomeUrlWhenShowOnFrontIsPageWithNoFrontPageConfigured() {
		global $post, $pagenow, $wp_scripts;

		$post    = $this->factory()->post->create_and_get( [ 'post_type' => 'page' ] );
		$pagenow = 'post.php';

		update_option( 'show_on_front', 'page' );
		delete_option( 'page_on_front' ); // No static front page configured — WP falls back to latest posts.

		$filter_callback = static function () {
			return true;
		};
		add_filter( 'edac_filter_post_is_latest_posts_home', $filter_callback );

		$this->enqueue_admin::maybe_enqueue_admin_and_editor_app_scripts();

		remove_filter( 'edac_filter_post_is_latest_posts_home', $filter_callback );
		delete_option( 'show_on_front' );

		$localized_data = $wp_scripts->get_data( 'edac-editor-app', 'data' );
		$this->assertStringContainsString( 'edac_pageScanner', $localized_data );
		$this->assertStringNotContainsString( 'preview=true', $localized_data );
		// The scan URL should be based on the home URL, not a preview link.
		$this->assertStringContainsString( trailingslashit( get_home_url() ), $localized_data );
	}

	/**
	 * Test that the 'active' flag in edac_editor_app reflects the filtered post ID's post type.
	 *
	 * Before Fix 3, $active was set to $is_scannable_post which was computed from the global
	 * $post before the edac_filter_admin_post_id filter ran. If the filter returns a different
	 * post ID whose type is not scannable, $active must be false so the scanner doesn't run.
	 *
	 * @return void
	 */
	public function testActiveReflectsFilteredPostIdPostType() {
		global $post, $pagenow, $wp_scripts;

		// Global $post is a 'post' type (scannable under current option).
		$scannable_post = $this->factory()->post->create_and_get( [ 'post_type' => 'post' ] );
		// The filter will return a 'page' ID; make 'page' non-scannable for this test.
		$non_scannable_post = $this->factory()->post->create_and_get( [ 'post_type' => 'page' ] );
		$post               = $scannable_post;
		$pagenow            = 'post.php';

		// Restrict scannable types to 'post' only so 'page' becomes non-scannable.
		update_option( 'edac_post_types', [ 'post' ] );

		$filter_callback = static function () use ( $non_scannable_post ) {
			return $non_scannable_post->ID;
		};
		add_filter( 'edac_filter_admin_post_id', $filter_callback );

		$this->enqueue_admin::maybe_enqueue_admin_and_editor_app_scripts();

		remove_filter( 'edac_filter_admin_post_id', $filter_callback );
		// Restore original scannable post types.
		update_option( 'edac_post_types', [ 'post', 'page' ] );

		$localized_data = $wp_scripts->get_data( 'edac-editor-app', 'data' );
		$this->assertNotEmpty( $localized_data );
		// $active must be false because the filtered post type ('page') is not scannable.
		$this->assertMatchesRegularExpression( '/"active"\s*:\s*false/', $localized_data );
	}

	/**
	 * Helper to set a mock current screen with block editor context.
	 *
	 * @param bool $is_block_editor Whether the screen should behave as block editor.
	 */
	private function set_mock_screen( bool $is_block_editor ): void {
		// As of WP 7.0, get_current_screen() requires an actual WP_Screen
		// instance. Use WP_Screen::get() to obtain one without the side effects
		// of set_current_screen() (e.g. setting $hook_suffix, $typenow, firing
		// the current_screen action).
		$GLOBALS['current_screen']                  = WP_Screen::get( 'post' );
		$GLOBALS['current_screen']->is_block_editor = $is_block_editor;
	}
}
