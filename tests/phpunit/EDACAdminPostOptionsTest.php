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
	private $debug = true;

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

	/**
	 * Send log to console.
	 */
	private function log( $message, $type = 'log') {
		if($this->debug){

			if('TEST' === $type){
				fwrite(STDERR, PHP_EOL);
			}

			fwrite(STDERR, $type . ': ' . print_r($message, TRUE) . PHP_EOL);

			if('FAIL' === $type){
				die('Exiting due to FAIL.');
			}
			
		}
	}

	/**
	 * Tests that Set/update_post_metadata, Get/get_post_metadata, Delete/delete_post_metadata work using a non-legacy ungrouped item name.
	 */
	public function testGetSetUngroupedItem() {
		
		$this->log('Starting tests to get/set for ungrouped items');

		$post_options = new Post_Options( self::$post_id );

	
		foreach ( array_keys($post_options->ungrouped_items) as $name ) {
	
			$datatype = Post_Options::ITEMS[ $name ]['datatype'];
			$value    = $this->getRandomValueByDatatype( $datatype );

			$this->log('Testing ' . $name . ' with simulated an existing legacy value.');
			$this->_testSetGetItem( $name, $value , true);
			
			$this->log('Testing ' . $name . ' without a simulated an existing legacy value.');
			$this->_testSetGetItem( $name, $value , false);
			
		}

	}

	/**
	 * Tests that Set/update_post_metadata, Get/get_post_metadata, Delete/delete_post_metadata work for the specially handled _edac_summary option.
	 */
	public function HOLD_testEdacSummaryItem() {

		$name        = '_edac_summary';
		$legacy_name = '';
		
		// Set a value for _edac_summary to simulate a site that has existing options using this legacy name item.
		$value = array(
			'passed_test'     => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
			'errors'          => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
			'contrast_errors' => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
			'warnings'        => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
			'ignored'         => $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER ),
		);
		
		update_post_meta( self::$post_id, $name, $value );
		
		
		$post_options = new Post_Options( self::$post_id );
		
		var_dump( $post_options->as_array() );
		
		// var_dump($post_options->get('edac_passed_test'));
		var_dump( $post_options->get( 'passed_test' ) );
		die( 'OK!!!!' );
		
		
		var_dump( $test );
		die();
		/*
		$post_options->get( 'errors', $value['errors']);
		$post_options->set( 'contrast_errors', $value['contrast_errors']);
		$post_options->set( 'warnings', $value['warnings']);
		$post_options->set( 'ignored', $value['ignored']);
		
		$set_value = array(


		);
		*/
		$this->assertEquals( $value, $set_value );
	}

	/**
	 * Tests that we cannot add a unknown named item.
	 */
	public function HOLD_testAddingAnUnknownNamedItem() {

		$name        = 'this_is_an_unknown_named_item_' . wp_generate_password( 25, false );
		$legacy_name = '';
		
		$value = $this->getRandomValueByDatatype( Post_Options::DATATYPE_NUMBER );
		
		$post_options = new Post_Options( self::$post_id );

		try {
			$post_options->set( $name, $value );
		} catch ( Exception $e ) {
			$this->assertStringContainsString( 'is not a valid option', $e->getMessage() );
		}
	}
	
	
	/**
	 * Tests that Set/update_post_metadata, Get/get_post_metadata, Delete/delete_post_metadata work for all grouped Post_Options.
	 *
	 * @return void
	 */
	public function HOLD_testGroupedItems() {

		$grouped_items = array_filter( 
			Post_Options::ITEMS, 
			fn( $item ) => true === $item['grouped']
		);
	
		$map      = Post_Options::LEGACY_NAMES_MAPPING;
		$map_keys = array_keys( $map );

		foreach ( array_keys( $grouped_items ) as $name ) {
	
			$datatype = Post_Options::ITEMS[ $name ]['datatype'];
			$value    = $this->getRandomValueByDatatype( $datatype );

			$this->_testSetGetItem( $name, $value );
		}
	}
	
	/**
	 * Tests that Set/update_post_metadata, Get/get_post_metadata, Delete/delete_post_metadata work for all ungrouped Post_Options.
	 *
	 * @return void
	 */
	public function HOLD_testUngroupedItems() {
		
		$ungrouped_items = array_filter( 
			Post_Options::ITEMS, 
			fn( $item ) => false === $item['grouped']
		);
	
		$map      = Post_Options::LEGACY_NAMES_MAPPING;
		$map_keys = array_keys( $map );
	
		foreach ( array_keys( $ungrouped_items ) as $name ) {
	
			$datatype = Post_Options::ITEMS[ $name ]['datatype'];
			$value    = $this->getRandomValueByDatatype( $datatype );

			$this->_testSetGetItem( $name, $value );
		}
	}

	/**
	 * Tests that deletes are correctly handled.
	 *
	 * @return void
	 */
	public function HOLD_testCleanup() {
		
		$post_options = new Post_Options( self::$post_id );

		// Delete all the items.
		foreach ( Post_Options::ITEMS as $name => $item ) {
		
			$post_options->delete( $name );

			if ( array_key_exists( $name, $post_options->grouped_items ) ) {
				// grouped item.
				// After delete, so check that get will return the default value for the item.
			
				$default_value = $post_options->default_value( $name );
				$this->assertEquals( $default_value, $post_options->get( $name ) );
	
			} else {
				// ungrouped item.
	
				// After delete, so check the option has been deleted.
				$this->assertEquals(
					false, 
					metadata_exists( 'post', self::$post_id, Post_Options::OPTION_NAME . '_' . $name ) 
				);    

			}
		}


		// special handler for _edac_summary b/c it's not included in the ITEMS list.
		$post_options->delete( '_edac_summary' );
		
		// We need a straight get_post_meta call, otherwise the hook will try to return value for the non-legacy named item.
		// remove_filter( 'get_post_metadata', 'EDAC\Admin\Post_Options::get_post_metadata_hook', 10, 4 );
		$this->assertEquals(
			false, 
			metadata_exists( 'post', self::$post_id, '_edac_summary' )
		);
		// add_filter( 'get_post_metadata', 'EDAC\Admin\Post_Options::get_post_metadata_hook', 10, 4 );
		

		// Make sure the database records that should not be deleted are not deleted.
		// Make sure the database records that should be deleted are actually deleted.
		foreach ( Post_Options::LEGACY_NAMES_MAPPING as $legacy_name => $name ) {
	
			if ( Post_Options::ITEMS[ $name ]['grouped'] ) {
				// we don't provide backward compatiblity for a legacy item that is now stored as a group item, 
				// so delete/cleanup should not delete those (in case site is somehow using that existing data).

				// We need a straight get_post_meta call, otherwise the hook will try to return value for ther non-legacy named item.
				// remove_filter( 'get_post_metadata', 'EDAC\Admin\Post_Options::get_post_metadata_hook', 10, 4 );
				$this->assertEquals( true, metadata_exists( 'post', self::$post_id, $legacy_name ) );
				// add_filter( 'get_post_metadata', 'EDAC\Admin\Post_Options::get_post_metadata_hook', 10, 4 );
	
			} else {
				// we do provide backward compatiblity for a legacy item that is not stored as a group item, 
				// so delete/cleanup should have deleted those.
				$this->assertEquals( false, metadata_exists( 'post', self::$post_id, $legacy_name ) );
	
			}       
		}

		
		// Make sure we can delete all the items.
		$post_options->delete_all();
		$this->assertEquals( false, metadata_exists( 'post', self::$post_id, Post_Options::OPTION_NAME ) );
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
	 * @param string $legacy_name legacy name of the item.
	 * @param mixed  $value value of the item.
	 * @return void
	 */
	private function _testSetGetItem( $name, $value, $simulate_existing_legacy_value = false ) {

		
		$post_options = new Post_Options( self::$post_id );
		$legacy_name  = $post_options->legacy_name( $name );
		$data_type     = $post_options->data_type( $name );

		
		if ( $simulate_existing_legacy_value ) {
		
			// Set a value for the legacy post_meta_option to simulate a site that has an existing ungrouped legacy named option.
			if ( false !== $legacy_name ) {
				$data_type     = $post_options->data_type( $name );
				$initial_value = $this->getRandomValueByDatatype( $data_type );

				// Turn off our hooks so we get a straight update_post_meta call.
				Post_Options::disable_hooks();
				update_post_meta( self::$post_id, $legacy_name, $initial_value );
				Post_Options::init_hooks();
			
				$this->log('Set the initial value for legacy named item: ' . $legacy_name);
				$this->log('[' . $data_type . '] value is: ' . $initial_value );

				
			}
		}

		$this->log('Testing that post_options->set() for an item correctly sets the underlying post_meta value.', 'TEST');
		
		// Set the item value.
		$post_options->set( $name, $value );

		$this->log('Set the value for item: ' . $name);
		$this->log('[' . $data_type . '] value is: ' . $value );

		if ( array_key_exists( $name, $post_options->grouped_items ) ) {

			// This is a grouped item.
			$this->log('This is a grouped item.');
			
			Post_Options::disable_hooks();

			$grouped_option = get_post_meta( self::$post_id, Post_Options::OPTION_NAME, true ); 

			$this->log('The grouped item value is: ' . $grouped_option);
	
			// Check that the grouped option is saved as an array.
			$this->assertIsArray( $grouped_option, metadata_exists( 'post', self::$post_id, Post_Options::OPTION_NAME ), 'The grouped option is not saved as an array.' );
	
			// Check that a direct call using get_post_meta and a post_options->get return the same value.
			$direct_value = $grouped_option[ $name ];
			$this->log('The grouped item value is: ' . $direct_value);
			if($direct_value !== $post_options->get( $name )) {
				$this->log('The two raw values are the not same.', 'WARN');
			}

			$cast_and_validated_direct_value = $post_options->cast_and_validate( $name, $direct_value );
			if($cast_and_validated_direct_value === $post_options->get( $name )) {
				$this->log('Testing that post_options->set() for an item correctly sets the underlying post_meta value.', 'PASS');
			} else {
				$this->log('Testing that post_options->set() for an item correctly sets the underlying post_meta value.', 'FAIL');
			}
		
			$this->assertEquals( $cast_and_validated_direct_value, $post_options->get( $name ), 'The post_options->get() and get_post_meta() values are not equal.');
	
			Post_Options::init_hooks();
			
	
		} else {
			// This is an ungrouped item.
			$this->log('This is an ungrouped item.');
		
			Post_Options::disable_hooks();
		
			$direct_value = get_post_meta( self::$post_id, Post_Options::OPTION_NAME . '_' . $name, true );
			$this->log('The ungrouped item value is: ' . $direct_value);
			if($direct_value !== $post_options->get( $name )) {
				$this->log('The two raw values are the not same.', 'WARN');
			}

			$cast_and_validated_direct_value = $post_options->cast_and_validate( $name, $direct_value );
			if($cast_and_validated_direct_value === $post_options->get( $name )) {
				$this->log('Testing that post_options->set() for an item correctly sets the underlying post_meta value.', 'PASS');
			} else {
				$this->log('Testing that post_options->set() for an item correctly sets the underlying post_meta value.', 'FAIL');
			}
		
			// Check that a direct call using get_post_meta and a post_options->get return the same value.
			$this->assertEquals( $cast_and_validated_direct_value, $post_options->get( $name ), 'The post_options->get() and get_post_meta() values are not equal.');
			Post_Options::init_hooks();
		
		}
	

		Post_Options::disable_hooks();
		if ( metadata_exists( 'post', self::$post_id, $legacy_name ) ) {
			$has_legacy_metadata = true;
		}
		Post_Options::init_hooks();


		// Check that if a legacy option exists, the value we set using post_options->set() will be the same as get_post_meta for the legacy named option.
		if ( $has_legacy_metadata ) {
		
			$this->log('Testing that post_options->set() also updates the get_post_meta for the legacy named option.', 'TEST');
		
			// Set the post_options item to a new value.
			$current_value = $post_options->get($name);
			do {
				$new_value = $this->getRandomValueByDatatype( $data_type );	
				$this->log($new_value);

			} while ( $new_value !== $current_value );
			$post_options->set( $name, $new_value );


			Post_Options::disable_hooks();
			$current_legacy_value = get_post_meta( self::$post_id, $legacy_name );
			Post_Options::init_hooks();
	
			
			$cast_and_validated_value = $post_options->cast_and_validate( $name, $current_legacy_value );
	
			if($cast_and_validated_value === $post_options->get( $name )){
				$this->log('Updating an item with post_options->set() also correctly updates the legacy post_meta_option.', 'PASS');
			} else {
				$this->log('Updating an item with post_options->set() also correctly updates the legacy post_meta_option.', 'FAIL');
			}
			$this->assertEquals( $cast_and_validated_value , $post_options->get( $name ), 'The post_options->get() and get_post_meta() values are not equal.');
		}


	
		// Check that if a legacy option exists and we set a value using set_post_meta, our hooks will fire so that post_options->get will return the same value.
		if ( $has_legacy_metadata ) {
			
			$this->log('Testing that updating a legacy option will also update the correct post_options item.', 'TEST');
	
			Post_Options::disable_hooks();
			if ( metadata_exists( 'post', self::$post_id, $legacy_name ) ) {
				$current_legacy_value = get_post_meta( self::$post_id, $legacy_name );
			}
			Post_Options::init_hooks();
	
			//make sure we have a different value.
			do {
				$new_value = $this->getRandomValueByDatatype( $data_type );	
				$this->log($new_value);
			} while ( $new_value !== $current_legacy_value );

			//Do not disable hooks b/c we want the hooks to fire and update the post_options item
			update_post_meta( self::$post_id, $legacy_name, $new_value );
	
			$cast_and_validated_new_value = $post_options->cast_and_validate( $name, $new_value );
	
			if($cast_and_validated_new_value === $post_options->get( $name )){
				$this->log('Updating a legacy option also updates the correct post_options item.', 'PASS');
			} else {
				$this->log('Updating a legacy option does not update the correct post_options item.', 'FAIL');
			}
			$this->assertEquals( $cast_and_validated_new_value , $post_options->get( $name ), 'The post_options->get() and get_post_meta() values are not equal.');
		}


	}
}
