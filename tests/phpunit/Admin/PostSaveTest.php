<?php
/**
 * Tests for the Post_Save class.
 *
 * @package Accessibility_Checker
 * @since 1.23.0
 */

namespace EDAC\Tests\Admin;

use EDAC\Admin\Post_Save;
use WP_UnitTestCase;

/**
 * Test class for Post_Save functionality.
 *
 * @package EDAC\Tests\Admin
 * @since 1.23.0
 */
class Post_Save_Test extends WP_UnitTestCase {
	/**
	 * Instance of Post_Save class.
	 *
	 * @var Post_Save
	 */
	private $post_save;

	/**
	 * Test post object.
	 *
	 * @var \WP_Post
	 */
	private $post;

	/**
	 * Set up the test environment.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		$this->post_save = new Post_Save();
		
		// Create a test post.
		$this->post = $this->factory->post->create_and_get(
			[
				'post_title'   => 'Test Post',
				'post_content' => 'Test content',
				'post_status'  => 'publish',
			]
		);

		// Setup post types option.
		update_option( 'edac_post_types', [ 'post' ] );
	}

	/**
	 * Test that hooks are initialized correctly.
	 *
	 * @return void
	 */
	public function test_init_hooks() {
		$this->post_save->init_hooks();
		$this->assertEquals(
			10,
			has_filter( 'save_post', [ $this->post_save, 'save_post' ] )
		);
	}

	/**
	 * Test that save_post ignores non-supported post types.
	 *
	 * @return void
	 */
	public function test_save_post_ignores_non_supported_post_type() {
		// Set post types option to only pages.
		update_option( 'edac_post_types', [ 'page' ] );
		
		$result = $this->post_save->save_post(
			$this->post->ID,
			$this->post,
			true
		);
		
		$this->assertNull( $result );
	}

	/**
	 * Test that save_post handles trashed posts correctly.
	 *
	 * @return void
	 */
	public function test_save_post_handles_trash_status() {
		$this->post->post_status = 'trash';
		
		$result = $this->post_save->save_post(
			$this->post->ID,
			$this->post,
			true
		);
		
		$this->assertNull( $result );
	}

	/**
	 * Clean up after the test.
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
		delete_option( 'edac_post_types' );
	}
}
