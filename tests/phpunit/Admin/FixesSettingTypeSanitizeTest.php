<?php
/**
 * Tests for the fixes page setting type sanitizers.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage;

/**
 * Tests for FixesPage::sanitize_text() and FixesPage::sanitize_checkbox(),
 * which come from the AdminPage\FixesSettingType\Text and \Checkbox traits.
 *
 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage::sanitize_text
 * @covers \EqualizeDigital\AccessibilityChecker\Admin\AdminPage\FixesPage::sanitize_checkbox
 */
class FixesSettingTypeSanitizeTest extends WP_UnitTestCase {

	/**
	 * Text settings are cleaned rather than trusted.
	 *
	 * @dataProvider provider_text_inputs
	 *
	 * @param mixed  $input    The raw input.
	 * @param string $expected The expected sanitized value.
	 */
	public function test_sanitize_text( $input, string $expected ): void {
		$this->assertSame( $expected, FixesPage::sanitize_text( $input ) );
	}

	/**
	 * Data provider for text inputs.
	 *
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public function provider_text_inputs(): array {
		return [
			'plain text'   => [ 'Fixes label', 'Fixes label' ],
			'padding'      => [ '  padded  ', 'padded' ],
			'html tags'    => [ '<b>bold</b>', 'bold' ],
			'script tag'   => [ 'value<script>alert(1)</script>', 'value' ],
			'newline'      => [ "line\nbreak", 'line break' ],
			'empty string' => [ '', '' ],
			'null'         => [ null, '' ],
			'array input'  => [ [ 'not', 'a', 'string' ], '' ],
		];
	}

	/**
	 * Checkboxes are stored as the integer 1 or 0, whatever was posted.
	 *
	 * @dataProvider provider_checkbox_inputs
	 *
	 * @param mixed $input    The raw input.
	 * @param int   $expected The expected stored value.
	 */
	public function test_sanitize_checkbox( $input, int $expected ): void {
		$this->assertSame( $expected, FixesPage::sanitize_checkbox( $input ) );
	}

	/**
	 * Data provider for checkbox inputs.
	 *
	 * Only a boolean true, a non-zero integer, the string '1' and the string
	 * 'true' (any case) count as checked; every other value is unchecked.
	 *
	 * @return array<string, array{0: mixed, 1: int}>
	 */
	public function provider_checkbox_inputs(): array {
		return [
			'null'         => [ null, 0 ],
			'true'         => [ true, 1 ],
			'false'        => [ false, 0 ],
			'int 1'        => [ 1, 1 ],
			'int 0'        => [ 0, 0 ],
			'int 42'       => [ 42, 1 ],
			'string 1'     => [ '1', 1 ],
			'string true'  => [ 'true', 1 ],
			'string TRUE'  => [ 'TRUE', 1 ],
			'string 0'     => [ '0', 0 ],
			'string false' => [ 'false', 0 ],
			'string yes'   => [ 'yes', 0 ],
			'empty string' => [ '', 0 ],
		];
	}
}
