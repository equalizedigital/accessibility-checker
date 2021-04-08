<?php

/**
 * Check for ambiguous text on links
 *
 * @param string $content
 * @param array $post
 * @return array
 * 
 * Logic order: aria-label, aria-labelledby, plain text, img alt
 * 
 * aria-label:
 * <a href="link.html" aria-label="Read more">Read More</a>
 * 
 * aria-labelledby:
 * <a href="link.html" aria-labelledby="my-label">Read More</a>
 * <div id="my-label">Read More</div>
 * 
 * aria-labelledby multiple labels:
 * <a href="link.html" aria-labelledby="my-label-one my-label-two">Read More</a>
 * <div id="my-label-one">Read</div>
 * <div id="my-label-two">More</div>
 * 
 * plain text:
 * <a href="link.html">Read More</a>
 * 
 * img alt:
 * <a href="link.html"><img src="image.jpg" alt="Read More"></a>
 */
function edac_rule_link_ambiguous_text($content, $post){

	// rule vars
	$dom = $content['html'];
	$errors = [];

	// get and loop through anchor links
	$as = $dom->find('a');
	foreach ($as as $a){

		$error = false;
		$error_code = $a->outertext;
		
		// check aria-label
		if($a->hasAttribute('aria-label')){

			$text = $a->getAttribute('aria-label');
			$error = edac_check_ambiguous_phrase($text);

		// check aria-labelledby
		}elseif($a->hasAttribute('aria-labelledby')){
			
			// get aria-labelledby and explode into array since aria-labelledby allows for multiple element ids
			$label_string = $a->getAttribute('aria-labelledby');
			$labels = explode( ' ', $label_string );
			$label_text = [];
			
			if($labels){
				foreach( $labels as $label ) {

					// if element has text push into array
					$element = $dom->find( '#' . $label, 0 );
					if($element->plaintext){
						$label_text[] = $element->plaintext;
					}
				}

				// implode array and check
				if($label_text){
					$text = implode(' ',$label_text);
					$error = edac_check_ambiguous_phrase($text);
					if($error){
						$error_code = $error_code.' Label Text: '.$text;
					}
				}
			}
		
		// check plain text
		}elseif($a->plaintext){

			$text = $a->plaintext;
			$error = edac_check_ambiguous_phrase($text);

		// check image alt text
		}else{
			$images = $a->find('img');
			foreach ($images as $image){
				$alt = $image->getAttribute('alt');
				$error = edac_check_ambiguous_phrase($alt);
				if($error == true){
					break;
				}
			}
		}

		// push error code into array
		if($error){
			$errors[] = $error_code;
		}

	}
	return $errors;
}

/**
 * Check for ambiguous phrase
 *
 * @param string $text
 * @return bool
 */
function edac_check_ambiguous_phrase($text){

	$text = strtolower($text);

	// phrases
	$ambiguous_phrases = [
		__('click here','edac'),
		__('here','edac'),
		__('go here','edac'),
		__('more','edac'),
		__('more...','edac'),
		__('details','edac'),
		__('more details','edac'),
		__('link','edac'),
		__('this page','edac'),
		__('continue','edac'),
		__('continue reading','edac'),
		__('read more','edac'),
		__('open','edac'),
		__('download','edac'),
		__('button','edac'),
		__('keep reading','edac'),
	];

	// check if text contains phrase
	if(strpos($text,__('click here','edac')) == true || strpos($text,__('click','edac')) == true){
		return true;
	}

	// check if text is equal to
	foreach ($ambiguous_phrases as $ambiguous_phrase) {
		if($text == $ambiguous_phrase){
			return true;
			break;
		}
	}

	return false;

}