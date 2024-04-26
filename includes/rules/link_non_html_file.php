<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Link Non-HTML File Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_link_non_html_file( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom             = $content['html'];
	$file_extensions = [ '.rtf', '.wpd', '.ods', '.odt', '.odp', '.sxw', '.sxc', '.sxd', '.sxi', '.pages', '.key' ];
	$errors          = [];

	$as = $dom->find( 'a' );
	foreach ( $as as $a ) {

		if ( $a->getAttribute( 'href' ) ) {
			if ( $file_extensions ) {
				foreach ( $file_extensions as $file_extension ) {
					if ( strpos( strtolower( $a ), $file_extension ) ) {
						$link_code = $a;

						$errors[] = $link_code;
					}
				}
			}
		}
	}
	return $errors;
}
