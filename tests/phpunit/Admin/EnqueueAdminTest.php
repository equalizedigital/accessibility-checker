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
	 * Test that sr-only editor styles are injected and existing styles are preserved.
	 */
	public function testInjectSrOnlyEditorStylesAddsCssAndPreservesExistingStyles() {
		global $post, $pagenow;
		$post    = $this->factory()->post->create_and_get();
		$pagenow = 'post.php';

		$this->set_mock_screen( true );

		$editor_settings = [
			'styles' => [
				[ 'css' => '.existing-style { color: red; }' ],
			],
		];

		$result = Enqueue_Admin::maybe_inject_sr_only_editor_styles( $editor_settings );

		$this->assertCount( 2, $result['styles'] );
		$this->assertSame( '.existing-style { color: red; }', $result['styles'][0]['css'] );
		$this->assertStringContainsString( '.text-format-sr-only', $result['styles'][1]['css'] );
		$this->assertStringContainsString( 'Screen Reader Text', $result['styles'][1]['css'] );
	}

	/**
	 * Test that sr-only body class is appended when always-show user preference is enabled.
	 */
	public function testInjectSrOnlyEditorStylesAddsBodyClassWhenPreferenceEnabled() {
		global $post, $pagenow;
		$post    = $this->factory()->post->create_and_get();
		$pagenow = 'post.php';

		$this->set_mock_screen( true );
		update_user_meta( get_current_user_id(), 'show_sr_text_in_editor', true );

		$editor_settings = [
			'bodyClassName' => 'editor-default',
		];

		$result = Enqueue_Admin::maybe_inject_sr_only_editor_styles( $editor_settings );

		$this->assertStringContainsString( 'editor-default', $result['bodyClassName'] );
		$this->assertStringContainsString( 'sr-only-show-always', $result['bodyClassName'] );
		$this->assertArrayHasKey( 'styles', $result );
		$this->assertNotEmpty( $result['styles'] );
	}

	/**
	 * Test that sr-only body class is not appended when always-show user preference is disabled.
	 */
	public function testInjectSrOnlyEditorStylesDoesNotAddBodyClassWhenPreferenceDisabled() {
		global $post, $pagenow;
		$post    = $this->factory()->post->create_and_get();
		$pagenow = 'post.php';

		$this->set_mock_screen( true );
		update_user_meta( get_current_user_id(), 'show_sr_text_in_editor', false );

		$result = Enqueue_Admin::maybe_inject_sr_only_editor_styles( [] );

		$this->assertArrayNotHasKey( 'bodyClassName', $result );
		$this->assertArrayHasKey( 'styles', $result );
		$this->assertNotEmpty( $result['styles'] );
	}

	/**
	 * Test that sr-only body class is not duplicated when already present.
	 */
	public function testInjectSrOnlyEditorStylesDoesNotDuplicateBodyClassWhenAlreadyPresent() {
		global $post, $pagenow;
		$post    = $this->factory()->post->create_and_get();
		$pagenow = 'post.php';

		$this->set_mock_screen( true );
		update_user_meta( get_current_user_id(), 'show_sr_text_in_editor', true );

		$editor_settings = [
			'bodyClassName' => 'editor-default sr-only-show-always',
		];

		$result = Enqueue_Admin::maybe_inject_sr_only_editor_styles( $editor_settings );

		$this->assertSame( 'editor-default sr-only-show-always', $result['bodyClassName'] );
		$this->assertSame( 1, preg_match_all( '/\bsr-only-show-always\b/', $result['bodyClassName'] ) );
		$this->assertArrayHasKey( 'styles', $result );
		$this->assertNotEmpty( $result['styles'] );
	}

	/**
	 * Test that sr-only body class appends cleanly for empty bodyClassName values.
	 */
	public function testInjectSrOnlyEditorStylesAppendsBodyClassWithoutLeadingWhitespace() {
		global $post, $pagenow;
		$post    = $this->factory()->post->create_and_get();
		$pagenow = 'post.php';

		$this->set_mock_screen( true );
		update_user_meta( get_current_user_id(), 'show_sr_text_in_editor', true );

		$editor_settings = [
			'bodyClassName' => '',
		];

		$result = Enqueue_Admin::maybe_inject_sr_only_editor_styles( $editor_settings );

		$this->assertSame( 'sr-only-show-always', $result['bodyClassName'] );
		$this->assertArrayHasKey( 'styles', $result );
		$this->assertNotEmpty( $result['styles'] );
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

			/**
			 * Mock in_admin method.
			 *
			 * @return bool
			 */
			public function in_admin(): bool {
				return true;
			}
		};
	}
}
