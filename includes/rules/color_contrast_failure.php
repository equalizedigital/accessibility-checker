<?php

function edac_rule_color_contrast_failure($content, $post)
{	
	// check links in content for style tags
	$dom = $content;
	$errors = [];

	$elements = $dom->find('*');
	foreach ($elements as $element) {

		if (isset($element) and stristr($element->getAttribute('style'), 'color:') and $element->innertext != "") {
			$foreground = "";
			$background = "";

			// get background color
			preg_match('/background-color:\s*(#(?:[0-9a-f]{2}){2,4}|#[0-9a-f]{3}|(?:rgba?|hsla?)\((?:\d+%?(?:deg|rad|grad|turn)?(?:,|\s)+){2,3}[\s\/]*[\d\.]+%?\)\s*(!important)*)/i', $element->getAttribute('style'), $matches, PREG_OFFSET_CAPTURE);
			if (isset($matches[1][0]) and $matches[1][0] != "") $rules['background-color'] = $matches[1][0];

			preg_match('/background:\s*(rgb\(\s*\d{1,3},\s*\d{1,3},\s*\d{1,3}\)|\#*[\w]{3,25}\s*(!important)*)/i', $element->getAttribute('style'), $matches, PREG_OFFSET_CAPTURE);

			if (isset($matches[1][0]) and $matches[1][0] != "") {
				$rules['background'] = $matches[1][0];
			}

			// if no background color is set assume white
			$assumedbackground = '#ffffff';
			if (!isset($rules) and $assumedbackground != "") $rules['background'] = $assumedbackground;

			// reverse array if background color is before background
			if (isset($rules)) {
				if (strpos($element->getAttribute('style'), 'background-color:') > strpos($element->getAttribute('style'), 'background:'))
					$rules = array_reverse($rules);

				$preference = ac_deteremine_hierarchy($rules);

				if ($preference == 'background') $background =  ac_check_color_match2($rules['background']);
				elseif ($preference == 'background-color') $background = $rules['background-color'];
				else return 1;

				// get foreground color
				preg_match('/[\s|\"|\']*[^-]color:\s*(#(?:[0-9a-f]{2}){2,4}|#[0-9a-f]{3}|(?:rgba?|hsla?)\((?:\d+%?(?:deg|rad|grad|turn)?(?:,|\s)+){2,3}[\s\/]*[\d\.]+%?\)\s*(!important)*)/i', ' ' . $element->getAttribute('style'), $matches, PREG_OFFSET_CAPTURE);

				if (isset($matches[1][0]) and $matches[1][0] != "") $foreground = $matches[1][0];


				if ($foreground != "" and $background != "initial" and $background != "inherit" and $background != "transparent") {

					if (ac_coldiff($foreground, $background)) {

						//edac_log($element->outertext);

						$errors[] = $element->outertext;
					}
				}
			}
		}
	}
	
	/*
	 * check for styles within style tags
	 * <style></style>
	 */
	if($content){
		$dom_styles = $content;
		$styles = $dom_styles->find('style');

		if ($styles) {
			foreach ($styles as $style) {
				$errors = array_merge(ac_check_contrast($content, $style->innertext),$errors);
			}
		}
		

		/*
		* check for styles from file
		*/
		foreach ($dom_styles->find('link[rel="stylesheet"]') as $stylesheet){
			$stylesheet_url = $stylesheet->href;
			$styles = @file_get_contents($stylesheet_url);
			$errors = array_merge(ac_check_contrast($content, $styles),$errors);
		}
	}

	return $errors;
}

/**
 * Scan the content from a css file or style tag inside a post
 *
 * @param array $content
 * @param string $styles
 * @return array
 */
function ac_check_contrast($content, $styles)
{
	$dom = $content;
	$errors = [];
	$error_code = '';
	$css_array = edac_parse_css($styles);

	foreach ($css_array as $element => $rules) {

		if (array_key_exists('color', $rules)) {

			$background = "";
			$foreground = $rules['color'];

			// determin which rule has preference if both background and background-color are present
			$preference = ac_deteremine_hierarchy($rules);

			if (array_key_exists('background', $rules) and $preference == 'background')
				$background = ac_check_color_match2($rules['background']);

			if (array_key_exists('background-color', $rules) and $preference == 'background-color')
				$background = $rules['background-color'];

			// if background color not set exit	
			if ($background == "initial" or $background == "inherit" or $background == "transparent" or $background == "" or $foreground == "") goto a;

			if (ac_coldiff($foreground, $background)) {

				$error_code = $element . '{';
				foreach ($rules as $key => $value) {
					$error_code .= $key . ': ' . $value . '; ';
				}

				$elements = $dom->find($element);
				
				if($elements){
					foreach ($elements as $element) {
						$errors[] = $element->outertext.' '.$error_code;
					}
				}
			}
			a:
		}
	}
	return $errors;
}

