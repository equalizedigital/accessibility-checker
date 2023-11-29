<?php
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
function edac_rule_link_non_html_file( $content, $post ) {

	$dom = $content['html'];
	$file_extensions = array( '.rtf', '.wpd', '.ods', '.odt', '.odp', '.sxw', '.sxc', '.sxd', '.sxi', '.pages', '.key' );
	$errors = array();

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
