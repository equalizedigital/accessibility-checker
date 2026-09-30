<?php
/**
 * Class SvgMarkupToDataUriTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_svg_markup_to_data_uri() function.
 */
class SvgMarkupToDataUriTest extends WP_UnitTestCase {

	/**
	 * Tests that the function returns a data URI with the markup percent-encoded.
	 */
	public function test_encodes_markup_as_data_uri() {
		$svg = '<svg width="100" height="100"><circle cx="50" cy="50" r="40" /></svg>';

		$this->assertSame(
			'data:image/svg+xml,' . rawurlencode( $svg ),
			edac_svg_markup_to_data_uri( $svg )
		);
	}

	/**
	 * Tests that no raw markup characters survive encoding - the whole point
	 * is that the string is inert HTML once placed in an <img src> attribute.
	 *
	 * @dataProvider malicious_svg_data
	 *
	 * @param string $svg The malicious SVG markup to encode.
	 */
	public function test_strips_no_bytes_but_leaves_no_raw_markup_characters( $svg ) {
		$data_uri = edac_svg_markup_to_data_uri( $svg );

		$this->assertStringStartsWith( 'data:image/svg+xml,', $data_uri );
		$this->assertStringNotContainsString( '<', $data_uri );
		$this->assertStringNotContainsString( '>', $data_uri );
		$this->assertStringNotContainsString( '"', $data_uri );
		$this->assertStringNotContainsString( "'", $data_uri );

		// The encoded payload still round-trips back to the original markup.
		$this->assertSame(
			$svg,
			rawurldecode( substr( $data_uri, strlen( 'data:image/svg+xml,' ) ) )
		);
	}

	/**
	 * Data provider of SVG markup containing common XSS vectors.
	 */
	public function malicious_svg_data() {
		return [
			'onload handler'      => [ '<svg onload="alert(document.cookie)"></svg>' ],
			'script child'        => [ '<svg><script>alert(1)</script></svg>' ],
			'foreignObject'       => [ '<svg><foreignObject><img src=x onerror="alert(1)"></foreignObject></svg>' ],
			'javascript: xlink'   => [ '<svg><a xlink:href="javascript:alert(1)"><text>click</text></a></svg>' ],
			'animate onbegin'     => [ '<svg><animate onbegin="alert(1)" attributeName="x" /></svg>' ],
			'combined all-in-one' => [
				'<svg onload="alert(1)"><script>alert(2)</script><a onclick="alert(3)"><foreignObject><body>hi</body></foreignObject></a></svg>',
			],
		];
	}

	/**
	 * Tests that the combined-vector SVG's dangerous constructs survive
	 * encoding intact (this function encodes, it does not sanitize - the
	 * caller's <img src> context is what neutralizes them, not this string).
	 */
	public function test_combined_vector_payload_is_preserved_not_stripped() {
		$svg     = '<svg onload="alert(1)"><script>alert(2)</script><a onclick="alert(3)"><foreignObject><body>hi</body></foreignObject></a></svg>';
		$decoded = rawurldecode( substr( edac_svg_markup_to_data_uri( $svg ), strlen( 'data:image/svg+xml,' ) ) );

		$this->assertSame( $svg, $decoded );
		$this->assertStringContainsString( 'onload="alert(1)"', $decoded );
		$this->assertStringContainsString( '<script>alert(2)</script>', $decoded );
		$this->assertStringContainsString( 'onclick="alert(3)"', $decoded );
		$this->assertStringContainsString( '<foreignObject>', $decoded );
	}

	/**
	 * Tests that non-string input never fatals and always yields a bare,
	 * payload-less data URI rather than attempting to encode it.
	 *
	 * @dataProvider non_string_data
	 *
	 * @param mixed $value A non-string value.
	 */
	public function test_non_string_input_returns_bare_data_uri( $value ) {
		$this->assertSame( 'data:image/svg+xml,', edac_svg_markup_to_data_uri( $value ) );
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
}
