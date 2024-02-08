<?php
/**
 * Class EDACAdminPostOptionsTest
 *
 * @package Accessibility_Checker
 */

//phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
 
use EDAC\Admin\Post_Options;
use PhpParser\Node\Expr\PostDec;

/**
 * PostOptions test case.
 */
class EDACAdminPostOptionsTest extends WP_UnitTestCase {

	/**
	 * Use debug mode or not.
	 *
	 * @var boolean
	 */ 
	private $debug = false;

	/**
	 * The id of the first test post.
	 *
	 * @var integer 
	 */
	private static $post_id;

	/**
	 * Set up that runs once at top.
	 * 
	 * @param WP_UnitTest_Factory $factory The factory.
	 */
	public static function wpSetUpBeforeClass( $factory ) {
		
		// create a post.
		self::$post_id = $factory->post->create();
	}

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
	}

	//phpcs:disable
	/**
	 * Debug helper function to log messages to STDERR.
	 *
	 * @param [type] $message
	 * @param string $type
	 * @return void
	 */
	private function log( $message, $type = 'log' ) {
		if ( $this->debug ) {

			if ( 'TEST' === $type ) {
				fwrite( STDERR, PHP_EOL );
			}

			fwrite( STDERR, $type . ': ' . print_r( $message, true ) . PHP_EOL );

			if ( 'FAIL' === $type ) {
				die( 'Exiting due to FAIL.' );
			}       
		}
	}
	//phpcs:enable
	

	/**
	 * Tests that Set/update_post_metadata, Get/get_post_metadata, Delete/delete_post_metadata work on ungrouped items.
	 */
	public function testGetSetDeleteUngroupedItem() {
		
		$this->log( 'Starting tests to get/set for ungrouped items' );

		$post_options = new Post_Options( self::$post_id );

	
		foreach ( array_keys( $post_options->ungrouped_items ) as $name ) {
	
			$datatype = Post_Options::ITEMS[ $name ]['datatype'];
			$value    = $this->getRandomValueByDatatype( $datatype );

			$this->log( 'Testing ' . $name . ' with a simulated existing legacy value.' );
			$this->_testSetGetItem( $name, $value, true );

			$this->log( 'Testing ' . $name . ' delete.' );
			$this->_testDeleteItem( $name );
			
			$this->log( 'Testing ' . $name . ' without a simulated existing legacy value.' );
			$this->_testSetGetItem( $name, $value, false );

			$this->log( 'Testing ' . $name . ' delete.' );
			$this->_testDeleteItem( $name );
			
		}

		
		$post_options->delete_all();

		Post_Options::disable_hooks();
		$grouped_item_exists = metadata_exists( 'post', self::$post_id, Post_Options::OPTION_NAME );
		Post_Options::init_hooks();

		$this->assertEquals( false, $grouped_item_exists, 'The grouped item was not deleted.' );
	}

	/**
	 * Tests that Set/update_post_metadata, Get/get_post_metadata, Delete/delete_post_metadata work on grouped items.
	 */
	public function testGetSetDeleteGroupedItem() {
		
		$this->log( 'Starting tests to get/set for grouped items' );

		$post_options = new Post_Options( self::$post_id );

	
		foreach ( array_keys( $post_options->grouped_items ) as $name ) {
	
			$datatype = Post_Options::ITEMS[ $name ]['datatype'];
			$value    = $this->getRandomValueByDatatype( $datatype );

			$this->log( 'Testing ' . $name . ' with a simulated existing legacy value.' );
			$this->_testSetGetItem( $name, $value, true );

			$this->log( 'Testing ' . $name . ' delete.' );
			$this->_testDeleteItem( $name );
			
			$this->log( 'Testing ' . $name . ' without a simulated existing legacy value.' );
			$this->_testSetGetItem( $name, $value, false );

			$this->log( 'Testing ' . $name . ' delete.' );
			$this->_testDeleteItem( $name );
			
		}

		
		$post_options->delete_all();

		Post_Options::disable_hooks();
		$grouped_item_exists = metadata_exists( 'post', self::$post_id, Post_Options::OPTION_NAME );
		Post_Options::init_hooks();

		$this->assertEquals( false, $grouped_item_exists, 'The grouped item was not deleted.' );
	}



	/**
	 * Tests that Set/update_post_metadata, Get/get_post_metadata, Delete/delete_post_metadata work for the specially handled _edac_summary option.
	 */
	public function testEdacSummaryItem() {

		$post_options = new Post_Options( self::$post_id );
		

		// Set a value for _edac_summary.
		$value = array(
			'passed_tests'    => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
			'errors'          => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
			'contrast_errors' => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
			'warnings'        => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
			'ignored'         => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
		);

		foreach ( $value as $key => $val ) {
			$post_options->set( $key, $val );
		}

		// Test that get() on the special named item '_edac_summary' returns the array of items we expect.
		$edac_summary = $post_options->get( '_edac_summary' );
		$this->assertEquals( $value, $edac_summary );
		


		// Set a new value for _edac_summary.
		$new_value = array(
			'passed_tests'    => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
			'errors'          => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
			'contrast_errors' => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
			'warnings'        => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
			'ignored'         => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
		);

		// Test that setting _edac_summary using update_post_meta works.
		update_post_meta( self::$post_id, '_edac_summary', $new_value );
		$edac_summary = $post_options->get( '_edac_summary' );
		$this->assertEquals( $new_value, $edac_summary );
	}

	/**
	 * Tests that we cannot add a unknown named item.
	 */
	public function testAddingAnUnknownNamedItem() {

		$name = 'this_is_an_unknown_named_item_' . wp_generate_password( 25, false );
		
		$value = $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER );
		
		$post_options = new Post_Options( self::$post_id );

		try {
			$post_options->set( $name, $value );
		} catch ( Exception $e ) {
			$this->assertStringContainsString( 'is not a valid option', $e->getMessage() );
		}
	}
	
	
	/**
	 * Gets a random value based on the datatype.
	 *
	 * @param string $datatype The datatype.
	 * @return mixed
	 */
	private function getRandomValueByDatatype( $datatype ) {

		$value = wp_generate_password( 255, true, true );
	
		if ( Post_Options::DATATYPE_NUMBER === $datatype ) {
			$value = wp_rand( 0, 100 );
		}

		if ( Post_Options::DATATYPE_BOOLEAN === $datatype ) {
			$tmp   = wp_rand( 0, 100 );
			$value = true;
			if ( $tmp <= 50 ) {
				$value = false;
			}
		}

		if ( Post_Options::DATATYPE_ARRAY === $datatype ) {
			$value = array(
				'number'    => wp_rand( 0, 100 ),
				'string'    => wp_generate_password( 25, true, true ),
				'array'     => array( 1, 2, 3, 4, 5, 'a', 'b', 'c', 'd', 'e', true, false, array( 3, 2, 1 ) ),
				'boolean_1' => true,
				'boolean_2' => false,
			);

		}

		return $value;
	}

	/**
	 * Tests that Set/update_post_metadata, Get/get_post_metadata work for a Post_Options item.
	 *
	 * @param string $name name of the item.
	 * @param mixed  $value value of the item.
	 * @param bool   $simulate_existing_legacy_value simulate an existing legacy value.
	 * @return void
	 */
	private function _testSetGetItem( $name, $value, $simulate_existing_legacy_value = false ) {

		
		$post_options = new Post_Options( self::$post_id );
		$legacy_name  = $post_options->legacy_name( $name );
		$data_type    = $post_options->data_type( $name );

		
		if ( $simulate_existing_legacy_value ) {
		
			// Set a value for the legacy post_meta_option to simulate a site that has an existing ungrouped legacy named option.
			if ( false !== $legacy_name ) {
				$data_type     = $post_options->data_type( $name );
				$initial_value = $this->getRandomValueByDatatype( $data_type );

				// Turn off our hooks so we get a straight update_post_meta call.
				Post_Options::disable_hooks();
				update_post_meta( self::$post_id, $legacy_name, $initial_value );
				Post_Options::init_hooks();
			
				$this->log( 'Set the initial value for legacy named item: ' . $legacy_name );
				$this->log( '[' . $data_type . '] value is: ' . $initial_value );

				
			}
		}

		$this->log( 'Testing that post_options->set() for an item correctly sets the underlying post_meta value.', 'TEST' );
		
		// Set the item value.
		$post_options->set( $name, $value );

		$this->log( 'Set the value for item: ' . $name );
		$this->log( '[' . $data_type . '] value is: ' . $value );

		if ( array_key_exists( $name, $post_options->grouped_items ) ) {

			// This is a grouped item.
			$this->log( 'This is a grouped item.' );
			
			Post_Options::disable_hooks();

			$grouped_option = get_post_meta( self::$post_id, Post_Options::OPTION_NAME, true ); 

			$this->log( 'The grouped item value is: ' );
			$this->log( $grouped_option );
	
			// Check that the grouped option is saved as an array.
			$this->assertIsArray( $grouped_option, metadata_exists( 'post', self::$post_id, Post_Options::OPTION_NAME ), 'The grouped option is not saved as an array.' );
	
			// Check that a direct call using get_post_meta and a post_options->get return the same value.
			$direct_value = $grouped_option[ $name ];
			$this->log( 'The grouped item raw value is: ' );
			$this->log( $direct_value );
			if ( $direct_value !== $post_options->get( $name ) ) {
				$this->log( 'The two raw values are the not same.', 'WARN' );
			}

			$cast_and_validated_direct_value = $post_options->cast_and_validate( $name, $direct_value );
			if ( $cast_and_validated_direct_value === $post_options->get( $name ) ) {
				$this->log( 'Testing that post_options->set() for an item correctly sets the underlying post_meta value.', 'PASS' );
			} else {
				$this->log( 'Testing that post_options->set() for an item correctly sets the underlying post_meta value.', 'FAIL' );
			}
		
			$this->assertEquals( $cast_and_validated_direct_value, $post_options->get( $name ), 'The post_options->get() and get_post_meta() values are not equal.' );
	
			Post_Options::init_hooks();
			
	
		} else {
			// This is an ungrouped item.
			$this->log( 'This is an ungrouped item.' );
		
			Post_Options::disable_hooks();
		
			$direct_value = get_post_meta( self::$post_id, Post_Options::OPTION_NAME . '_' . $name, true );
			$this->log( 'The ungrouped item value is: ' . $direct_value );
			if ( $direct_value !== $post_options->get( $name ) ) {
				$this->log( 'The two raw values are the not same.', 'WARN' );
			}

			$cast_and_validated_direct_value = $post_options->cast_and_validate( $name, $direct_value );
			if ( $cast_and_validated_direct_value === $post_options->get( $name ) ) {
				$this->log( 'Testing that post_options->set() for an item correctly sets the underlying post_meta value.', 'PASS' );
			} else {
				$this->log( 'Testing that post_options->set() for an item correctly sets the underlying post_meta value.', 'FAIL' );
			}
		
			// Check that a direct call using get_post_meta and a post_options->get return the same value.
			$this->assertEquals( $cast_and_validated_direct_value, $post_options->get( $name ), 'The post_options->get() and get_post_meta() values are not equal.' );
			Post_Options::init_hooks();
		
		}
	

		$has_legacy_metadata = false;
		Post_Options::disable_hooks();
		if ( metadata_exists( 'post', self::$post_id, $legacy_name ) ) {
			$has_legacy_metadata = true;
		}
		Post_Options::init_hooks();


		// Check that if a legacy option exists, the value we set using post_options->set() will be the same as get_post_meta for the legacy named option.
		if ( $has_legacy_metadata ) {
		
			$this->log( 'Testing that post_options->set() also updates the get_post_meta for the legacy named option.', 'TEST' );
		
			$this->log( 'Item: ' . $name );
			$this->log( 'Legacy name: ' . $legacy_name );

		
			// Set the post_options item to a new value.
			$current_value = $post_options->get( $name );
			$this->log( 'Current value: ' . $current_value );
			
			do {
				$new_value = $this->getRandomValueByDatatype( $data_type ); 
				$this->log( 'Getting new value.' );
			
			} while ( $new_value === $current_value );
			$post_options->set( $name, $new_value );

			$this->log( $name . ' is:' );
			$this->log( $new_value );    
	

			Post_Options::disable_hooks();
			$current_legacy_value = get_post_meta( self::$post_id, $legacy_name, true );
			Post_Options::init_hooks();
	
			$this->log( $legacy_name . ' raw value is: ' );
			$this->log( $current_legacy_value );
			
			
			$cast_and_validated_value = $post_options->cast_and_validate( $name, $current_legacy_value );
	
			$this->log( $legacy_name . ' cast and validated value is:' );
			$this->log( $cast_and_validated_value );
			
			if ( $cast_and_validated_value === $post_options->get( $name ) ) {
				$this->log( 'Updating an item with post_options->set() also correctly updates the legacy post_meta_option.', 'PASS' );
			} else {
				$this->log( 'Updating an item with post_options->set() also correctly updates the legacy post_meta_option.', 'FAIL' );
			}
			$this->assertEquals( $cast_and_validated_value, $post_options->get( $name ), 'The post_options->get() and get_post_meta() values are not equal.' );
		}


	
		// Check that if a legacy option exists and we set a value using set_post_meta, our hooks will fire so that post_options->get will return the same value.
		if ( $has_legacy_metadata ) {
			
			$this->log( 'Testing that updating a legacy option will also update the correct post_options item.', 'TEST' );
	
			Post_Options::disable_hooks();
			if ( metadata_exists( 'post', self::$post_id, $legacy_name ) ) {
				$current_legacy_value = get_post_meta( self::$post_id, $legacy_name, true );
			}
			Post_Options::init_hooks();
	
			// Make sure we have a different value.
			do {
				$new_value = $this->getRandomValueByDatatype( $data_type );             
				$this->log( 'Getting new value.' );
			} while ( $new_value === $current_legacy_value );

			// Do not disable hooks b/c we want the hooks to fire and update the post_options item.
			update_post_meta( self::$post_id, $legacy_name, $new_value );
			$this->log( $legacy_name . ' raw value is:' );
			$this->log( $new_value );    
	
			
			$cast_and_validated_new_value = $post_options->cast_and_validate( $name, $new_value );
			$this->log( $legacy_name . ' cast and validated value is:' );
			$this->log( $cast_and_validated_value );
			
			$this->log( $name . ' is:' );
			$this->log( $post_options->get( $name ) );

			if ( $cast_and_validated_new_value === $post_options->get( $name ) ) {
				$this->log( 'Updating a legacy option also updates the correct post_options item.', 'PASS' );
			} else {
				$this->log( 'Updating a legacy option does not update the correct post_options item.', 'FAIL' );
			}
			$this->assertEquals( $cast_and_validated_new_value, $post_options->get( $name ), 'The post_options->get() and get_post_meta() values are not equal.' );
		}
	}

	/**
	 * Tests that delete works.
	 *
	 * @param string $name name of the item.
	 * @return void
	 */
	private function _testDeleteItem( $name ) {

		$post_options = new Post_Options( self::$post_id );
	
		$legacy_name = $post_options->legacy_name( $name );

		$this->log( 'Testing delete item.', 'TEST' );

		Post_Options::disable_hooks();
		$has_legacy_metadata_before_delete = false;
		if ( metadata_exists( 'post', self::$post_id, $legacy_name ) ) {
			$has_legacy_metadata_before_delete = true;
		}
		Post_Options::init_hooks();
		
		$post_options->delete( $name );



		if ( array_key_exists( $name, $post_options->grouped_items ) ) {
			// If a grouped item is deleted, a new get() will return the default value for the item.
			
			// Check that get will return the default value for the item.
			$default_value = $post_options->default_value( $name );

			if ( $default_value !== $post_options->get( $name ) ) {
				$this->log( 'After deleting ' . $name . ' the default value is not returned.', 'FAIL' );
			} else {
				$this->log( 'After deleting ' . $name . ' the default value was returned.', 'PASS' );
			}

			$this->assertEquals( $default_value, $post_options->get( $name ) );

		} 
		
		

		// Check the option has been deleted.

		Post_Options::disable_hooks();
		$has_metadata = false;
		if ( metadata_exists( 'post', self::$post_id, Post_Options::OPTION_NAME . '_' . $name ) ) {
			$has_metadata = true;
		}
		Post_Options::init_hooks();
		
		if ( false === $has_metadata ) {
			$this->log( 'The post_meta_data: ' . $name . ' record was deleted.', 'PASS' );
		} else {
			$this->log( 'The post_meta_data: ' . $name . ' record was not deleted.', 'FAIL' );
		}
		$this->assertEquals( false, $has_metadata, 'The option was not deleted.' );
			


		// Check that legacy option has not been deleted.
		Post_Options::disable_hooks();
		$has_legacy_metadata_after_delete = false;
		if ( metadata_exists( 'post', self::$post_id, $legacy_name ) ) {
			$has_legacy_metadata_after_delete = true;
		}
		Post_Options::init_hooks();
		
		
		if ( $has_legacy_metadata_before_delete &&
			Post_Options::OPTION_NAME . '_' . $name !== $legacy_name  // special case were the legacy name is the same as the new name and we can disregard this test.
		) {

			if ( true === $has_legacy_metadata_after_delete ) {
				$this->log( 'The legacy post_meta_data: ' . $legacy_name . ' record was not deleted.', 'PASS' );
			} else {
				$this->log( 'The legacy post_meta_data: ' . $legacy_name . ' record was deleted.', 'FAIL' );
			}
			

			$this->assertEquals( true, $has_legacy_metadata_after_delete, 'The legacy option was deleted.' );
		}
	}
}
