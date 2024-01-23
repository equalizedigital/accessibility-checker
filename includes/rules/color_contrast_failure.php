<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Color Contrast Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 *
 * WCAG 2.0 level AA requires a contrast ratio of at least 4.5:1 for normal text and 3:1 for large text.
 * Large text is defined as 14 point (typically 18.66px) and bold or larger, or 18 point (typically 24px) or larger.
 */
function edac_rule_color_contrast_failure( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	// check links in content for style tags.
	$dom    = $content['html'];
	$errors = array();

	$elements = $dom->find( '*' );
	foreach ( $elements as $element ) {

		if ( isset( $element ) && stristr( $element->getAttribute( 'style' ), 'color:' ) && '' !== $element->innertext ) {
			$foreground = '';
			$background = '';

			// get background color.
			preg_match( '/background-color:\s*(#(?:[0-9a-f]{2}){2,4}|#[0-9a-f]{3}|(?:rgba?|hsla?)\((?:\d+%?(?:deg|rad|grad|turn)?(?:,|\s)+){2,3}[\s\/]*[\d\.]+%?\)\s*(!important)*)/i', $element->getAttribute( 'style' ), $matches, PREG_OFFSET_CAPTURE );
			if ( isset( $matches[1][0] ) && '' !== $matches[1][0] ) {
				$rules['background-color'] = $matches[1][0];
			}

			preg_match( '/background:\s*(rgb\(\s*\d{1,3},\s*\d{1,3},\s*\d{1,3}\)|\#*[\w]{3,25}\s*(!important)*)/i', $element->getAttribute( 'style' ), $matches, PREG_OFFSET_CAPTURE );

			if ( isset( $matches[1][0] ) && '' !== $matches[1][0] ) {
				$rules['background'] = $matches[1][0];
			}

			// if no background color is set assume white.
			$assumedbackground = '#ffffff';
			if ( ! isset( $rules ) && '' !== $assumedbackground ) {
				$rules['background'] = $assumedbackground;
			}

			if ( isset( $rules ) ) {

				// reverse array if background color is before background.
				if ( strpos( $element->getAttribute( 'style' ), 'background-color:' ) > strpos( $element->getAttribute( 'style' ), 'background:' ) ) {
					$rules = array_reverse( $rules );
				}

				$preference = edac_deteremine_hierarchy( $rules );

				if ( 'background' === $preference ) {
					$background = edac_check_color_match2( $rules['background'] );
				} elseif ( 'background-color' === $preference ) {
					$background = $rules['background-color'];
				} else {
					return 1;
				}

				// get foreground color.
				preg_match( '/[\s|\"|\']*[^-]color:\s*(#(?:[0-9a-f]{2}){2,4}|#[0-9a-f]{3}|(?:rgba?|hsla?)\((?:\d+%?(?:deg|rad|grad|turn)?(?:,|\s)+){2,3}[\s\/]*[\d\.]+%?\)\s*(!important)*)/i', ' ' . $element->getAttribute( 'style' ), $matches, PREG_OFFSET_CAPTURE );

				if ( isset( $matches[1][0] ) && '' !== $matches[1][0] ) {
					$foreground = $matches[1][0];
				}

				// get font size.
				$font_size = null;

				if ( stristr( $element->getAttribute( 'style' ), 'font-size:' ) ) {

					$fontsearchpatterns[] = '|font\-size:\s?([\d]+)pt|i';
					$fontsearchpatterns[] = '|font\-size:\s?([\d]+)px|i';
					$fontsearchpatterns[] = '|font:\s?[\w\s\d*\s]*([\d]+)pt|i';
					$fontsearchpatterns[] = '|font:\s?[\w\s\d*\s]*([\d]+)px|i';

					// Get font size.
					// 1 px = 0.75 point; 1 point = 1.333333 px.
					foreach ( $fontsearchpatterns as $pattern ) {
						if ( preg_match_all( $pattern, $element, $matches, PREG_PATTERN_ORDER ) ) {
							$matchsize = count( $matches );
							for ( $i = 0; $i < $matchsize; $i++ ) {
								if ( isset( $matches[0][ $i ] ) && '' !== $matches[0][ $i ] ) {
									$absolute_fontsize_errorcode = htmlspecialchars( $matches[0][ $i ] );

									preg_match_all( '!\d+!', $absolute_fontsize_errorcode, $matches );

									// convert pixels to points.
									if ( stristr( $absolute_fontsize_errorcode, 'px' ) === 'px' ) {
										$font_size = implode( ' ', $matches[0] ) * 0.75;
									}

									if ( stristr( $absolute_fontsize_errorcode, 'pt' ) === 'pt' ) {
										$font_size = implode( ' ', $matches[0] );
									}
								}
							}
						}
					}
				}

				// get font weight.
				$font_bold = false;

				if (
					preg_match( '(bold|bolder|700|800|900)', stristr( $element->getAttribute( 'style' ), 'font-weight:' ) ) === 1 ||
					$element->find( 'b' ) ||
					$element->find( 'strong' )
				) {
					$font_bold = true;
				}

				// ratio.
				$ratio = 4.5;
				if ( ( $font_size >= 14 && true === $font_bold ) || $font_size >= 18 ) {
					$ratio = 3;
				}

				if ( '' !== $foreground && 'initial' !== $background && 'inherit' !== $background && 'transparent' !== $background ) {

					if ( edac_coldiff( $foreground, $background, $ratio ) ) {

						$errors[] = $element->outertext;
					}
				}
			}
		}
	}

	// check styles.
	if ( $content['css_parsed'] ) {
		$errors = array_merge( edac_check_contrast( $content ), $errors );
	}

	return $errors;
}

