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
	 * Setup the option, global wp_scripts and the Enqueue_Admin instance.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

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
		$this->enqueue_admin::maybe_enqueue_admin_and_editor_app_scripts();

		$this->assertTrue( wp_script_is( 'edac', 'enqueued' ) );
		$this->assertFalse( wp_script_is( 'edac-editor-app', 'enqueued' ) );
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

		$this->enqueue_admin::maybe_enqueue_sidebar_script();

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

		$this->enqueue_admin::maybe_enqueue_sidebar_script();

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

		$this->enqueue_admin::maybe_enqueue_sidebar_script();

		$this->assertFalse( wp_script_is( 'edac-sidebar', 'enqueued' ) );
		$this->assertFalse( wp_style_is( 'edac-sidebar', 'enqueued' ) );
	}

	/**
	 * Helper to set a mock current screen with block editor context.
	 *
	 * @param bool $is_block_editor Whether the screen should behave as block editor.
	 */
	private function set_mock_screen( bool $is_block_editor ): void {
		$GLOBALS['current_screen'] = new class( $is_block_editor ) {
			/**
			 * True or false whether the screen is block editor.
			 *
			 * @var bool
			 */
			private $is_block_editor;

			/**
			 * Constructor.
			 *
			 * @param bool $is_block_editor Whether the screen should behave as block editor.
			 */
			public function __construct( bool $is_block_editor ) {
				$this->is_block_editor = $is_block_editor;
			}

			/**
			 * Mock is_block_editor method.
			 *
			 * @return bool
			 */
			public function is_block_editor(): bool {
				return $this->is_block_editor;
			}
		};
	}
}
