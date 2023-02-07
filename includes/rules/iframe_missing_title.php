<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * IFrame Missing Title Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_iframe_missing_title( $content, $post ) {

	$dom = $content['html'];
	$iframe_tags = $dom->find( 'iframe' );
	$errors = array();

	foreach ( $iframe_tags as $iframe ) {
		if ( isset( $iframe ) && $iframe->getAttribute( 'title' ) == '' && $iframe->getAttribute( 'aria-label' ) == '' ) {

			$iframecode = htmlspecialchars( $iframe->outertext );

			$errors[] = $iframecode;

		}
	}
	return $errors;
}
