<?php
/**
 * Class ParseHtmlForMediaTest
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases for edac_parse_html_for_media() function.
 */
class ParseHtmlForMediaTest extends WP_UnitTestCase {

	/**
	 * Tests the edac_parse_html_for_media function.
	 *
	 * @dataProvider parse_html_for_media_data
	 *
	 * @param string $html     The HTML string to parse.
	 * @param array  $expected The expected result.
	 */
	public function test_edac_parse_html_for_media( $html, $expected ) {
		$this->assertSame(
			$expected,
			edac_parse_html_for_media( $html )
		);
	}

	/**
	 * Data provider for test_edac_parse_html_for_media.
	 */
	public function parse_html_for_media_data() {
		return [
			'empty string'                                 => [
				'html'     => '',
				'expected' => [
					'img' => null,
					'svg' => null,
				],
			],
			'simple img tag with src'                      => [
				'html'     => '<img src="image.jpg" alt="Test image">',
				'expected' => [
					'img' => 'image.jpg',
					'svg' => null,
				],
			],
			'img tag with double quotes'                   => [
				'html'     => '<img src="https://example.com/image.png" alt="Example" class="responsive">',
				'expected' => [
					'img' => 'https://example.com/image.png',
					'svg' => null,
				],
			],
			'img tag with single quotes'                   => [
				'html'     => "<img src='image.gif' alt='Animated image'>",
				'expected' => [
					'img' => 'image.gif',
					'svg' => null,
				],
			],
			'img tag with additional attributes'           => [
				'html'     => '<img width="300" height="200" src="/assets/photo.jpg" alt="Photo" ' .
					'loading="lazy">',
				'expected' => [
					'img' => '/assets/photo.jpg',
					'svg' => null,
				],
			],
			'simple svg tag'                               => [
				'html'     => '<svg width="100" height="100"><circle cx="50" cy="50" r="40" ' .
					'stroke="black" fill="red" /></svg>',
				'expected' => [
					'img' => null,
					'svg' => '<svg width="100" height="100"><circle cx="50" cy="50" r="40" ' .
						'stroke="black" fill="red" /></svg>',
				],
			],
			'complex svg with multiple elements'           => [
				'html'     => '<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">' .
					'<rect x="10" y="10" width="30" height="30" stroke="black" ' .
					'fill="transparent" stroke-width="5"/><rect x="60" y="10" rx="10" ry="10" ' .
					'width="30" height="30" stroke="black" fill="transparent" stroke-width="5"/></svg>',
				'expected' => [
					'img' => null,
					'svg' => '<svg viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">' .
						'<rect x="10" y="10" width="30" height="30" stroke="black" ' .
						'fill="transparent" stroke-width="5"/><rect x="60" y="10" rx="10" ry="10" ' .
						'width="30" height="30" stroke="black" fill="transparent" stroke-width="5"/></svg>',
				],
			],
			'html with no media tags'                      => [
				'html'     => '<p>This is just text with <strong>bold</strong> and <em>italic</em> content.</p>',
				'expected' => [
					'img' => null,
					'svg' => null,
				],
			],
			'html with encoded entities in img src'        => [
				'html'     => '<img src="image.jpg?param=value&amp;other=test" alt="Image with encoded URL">',
				'expected' => [
					'img' => 'image.jpg?param=value&other=test',
					'svg' => null,
				],
			],
			'html with both img and svg (img takes precedence)' => [
				'html'     => '<div><img src="photo.jpg" alt="Photo"><svg><circle cx="50" cy="50" r="40"/></svg></div>',
				'expected' => [
					'img' => 'photo.jpg',
					'svg' => null,
				],
			],
			'malformed img tag without src'                => [
				'html'     => '<img alt="Image without src" class="test">',
				'expected' => [
					'img' => null,
					'svg' => null,
				],
			],
			'img tag with empty src'                       => [
				'html'     => '<img src="" alt="Empty src">',
				'expected' => [
					'img' => '',
					'svg' => null,
				],
			],
			'multiline svg'                                => [
				'html'     => "<svg width='100' height='100'>\n  <circle cx='50' cy='50' r='40'/>\n</svg>",
				'expected' => [
					'img' => null,
					'svg' => "<svg width='100' height='100'>\n  <circle cx='50' cy='50' r='40'/>\n</svg>",
				],
			],
			'img tag with data-src (matches data-src too)' => [
				'html'     => '<img data-src="lazy-image.jpg" alt="Lazy loaded image">',
				'expected' => [
					'img' => 'lazy-image.jpg',
					'svg' => null,
				],
			],
			'case insensitive matching'                    => [
				'html'     => '<IMG SRC="uppercase.jpg" ALT="Uppercase tag">',
				'expected' => [
					'img' => 'uppercase.jpg',
					'svg' => null,
				],
			],
			'svg with case variations'                     => [
				'html'     => '<SVG width="100"><CIRCLE cx="50" cy="50" r="40"/></SVG>',
				'expected' => [
					'img' => null,
					'svg' => '<SVG width="100"><CIRCLE cx="50" cy="50" r="40"/></SVG>',
				],
			],
		];
	}
}
