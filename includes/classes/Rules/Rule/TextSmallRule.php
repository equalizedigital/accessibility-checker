<?php
/**
 * TextSmall Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * TextSmall Rule class.
 */
class TextSmallRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Text Too Small', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1975',
			'slug'                  => 'text_small',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This text is smaller than 10 pixels and may be difficult to read.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These text elements are smaller than 10 pixels and may be difficult to read.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Text that is too small can be hard to read, especially for people with low vision. Ensuring a minimum readable size improves overall accessibility and usability, reducing the need for users to zoom.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
				// translators: %s is <code>10px</code>.
				esc_html__( 'Update your styles so that all text is at least %s in size. Use relative units like em or rem when possible to support user preferences and browser scaling.', 'accessibility-checker' ),
				'<code>10px</code>'
			),
			'references'            => [
				[
					'text' => __( 'MDN: font-size CSS property', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/CSS/font-size',
				],
				[
					'text' => __( 'How to Add Custom CSS in WordPress', 'accessibility-checker' ),
					'url'  => 'https://developer.wordpress.org/advanced-administration/wordpress/css/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.4.4',
			'severity'              => 3, // Medium.
			'affected_disabilities' => [
				AffectedDisabilities::LOW_VISION,
			],
		];
	}
}