/**
 * Scan the content from a css file or style tag inside a post
 *
 * @param array $content to be checked.
 * @return array
 */
function edac_check_contrast( $content ) {
	$dom        = $content['html'];
	$errors     = array();
	$error_code = '';
	$css_array  = $content['css_parsed'];

	foreach ( $css_array as $element => $rules ) {

		if ( array_key_exists( 'color', $rules ) ) {

			$background = '';
			$foreground = edac_replace_css_variables( $rules['color'], $css_array );

			// determin which rule has preference if both background and background-color are present.
			$preference = edac_deteremine_hierarchy( $rules );

			if ( array_key_exists( 'background', $rules ) && 'background' === $preference ) {
				$rules['background'] = edac_replace_css_variables( $rules['background'], $css_array );
				$background          = edac_check_color_match2( $rules['background'] );
			}

			if ( array_key_exists( 'background-color', $rules ) && 'background-color' === $preference ) {
				$rules['background-color'] = edac_replace_css_variables( $rules['background-color'], $css_array );
				$background                = $rules['background-color'];
			}

			// if background color not set exit.
			if ( 'initial' === $background || 'inherit' === $background || 'transparent' === $background || '' === $background || '' === $foreground ) {
				continue;
			}

			// get font size.
			$font_size = 0;

			if ( array_key_exists( 'font-size', $rules ) ) {

				$rules['font-size'] = edac_replace_css_variables( $rules['font-size'], $css_array );

				$unit  = str_replace( '.', '', preg_replace( '/\d/', '', $rules['font-size'] ) );
				$value = str_replace( $unit, '', $rules['font-size'] );

				if ( 'px' === $unit ) {
					$font_size = (float) $value * 0.75;
				}

				if ( 'pt' === $unit ) {
					$font_size = $value;
				}
			}

			// get font weight.
			$font_bold = false;

			if ( array_key_exists( 'font-weight', $rules ) ) {

				$rules['font-weight'] = edac_replace_css_variables( $rules['font-weight'], $css_array );

				if (
					'bold' === $rules['font-weight'] ||
					'bolder' === $rules['font-weight'] ||
					'700' === $rules['font-weight'] ||
					'800' === $rules['font-weight'] ||
					'900' === $rules['font-weight']
				) {
					$font_bold = true;
				}
			}

			// check for bold or strong tags within element.
			$bold_elements = $dom->find( $element );
			if ( $bold_elements ) {
				foreach ( $bold_elements as $bold_element ) {
					if ( $bold_element->find( 'b' ) || $bold_element->find( 'strong' ) ) {
						$font_bold = true;
					}
				}
			}

			// ratio.
			$ratio = 4.5;
			if ( ( $font_size >= 14 && true === $font_bold ) || $font_size >= 18 ) {
				$ratio = 3;
			}

			if ( edac_coldiff( $foreground, $background, $ratio ) ) {

				$error_code = $element . ' { ';
				foreach ( $rules as $key => $value ) {
					$error_code .= $key . ': ' . $value . '; ';
				}
				$error_code .= '}';

				$els = $dom->find( '*[' . $element . ']' );
				if ( $els ) {
					foreach ( $els as $el ) {
						$errors[] = $el->outertext . ' ' . $error_code;
					}
				}
			}
		}
	}
	return $errors;
}

