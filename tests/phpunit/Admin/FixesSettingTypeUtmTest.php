<?php
/**
 * Tests UTM parameter keys in fix setting type renderers.
 *
 * @package accessibility-checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage;

/**
 * Tests for UTM key formatting in fix setting type links.
 */
class FixesSettingTypeUtmTest extends WP_UnitTestCase {

	/**
	 * Verify text field help link uses underscore UTM keys.
	 */
	public function testTextHelpLinkUsesUnderscoreUtmKeys() {
		if ( ! function_exists( 'edac_generate_link_type' ) ) {
			$this->markTestSkipped( 'edac_generate_link_type is not available in this test environment.' );
		}

		ob_start();
		FixesPage::text(
			[
				'name'        => 'edac_fix_test_text',
				'description' => 'Description',
				'label'       => 'Test Text Fix',
				'help_id'     => 1234,
			]
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( 'utm_campaign=fix-description', $output );
		$this->assertStringContainsString( 'utm_content=edac_fix_test_text', $output );
		$this->assertStringNotContainsString( 'utm-campaign=fix-description', $output );
		$this->assertStringNotContainsString( 'utm-content=edac_fix_test_text', $output );
	}

	/**
	 * Verify checkbox help link uses underscore UTM keys.
	 */
	public function testCheckboxHelpLinkUsesUnderscoreUtmKeys() {
		if ( ! function_exists( 'edac_generate_link_type' ) ) {
			$this->markTestSkipped( 'edac_generate_link_type is not available in this test environment.' );
		}

		ob_start();
		FixesPage::checkbox(
			[
				'name'        => 'edac_fix_test_checkbox',
				'description' => 'Description',
				'label'       => 'Test Checkbox Fix',
				'help_id'     => 1234,
			]
		);
		$output = ob_get_clean();

		$this->assertStringContainsString( 'utm_campaign=fix-description', $output );
		$this->assertStringContainsString( 'utm_content=edac_fix_test_checkbox', $output );
		$this->assertStringNotContainsString( 'utm-campaign=fix-description', $output );
		$this->assertStringNotContainsString( 'utm-content=edac_fix_test_checkbox', $output );
	}
}
