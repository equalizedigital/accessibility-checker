<?php
/**
 * Class EDACAdminOptionsTest
 *
 * @package Accessibility_Checker
 */

//phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
 
use EDAC\Admin\Options;
/**
 * Options test case.
 */
class EDACAdminOptionsTest extends WP_UnitTestCase {
	

	
	/**
	 * The name of the list.
	 *
	 * @var string $list_name.
	 */
	private $list_name = 'edac';


	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		Options::boot();
	}

	/**
	 * Get the name and default value for a given type.
	 *
	 * @param [string] $type The type.
	 * @return array|false
	 */
	private function get_name_default_value_by_type( $type ) {
	
		foreach ( Options::CASTS as $name => $value_type ) {

			if ( $value_type === $type ) {
				return array( $name, Options::DEFAULT_VALUES[ $name ] );
			}       
		}

		return false;
	}

	/**
	 * Test that a string value can be set/gotten/deleted/set-again.
	 */
	public function test_set_get_delete_for_string_value() {   
	
		$retval = $this->get_name_default_value_by_type( '' );
		if ( ! $retval ) {
			$retval = $this->get_name_default_value_by_type( 'string' );
		}
		
		if ( $retval ) {
			list($name, $default_value) = $retval;

				// Test that the default value is returned.
			$this->assertEquals( $default_value, Options::get( $name ) );

			// Test that a new empty value is returned.
			Options::set( $name, 'a new string' );
			$this->assertEquals( 'a new string', Options::get( $name ) );

			// Test that the value is deleted and returns null.
			Options::delete( $name );
			$this->assertEquals( null, Options::get( $name ) );

			// Test that a new value is returned.
			Options::set( $name, 'another new string' );
			$this->assertEquals( 'another new string', Options::get( $name ) );
		
		}
	}

	/**
	 * Test that a boolean value can be set/gotten/deleted/set-again.
	 */
	public function test_set_get_delete_for_boolean_value() {
		
		$retval = $this->get_name_default_value_by_type( 'bool' );
		
		if ( $retval ) {
			list($name, $default_value) = $retval;

			// Test that the default value is returned.
			$this->assertEquals( $default_value, Options::get( $name ) );

			// Test that a new false value is returned.
			Options::set( $name, false );
			$this->assertEquals( false, Options::get( $name ) );

			// Test that the value is deleted and returns null.
			Options::delete( $name );
			$this->assertEquals( null, Options::get( $name ) );

			// Test that a new value is returned.
			Options::set( $name, true );
			$this->assertEquals( true, Options::get( $name ) );
		}
	}

	/**
	 * Test that a number value can be set/gotten/deleted/set-again.
	 */
	public function test_set_get_delete_for_number_value() {
		
		$retval = $this->get_name_default_value_by_type( 'number' );
		
		if ( $retval ) {
			list($name, $default_value) = $retval;


			// Test that the default value is returned.
			$this->assertEquals( $default_value, Options::get( $name ) );

			// Test that a new value is returned.
			Options::set( $name, 2 );
			$this->assertEquals( 2, Options::get( $name ) );

			// Test that the value is deleted and returns null.
			Options::delete( $name );
			$this->assertEquals( null, Options::get( $name ) );

			// Test that a new value is returned.
			Options::set( $name, 3 );
			$this->assertEquals( 3, Options::get( $name ) );
		}
	}

	/**
	 * Test that an array value can be set/gotten/deleted/set-again.
	 */
	public function test_set_get_delete_for_array_value() {
		
		$retval = $this->get_name_default_value_by_type( 'array' );
		
		if ( $retval ) {
			list($name, $default_value) = $retval;

			// Test that the default value is returned.
			$this->assertEquals( $default_value, Options::get( $name ) );

			// Test that a new value is returned.
			Options::set( $name, array( 'd', 'e', 'f' ) );
			$this->assertEquals( array( 'd', 'e', 'f' ), Options::get( $name ) );

			// Test that the value is deleted and returns null.
			Options::delete( $name );
			$this->assertEquals( null, Options::get( $name ) );

			// Test that a new value is returned.
			Options::set( $name, array( 'g', 'h', 'i' ) );
			$this->assertEquals( array( 'g', 'h', 'i' ), Options::get( $name ) );
	
		}
	}

	/**
	 * Test that the list can be filled with an array of values.
	 */
	public function test_fill_values() {
		
		$fill_array = array();

		$retval = $this->get_name_default_value_by_type( '' );  
		if ( ! $retval ) {
			$retval = $this->get_name_default_value_by_type( 'string' );
		}
		if ( $retval ) {
			list($string_name, $string_default_value) = $retval;
			$fill_array[ $string_name ]               = 'a_filled_string';
		}
	
		$retval = $this->get_name_default_value_by_type( 'bool' );  
		if ( $retval ) {
			list($bool_name, $bool_default_value) = $retval;
			$fill_array[ $bool_name ]             = false;
		}
	
		$retval = $this->get_name_default_value_by_type( 'number' );    
		if ( $retval ) {
			list($number_name, $number_default_value) = $retval;
			$fill_array[ $number_name ]               = -1;
		}

		$retval = $this->get_name_default_value_by_type( 'array' ); 
		if ( $retval ) {
			list($array_name, $array_default_value) = $retval;
			$fill_array[ $array_name ]              = array( 'a filled value 1', 'a filled value 2', 'a filled value 3' );
		}       
	
		// Test that the values are filled.
		Options::fill(
			$fill_array
		);

		if ( isset( $string_name ) ) {
			$this->assertEquals( 'a_filled_string', Options::get( $string_name ) );
		}

		if ( isset( $bool_name ) ) {
			$this->assertEquals( false, Options::get( $bool_name ) );
		}

		if ( isset( $number_name ) ) {
			$this->assertEquals( -1, Options::get( $number_name ) );
		}

		if ( isset( $array_name ) ) {
			$this->assertEquals( array( 'a filled value 1', 'a filled value 2', 'a filled value 3' ), Options::get( $array_name ) );
		}   
	}

	/**
	 * Test that the names method returns a correct list.
	 */
	public function test_get_names_list() {
	
		$names = Options::names();

		$this->assertIsArray( $names );
		foreach ( $names as $name ) {
			array_key_exists( $name, Options::DEFAULT_VALUES );
		}
	}

	/**
	 * Test that the as_array method returns a correct list.
	 */
	public function test_as_array() {
		
		$fill_array = array();

		$retval = $this->get_name_default_value_by_type( '' );  
		if ( ! $retval ) {
			$retval = $this->get_name_default_value_by_type( 'string' );
		}
		if ( $retval ) {
			list($string_name, $string_default_value) = $retval;
			$fill_array[ $string_name ]               = 'a_filled_string';
		}
	
		$retval = $this->get_name_default_value_by_type( 'bool' );  
		if ( $retval ) {
			list($bool_name, $bool_default_value) = $retval;
			$fill_array[ $bool_name ]             = false;
		}
	
		$retval = $this->get_name_default_value_by_type( 'number' );    
		if ( $retval ) {
			list($number_name, $number_default_value) = $retval;
			$fill_array[ $number_name ]               = -1;
		}

		$retval = $this->get_name_default_value_by_type( 'array' ); 
		if ( $retval ) {
			list($array_name, $array_default_value) = $retval;
			$fill_array[ $array_name ]              = array( 'a filled value 1', 'a filled value 2', 'a filled value 3' );
		}       
	
	
	
		Options::fill(
			$fill_array
		);

		$as_array = Options::as_array();

	

		$this->assertIsArray( $as_array );

		foreach ( $fill_array as $key => $value ) {
			$this->assertEquals( $value, $as_array[ $key ] );
		}
	}
	


	/**
	 * Test that the option can be completely deleted.
	 */
	public function test_delete_all() {
		
		Options::delete_all();
		$this->assertEquals( false, get_option( $this->list_name ) );
	}
}
