<?php
/**
 * Test class for AddMissingOrEmptyPageTitleFix.
 *
 * @package accessibility-checker
 */

use PHPUnit\Framework\TestCase;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddMissingOrEmptyPageTitleFix;
use EqualizeDigital\AccessibilityChecker\Fixes\FixInterface;

require_once __DIR__ . '/FixTestTrait.php';

/**
 * Unit tests for the AddMissingOrEmptyPageTitleFix class.
 */
class AddMissingOrEmptyPageTitleFixTest extends WP_UnitTestCase {

	use FixTestTrait;

	/**
	 * Set up the test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->fix = new AddMissingOrEmptyPageTitleFix();
	}

	/**
	 * Get the expected slug for this fix.
	 *
	 * @return string
	 */
	protected function get_expected_slug(): string {
		return 'missing_or_empty_page_title';
	}

	/**
	 * Get the expected type for this fix.
	 *
	 * @return string
	 */
	protected function get_expected_type(): string {
		return 'none';
	}

	/**
	 * Get the fix class name.
	 *
	 * @return string
	 */
	protected function get_fix_class_name(): string {
		return AddMissingOrEmptyPageTitleFix::class;
	}

	/**
	 * Test get_fields_array returns properly structured array.
	 *
	 * @return void
	 */
	public function test_get_fields_array() {
		$fields = $this->fix->get_fields_array();

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
		// Test without is_pro property (should show upsell).
		$fields = $this->fix->get_fields_array();
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
	 * Test register method adds filter.
	 *
	 * @return void
	 */
	public function test_register_adds_filter() {
		$this->fix->register();

		// Verify that the filter was added by checking if it has the expected callback.
		$this->assertTrue( has_filter( 'edac_filter_fixes_settings_fields', [ $this->fix, 'get_fields_array' ] ) !== false );
	}

	/**
	 * Test run method does nothing (intentionally empty).
	 *
	 * @return void
	 */
	public function test_run_does_nothing() {
		// Since run() is intentionally empty, just ensure it doesn't throw errors.
		$this->assertNull( $this->fix->run() );
	}

	/**
	 * Test field array structure validation.
	 *
	 * @return void
	 */
	public function test_field_array_structure() {
		$fields = $this->fix->get_fields_array();
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