/**
 * Determine rule hierarchy
 *
 * @param array $rules array of rules.
 * @return string
 */
function edac_deteremine_hierarchy( $rules ) {
	$first = '';
	$rules = array_reverse( $rules );

	foreach ( $rules as $key => $value ) {
		if ( ( 'background' === $key || 'background-color' === $key ) && '' !== $value ) {
			$first = $key;
			break;
		}
	}

	// if background color has preference and is marked important then return background color.
	if ( 'background-color' === $first && stristr( $rules['background-color'], '!important' ) ) {
		return 'background-color';
	}

	// if background has preference and is not marked important but background color is then return background color.
	if (
		'background' === $first && ! stristr( $rules['background'], '!important' )
		&& ( array_key_exists( 'background-color', $rules ) && stristr( $rules['background-color'], '!important' ) )
	) {
		return 'background-color';
	}

	// if background color has preference but is not marked important but background is and has a color value then return background.
	if (
		'background-color' === $first && ! stristr( $rules['background-color'], '!important' )
		&& ( array_key_exists( 'background', $rules )
			&& stristr( $rules['background'], '!important' )
			&& edac_check_color_match2( $rules['background'] ) )
	) {
		return 'background';
	}

	if ( 'background' === $first && edac_check_color_match2( $rules['background'] ) ) {
		return 'background';
	}
	if ( array_key_exists( 'background-color', $rules ) ) {
		return 'background-color';
	}

	return $first;
}

/**
 * Check the color contrast
 *
 * @param string $foreground color hex.
 * @param string $background color hex.
 * @param int    $ratio number.
 * @return bool
 */
function edac_coldiff( $foreground, $background, $ratio ) {

	// convert color names to hex.
	$foreground = trim( edac_convert_color_names( $foreground ) );
	$background = trim( edac_convert_color_names( $background ) );

	// convert hex to rgb.
	$color1 = edac_hex_to_rgb( $foreground );
	$color2 = edac_hex_to_rgb( $background );

	$dif = edac_test_color_diff( $color1, $color2 );

	// failed.
	if ( $dif < $ratio ) {
		if ( $dif < $ratio ) {
			return 1;
		}
	}

	return 0;
}

/**
 * Test color contrast
 *
 * @param array $color1 rgb color.
 * @param array $color2 rgb color.
 * @return int
 */
function edac_test_color_diff( $color1, $color2 ) {
	$l1 = 0.2126 * pow( $color1['r'] / 255, 2.2 ) +
		0.7152 * pow( $color1['g'] / 255, 2.2 ) +
		0.0722 * pow( $color1['b'] / 255, 2.2 );

	$l2 = 0.2126 * pow( $color2['r'] / 255, 2.2 ) +
		0.7152 * pow( $color2['g'] / 255, 2.2 ) +
		0.0722 * pow( $color2['b'] / 255, 2.2 );

	return ( $l1 > $l2 )
		? ( ( $l1 + 0.05 ) / ( $l2 + 0.05 ) ) + 0.05
		: ( ( $l2 + 0.05 ) / ( $l1 + 0.05 ) ) + 0.05;
}

