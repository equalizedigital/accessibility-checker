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

		global $wp_scripts;
		$wp_scripts = new \WP_Scripts();

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

		global $wp_scripts;
		unset( $wp_scripts );

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
		$this->assertStringContainsString( str_replace( '/', '\\/', esc_url_raw( get_permalink( $post->ID ) ) ), $localized_data );
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
		$this->assertStringContainsString( str_replace( '/', '\\/', esc_url_raw( get_permalink( $post->ID ) ) ), $localized_data );
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
}
