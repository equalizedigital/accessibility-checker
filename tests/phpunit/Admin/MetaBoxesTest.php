<?php
/**
 * Test class for the editor metabox.
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Admin;
use EDAC\Admin\Meta_Boxes;

/**
 * Tests for the editor meta box class.
 */
class MetaBoxesTest extends WP_UnitTestCase {

	/**
	 * Set up options used by metabox registration tests.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		update_option( 'edac_post_types', [ 'post', 'page' ] );
		delete_option( 'edac_show_metabox_in_block_editor' );
	}

	/**
	 * Clean up options and globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		delete_option( 'edac_post_types' );
		delete_option( 'edac_show_metabox_in_block_editor' );
		unset( $GLOBALS['current_screen'], $GLOBALS['wp_meta_boxes'] );
		parent::tearDown();
	}


	/**
	 * Test the hooks are added only in admin.
	 *
	 * @return void
	 */
	public function test_meta_boxes_are_registered_in_admin(): void {

		$meta_boxes = $this->getMockBuilder( Meta_Boxes::class )
			->onlyMethods( [ 'register_meta_boxes' ] )
			->getMock();

		$meta_boxes->expects( $this->once() )
			->method( 'register_meta_boxes' );

		$this->invoke_admin_init( $meta_boxes );
		do_action( 'add_meta_boxes' );
	}

	/**
	 * Test the init_hooks method.
	 *
	 * @return void
	 */
	public function test_init_hooks(): void {
		$meta_boxes = new Meta_Boxes();
		$meta_boxes->init_hooks();

		$this->assertEquals(
			10,
			has_action(
				'add_meta_boxes',
				[
					$meta_boxes,
					'register_meta_boxes',
				]
			)
		);
	}

	/**
	 * Test the render method.
	 *
	 * @return void
	 */
	public function test_render(): void {
		$meta_boxes = new Meta_Boxes();
		$meta_boxes->render();

		$this->expectOutputRegex( '/^<div id="edac-tabs"/' );
		$this->expectOutputRegex( '/role="tablist"/' );
		$this->expectOutputRegex( '/role="tab"/' );
		$this->expectOutputRegex( '/role="tabpanel"/' );
	}


	/**
	 * Test that metaboxes are not registered in the block editor when disabled by setting.
	 *
	 * @return void
	 */
	public function test_register_meta_boxes_skips_block_editor_when_setting_disabled(): void {
		$meta_boxes = new Meta_Boxes();
		$this->set_mock_screen( true );
		update_option( 'edac_show_metabox_in_block_editor', 0 );

		$meta_boxes->register_meta_boxes();

		$this->assertFalse( isset( $GLOBALS['wp_meta_boxes']['post']['normal']['high']['edac-meta-box'] ) );
	}

	/**
	 * Test that metaboxes are still registered in classic editor when block-editor setting is disabled.
	 *
	 * @return void
	 */
	public function test_register_meta_boxes_keeps_classic_editor_when_setting_disabled(): void {
		$meta_boxes = new Meta_Boxes();
		$this->set_mock_screen( false );
		update_option( 'edac_show_metabox_in_block_editor', 0 );

		$meta_boxes->register_meta_boxes();

		$this->assertTrue( isset( $GLOBALS['wp_meta_boxes']['post']['normal']['high']['edac-meta-box'] ) );
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

	/**
	 * Invoke the admin init method.
	 *
	 * @param Meta_Boxes $meta_boxes The metabox class.
	 * @return void
	 */
	private function invoke_admin_init( $meta_boxes ): void {
		$admin = new Admin( $meta_boxes );
		$admin->init();
	}
}
