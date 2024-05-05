<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Empty Heading Tab Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_empty_heading_tag( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	// rule vars.
	$dom    = $content['html'];
	$errors = [];

	// Loop heading 1 - 6.
	for ( $i = 1; $i <= 6; $i++ ) {

		$headings = $dom->find( 'h' . $i );
		foreach ( $headings as $heading ) {

			$heading_code = $heading->outertext;

			if ( ( 
					empty( str_ireplace( [ ' ', '&nbsp;', '-', '_' ], '', htmlentities( trim( $heading->plaintext ) ) ) ) || 
					empty( str_ireplace( [ ' ', '&nbsp;', '-', '_' ], '', trim( $heading->plaintext ) ) )
				) && 
					empty( $heading->getAttribute( 'aria-label' ) ) &&
					! preg_match(
						'#<img(\S|\s)*alt=(\'|\")(\w|\s)(\w|\s|\p{P}|\(|\)|\p{Sm}|~|`|’|\^|\$)+(\'|\")#',
						$heading_code 
					) 
			) {

				$errors[] = $heading_code;

			}
		}
	}
	return $errors;
}
