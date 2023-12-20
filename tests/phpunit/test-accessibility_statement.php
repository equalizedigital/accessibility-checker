<?php
/**
 * Class EDACAdminNoticesTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Inc\Accessibility_Statement;

/**
 * Admin Notices test case.
 */
class EDACAAccessibilityStatementTest extends WP_UnitTestCase {
	
	/**
	 * Instance of the Accessibility_Statement class.
	 *
	 * @var Accessibility_Statement $accessibility_statement.
	 */
	private $accessibility_statement;
	
	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		$this->accessibility_statement = new Accessibility_Statement();
	}

	/**
	 * Tests various scenarios for get_accessibility_statement method.
	 * - Verifies return value with no options set.
	 * - Checks output with 'include_statement' option enabled.
	 * - Tests output with 'include_statement_link' and a policy page URL set.
	 *
	 * @return void
	 */
	public function test_get_accessibility_statement() {
		// Test with no options set.
		$this->assertEquals( '', $this->accessibility_statement->get_accessibility_statement() );

		// Test with the include_statement option set.
		update_option( 'edac_include_accessibility_statement', true );
		$this->assertStringContainsString( 'We strive to ensure', $this->accessibility_statement->get_accessibility_statement() );

		// Test with the include_statement_link option set.
		update_option( 'edac_include_accessibility_statement_link', true );
		update_option( 'edac_accessibility_policy_page', 'http://example.com' );
		$this->assertStringContainsString( 'Read our <a href="http://example.com">Accessibility Policy</a>', $this->accessibility_statement->get_accessibility_statement() );
	}

	/**
	 * Tests output_accessibility_statement method for different states.
	 * - Ensures no output when no statement is set.
	 * - Confirms correct output format when a statement is set.
	 *
	 * @return void
	 */
	public function test_output_accessibility_statement() {
		// Test with no statement.
		$this->expectOutputString('');
		$this->accessibility_statement->output_accessibility_statement();

		// Test with a statement.
		update_option( 'edac_include_accessibility_statement', true );
		$this->expectOutputRegex( '/<p class="edac-accessibility-statement"><small>We strive to ensure/' );
		$this->accessibility_statement->output_accessibility_statement();
	}
}
