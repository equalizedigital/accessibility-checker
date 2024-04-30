<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Missing Table Header Check
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_missing_table_header( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom    = $content['html'];
	$errors = [];
	$tables = $dom->find( 'table' );

	if ( ! $tables ) {
		return;
	}
	foreach ( $tables as $table ) {

		if ( ! edac_th_match_td( $table ) ) {
			$errors[] = $table;
		}
	}
	return $errors;
}

/**
 * Check for TH TD matching
 *
 * @param obj $table Object to check.
 * @return int
 */
function edac_th_match_td( $table ) {
	$table_rows   = $table->find( 'tr' );
	$header_count = 0;
	$max_rows     = 0;
	foreach ( $table_rows as $table_row ) {
		if ( 0 === $header_count ) {
			$header_count = count( $table_row->find( 'th' ) );
		}
		$max_rows = max( $max_rows, count( $table_row->find( 'td' ) ) );
	}
	return $max_rows <= $header_count;
}
