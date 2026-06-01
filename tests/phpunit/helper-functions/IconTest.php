<?php
/**
 * Tests for the edac_icon helper.
 *
 * @package Accessibility_Checker
 * @since 1.38.0
 */

/**
 * Tests for edac_icon.
 *
 * @covers ::edac_icon
 * @since 1.38.0
 */
class IconTest extends WP_UnitTestCase {

	/**
	 * Unknown icon name returns an empty string.
	 */
	public function test_unknown_icon_name_returns_empty_string(): void {
		$this->assertSame( '', edac_icon( 'nonexistent' ) );
	}

	/**
	 * Each supported icon name renders a non-empty span.
	 *
	 * @dataProvider provider_supported_icon_names
	 *
	 * @param string $name Icon name.
	 */
	public function test_supported_icon_names_return_html( string $name ): void {
		$html = edac_icon( $name );
		$this->assertNotEmpty( $html );
		$this->assertStringContainsString( '<span', $html );
		$this->assertStringContainsString( '<svg', $html );
	}

	/**
	 * Data provider for supported icon names.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_supported_icon_names(): array {
		return [
			'check'     => [ 'check' ],
			'warning'   => [ 'warning' ],
			'error'     => [ 'error' ],
			'info'      => [ 'info' ],
			'dismissed' => [ 'dismissed' ],
		];
	}

	/**
	 * Auto-derived BEM modifier classes are applied correctly.
	 *
	 * @dataProvider provider_auto_type_classes
	 *
	 * @param string $name           Icon name.
	 * @param string $expected_class Expected BEM modifier class.
	 */
	public function test_auto_derived_type_class( string $name, string $expected_class ): void {
		$html = edac_icon( $name );
		$this->assertStringContainsString( $expected_class, $html );
	}

	/**
	 * Data provider for auto-derived type classes.
	 *
	 * @return array<string, array<string>>
	 */
	public function provider_auto_type_classes(): array {
		return [
			'check auto type is success'       => [ 'check', 'edac-icon--success' ],
			'warning auto type is warning'     => [ 'warning', 'edac-icon--warning' ],
			'error auto type is error'         => [ 'error', 'edac-icon--error' ],
			'info auto type is info'           => [ 'info', 'edac-icon--info' ],
			'dismissed auto type is dismissed' => [ 'dismissed', 'edac-icon--dismissed' ],
		];
	}

	/**
	 * An explicit $type overrides the auto-derived type.
	 */
	public function test_explicit_type_overrides_auto_derived_type(): void {
		$html = edac_icon( 'check', 'warning' );
		$this->assertStringContainsString( 'edac-icon--warning', $html );
		$this->assertStringNotContainsString( 'edac-icon--success', $html );
	}

	/**
	 * The base class is always present.
	 */
	public function test_base_class_always_present(): void {
		$html = edac_icon( 'check' );
		$this->assertStringContainsString( 'edac-icon', $html );
	}

	/**
	 * An extra $class is appended to the wrapper span.
	 */
	public function test_extra_class_is_appended(): void {
		$html = edac_icon( 'check', '', true, '', 'my-custom-class' );
		$this->assertStringContainsString( 'my-custom-class', $html );
	}

	/**
	 * The aria-hidden attribute defaults to true when no aria-label is provided.
	 */
	public function test_aria_hidden_true_by_default(): void {
		$html = edac_icon( 'check' );
		$this->assertStringContainsString( 'aria-hidden="true"', $html );
	}

	/**
	 * The aria-hidden attribute is false when an aria-label is provided.
	 */
	public function test_aria_hidden_false_when_aria_label_provided(): void {
		$html = edac_icon( 'check', '', true, 'Passed' );
		$this->assertStringContainsString( 'aria-hidden="false"', $html );
		$this->assertStringNotContainsString( 'aria-hidden="true"', $html );
	}

	/**
	 * The aria-label attribute is rendered when provided.
	 */
	public function test_aria_label_attribute_rendered(): void {
		$html = edac_icon( 'check', '', true, 'Passed checks' );
		$this->assertStringContainsString( 'aria-label="Passed checks"', $html );
	}

	/**
	 * The aria-label value is escaped for attribute safety.
	 */
	public function test_aria_label_is_escaped(): void {
		$html = edac_icon( 'check', '', true, '<script>alert(1)</script>' );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( 'aria-label=', $html );
	}

	/**
	 * Explicit aria_hidden=false is respected when no aria-label is given.
	 */
	public function test_explicit_aria_hidden_false_without_label(): void {
		$html = edac_icon( 'check', '', false );
		$this->assertStringContainsString( 'aria-hidden="false"', $html );
	}

	/**
	 * No aria-label attribute is added when none is provided.
	 */
	public function test_no_aria_label_attribute_when_not_provided(): void {
		$html = edac_icon( 'check' );
		$this->assertStringNotContainsString( 'aria-label=', $html );
	}
}
