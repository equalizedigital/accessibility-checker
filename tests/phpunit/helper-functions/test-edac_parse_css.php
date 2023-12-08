<?php
/**
 * Class SampleTest
 *
 * @package Accessibility_Checker
 */

/**
 * Sample test case.
 */
class EDACParseCSS extends WP_UnitTestCase {

	/**
	 * Tests the edac_parse_css function.
	 * 
	 * @dataProvider edac_parse_css_data
	 * 
	 * @param string $css_string The CSS we want to parse.
	 * @param string $css_array  The CSS array that should be returned.
	 */
	public function test_edac_parse_css( $css_string, $css_array ) {
		$this->assertSame(
			$css_array, 
			edac_parse_css( $css_string ) 
		);
	}

	/**
	 * Data provider for test_edac_parse_css.
	 */
	public function edac_parse_css_data() {
		return array(
			'simple'                                 => array(
				'css_string' => 'p { font-size: 12px; }',
				'css_array'  => array(
					'p' => array(
						'font-size' => '12px',
					),
				),
			),
			'with comments'                          => array(
				'css_string' => 'p { font-size: 12px; /* comment */ }',
				'css_array'  => array(
					'p' => array(
						'font-size' => '12px',
					),
				),
			),
			'with multiple selectors'                => array(
				'css_string' => 'p, div { font-size: 12px; }',
				'css_array'  => array(
					'p, div' => array(
						'font-size' => '12px',
					),
				),
			),
			'with multiple properties'               => array(
				'css_string' => 'p { font-size: 12px; color: #000; }',
				'css_array'  => array(
					'p' => array(
						'font-size' => '12px',
						'color'     => '#000',
					),
				),
			),
			'with multiple selectors and properties' => array(
				'css_string' => 'p, div { font-size: 12px; color: #000; }',
				'css_array'  => array(
					'p, div' => array(
						'font-size' => '12px',
						'color'     => '#000',
					),
				),
			),
			'with multiple selectors and multiple properties' => array(
				'css_string' => 'p, div { font-size: 12px; color: #000; } a { font-size: 14px; }',
				'css_array'  => array(
					'p, div' => array(
						'font-size' => '12px',
						'color'     => '#000',
					),
					'a'      => array(
						'font-size' => '14px',
					),
				),
			),
			'with multiple selectors and multiple properties and comments' => array(
				'css_string' => 'p, div { font-size: 12px; /* comment */ color: #000; } a { font-size: 14px; }',
				'css_array'  => array(
					'p, div' => array(
						'font-size' => '12px',
						'color'     => '#000',
					),
					'a'      => array(
						'font-size' => '14px',
					),
				),
			),
			'with multiple selectors and multiple properties and comments and newlines' => array(
				'css_string'             => 'p, div {
					font-size: 12px; 
					/**
					 *  comment 
					 */ 
					color: #000; 
				} 
				a 
				{ font-size: 14px; }',
				'css_array'              => array(
					'p, div' => array(
						'font-size' => '12px',
						'color'     => '#000',
					),
					'a'      => array(
						'font-size' => '14px',
					),
				),
				'a more complex example' => array(
					'css_string' => '
					div.foo {
						background-image: url("foo.png");
						background-color: #000;
						background-repeat: no-repeat;
						background-position: center center;
						background-size: cover;
					}
					div.foo a {
						color: #fff;
						text-decoration: none;
					}
					div.foo:after {
						content: "";
						display: block;
						position: absolute;
						top: 0;
						left: 0;
						width: 100%;
						height: 100%;
						background-color: rgba(0,0,0,0.5);
						background-image: url("https://example.com/foo.png");
					}
					',
					'css_array'  => array(
						'div.foo'       => array(
							'background-image'    => 'url("foo.png")',
							'background-color'    => '#000',
							'background-repeat'   => 'no-repeat',
							'background-position' => 'center center',
							'background-size'     => 'cover',
						),
						'div.foo a'     => array(
							'color'           => '#fff',
							'text-decoration' => 'none',
						),
						'div.foo:after' => array(
							'content'          => '""',
							'display'          => 'block',
							'position'         => 'absolute',
							'top'              => '0',
							'left'             => '0',
							'width'            => '100%',
							'height'           => '100%',
							'background-color' => 'rgba(0,0,0,0.5)',
							'background-image' => 'url("https://example.com/foo.png")',
						),
					),
				),
			),
		);
	}
}
