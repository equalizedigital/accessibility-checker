<?php
/**
 * ColorContrastFailure Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * ColorContrastFailure Rule class.
 */
class ColorContrastFailureRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Insufficient Color Contrast', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1983',
			'slug'                  => 'color_contrast_failure',
			'rule_type'             => 'error',
			'summary'               => esc_html__( 'This element has foreground and background colors that do not meet the minimum contrast ratio for Level AA conformance.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These elements have foreground and background colors that do not meet the minimum contrast ratio for Level AA conformance.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Insufficient color contrast makes text and interactive elements difficult or impossible to read for users with low vision or color blindness. Ensuring adequate contrast helps all users access your content clearly.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Adjust the foreground and background colors of the flagged elements to ensure a contrast ratio of at least 4.5:1 for normal text. Use a contrast checker to confirm that your color combinations meet this requirement.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'Brand Color Contrast Grid', 'accessibility-checker' ),
					'url'  => 'https://contrast-grid.equalizedigital.com/',
				],
				[
					'text' => __( 'WebAIM Contrast Checker', 'accessibility-checker' ),
					'url'  => 'https://webaim.org/resources/contrastchecker/',
				],
				[
					'text' => __( 'TPGI Colour Contrast Analyser', 'accessibility-checker' ),
					'url'  => 'https://www.tpgi.com/color-contrast-checker/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.4.3',
			'severity'              => 2, // High.
			'affected_disabilities' => [
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::COLORBLIND,
			],
		];
	}
}
