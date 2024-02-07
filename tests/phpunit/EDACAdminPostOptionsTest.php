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

		// add legacy named options to the post to simulate a site with existing legacy named options.

		// Turn off our actions so we get a straight update_post_meta call, otherwise the hook will update the non-legacy (new named) item.
		remove_action( 'update_post_metadata', 'EDAC\Admin\Post_Options::update_post_metadata_hook', 10, 4 );
		foreach ( Post_Options::LEGACY_NAMES_MAPPING as $legacy_name => $name ) {
			$value = wp_generate_password( 25 );
			update_post_meta( self::$post_id, $legacy_name, $value );
		}
		add_action( 'update_post_metadata', 'EDAC\Admin\Post_Options::update_post_metadata_hook', 10, 4 );
	}

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
	}


	/**
	 * Tests that Set/update_post_metadata, Get/get_post_metadata, Delete/delete_post_metadata work for the specially handled _edac_summary option.
	 */
	public function testEdacSummaryItem() {

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
		$this->_testSetGetItem( $name, $legacy_name, $value );
	}

	/**
	 * Tests that we cannot add a unknown named item.
	 */
	public function testAddingAnUnknownNamedItem() {

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
	public function testGroupedItems() {

		$grouped_items = array_filter( 
			Post_Options::ITEMS, 
			fn( $item ) => true === $item['grouped']
		);
	
		$map      = Post_Options::LEGACY_NAMES_MAPPING;
		$map_keys = array_keys( $map );

		foreach ( array_keys( $grouped_items ) as $name ) {
	
			$datatype = Post_Options::ITEMS[ $name ]['datatype'];
			$value    = $this->getRandomValueByDatatype( $datatype );

			// Check if we have a legacy name for this grouped item.
			$legacy_name = array_search( $name, Post_Options::LEGACY_NAMES_MAPPING, true );
			if ( ! $legacy_name ) {
				$legacy_name = '';
			}
	
			$this->_testSetGetItem( $name, $legacy_name, $value );
		}
	}
	
	/**
	 * Tests that Set/update_post_metadata, Get/get_post_metadata, Delete/delete_post_metadata work for all ungrouped Post_Options.
	 *
	 * @return void
	 */
	public function testUngroupedItems() {
		
		$ungrouped_items = array_filter( 
			Post_Options::ITEMS, 
			fn( $item ) => false === $item['grouped']
		);
	
		$map      = Post_Options::LEGACY_NAMES_MAPPING;
		$map_keys = array_keys( $map );
	
		foreach ( array_keys( $ungrouped_items ) as $name ) {
	
			$datatype = Post_Options::ITEMS[ $name ]['datatype'];
			$value    = $this->getRandomValueByDatatype( $datatype );

			// Check if we have a legacy name for this grouped item.
			$legacy_name = array_search( $name, Post_Options::LEGACY_NAMES_MAPPING, true );
			if ( ! $legacy_name ) {
				$legacy_name = '';
			}
	
			$this->_testSetGetItem( $name, $legacy_name, $value );
		}
	}

	/**
	 * Tests that deletes are correctly handled.
	 *
	 * @return void
	 */
	public function testCleanup() {
		
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
		remove_action( 'get_post_metadata', 'EDAC\Admin\Post_Options::get_post_metadata_hook', 10, 4 );
		$this->assertEquals(
			false, 
			metadata_exists( 'post', self::$post_id, '_edac_summary' )
		);
		add_action( 'get_post_metadata', 'EDAC\Admin\Post_Options::get_post_metadata_hook', 10, 4 );
		

		// Make sure the database records that should not be deleted are not deleted.
		// Make sure the database records that should be deleted are actually deleted.
		foreach ( Post_Options::LEGACY_NAMES_MAPPING as $legacy_name => $name ) {
	
			if ( Post_Options::ITEMS[ $name ]['grouped'] ) {
				// we don't provide backward compatiblity for a legacy item that is now stored as a group item, 
				// so delete/cleanup should not delete those (in case site is somehow using that existing data).

				// We need a straight get_post_meta call, otherwise the hook will try to return value for ther non-legacy named item.
				remove_action( 'get_post_metadata', 'EDAC\Admin\Post_Options::get_post_metadata_hook', 10, 4 );
				$this->assertEquals( true, metadata_exists( 'post', self::$post_id, $legacy_name ) );
				add_action( 'get_post_metadata', 'EDAC\Admin\Post_Options::get_post_metadata_hook', 10, 4 );
	
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
	private function _testSetGetItem( $name, $legacy_name, $value ) {

		if ( '' !== $legacy_name ) {
			// We have a legacy name for this grouped item. 
			// Set a value for it to simulate a site that has existing options using the legacy named items.

			// Turn off our actions so we get a straight update_post_meta call.
			remove_action( 'update_post_metadata', 'EDAC\Admin\Post_Options::update_post_metadata_hook', 10, 4 );
			update_post_meta( self::$post_id, $legacy_name, 1 );
			// Turn our actions back on.
			add_action( 'update_post_metadata', 'EDAC\Admin\Post_Options::update_post_metadata_hook', 10, 4 );
			
		}

		$post_options = new Post_Options( self::$post_id );

		// Set the item value.
		$post_options->set( $name, $value );
	
		if ( array_key_exists( $name, $post_options->grouped_items ) ) {
			// grouped item.
			// Check that a direct call using get_post_meta returns the correct grouped item value.
			$grouped_option = get_post_meta( self::$post_id, Post_Options::OPTION_NAME, true ); 
			$this->assertEquals( $grouped_option[ $name ], $post_options->get( $name ) );
	
		} else {
			// ungrouped item.
			
			if ( '_edac_summary' === $name ) {
				$option = get_post_meta( self::$post_id, $name, true );
			} else {
				$option = get_post_meta( self::$post_id, Post_Options::OPTION_NAME . '_' . $name, true );
	
			}

			$this->assertEquals( $option, $post_options->get( $name ) );
		}
	
		if ( '' !== $legacy_name ) {
			// Check that the legacy name has been set (for backward compatibility).
			$this->assertEquals( $value, get_post_meta( self::$post_id, $legacy_name, true ) );
		}
	}
}
