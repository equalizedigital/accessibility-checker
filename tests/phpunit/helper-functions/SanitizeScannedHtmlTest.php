<?php
/**
 * Class SanitizeScannedHtmlTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_sanitize_scanned_html() function.
 */
class SanitizeScannedHtmlTest extends WP_UnitTestCase {

	/**
	 * Tests that dangerous constructs are stripped from otherwise-valid SVG markup.
	 *
	 * @dataProvider malicious_svg_data
	 *
	 * @param string $svg           The malicious SVG markup.
	 * @param string $must_not_contain A substring that must not survive sanitization.
	 */
	public function test_strips_dangerous_constructs( $svg, $must_not_contain ) {
		$sanitized = edac_sanitize_scanned_html( $svg );

		$this->assertStringNotContainsString( $must_not_contain, $sanitized );
	}

	/**
	 * Data provider of SVG markup containing common XSS vectors.
	 */
	public function malicious_svg_data() {
		return [
			'onload handler'      => [ '<svg onload="alert(document.cookie)"><circle r="5" /></svg>', 'onload' ],
			'script child'        => [ '<svg><script>alert(1)</script></svg>', '<script' ],
			'foreignObject'       => [ '<svg><foreignObject><img src=x onerror="alert(1)"></foreignObject></svg>', 'foreignObject' ],
			'onclick handler'     => [ '<svg><a onclick="alert(1)"><circle r="5" /></a></svg>', 'onclick' ],
			'javascript: xlink'   => [ '<svg><use xlink:href="javascript:alert(1)" /></svg>', 'javascript:' ],
			'javascript: href'    => [ '<svg><use href="javascript:alert(1)" /></svg>', 'javascript:' ],
			'onbegin animate'     => [ '<svg><animate onbegin="alert(1)" attributeName="x" /></svg>', 'onbegin' ],
			'onmouseover handler' => [ '<svg onmouseover="alert(1)"><rect width="10" height="10" /></svg>', 'onmouseover' ],
		];
	}

	/**
	 * Tests the combined all-in-one payload (script + onload + onclick +
	 * foreignObject together) has every dangerous construct removed.
	 */
	public function test_combined_vector_payload_is_fully_stripped() {
		$svg       = '<svg onload="alert(1)"><script>alert(2)</script><a onclick="alert(3)"><foreignObject><body>hi</body></foreignObject></a></svg>';
		$sanitized = edac_sanitize_scanned_html( $svg );

		$this->assertStringNotContainsString( 'onload', $sanitized );
		$this->assertStringNotContainsString( '<script', $sanitized );
		$this->assertStringNotContainsString( 'onclick', $sanitized );
		$this->assertStringNotContainsString( 'foreignObject', $sanitized );
	}

	/**
	 * Tests that a realistic, benign icon-style SVG survives sanitization
	 * with its meaningful content intact - the whole point of a wide
	 * allow-list is not mangling ordinary safe SVGs.
	 */
	public function test_preserves_safe_svg_content() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" role="img" aria-label="Warning">'
			. '<title>Warning</title>'
			. '<defs><linearGradient id="g1" x1="0" y1="0" x2="1" y2="1">'
			. '<stop offset="0%" stop-color="#fff" /><stop offset="100%" stop-color="#000" /></linearGradient>'
			. '<clipPath id="c1"><rect x="0" y="0" width="24" height="24" /></clipPath></defs>'
			. '<g fill="url(#g1)" stroke="#333" stroke-width="1" clip-path="url(#c1)">'
			. '<circle cx="12" cy="12" r="10" />'
			. '<path d="M12 6v8" />'
			. '</g>'
			. '<use xlink:href="#g1" />'
			. '</svg>';

		$sanitized = edac_sanitize_scanned_html( $svg );

