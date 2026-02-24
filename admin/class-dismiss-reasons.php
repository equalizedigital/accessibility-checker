<?php
/**
 * Class file for dismiss reasons constants.
 *
 * Provides the canonical list of dismiss reasons used across
 * the sidebar, issue modal, and classic metabox.
 *
 * @package Accessibility_Checker
 */

namespace EqualizeDigital\AccessibilityChecker\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that defines dismiss reason options.
 */
class Dismiss_Reasons {

	/**
	 * Get the dismiss reasons with translatable labels and descriptions.
	 *
	 * @return array<string, array{label: string, description: string}>
	 */
	public static function get_reasons(): array {
		return [
			'false_positive' => [
				'label'       => __( 'False positive', 'accessibility-checker' ),
				'description' => __( 'The scanner flagged this, but it does not apply to this content.', 'accessibility-checker' ),
			],
			'remediated'     => [
				'label'       => __( 'Remediated', 'accessibility-checker' ),
				'description' => __( 'The issue has been fixed, but the page has not been rescanned yet.', 'accessibility-checker' ),
			],
			'accessible'     => [
				'label'       => __( 'Confirmed accessible', 'accessibility-checker' ),
				'description' => __( 'Reviewed and verified to meet accessibility requirements.', 'accessibility-checker' ),
			],
		];
	}
}