/**
 * determine rule hierarchy	
 */
function ac_deteremine_hierarchy($rules)
{
	$first = "";
	$rules = array_reverse($rules);
	foreach ($rules as $key => $value) {
		if (($key == 'background' or $key == 'background-color') and $value != "") {
			$first = $key;
			goto a;
		}
	}
	a:

	// if background color has preference and is marked important then return background color
	if ($first == 'background-color' and stristr($rules['background-color'], '!important')) return 'background-color';

	// if background has preference and is not marked important but background color is then return background color
	if (
		$first == 'background' and !stristr($rules['background'], '!important')
		and (array_key_exists('background-color', $rules) and stristr($rules['background-color'], '!important'))
	) return 'background-color';

	// if background color has preference but is not marked important but background is and has a color value then return background
	if (
		$first == 'background-color' and !stristr($rules['background-color'], '!important')
		and (array_key_exists('background', $rules)
			and stristr($rules['background'], '!important')
			and ac_check_color_match2($rules['background']))
	) return 'background';

	if ($first == 'background' and ac_check_color_match2($rules['background'])) return 'background';
	elseif (array_key_exists('background-color', $rules)) return 'background-color';


	return $first;
}

/**
 * check the color contrast
 */
function ac_coldiff($foreground, $background)
{

	//convert color names to hex
	$foreground = trim(ac_convert_color_names($foreground));
	$background = trim(ac_convert_color_names($background));

	// convert hex to rgb	
	$color1 = ac_hexToRgb($foreground);
	$color2 = ac_hexToRgb($background);

	$dif = ac_test_color_diff($color1, $color2);

	// failed
	if ($dif < 4.5) {

		//echo $foreground.'<br />';
		//echo $background.'<br />';
		//echo $dif.'<br />';
		if ($dif < 4.5)  return 1;
	}

	return 0;
}

/**
 * test color contrast
 */
function ac_test_color_diff($color1, $color2)
{
	$L1 = 0.2126 * pow($color1['r'] / 255, 2.2) +
		0.7152 * pow($color1['g'] / 255, 2.2) +
		0.0722 * pow($color1['b'] / 255, 2.2);

	$L2 = 0.2126 * pow($color2['r'] / 255, 2.2) +
		0.7152 * pow($color2['g'] / 255, 2.2) +
		0.0722 * pow($color2['b'] / 255, 2.2);

	if ($L1 > $L2) {
		$dif = (($L1 + 0.05) / ($L2 + 0.05)) + 0.05;
	} else {
		$dif = (($L2 + 0.05) / ($L1 + 0.05)) + 0.05;
	}

	return $dif;
}

/**
 * convert hex to rgb
 */
function ac_hexToRgb($hex, $alpha = false)
{
	$hex      = str_replace('#', '', $hex);
	$length   = strlen($hex);
	$rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
	$rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
	$rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
	if ($alpha) {
		$rgb['a'] = $alpha;
	}
	return $rgb;
}

/**
 * convert color names to hex
 */
