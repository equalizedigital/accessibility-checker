<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * Accessibility Checker pluign file.
 *
 * @package Accessibility_Checker
 */

/**
 * Aria Hidden
 *
 * @param array  $content Array of content to check.
 * @param object $post Object to check.
 * @return array
 */
function edac_rule_aria_hidden( $content, $post ) { // phpcs:ignore -- $post is reserved for future use or for compliance with a specific interface.

	$dom      = $content['html'];
	$errors   = array();
	$elements = $dom->find( '[aria-hidden="true"]' );

	$attributes_that_make_this_valid_hidden_use = array(
		'class' => 'wp-block-spacer',
		'role'  => 'presentation',
	);

	if ( $elements ) {
		foreach ( $elements as $element ) {

			foreach ( $attributes_that_make_this_valid_hidden_use as $attribute => $value ) {
				if ( stristr( $element->getAttribute( $attribute ), $value ) ) {
					continue 2;
				}
			}

			$parent_node = $element->parent();
			if ( $parent_node && edac_rule_aria_hidden_valid_parentnode_condition_check( $parent_node, $element ) ) {
				continue;
			}

			$errors[] = $element;
		}
	}

	return $errors;
}

/**
 * Check if the parent has a valid situation to avoid flagging aria-hidden="true" warning.
 *
 * @param object $parent_node  The parent element.
 * @param object $element The element.
 *
 * @return bool
 */
function edac_rule_aria_hidden_valid_parentnode_condition_check( object $parent_node, object $element ): bool {
	// bail early if we don't have a parent node or an element node.
	if ( ! $parent_node || ! $element ) {
		return false;
	}

	// parent node with a non-empty aria-label makes the aria-hidden="true" likely valid.
	if ( ! empty( $parent_node->getAttribute( 'aria-label' ) ) ) {
		return true;
	}

	return false;
}