/**
 * Convert hex to rgb
 *
 * @param string  $hex code.
 * @param boolean $alpha is alpha or not.
 * @return string
 */
function edac_hex_to_rgb( $hex, $alpha = false ) {
	$hex      = str_replace( '#', '', $hex );
	$length   = strlen( $hex );
	$rgb['r'] = hexdec( 6 === $length ? substr( $hex, 0, 2 ) : ( 3 === $length ? str_repeat( substr( $hex, 0, 1 ), 2 ) : 0 ) );
	$rgb['g'] = hexdec( 6 === $length ? substr( $hex, 2, 2 ) : ( 3 === $length ? str_repeat( substr( $hex, 1, 1 ), 2 ) : 0 ) );
	$rgb['b'] = hexdec( 6 === $length ? substr( $hex, 4, 2 ) : ( 3 === $length ? str_repeat( substr( $hex, 2, 1 ), 2 ) : 0 ) );
	if ( $alpha ) {
		$rgb['a'] = $alpha;
	}
	return $rgb;
}

/**
 * Convert color names to hex
 *
 * @param string $color_name string of color name.
 * @return string
 */
function edac_convert_color_names( $color_name ) {

	if ( stristr( $color_name, 'rgb(' ) ) {
		$color_name = str_replace( 'rgb(', '', $color_name );
		$color_name = str_replace( ')', '', $color_name );
		$rgb        = explode( ',', $color_name );
		return '#' . sprintf( '%02x', $rgb['0'] ) . sprintf( '%02x', $rgb['1'] ) . sprintf( '%02x', $rgb['2'] );
	}

	// standard 147 HTML color names.
	$colors = array(
		'aliceblue'            => 'F0F8FF',
		'antiquewhite'         => 'FAEBD7',
		'aqua'                 => '00FFFF',
		'aquamarine'           => '7FFFD4',
		'azure'                => 'F0FFFF',
		'beige'                => 'F5F5DC',
		'bisque'               => 'FFE4C4',
		'black'                => '000000',
		'blanchedalmond '      => 'FFEBCD',
		'blue'                 => '0000FF',
		'blueviolet'           => '8A2BE2',
		'brown'                => 'A52A2A',
		'burlywood'            => 'DEB887',
		'cadetblue'            => '5F9EA0',
		'chartreuse'           => '7FFF00',
		'chocolate'            => 'D2691E',
		'coral'                => 'FF7F50',
		'cornflowerblue'       => '6495ED',
		'cornsilk'             => 'FFF8DC',
		'crimson'              => 'DC143C',
		'cyan'                 => '00FFFF',
		'darkblue'             => '00008B',
		'darkcyan'             => '008B8B',
		'darkgoldenrod'        => 'B8860B',
		'darkgray'             => 'A9A9A9',
		'darkgreen'            => '006400',
		'darkgrey'             => 'A9A9A9',
		'darkkhaki'            => 'BDB76B',
		'darkmagenta'          => '8B008B',
		'darkolivegreen'       => '556B2F',
		'darkorange'           => 'FF8C00',
		'darkorchid'           => '9932CC',
		'darkred'              => '8B0000',
		'darksalmon'           => 'E9967A',
		'darkseagreen'         => '8FBC8F',
		'darkslateblue'        => '483D8B',
		'darkslategray'        => '2F4F4F',
		'darkslategrey'        => '2F4F4F',
		'darkturquoise'        => '00CED1',
		'darkviolet'           => '9400D3',
		'deeppink'             => 'FF1493',
		'deepskyblue'          => '00BFFF',
		'dimgray'              => '696969',
		'dimgrey'              => '696969',
		'dodgerblue'           => '1E90FF',
		'firebrick'            => 'B22222',
		'floralwhite'          => 'FFFAF0',
		'forestgreen'          => '228B22',
		'fuchsia'              => 'FF00FF',
		'gainsboro'            => 'DCDCDC',
		'ghostwhite'           => 'F8F8FF',
		'gold'                 => 'FFD700',
		'goldenrod'            => 'DAA520',
		'gray'                 => '808080',
		'green'                => '008000',
		'greenyellow'          => 'ADFF2F',
		'grey'                 => '808080',
		'honeydew'             => 'F0FFF0',
		'hotpink'              => 'FF69B4',
		'indianred'            => 'CD5C5C',
		'indigo'               => '4B0082',
		'ivory'                => 'FFFFF0',
		'khaki'                => 'F0E68C',
		'lavender'             => 'E6E6FA',
		'lavenderblush'        => 'FFF0F5',
		'lawngreen'            => '7CFC00',
		'lemonchiffon'         => 'FFFACD',
		'lightblue'            => 'ADD8E6',
		'lightcoral'           => 'F08080',
		'lightcyan'            => 'E0FFFF',
		'lightgoldenrodyellow' => 'FAFAD2',
		'lightgray'            => 'D3D3D3',
		'lightgreen'           => '90EE90',
		'lightgrey'            => 'D3D3D3',
		'lightpink'            => 'FFB6C1',
		'lightsalmon'          => 'FFA07A',
		'lightseagreen'        => '20B2AA',
		'lightskyblue'         => '87CEFA',
		'lightslategray'       => '778899',
		'lightslategrey'       => '778899',
		'lightsteelblue'       => 'B0C4DE',
		'lightyellow'          => 'FFFFE0',
		'lime'                 => '00FF00',
		'limegreen'            => '32CD32',
		'linen'                => 'FAF0E6',
		'magenta'              => 'FF00FF',
		'maroon'               => '800000',
		'mediumaquamarine'     => '66CDAA',
		'mediumblue'           => '0000CD',
		'mediumorchid'         => 'BA55D3',
		'mediumpurple'         => '9370D0',
		'mediumseagreen'       => '3CB371',
		'mediumslateblue'      => '7B68EE',
		'mediumspringgreen'    => '00FA9A',
		'mediumturquoise'      => '48D1CC',
		'mediumvioletred'      => 'C71585',
		'midnightblue'         => '191970',
		'mintcream'            => 'F5FFFA',
		'mistyrose'            => 'FFE4E1',
		'moccasin'             => 'FFE4B5',
		'navajowhite'          => 'FFDEAD',
		'navy'                 => '000080',
		'oldlace'              => 'FDF5E6',
		'olive'                => '808000',
		'olivedrab'            => '6B8E23',
		'orange'               => 'FFA500',
		'orangered'            => 'FF4500',
		'orchid'               => 'DA70D6',
		'palegoldenrod'        => 'EEE8AA',
		'palegreen'            => '98FB98',
		'paleturquoise'        => 'AFEEEE',
		'palevioletred'        => 'DB7093',
		'papayawhip'           => 'FFEFD5',
		'peachpuff'            => 'FFDAB9',
		'peru'                 => 'CD853F',
		'pink'                 => 'FFC0CB',
		'plum'                 => 'DDA0DD',
		'powderblue'           => 'B0E0E6',
		'purple'               => '800080',
		'red'                  => 'FF0000',
		'rosybrown'            => 'BC8F8F',
		'royalblue'            => '4169E1',
		'saddlebrown'          => '8B4513',
		'salmon'               => 'FA8072',
		'sandybrown'           => 'F4A460',
		'seagreen'             => '2E8B57',
		'seashell'             => 'FFF5EE',
		'sienna'               => 'A0522D',
		'silver'               => 'C0C0C0',
		'skyblue'              => '87CEEB',
		'slateblue'            => '6A5ACD',
		'slategray'            => '708090',
		'slategrey'            => '708090',
		'snow'                 => 'FFFAFA',
		'springgreen'          => '00FF7F',
		'steelblue'            => '4682B4',
		'tan'                  => 'D2B48C',
		'teal'                 => '008080',
		'thistle'              => 'D8BFD8',
		'tomato'               => 'FF6347',
		'turquoise'            => '40E0D0',
		'violet'               => 'EE82EE',
		'wheat'                => 'F5DEB3',
		'white'                => 'FFFFFF',
		'whitesmoke'           => 'F5F5F5',
		'yellow'               => 'FFFF00',
		'yellowgreen'          => '9ACD32',
	);

	$color_name = strtolower( $color_name );
	$color_name = trim( str_replace( '!important', '', $color_name ) );

	if ( isset( $colors[ $color_name ] ) ) {
		return ( '#' . $colors[ $color_name ] );
	}
	return ( $color_name );
}