		$this->assertStringContainsString( '<svg', $sanitized );
		$this->assertStringContainsString( 'viewBox="0 0 24 24"', $sanitized );
		$this->assertStringContainsString( '<title>Warning</title>', $sanitized );
		$this->assertStringContainsString( '<linearGradient', $sanitized );
		$this->assertStringContainsString( '<stop', $sanitized );
		$this->assertStringContainsString( 'stop-color="#fff"', $sanitized );
		$this->assertStringContainsString( '<clipPath', $sanitized );
		$this->assertStringContainsString( 'clip-path="url(#c1)"', $sanitized );
		$this->assertStringContainsString( '<circle', $sanitized );
		$this->assertStringContainsString( 'cx="12"', $sanitized );
		$this->assertStringContainsString( '<path', $sanitized );
		$this->assertStringContainsString( 'd="M12 6v8"', $sanitized );
		$this->assertStringContainsString( 'xlink:href="#g1"', $sanitized );
	}

	/**
	 * Tests that every case-sensitive SVG attribute still on the allow-list
	 * comes back out with its correct camelCase name even though wp_kses()
	 * lowercases attribute names internally.
	 */
	public function test_restores_case_sensitive_svg_attribute_names() {
		$svg = '<svg viewBox="0 0 10 10" preserveAspectRatio="xMidYMid meet">'
			. '<linearGradient id="g" gradientUnits="userSpaceOnUse" gradientTransform="rotate(45)" spreadMethod="pad">'
			. '<stop offset="0" stop-color="#fff" /></linearGradient>'
			. '<rect width="10" height="10" fill="url(#g)" />'
			. '</svg>';

		$sanitized = edac_sanitize_scanned_html( $svg );

		$this->assertStringContainsString( 'viewBox=', $sanitized );
		$this->assertStringContainsString( 'preserveAspectRatio=', $sanitized );
		$this->assertStringContainsString( 'gradientUnits=', $sanitized );
		$this->assertStringContainsString( 'gradientTransform=', $sanitized );
		$this->assertStringContainsString( 'spreadMethod=', $sanitized );
	}

	/**
	 * Tests that structural SVG elements deliberately left off the allow-list
	 * (pattern, mask, marker, switch, textPath) are stripped as tags - kses
	 * removes the tag itself while keeping any benign child shapes.
	 */
	public function test_strips_svg_elements_outside_the_allow_list() {
		$svg = '<svg viewBox="0 0 10 10">'
			. '<pattern id="p"><circle r="1" /></pattern>'
			. '<mask id="m"><rect width="10" height="10" /></mask>'
			. '<marker id="k"><path d="M0 0" /></marker>'
			. '<switch><text x="0" y="0">Hi</text></switch>'
			. '<text><textPath href="#p">curved</textPath></text>'
			. '</svg>';

		$sanitized = edac_sanitize_scanned_html( $svg );

		$this->assertStringNotContainsString( '<pattern', $sanitized );
		$this->assertStringNotContainsString( '<mask', $sanitized );
		$this->assertStringNotContainsString( '<marker', $sanitized );
		$this->assertStringNotContainsString( '<switch', $sanitized );
		$this->assertStringNotContainsString( '<textPath', $sanitized );
		$this->assertStringNotContainsString( '<textpath', $sanitized );
	}

	/**
	 * Tests that a local same-document fragment reference (the common,
	 * legitimate icon-sprite pattern) is preserved on both href and
	 * xlink:href.
	 */
	public function test_preserves_local_fragment_references() {
		$svg = '<svg><use href="#icon-check" /><use xlink:href="#icon-check" /></svg>';

		$sanitized = edac_sanitize_scanned_html( $svg );

		$this->assertStringContainsString( 'href="#icon-check"', $sanitized );
		$this->assertStringContainsString( 'xlink:href="#icon-check"', $sanitized );
	}

	/**
	 * Tests that ordinary (non-SVG) post-content HTML - the kind most
	 * flagged elements actually are - passes through unaffected.
	 */
	public function test_preserves_ordinary_html_snippet() {
		$html = '<a href="https://example.com"><strong>Click here</strong></a>';

		$this->assertSame( $html, edac_sanitize_scanned_html( $html ) );
	}

	/**
	 * Tests that a plain empty-link snippet (no SVG involved at all) is untouched.
	 */
	public function test_preserves_empty_link_snippet() {
		$html = '<a href="#"><img src="icon.png" alt="Icon"></a>';

		$sanitized = edac_sanitize_scanned_html( $html );

		$this->assertStringContainsString( 'href="#"', $sanitized );
		$this->assertStringContainsString( 'src="icon.png"', $sanitized );
	}

	/**
	 * Tests that non-string input returns an empty string rather than
	 * throwing or emitting a PHP warning.
	 *
	 * @dataProvider non_string_data
	 *
	 * @param mixed $value A non-string value.
	 */
	public function test_non_string_input_returns_empty_string( $value ) {
		$this->assertSame( '', edac_sanitize_scanned_html( $value ) );
	}

	/**
	 * Data provider of non-string values.
	 */
	public function non_string_data() {
		return [
			'null'   => [ null ],
			'array'  => [ [ '<svg onload="alert(1)"></svg>' ] ],
			'int'    => [ 42 ],
			'bool'   => [ true ],
			'object' => [ (object) [ 'markup' => '<svg></svg>' ] ],
		];
	}

	/**
	 * Tests that an empty string input returns an empty string.
	 */
	public function test_empty_string_input_returns_empty_string() {
		$this->assertSame( '', edac_sanitize_scanned_html( '' ) );
	}
}
