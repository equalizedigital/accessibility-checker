<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Link MS Office File Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_link_ms_office_file( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom             = $content['html'];
	$file_extensions = [ '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.pps', '.ppsx' ];
	$errors          = [];

	$as = $dom->find( 'a' );
	foreach ( $as as $a ) {
		// Get the href attribute of the anchor tag.
		$href = $a->getAttribute( 'href' );
		if ( $href ) {
			// Remove any query or fragment from the URL.
			$url_components = wp_parse_url( $href );
			$clean_path     = $url_components['path'] ?? '';

			// Check if cleaned URL contains any file extension.
			foreach ( $file_extensions as $file_extension ) {
				// Check if the cleaned path ends with a file extension.
				if ( substr( strtolower( $clean_path ), -strlen( $file_extension ) ) === $file_extension ) {
					// If match found, store the outer HTML of the anchor tag.
					$link_code = $a->outertext;
					$errors[]  = $link_code;
					// No need to check other extensions for this link.
					break;
				}
			}
		}
	}
	return $errors;
}
