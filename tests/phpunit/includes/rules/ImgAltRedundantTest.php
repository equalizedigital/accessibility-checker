<?php
/**
 * Tests for the img_alt_redundant rule.
 *
 * @package Accessibility_Checker
 */

/**
 * Test cases to verify behavior of the img_alt_redundant rule.
 *
 * @group rules
 */
class ImgAltRedundantTest extends WP_UnitTestCase {

	/**
	 * Test the rule catches redundant alt attributes on images.
	 */
	public function test_does_not_flag_duplicate_alt_when_alt_is_not_present() {
		$html   = '<img src="image.jpg">';
		$dom    = str_get_html( $html );
		$errors = edac_rule_img_alt_redundant( array( 'html' => $dom ), null );
		$this->assertEmpty( $errors );
	}

	/**
	 * Test the rule doesn't trigger with an empty string for alt.
	 *
	 * Other rules are responsible for checking for empty alt attributes.
	 */
	public function test_idoes_not_flag_duplicate_alt_when_alt_is_empty_string() {
		$html   = '<img src="image.jpg" alt="">';
		$dom    = str_get_html( $html );
		$errors = edac_rule_img_alt_redundant( array( 'html' => $dom ), null );
		$this->assertEmpty( $errors );
	}

	/**
	 * Test the rule doesn't flag redundant alt when alt and title are both empty strings.
	 *
	 * Other rules are responsible for checking for empty alt attributes.
	 */
	public function test_does_not_flag_duplicate_alt_when_alt_and_title_are_empty_strings() {
		$html   = '<img src="image.jpg" alt="" title="">';
		$dom    = str_get_html( $html );
		$errors = edac_rule_img_alt_redundant( array( 'html' => $dom ), null );
		$this->assertEmpty( $errors );
	}

	/**
	 * Test the rule catches redundant alt attributes on images.
	 */
	public function test_flags_images_where_alt_and_title_are_the_same() {
		$html   = '<img src="image.jpg" alt="test" title="test">';
		$dom    = str_get_html( $html );
		$errors = edac_rule_img_alt_redundant( array( 'html' => $dom ), null );
		$this->assertNotEmpty( $errors );
	}

	/**
	 * Test the rule doesn't flag redundant alt when alt and title are different.
	 */
	public function test_img_alt_redundant_rule_does_not_flag_duplicate_alt_when_alt_and_title_are_different() {
		$html   = '<img src="image.jpg" alt="test" title="different">';
		$dom    = str_get_html( $html );
		$errors = edac_rule_img_alt_redundant( array( 'html' => $dom ), null );
		$this->assertEmpty( $errors );
	}
}
