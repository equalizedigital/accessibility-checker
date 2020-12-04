<?php

function edac_rule_img_animated_gif($content, $post)
{
	$dom = $content['the_content_html'];
	$errors = [];

	$gifs = $dom->find('img[src$=gif]');
	if ($gifs) {
		foreach ($gifs as $gif) {

			if(ac_img_gif_is_animated($gif->getAttribute('src')) == false) continue;

			$errors[] = $gif->outertext;
		}
	}
	return $errors;
}

function ac_img_gif_is_animated($filename)
{
	if (!($fh = @fopen($filename, 'rb')))
		return false;
	$count = 0;
	//an animated gif contains multiple "frames", with each frame having a
	//header made up of:
	// * a static 4-byte sequence (\x00\x21\xF9\x04)
	// * 4 variable bytes
	// * a static 2-byte sequence (\x00\x2C) (some variants may use \x00\x21 ?)

	// We read through the file til we reach the end of the file, or we've found
	// at least 2 frame headers
	$chunk = false;
	while (!feof($fh) && $count < 2) {
		//add the last 20 characters from the previous string, to make sure the searched pattern is not split.
		$chunk = ($chunk ? substr($chunk, -20) : "") . fread($fh, 1024 * 100); //read 100kb at a time
		$count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
	}

	fclose($fh);
	return $count > 1;
}