function ac_convert_color_names($color_name)
{

	if (stristr($color_name, 'rgb(')) {
		$color_name = str_replace('rgb(', '', $color_name);
		$color_name = str_replace(')', '', $color_name);
		$rgb = explode(',', $color_name);
		return '#' . sprintf('%02x', $rgb['0']) . sprintf('%02x', $rgb['1']) . sprintf('%02x', $rgb['2']);
	}

	// standard 147 HTML color names
	$colors  =  array(
		'aliceblue' => 'F0F8FF',
		'antiquewhite' => 'FAEBD7',
		'aqua' => '00FFFF',
		'aquamarine' => '7FFFD4',
		'azure' => 'F0FFFF',
		'beige' => 'F5F5DC',
		'bisque' => 'FFE4C4',
		'black' => '000000',
		'blanchedalmond ' => 'FFEBCD',
		'blue' => '0000FF',
		'blueviolet' => '8A2BE2',
		'brown' => 'A52A2A',
		'burlywood' => 'DEB887',
		'cadetblue' => '5F9EA0',
		'chartreuse' => '7FFF00',
		'chocolate' => 'D2691E',
		'coral' => 'FF7F50',
		'cornflowerblue' => '6495ED',
		'cornsilk' => 'FFF8DC',
		'crimson' => 'DC143C',
		'cyan' => '00FFFF',
		'darkblue' => '00008B',
		'darkcyan' => '008B8B',
		'darkgoldenrod' => 'B8860B',
		'darkgray' => 'A9A9A9',
		'darkgreen' => '006400',
		'darkgrey' => 'A9A9A9',
		'darkkhaki' => 'BDB76B',
		'darkmagenta' => '8B008B',
		'darkolivegreen' => '556B2F',
		'darkorange' => 'FF8C00',
		'darkorchid' => '9932CC',
		'darkred' => '8B0000',
		'darksalmon' => 'E9967A',
		'darkseagreen' => '8FBC8F',
		'darkslateblue' => '483D8B',
		'darkslategray' => '2F4F4F',
		'darkslategrey' => '2F4F4F',
		'darkturquoise' => '00CED1',
		'darkviolet' => '9400D3',
		'deeppink' => 'FF1493',
		'deepskyblue' => '00BFFF',
		'dimgray' => '696969',
		'dimgrey' => '696969',
		'dodgerblue' => '1E90FF',
		'firebrick' => 'B22222',
		'floralwhite' => 'FFFAF0',
		'forestgreen' => '228B22',
		'fuchsia' => 'FF00FF',
		'gainsboro' => 'DCDCDC',
		'ghostwhite' => 'F8F8FF',
		'gold' => 'FFD700',
		'goldenrod' => 'DAA520',
		'gray' => '808080',
		'green' => '008000',
		'greenyellow' => 'ADFF2F',
		'grey' => '808080',
		'honeydew' => 'F0FFF0',
		'hotpink' => 'FF69B4',
		'indianred' => 'CD5C5C',
		'indigo' => '4B0082',
		'ivory' => 'FFFFF0',
		'khaki' => 'F0E68C',
		'lavender' => 'E6E6FA',
		'lavenderblush' => 'FFF0F5',
		'lawngreen' => '7CFC00',
		'lemonchiffon' => 'FFFACD',
		'lightblue' => 'ADD8E6',
		'lightcoral' => 'F08080',
		'lightcyan' => 'E0FFFF',
		'lightgoldenrodyellow' => 'FAFAD2',
		'lightgray' => 'D3D3D3',
		'lightgreen' => '90EE90',
		'lightgrey' => 'D3D3D3',
		'lightpink' => 'FFB6C1',
		'lightsalmon' => 'FFA07A',
		'lightseagreen' => '20B2AA',
		'lightskyblue' => '87CEFA',
		'lightslategray' => '778899',
		'lightslategrey' => '778899',
		'lightsteelblue' => 'B0C4DE',
		'lightyellow' => 'FFFFE0',
		'lime' => '00FF00',
		'limegreen' => '32CD32',
		'linen' => 'FAF0E6',
		'magenta' => 'FF00FF',
		'maroon' => '800000',
		'mediumaquamarine' => '66CDAA',
		'mediumblue' => '0000CD',
		'mediumorchid' => 'BA55D3',
		'mediumpurple' => '9370D0',
		'mediumseagreen' => '3CB371',
		'mediumslateblue' => '7B68EE',
		'mediumspringgreen' => '00FA9A',
		'mediumturquoise' => '48D1CC',
		'mediumvioletred' => 'C71585',
		'midnightblue' => '191970',
		'mintcream' => 'F5FFFA',
		'mistyrose' => 'FFE4E1',
		'moccasin' => 'FFE4B5',
		'navajowhite' => 'FFDEAD',
		'navy' => '000080',
		'oldlace' => 'FDF5E6',
		'olive' => '808000',
		'olivedrab' => '6B8E23',
		'orange' => 'FFA500',
		'orangered' => 'FF4500',
		'orchid' => 'DA70D6',
		'palegoldenrod' => 'EEE8AA',
		'palegreen' => '98FB98',
		'paleturquoise' => 'AFEEEE',
		'palevioletred' => 'DB7093',
		'papayawhip' => 'FFEFD5',
		'peachpuff' => 'FFDAB9',
		'peru' => 'CD853F',
		'pink' => 'FFC0CB',
		'plum' => 'DDA0DD',
		'powderblue' => 'B0E0E6',
		'purple' => '800080',
		'red' => 'FF0000',
		'rosybrown' => 'BC8F8F',
		'royalblue' => '4169E1',
		'saddlebrown' => '8B4513',
		'salmon' => 'FA8072',
		'sandybrown' => 'F4A460',
		'seagreen' => '2E8B57',
		'seashell' => 'FFF5EE',
		'sienna' => 'A0522D',
		'silver' => 'C0C0C0',
		'skyblue' => '87CEEB',
		'slateblue' => '6A5ACD',
		'slategray' => '708090',
		'slategrey' => '708090',
		'snow' => 'FFFAFA',
		'springgreen' => '00FF7F',
		'steelblue' => '4682B4',
		'tan' => 'D2B48C',
		'teal' => '008080',
		'thistle' => 'D8BFD8',
		'tomato' => 'FF6347',
		'turquoise' => '40E0D0',
		'violet' => 'EE82EE',
		'wheat' => 'F5DEB3',
		'white' => 'FFFFFF',
		'whitesmoke' => 'F5F5F5',
		'yellow' => 'FFFF00',
		'yellowgreen' => '9ACD32'
	);

	$color_name = strtolower($color_name);
	$color_name = trim(str_replace('!important', '', $color_name));

	if (isset($colors[$color_name])) {
		return ('#' . $colors[$color_name]);
	} else {
		return ($color_name);
	}
}