/**
 * Check background style for color
 *
 * @param string $background_rule string of background.
 * @return string
 */
function edac_check_color_match2( $background_rule ) {

	// standard 147 HTML color names.
	$colors = array(
		'aliceblue'            => 'F0F8FF',
		'antiquewhite'         => 'FAEBD7',
		'aqua'                 => '00FFFF',
		'aquamarine'           => '7FFFD4',
		'azure'                => 'F0FFFF',
		'beige'                => 'F5F5DC',
		'bisque'               => 'FFE4C4',
		'black'                => '000000',
		'blanchedalmond '      => 'FFEBCD',
		'blue'                 => '0000FF',
		'blueviolet'           => '8A2BE2',
		'brown'                => 'A52A2A',
		'burlywood'            => 'DEB887',
		'cadetblue'            => '5F9EA0',
		'chartreuse'           => '7FFF00',
		'chocolate'            => 'D2691E',
		'coral'                => 'FF7F50',
		'cornflowerblue'       => '6495ED',
		'cornsilk'             => 'FFF8DC',
		'crimson'              => 'DC143C',
		'cyan'                 => '00FFFF',
		'darkblue'             => '00008B',
		'darkcyan'             => '008B8B',
		'darkgoldenrod'        => 'B8860B',
		'darkgray'             => 'A9A9A9',
		'darkgreen'            => '006400',
		'darkgrey'             => 'A9A9A9',
		'darkkhaki'            => 'BDB76B',
		'darkmagenta'          => '8B008B',
		'darkolivegreen'       => '556B2F',
		'darkorange'           => 'FF8C00',
		'darkorchid'           => '9932CC',
		'darkred'              => '8B0000',
		'darksalmon'           => 'E9967A',
		'darkseagreen'         => '8FBC8F',
		'darkslateblue'        => '483D8B',
		'darkslategray'        => '2F4F4F',
		'darkslategrey'        => '2F4F4F',
		'darkturquoise'        => '00CED1',
		'darkviolet'           => '9400D3',
		'deeppink'             => 'FF1493',
		'deepskyblue'          => '00BFFF',
		'dimgray'              => '696969',
		'dimgrey'              => '696969',
		'dodgerblue'           => '1E90FF',
		'firebrick'            => 'B22222',
		'floralwhite'          => 'FFFAF0',
		'forestgreen'          => '228B22',
		'fuchsia'              => 'FF00FF',
		'gainsboro'            => 'DCDCDC',
		'ghostwhite'           => 'F8F8FF',
		'gold'                 => 'FFD700',
		'goldenrod'            => 'DAA520',
		'gray'                 => '808080',
		'green'                => '008000',
		'greenyellow'          => 'ADFF2F',
		'grey'                 => '808080',
		'honeydew'             => 'F0FFF0',
		'hotpink'              => 'FF69B4',
		'indianred'            => 'CD5C5C',
		'indigo'               => '4B0082',
		'ivory'                => 'FFFFF0',
		'khaki'                => 'F0E68C',
		'lavender'             => 'E6E6FA',
		'lavenderblush'        => 'FFF0F5',
		'lawngreen'            => '7CFC00',
		'lemonchiffon'         => 'FFFACD',
		'lightblue'            => 'ADD8E6',
		'lightcoral'           => 'F08080',
		'lightcyan'            => 'E0FFFF',
		'lightgoldenrodyellow' => 'FAFAD2',
		'lightgray'            => 'D3D3D3',
		'lightgreen'           => '90EE90',
		'lightgrey'            => 'D3D3D3',
		'lightpink'            => 'FFB6C1',
		'lightsalmon'          => 'FFA07A',
		'lightseagreen'        => '20B2AA',
		'lightskyblue'         => '87CEFA',
		'lightslategray'       => '778899',
		'lightslategrey'       => '778899',
		'lightsteelblue'       => 'B0C4DE',
		'lightyellow'          => 'FFFFE0',
		'lime'                 => '00FF00',
		'limegreen'            => '32CD32',
		'linen'                => 'FAF0E6',
		'magenta'              => 'FF00FF',
		'maroon'               => '800000',
		'mediumaquamarine'     => '66CDAA',
		'mediumblue'           => '0000CD',
		'mediumorchid'         => 'BA55D3',
		'mediumpurple'         => '9370D0',
		'mediumseagreen'       => '3CB371',
		'mediumslateblue'      => '7B68EE',
		'mediumspringgreen'    => '00FA9A',
		'mediumturquoise'      => '48D1CC',
		'mediumvioletred'      => 'C71585',
		'midnightblue'         => '191970',
		'mintcream'            => 'F5FFFA',
		'mistyrose'            => 'FFE4E1',
		'moccasin'             => 'FFE4B5',
		'navajowhite'          => 'FFDEAD',
		'navy'                 => '000080',
		'oldlace'              => 'FDF5E6',
		'olive'                => '808000',
		'olivedrab'            => '6B8E23',
		'orange'               => 'FFA500',
		'orangered'            => 'FF4500',
		'orchid'               => 'DA70D6',
		'palegoldenrod'        => 'EEE8AA',
		'palegreen'            => '98FB98',
		'paleturquoise'        => 'AFEEEE',
		'palevioletred'        => 'DB7093',
		'papayawhip'           => 'FFEFD5',
		'peachpuff'            => 'FFDAB9',
		'peru'                 => 'CD853F',
		'pink'                 => 'FFC0CB',
		'plum'                 => 'DDA0DD',
		'powderblue'           => 'B0E0E6',
		'purple'               => '800080',
		'red'                  => 'FF0000',
		'rosybrown'            => 'BC8F8F',
		'royalblue'            => '4169E1',
		'saddlebrown'          => '8B4513',
		'salmon'               => 'FA8072',
		'sandybrown'           => 'F4A460',
		'seagreen'             => '2E8B57',
		'seashell'             => 'FFF5EE',
		'sienna'               => 'A0522D',
		'silver'               => 'C0C0C0',
		'skyblue'              => '87CEEB',
		'slateblue'            => '6A5ACD',
		'slategray'            => '708090',
		'slategrey'            => '708090',
		'snow'                 => 'FFFAFA',
		'springgreen'          => '00FF7F',
		'steelblue'            => '4682B4',
		'tan'                  => 'D2B48C',
		'teal'                 => '008080',
		'thistle'              => 'D8BFD8',
		'tomato'               => 'FF6347',
		'turquoise'            => '40E0D0',
		'violet'               => 'EE82EE',
		'wheat'                => 'F5DEB3',
		'white'                => 'FFFFFF',
		'whitesmoke'           => 'F5F5F5',
		'yellow'               => 'FFFF00',
		'yellowgreen'          => '9ACD32',
	);

	$background_rule = strtolower( $background_rule );
	$background_rule = trim( str_replace( '!important', '', $background_rule ) );

	$rules = explode( ' ', $background_rule );

	foreach ( $rules as $rule ) {

		if ( array_key_exists( $rule, $colors ) ) {
			return $colors[ $rule ];
		}

		if ( preg_match( '/(rgb\(\s*\d{1,3},\s*\d{1,3},\s*\d{1,3}\)|\#[\w]{3,6})/i', $rule, $matches, PREG_OFFSET_CAPTURE ) ) {
			return $matches[1][0];
		}
	}
	return '';
}
