<?php
/**
 * Trait for common Fix class test methods.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Trait providing common test methods for Fix classes.
 */
trait FixTestTrait {

	/**
	 * The fix instance to test.
	 *
	 * @var FixInterface
	 */
	protected $fix;

	/**
	 * Get the expected slug for this fix.
	 * Must be implemented by test classes.
	 *
	 * @return string
	 */
	abstract protected function get_expected_slug(): string;

	/**
	 * Get the expected type for this fix.
	 * Must be implemented by test classes.
	 *
	 * @return string
	 */
	abstract protected function get_expected_type(): string;

	/**
	 * Get the fix class name.
	 * Must be implemented by test classes.
	 *
	 * @return string
	 */
	abstract protected function get_fix_class_name(): string;

	/**
	 * Test that the fix implements FixInterface.
	 *
	 * @return void
	 */
	public function test_implements_fix_interface() {
		$this->assertInstanceOf( FixInterface::class, $this->fix );
	}

	/**
	 * Test get_slug returns correct slug.
	 *
	 * @return void
	 */
	public function test_get_slug() {
		$class_name = $this->get_fix_class_name();
		$this->assertEquals( $this->get_expected_slug(), $class_name::get_slug() );
	}

	/**
	 * Test get_nicename returns non-empty string.
	 *
	 * @return void
	 */
	public function test_get_nicename_returns_non_empty_string() {
		$class_name = $this->get_fix_class_name();
		$nicename   = $class_name::get_nicename();
		$this->assertIsString( $nicename );
		$this->assertNotEmpty( $nicename );
	}

	/**
	 * Test get_fancyname returns non-empty string, if method exists.
	 *
	 * @return void
	 */
	public function test_get_fancyname_returns_non_empty_string() {
		$class_name = $this->get_fix_class_name();
		if ( ! method_exists( $class_name, 'get_fancyname' ) ) {
			$this->markTestSkipped( 'Fix class does not have get_fancyname method' );
		}

		$fancyname = $class_name::get_fancyname();
		$this->assertIsString( $fancyname );
		$this->assertNotEmpty( $fancyname );
	}

	/**
	 * Test get_type returns expected type.
	 *
	 * @return void
	 */
	public function test_get_type() {
		$class_name = $this->get_fix_class_name();
		$this->assertEquals( $this->get_expected_type(), $class_name::get_type() );
	}

	/**
	 * Test get_fields_array returns array.
	 *
	 * @return void
	 */
	public function test_get_fields_array_returns_array() {
		$fields = $this->fix->get_fields_array();
		$this->assertIsArray( $fields );
	}

	/**
	 * Test get_fields_array preserves existing fields.
	 *
	 * @return void
	 */
	public function test_get_fields_array_preserves_existing_fields() {
		$existing_fields = [ 'existing_field' => [ 'type' => 'text' ] ];
		$fields          = $this->fix->get_fields_array( $existing_fields );

		$this->assertArrayHasKey( 'existing_field', $fields );
		$this->assertEquals( [ 'type' => 'text' ], $fields['existing_field'] );
	}

	/**
	 * Test register method is callable.
	 *
	 * @return void
	 */
	public function test_register_method_exists() {
		$this->assertTrue( method_exists( $this->fix, 'register' ) );
		$this->assertTrue( is_callable( [ $this->fix, 'register' ] ) );
	}
}