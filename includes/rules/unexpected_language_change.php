<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Check for improper language changes against detected language.
 *
 * @param array   $content The content to check.
 * @param WP_Post $post The post object.
 * @return array List of elements with improper 'lang' attributes.
 */
function edac_rule_unexpected_language_change( $content, $post ) {

	// Rule vars.
	$dom = $content['html'];
	$errors = array();

	// Get the default language from WordPress settings (e.g., "en" from "en_US").
	$default_lang = substr( get_locale(), 0, 2 );

	// Find all text nodes.
	$text_elements = $dom->find( 'p' );

	// Loop through the found text elements.
	foreach ( $text_elements as $text_element ) {

		$parent = $text_element->parent();
		$declared_lang = isset( $parent->lang ) ? $parent->lang : '';
		$detected_lang = edac_detect_language( $text_element->innertext );

		// Check if the detected language does not match either the default or declared language.
		if ( $detected_lang !== $default_lang && $declared_lang !== $detected_lang ) {
			$errors[] = $text_element;
		}
	}

	return $errors;
}

/**
 * Detect the language of a given text.
 *
 * @param string $text The text to detect.
 * @return string Detected language or 'unknown' in case of an error.
 */
function edac_detect_language( $text ) {

	// TODO: Get autoloader working.
	/*
	$ld = new TextLanguageDetect\LanguageDetect\TextLanguageDetect();

	try {
		$result = $ld->detectSimple( $text );
		return $result;
	} catch ( TextLanguageDetect\LanguageDetect\TextLanguageDetectException $e ) {
		// Handle exception
		return 'unknown';
	}
	*/
}