<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- underscore is for valid function name.
/**
 * A helper class to generate dom objects that match the shape of the
 * object in the plugin that is used when running rules.
 *
 * @package Accessibility_Checker
 */

/**
 * Gets dom objects through EDAC_Dom that implements the same interface SimpleHTMLDom.
 */
trait GetDomHelperTrait {

	/**
	 * Wrapper to generate dom objects that match the shape of the object in the plugin.
	 *
	 * @param string $html_string HTML string.
	 * @return EDAC_Dom
	 */
	public function get_DOM( string $html_string = '' ): EDAC_Dom { // phpcs:ignore WordPress.NamingConventions -- This is a helper function.
		return new EDAC_Dom(
			$html_string,
			true,
			true,
			DEFAULT_TARGET_CHARSET,
			true,
			DEFAULT_BR_TEXT,
			DEFAULT_SPAN_TEXT
		);
	}
}
