<?php
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Link PDF Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_link_pdf( $content, $post ) {

	$dom = $content['html'];
	$errors = array();

	$as = $dom->find( 'a' );
	foreach ( $as as $a ) {

		if ( $a->getAttribute( 'href' ) ) {
			if ( strpos( strtolower( $a ), '.pdf' ) ) {
				$link_code = $a;

				$errors[] = $link_code;
			}
		}
	}
	return $errors;
}
