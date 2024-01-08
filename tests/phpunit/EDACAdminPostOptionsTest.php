<?php
/**
 * Class EDACAdminPostOptionsTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Admin\Post_Options;
/**
 * Post options test case.
 */
class EDACAdminPostOptionsTest extends WP_UnitTestCase {
	
	/**
	 * The id of the first test post.
	 *
	 * @var integer 
	 */
	private static $post_id;

	/**
	 * The id of the 2nd test post.
	 *
	 * @var integer 
	 */
	private static $post2_id;

	/**
	 * The default values.
	 *
	 * @var array [name => value]
	 */
	private $default_values = array(
		'a_string'  => 'a default value',
		'a_boolean' => true,
		'a_number'  => 1,
		'an_array'  => array( 'a', 'b', 'c' ),
	);

	/**
	 * The variable type for the stored value.
	 *
	 * @var array [name => string|number|bool|array] defaults to string if empty.
	 */
	private $casts = array(
		'a_string'  => 'string',
		'a_boolean' => 'bool',
		'a_number'  => 'number',
		'an_array'  => 'array',
	);


	/**
	 * Instance of the Options class.
	 *
	 * @var Options $options.
	 */
	private $options;
	
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
	 * Set up that runs before every test.
	 */
	protected function setUp(): void {  
		$this->options = new Post_Options( self::$post_id, $this->list_name, $this->default_values, $this->casts );
	}

	
	/**
	 * Test that setting a post id changes the post.
	 */
	public function test_that_post_id_changes_post() {

		// Get a test value from the 1st post.
		$post_test_value = $this->options->get( 'a_string' );

		// Get a test value from the 2nd post.
		$post2_options    = new Post_Options( self::$post2_id, $this->list_name, $this->default_values, $this->casts );
		$post2_test_value = $post2_options->get( 'a_string' );

		// Test that the default value is the same both for posts.
		$this->assertEquals( $post_test_value, $post2_test_value );
		

		// Change the value for the different post.
		$post2_options->set( 'a_string', 'a diff string.' );
		$post2_new_test_value = $post2_options->get( 'a_string' );    

	
		// Confirm the different post value was changed.
		$this->assertEquals( 'a diff string.', $post2_new_test_value );

		// Confirm the original post value was not changed by the change to post2.
		$this->assertEquals( $post_test_value, $this->options->get( 'a_string' ) );
	}
	

	/**
	 * Test that a string value can be set/gotten/deleted/set-again.
	 */
	public function test_set_get_delete_for_string_value() {   
	
		// Test that the default value is returned.
		$this->assertEquals( 'a default value', $this->options->get( 'a_string' ) );
	
		// Test that a new empty value is returned.
		$this->options->set( 'a_string', 'a new string' );
		$this->assertEquals( 'a new string', $this->options->get( 'a_string' ) );

		// Test that the value is deleted and returns null.
		$this->options->delete( 'a_string' );
		$this->assertEquals( null, $this->options->get( 'a_string' ) );

		// Test that a new value is returned.
		$this->options->set( 'a_string', 'another new string' );
		$this->assertEquals( 'another new string', $this->options->get( 'a_string' ) ); 
	}

	/**
	 * Test that a boolean value can be set/gotten/deleted/set-again.
	 */
	public function test_set_get_delete_for_boolean_value() {
	
		// Test that the default value is returned.
		$this->assertEquals( true, $this->options->get( 'a_boolean' ) );

		// Test that a new false value is returned.
		$this->options->set( 'a_boolean', false );
		$this->assertEquals( false, $this->options->get( 'a_boolean' ) );

		// Test that the value is deleted and returns null.
		$this->options->delete( 'a_boolean' );
		$this->assertEquals( null, $this->options->get( 'a_boolean' ) );

		// Test that a new value is returned.
		$this->options->set( 'a_boolean', true );
		$this->assertEquals( true, $this->options->get( 'a_boolean' ) );
	}

	/**
	 * Test that a number value can be set/gotten/deleted/set-again.
	 */
	public function test_set_get_delete_for_number_value() {
		
		// Test that the default value is returned.
		$this->assertEquals( 1, $this->options->get( 'a_number' ) );

		// Test that a new value is returned.
		$this->options->set( 'a_number', 2 );
		$this->assertEquals( 2, $this->options->get( 'a_number' ) );

		// Test that the value is deleted and returns null.
		$this->options->delete( 'a_number' );
		$this->assertEquals( null, $this->options->get( 'a_number' ) );

		// Test that a new value is returned.
		$this->options->set( 'a_number', 3 );
		$this->assertEquals( 3, $this->options->get( 'a_number' ) );
	}

	/**
	 * Test that an array value can be set/gotten/deleted/set-again.
	 */
	public function test_set_get_delete_for_array_value() {
		
		// Test that the default value is returned.
		$this->assertEquals( array( 'a', 'b', 'c' ), $this->options->get( 'an_array' ) );

		// Test that a new value is returned.
		$this->options->set( 'an_array', array( 'd', 'e', 'f' ) );
		$this->assertEquals( array( 'd', 'e', 'f' ), $this->options->get( 'an_array' ) );

		// Test that the value is deleted and returns null.
		$this->options->delete( 'an_array' );
		$this->assertEquals( null, $this->options->get( 'an_array' ) );

		// Test that a new value is returned.
		$this->options->set( 'an_array', array( 'g', 'h', 'i' ) );
		$this->assertEquals( array( 'g', 'h', 'i' ), $this->options->get( 'an_array' ) );
	}

	/**
	 * Test that the list can be filled with an array of values.
	 */
	public function test_fill_values() {
		
		// Test that the values are filled.
		$this->options->fill(
			array(
				'a_string'  => 'a filled string',
				'a_boolean' => false,
				'a_number'  => -1,
				'an_array'  => 
				array(
					'a filled value 1', 
					'a filled value 2', 
					'a filled value 3',
					array(
						'a filled value 4',
						'a filled value 5',
						'a filled value 6',
						array(
							'a filled value 7',
							'a filled value 8',
							'a filled value 9',
						),
					),

				),
			) 
		);

		$this->assertEquals( 'a filled string', $this->options->get( 'a_string' ) );
		$this->assertEquals( false, $this->options->get( 'a_boolean' ) );
		$this->assertEquals( -1, $this->options->get( 'a_number' ) );
		$this->assertEquals( 
			array(
				'a filled value 1', 
				'a filled value 2', 
				'a filled value 3',
				array(
					'a filled value 4',
					'a filled value 5',
					'a filled value 6',
					array(
						'a filled value 7',
						'a filled value 8',
						'a filled value 9',
					),
				),

			),
			$this->options->get( 'an_array' ) 
		);
	}

	/**
	 * Test that the names method returns a correct list.
	 */
	public function test_get_names_list() {
		
		$names = $this->options->names();

		$this->assertIsArray( $names );
		
		$this->assertEquals( array_keys( $this->default_values ), $names );
	}

	/**
	 * Test that the as_array method returns a correct list.
	 */
	public function test_as_array() {
		
		$arr = array(
			'a_string'  => 'a filled string',
			'a_boolean' => false,
			'a_number'  => -1,
			'an_array'  => array( 'a filled value 1', 'a filled value 2', 'a filled value 3' ),
		);


		$this->options->fill(
			$arr
		);

		$as_array = $this->options->as_array();


		$this->assertIsArray( $as_array );

		$this->assertEquals( $arr, $as_array );
	}
	


	/**
	 * Test that the option can be completely deleted.
	 */
	public function test_delete_all() {
		
		$this->options->delete_all();
		$this->assertEquals( false, get_option( $this->list_name ) );
	}
}
