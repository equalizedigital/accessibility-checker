<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Empty Table Header Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_empty_table_header( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	// rule vars.
	$dom            = $content['html'];
	$errors         = [];
	$table_headings = $dom->find( 'th' );

	foreach ( $table_headings as $table_heading ) {
		$th_code = $table_heading->plaintext;
		if ( empty( preg_replace( '/\s+/', '', $th_code ) ) ) {
			$errors[] = $table_heading->outertext;
		}
	}
	return $errors;
}
