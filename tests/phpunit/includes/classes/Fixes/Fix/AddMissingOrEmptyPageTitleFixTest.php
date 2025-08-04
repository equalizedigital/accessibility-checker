<?php
/**
 * Test class for AddMissingOrEmptyPageTitleFix.
 *
 * @package accessibility-checker
 */

use PHPUnit\Framework\TestCase;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddMissingOrEmptyPageTitleFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

/**
 * Unit tests for the AddMissingOrEmptyPageTitleFix class.
 */
class AddMissingOrEmptyPageTitleFixTest extends WP_UnitTestCase {

	/**
	 * Test that AddMissingOrEmptyPageTitleFix implements FixInterface.
	 *
	 * @return void
	 */
	public function test_implements_fix_interface() {
		$fix = new AddMissingOrEmptyPageTitleFix();
		$this->assertInstanceOf( FixInterface::class, $fix );
	}

	/**
	 * Test get_slug returns correct slug.
	 *
	 * @return void
	 */
	public function test_get_slug() {
		$this->assertEquals( 'missing_or_empty_page_title', AddMissingOrEmptyPageTitleFix::get_slug() );
	}

	/**
	 * Test get_nicename returns translated string.
	 *
	 * @return void
	 */
	public function test_get_nicename() {
		$nicename = AddMissingOrEmptyPageTitleFix::get_nicename();
		$this->assertIsString( $nicename );
		$this->assertNotEmpty( $nicename );
		$this->assertEquals( 'Add Missing or Empty Page Titles', $nicename );
	}

	/**
	 * Test get_fancyname returns translated string.
	 *
	 * @return void
	 */
	public function test_get_fancyname() {
		$fancyname = AddMissingOrEmptyPageTitleFix::get_fancyname();
		$this->assertIsString( $fancyname );
		$this->assertNotEmpty( $fancyname );
		$this->assertEquals( 'Set Page HTML Titles', $fancyname );
	}

	/**
	 * Test get_type returns none.
	 *
	 * @return void
	 */
	public function test_get_type() {
		$this->assertEquals( 'none', AddMissingOrEmptyPageTitleFix::get_type() );
	}

	/**
	 * Test get_fields_array returns properly structured array.
	 *
	 * @return void
	 */
	public function test_get_fields_array() {
		$fix    = new AddMissingOrEmptyPageTitleFix();
		$fields = $fix->get_fields_array();

		$this->assertIsArray( $fields );
		$this->assertArrayHasKey( 'edac_fix_add_missing_or_empty_page_title', $fields );

		$field = $fields['edac_fix_add_missing_or_empty_page_title'];
		$this->assertEquals( 'checkbox', $field['type'] );
		$this->assertEquals( 'Add Missing Page Title', $field['label'] );
		$this->assertEquals( 'add_missing_or_empty_page_title', $field['labelledby'] );
		$this->assertEquals( 'missing_or_empty_page_title', $field['fix_slug'] );
		$this->assertEquals( 'Add Missing or Empty Page Titles', $field['group_name'] );
		$this->assertEquals( 8490, $field['help_id'] );
		$this->assertStringContainsString( '&lt;title&gt;', $field['description'] );
	}

	/**
	 * Test get_fields_array with pro feature enabled.
	 *
	 * @return void
	 */
	public function test_get_fields_array_pro_upsell() {
		$fix = new AddMissingOrEmptyPageTitleFix();
		
		// Test without is_pro property (should show upsell).
		$fields = $fix->get_fields_array();
		$field  = $fields['edac_fix_add_missing_or_empty_page_title'];
		$this->assertTrue( $field['upsell'] );
	}

	/**
	 * Test get_fields_array with pro feature disabled (default behavior).
	 *
	 * @return void
	 */
	public function test_get_fields_array_pro_enabled() {
		// Create an anonymous class that extends the fix to simulate pro version.
		$fix = new class() extends AddMissingOrEmptyPageTitleFix {
			/**
			 * Pro flag for testing.
			 *
			 * @var bool
			 */
			public $is_pro = true;
		};
		
		$fields = $fix->get_fields_array();
		$field  = $fields['edac_fix_add_missing_or_empty_page_title'];
		$this->assertFalse( $field['upsell'] );
	}

	/**
	 * Test get_fields_array preserves existing fields.
	 *
	 * @return void
	 */
	public function test_get_fields_array_preserves_existing_fields() {
		$fix             = new AddMissingOrEmptyPageTitleFix();
		$existing_fields = [ 'existing_field' => [ 'type' => 'text' ] ];
		$fields          = $fix->get_fields_array( $existing_fields );

		$this->assertArrayHasKey( 'existing_field', $fields );
		$this->assertArrayHasKey( 'edac_fix_add_missing_or_empty_page_title', $fields );
	}

	/**
	 * Test register method adds filter.
	 *
	 * @return void
	 */
	public function test_register_adds_filter() {
		$fix = new AddMissingOrEmptyPageTitleFix();

		$fix->register();

		// Verify that the filter was added by checking if it has the expected callback.
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_fields', [ $fix, 'get_fields_array' ] ) !== false );
	}

	/**
	 * Test run method does nothing (intentionally empty).
	 *
	 * @return void
	 */
	public function test_run_does_nothing() {
		$fix = new AddMissingOrEmptyPageTitleFix();

		// Since run() is intentionally empty, just ensure it doesn't throw errors.
		$this->assertNull( $fix->run() );
	}

	/**
	 * Test field array structure validation.
	 *
	 * @return void
	 */
	public function test_field_array_structure() {
		$fix    = new AddMissingOrEmptyPageTitleFix();
		$fields = $fix->get_fields_array();
		$field  = $fields['edac_fix_add_missing_or_empty_page_title'];

		// Required field properties.
		$required_properties = [ 'type', 'label', 'labelledby', 'description', 'fix_slug', 'group_name', 'help_id' ];
		
		foreach ( $required_properties as $property ) {
			$this->assertArrayHasKey( $property, $field, "Field missing required property: {$property}" );
		}

		// Validate specific property types.
		$this->assertIsString( $field['type'] );
		$this->assertIsString( $field['label'] );
		$this->assertIsString( $field['labelledby'] );
		$this->assertIsString( $field['description'] );
		$this->assertIsString( $field['fix_slug'] );
		$this->assertIsString( $field['group_name'] );
		$this->assertIsInt( $field['help_id'] );
		$this->assertIsBool( $field['upsell'] );
	}
}
