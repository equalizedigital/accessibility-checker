<?php
/**
 * Class EDACAAccessibilityStatementTest
 *
 * @package Accessibility_Checker
 */

use EDAC\Inc\Accessibility_Statement;

/**
 * Accessibility statement test case.
 */
class EDACAAccessibilityStatementTest extends WP_UnitTestCase {
	
	/**
	 * Instance of the Accessibility_Statement class.
	 *
	 * @var Accessibility_Statement $accessibility_statement.
	 */
	private $accessibility_statement;
	
	/**
	 * Sets up the test environment before each test.
	 * 
	 * Initializes an instance of the Accessibility_Statement class
	 * and sets various options related to the accessibility statement.
	 */
	protected function setUp(): void {
		$this->accessibility_statement = new Accessibility_Statement();

		// Set the options
		update_option( 'edac_add_footer_accessibility_statement', true );
		update_option( 'edac_include_accessibility_statement_link', true );
		update_option( 'edac_accessibility_policy_page', 'http://example.com' );
	}

	/**
	 * Tests the get_accessibility_statement method.
	 * 
	 * Verifies that the method returns an accessibility statement
	 * containing the blog name, 'Accessibility Checker', and the accessibility policy link.
	 */
	public function test_get_accessibility_statement() {
		// Test that get_accessibility_statement returns the expected statement
		$statement = $this->accessibility_statement->get_accessibility_statement();
		$this->assertStringContainsString( get_bloginfo('name'), $statement );
		$this->assertStringContainsString( 'Accessibility Checker', $statement );
		$this->assertStringContainsString( 'Accessibility Policy', $statement );
		$this->assertStringContainsString( 'http://example.com', $statement );
	}

	/**
	 * Tests the get_accessibility_statement method without a policy link.
	 * 
	 * Configures the environment to not include an accessibility policy link
	 * and verifies that the returned statement does not contain the 'Accessibility Policy' string.
	 */
	public function test_get_accessibility_statement_no_policy() {
		// Set the options
		update_option( 'edac_include_accessibility_statement_link', false );
		delete_option( 'edac_accessibility_policy_page' );

		// Test that get_accessibility_statement returns the expected statement
		$statement = $this->accessibility_statement->get_accessibility_statement();
		$this->assertStringContainsString( get_bloginfo('name'), $statement );
		$this->assertStringContainsString( 'Accessibility Checker', $statement );
		$this->assertStringNotContainsString( 'Accessibility Policy', $statement );
	}

	/**
	 * Tests the output_accessibility_statement method.
	 * 
	 * Ensures that calling this method outputs a string representing the accessibility statement.
	 */
	public function test_output_accessibility_statement() {
		// Test that output_accessibility_statement outputs a string
		ob_start();
		$this->accessibility_statement->output_accessibility_statement();
		$output = ob_get_clean();
		$this->assertIsString( $output );
	}

	/**
	 * Tests the output_accessibility_statement method with no options set.
	 * 
	 * Deletes the accessibility-related options and verifies that 
	 * the method does not output anything.
	 */
	public function test_output_accessibility_statement_no_options() {
		// Ensure the options aren't set
		delete_option( 'edac_add_footer_accessibility_statement' );
		delete_option( 'edac_include_accessibility_statement_link' );
		delete_option( 'edac_accessibility_policy_page' );
	
		// Test that output_accessibility_statement doesn't output anything
		ob_start();
		$this->accessibility_statement->output_accessibility_statement();
		$output = ob_get_clean();
		$this->assertEmpty( $output );
	}

	/**
	 * Cleans up the test environment after each test.
	 * 
	 * Deletes the options related to the accessibility statement
	 * set during the test.
	 */
	protected function tearDown(): void {
		// Clean up
		delete_option( 'edac_add_footer_accessibility_statement' );
		delete_option( 'edac_include_accessibility_statement_link' );
		delete_option( 'edac_accessibility_policy_page' );
	
		parent::tearDown();
	}
}
