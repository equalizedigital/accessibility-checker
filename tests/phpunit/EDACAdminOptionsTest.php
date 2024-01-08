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
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		$this->options = Options::instance();
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
			$this->assertEquals( $default_value, $this->options->get( $name ) );

			// Test that a new empty value is returned.
			$this->options->set( $name, 'a new string' );
			$this->assertEquals( 'a new string', $this->options->get( $name ) );

			// Test that the value is deleted and returns null.
			$this->options->delete( $name );
			$this->assertEquals( null, $this->options->get( $name ) );

			// Test that a new value is returned.
			$this->options->set( $name, 'another new string' );
			$this->assertEquals( 'another new string', $this->options->get( $name ) );
		
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
			$this->assertEquals( $default_value, $this->options->get( $name ) );

			// Test that a new false value is returned.
			$this->options->set( $name, false );
			$this->assertEquals( false, $this->options->get( $name ) );

			// Test that the value is deleted and returns null.
			$this->options->delete( $name );
			$this->assertEquals( null, $this->options->get( $name ) );

			// Test that a new value is returned.
			$this->options->set( $name, true );
			$this->assertEquals( true, $this->options->get( $name ) );
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
			$this->assertEquals( $default_value, $this->options->get( $name ) );

			// Test that a new value is returned.
			$this->options->set( $name, 2 );
			$this->assertEquals( 2, $this->options->get( $name ) );

			// Test that the value is deleted and returns null.
			$this->options->delete( $name );
			$this->assertEquals( null, $this->options->get( $name ) );

			// Test that a new value is returned.
			$this->options->set( $name, 3 );
			$this->assertEquals( 3, $this->options->get( $name ) );
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
			$this->assertEquals( $default_value, $this->options->get( $name ) );

			// Test that a new value is returned.
			$this->options->set( $name, array( 'd', 'e', 'f' ) );
			$this->assertEquals( array( 'd', 'e', 'f' ), $this->options->get( $name ) );

			// Test that the value is deleted and returns null.
			$this->options->delete( $name );
			$this->assertEquals( null, $this->options->get( $name ) );

			// Test that a new value is returned.
			$this->options->set( $name, array( 'g', 'h', 'i' ) );
			$this->assertEquals( array( 'g', 'h', 'i' ), $this->options->get( $name ) );
	
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
		$this->options->fill(
			$fill_array
		);

		if ( isset( $string_name ) ) {
			$this->assertEquals( 'a_filled_string', $this->options->get( $string_name ) );
		}

		if ( isset( $bool_name ) ) {
			$this->assertEquals( false, $this->options->get( $bool_name ) );
		}

		if ( isset( $number_name ) ) {
			$this->assertEquals( -1, $this->options->get( $number_name ) );
		}

		if ( isset( $array_name ) ) {
			$this->assertEquals( array( 'a filled value 1', 'a filled value 2', 'a filled value 3' ), $this->options->get( $array_name ) );
		}   
	}

	/**
	 * Test that the names method returns a correct list.
	 */
	public function test_get_names_list() {
	
		$names = $this->options->names();

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
	
	
	
		$this->options->fill(
			$fill_array
		);

		$as_array = $this->options->as_array();

	

		$this->assertIsArray( $as_array );

		foreach ( $fill_array as $key => $value ) {
			$this->assertEquals( $value, $as_array[ $key ] );
		}
	}
	


	/**
	 * Test that the option can be completely deleted.
	 */
	public function test_delete_all() {
		
		$this->options->delete_all();
		$this->assertEquals( false, get_option( $this->list_name ) );
	}
}
