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
