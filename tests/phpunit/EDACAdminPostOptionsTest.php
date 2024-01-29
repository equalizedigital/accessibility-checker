<?php
/**
 * Class EDACAdminPostOptionsTest
 *
 * @package Accessibility_Checker
 */

//phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
 
use EDAC\Admin\Post_Options;
/**
 * PostOptions test case.
 */
class EDACAdminPostOptionsTest extends WP_UnitTestCase {
	
	/**
	 * The id of the first test post.
	 *
	 * @var integer 
	 */
	private static $post_id;

	/**
	 * The id of the second test post.
	 *
	 * @var integer 
	 */
	private static $post2_id;

	/**
	 * The name of the list.
	 *
	 * @var string $list_name.
	 */
	private $list_name = 'edac';



	/**
	 * Set up that runs once at top.
	 * 
	 * @param WP_UnitTest_Factory $factory The factory.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		
		// create a post.
		self::$post_id = $factory->post->create();
		
		
		// create another post.
		self::$post2_id = $factory->post->create();
	}

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
	}

	/**
	 * Get the name and default value for a given type.
	 *
	 * @return boolean|false
	 */
	public function test() {
		return true;
	}
}
