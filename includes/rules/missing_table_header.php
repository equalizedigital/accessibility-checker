<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker plugin file.
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
		return $errors; // Return empty errors array if no tables are found.
	}

	foreach ( $tables as $table ) {
		// Check if the table has proper headers.
		if ( ! edac_has_proper_headers( $table ) ) {
			$errors[] = $table;
		}
	}

	return $errors;
}

/**
 * Check if the table has proper headers
 *
 * @param object $table Object to check.
 * @return bool
 */
function edac_has_proper_headers( $table ) {
	$table_rows  = $table->find( 'tr' );
	$has_headers = false;

	foreach ( $table_rows as $table_row ) {
		// Check for row or column headers in the current row.
		$headers = $table_row->find( 'th' );
		foreach ( $headers as $header ) {
			// Check if the header has a valid scope or contains text.
			$scope = $header->getAttribute( 'scope' );
			$text  = trim( $header->plaintext );

			if ( ! empty( $text ) && ( in_array( $scope, [ 'row', 'col', 'rowgroup', 'colgroup' ], true ) || empty( $scope ) ) ) {
				$has_headers = true;
				break 2; // Exit both loops as headers are valid.
			}
		}
	}

	return $has_headers;
}
