<?php

function edac_rule_text_justified($content, $post){

	// rule vars
	$content = $content['the_content'];
	$fontsearchpatterns = array();
	$fontsearchpatterns[] = "|(text-)?align:\s?justify|i";
	$errors = [];

	foreach($fontsearchpatterns as $key => $pattern){

		if(preg_match_all($pattern, $content, $matches,PREG_OFFSET_CAPTURE)){
			$matchsize = sizeof($matches[0]);
			
			for($i=0; $i < $matchsize; $i++){
				
				if(isset($matches[0][$i][0]) and $matches[0][$i][0] != ""){
					
					$text_justified_errorcode = htmlspecialchars($matches[0][$i][0]).__(' (char #: ','edac').$matches[0][$i][1].')';

					if($text_justified_errorcode){
						$errors[] = $text_justified_errorcode;
					}

				}

			}

		}

	}
	return $errors;
} 