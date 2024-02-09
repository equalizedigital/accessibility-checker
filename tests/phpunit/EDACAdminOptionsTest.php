<?php
/**
 * Class EDACAdminOptionsTest
 *
 * @package Accessibility_Checker
 */

//phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
 
use EDAC\Admin\Options;
/**
 * PostOptions test case.
 */
class EDACAdminOptionsTest extends WP_UnitTestCase {
	
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
	}

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
	}


	/**
	 * Tests that we cannot add a unknown named item.
	 */
	public function testAddingAnUnknownNamedItem() {

		$name        = 'this_is_an_unknown_named_item_' . wp_generate_password( 25, false );
		$legacy_name = '';
		
		$value = $this->getRandomValueByDatatype( Options::DATATYPE_NUMBER );
		
		
		try {
			Options::set( $name, $value );
		} catch ( Exception $e ) {
			$this->assertStringContainsString( 'is not a valid option', $e->getMessage() );
		}
	}
	
	
	/**
	 * Tests that Set/update_option, Get/get_option, Delete/delete_option works.
	 *
	 * @return void
	 */
	public function testItems() {

		$items = Options::as_array();
		
		foreach ( array_keys( $items ) as $name ) {
			if ( array_key_exists( $name, Options::ITEMS ) ) {
				$datatype = Options::ITEMS[ $name ]['datatype'];
				$value    = $this->getRandomValueByDatatype( $datatype );
		
				$this->_testSetGetItem( $name, $value );
		
			}
		}
	}
	
	/**
	 * Tests that deletes are correctly handled.
	 *
	 * @return void
	 */
	public function testCleanup() {
		
		
		// Delete all the items.
		foreach ( Options::ITEMS as $name => $item ) {
		
			Options::delete( $name );

			if ( array_key_exists( $name, Options::as_array() ) ) {
			
				// After delete, so check that get will return the default value for the item.
			
				$default_value = Options::default_value( $name );
				$this->assertEquals( $default_value, Options::get( $name ) );
	
			}
		}


		
		// Make sure we can delete all the items.
		Options::delete_all();

		$option_value = get_option( Options::OPTION_NAME );

		$this->assertEquals( false, $option_value );
	}

	/**
	 * Gets a random value based on the datatype.
	 *
	 * @param string $datatype The datatype.
	 * @return mixed
	 */
	private function getRandomValueByDatatype( $datatype ) {

		$value = wp_generate_password( 255, true, true );
	
		if ( Options::DATATYPE_NUMBER === $datatype ) {
			$value = wp_rand( 0, 100 );
		}

		if ( Options::DATATYPE_BOOLEAN === $datatype ) {
			$tmp   = wp_rand( 0, 100 );
			$value = true;
			if ( $tmp <= 50 ) {
				$value = false;
			}
		}

		if ( Options::DATATYPE_ARRAY === $datatype ) {
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
	 * Tests that Set/update_option, Get/get_option work for a Options item.
	 *
	 * @param string $name name of the item.
	 * @param mixed  $value value of the item.
	 * @return void
	 */
	private function _testSetGetItem( $name, $value ) {

		// Set the item value.
		Options::set( $name, $value );

		if ( array_key_exists( $name, Options::ITEMS ) ) {
			// Check that a direct call using get_post_meta returns the correct grouped item value.
			$option = get_option( Options::OPTION_NAME ); 
			$this->assertEquals( $option[ $name ], Options::get( $name ) );
		}
	}
}