/**
 * check background style for color
 */
function ac_check_color_match2($background_rule)
{

	// standard 147 HTML color names
	$colors  =  array(
		'aliceblue' => 'F0F8FF',
		'antiquewhite' => 'FAEBD7',
		'aqua' => '00FFFF',
		'aquamarine' => '7FFFD4',
		'azure' => 'F0FFFF',
		'beige' => 'F5F5DC',
		'bisque' => 'FFE4C4',
		'black' => '000000',
		'blanchedalmond ' => 'FFEBCD',
		'blue' => '0000FF',
		'blueviolet' => '8A2BE2',
		'brown' => 'A52A2A',
		'burlywood' => 'DEB887',
		'cadetblue' => '5F9EA0',
		'chartreuse' => '7FFF00',
		'chocolate' => 'D2691E',
		'coral' => 'FF7F50',
		'cornflowerblue' => '6495ED',
		'cornsilk' => 'FFF8DC',
		'crimson' => 'DC143C',
		'cyan' => '00FFFF',
		'darkblue' => '00008B',
		'darkcyan' => '008B8B',
		'darkgoldenrod' => 'B8860B',
		'darkgray' => 'A9A9A9',
		'darkgreen' => '006400',
		'darkgrey' => 'A9A9A9',
		'darkkhaki' => 'BDB76B',
		'darkmagenta' => '8B008B',
		'darkolivegreen' => '556B2F',
		'darkorange' => 'FF8C00',
		'darkorchid' => '9932CC',
		'darkred' => '8B0000',
		'darksalmon' => 'E9967A',
		'darkseagreen' => '8FBC8F',
		'darkslateblue' => '483D8B',
		'darkslategray' => '2F4F4F',
		'darkslategrey' => '2F4F4F',
		'darkturquoise' => '00CED1',
		'darkviolet' => '9400D3',
		'deeppink' => 'FF1493',
		'deepskyblue' => '00BFFF',
		'dimgray' => '696969',
		'dimgrey' => '696969',
		'dodgerblue' => '1E90FF',
		'firebrick' => 'B22222',
		'floralwhite' => 'FFFAF0',
		'forestgreen' => '228B22',
		'fuchsia' => 'FF00FF',
		'gainsboro' => 'DCDCDC',
		'ghostwhite' => 'F8F8FF',
		'gold' => 'FFD700',
		'goldenrod' => 'DAA520',
		'gray' => '808080',
		'green' => '008000',
		'greenyellow' => 'ADFF2F',
		'grey' => '808080',
		'honeydew' => 'F0FFF0',
		'hotpink' => 'FF69B4',
		'indianred' => 'CD5C5C',
		'indigo' => '4B0082',
		'ivory' => 'FFFFF0',
		'khaki' => 'F0E68C',
		'lavender' => 'E6E6FA',
		'lavenderblush' => 'FFF0F5',
		'lawngreen' => '7CFC00',
		'lemonchiffon' => 'FFFACD',
		'lightblue' => 'ADD8E6',
		'lightcoral' => 'F08080',
		'lightcyan' => 'E0FFFF',
		'lightgoldenrodyellow' => 'FAFAD2',
		'lightgray' => 'D3D3D3',
		'lightgreen' => '90EE90',
		'lightgrey' => 'D3D3D3',
		'lightpink' => 'FFB6C1',
		'lightsalmon' => 'FFA07A',
		'lightseagreen' => '20B2AA',
		'lightskyblue' => '87CEFA',
		'lightslategray' => '778899',
		'lightslategrey' => '778899',
		'lightsteelblue' => 'B0C4DE',
		'lightyellow' => 'FFFFE0',
		'lime' => '00FF00',
		'limegreen' => '32CD32',
		'linen' => 'FAF0E6',
		'magenta' => 'FF00FF',
		'maroon' => '800000',
		'mediumaquamarine' => '66CDAA',
		'mediumblue' => '0000CD',
		'mediumorchid' => 'BA55D3',
		'mediumpurple' => '9370D0',
		'mediumseagreen' => '3CB371',
		'mediumslateblue' => '7B68EE',
		'mediumspringgreen' => '00FA9A',
		'mediumturquoise' => '48D1CC',
		'mediumvioletred' => 'C71585',
		'midnightblue' => '191970',
		'mintcream' => 'F5FFFA',
		'mistyrose' => 'FFE4E1',
		'moccasin' => 'FFE4B5',
		'navajowhite' => 'FFDEAD',
		'navy' => '000080',
		'oldlace' => 'FDF5E6',
		'olive' => '808000',
		'olivedrab' => '6B8E23',
		'orange' => 'FFA500',
		'orangered' => 'FF4500',
		'orchid' => 'DA70D6',
		'palegoldenrod' => 'EEE8AA',
		'palegreen' => '98FB98',
		'paleturquoise' => 'AFEEEE',
		'palevioletred' => 'DB7093',
		'papayawhip' => 'FFEFD5',
		'peachpuff' => 'FFDAB9',
		'peru' => 'CD853F',
		'pink' => 'FFC0CB',
		'plum' => 'DDA0DD',
		'powderblue' => 'B0E0E6',
		'purple' => '800080',
		'red' => 'FF0000',
		'rosybrown' => 'BC8F8F',
		'royalblue' => '4169E1',
		'saddlebrown' => '8B4513',
		'salmon' => 'FA8072',
		'sandybrown' => 'F4A460',
		'seagreen' => '2E8B57',
		'seashell' => 'FFF5EE',
		'sienna' => 'A0522D',
		'silver' => 'C0C0C0',
		'skyblue' => '87CEEB',
		'slateblue' => '6A5ACD',
		'slategray' => '708090',
		'slategrey' => '708090',
		'snow' => 'FFFAFA',
		'springgreen' => '00FF7F',
		'steelblue' => '4682B4',
		'tan' => 'D2B48C',
		'teal' => '008080',
		'thistle' => 'D8BFD8',
		'tomato' => 'FF6347',
		'turquoise' => '40E0D0',
		'violet' => 'EE82EE',
		'wheat' => 'F5DEB3',
		'white' => 'FFFFFF',
		'whitesmoke' => 'F5F5F5',
		'yellow' => 'FFFF00',
		'yellowgreen' => '9ACD32'
	);

	$background_rule = strtolower($background_rule);
	$background_rule = trim(str_replace('!important', '', $background_rule));

	$rules = explode(' ', $background_rule);

	foreach ($rules as $key => $value) {

		if (array_key_exists($value, $colors)) {
			return $colors[$value];
		}

		if (preg_match('/(rgb\(\s*\d{1,3},\s*\d{1,3},\s*\d{1,3}\)|\#[\w]{3,6})/i', $value,  $matches, PREG_OFFSET_CAPTURE)) {
			return $matches[1][0];
		}
	}
	return "";
}
