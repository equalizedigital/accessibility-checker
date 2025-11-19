<?php
/**
 * UnderlinedText Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * UnderlinedText Rule class.
 */
class UnderlinedTextRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Underlined Text', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1978',
			'slug'                  => 'underlined_text',
			'rule_type'             => 'warning',
			'summary'               => sprintf(
			// translators: %1$s is <code>&lt;u&gt;</code>, %2$s is <code>text-decoration: underline;</code.
				esc_html__( 'This element contains underlined text using the %1$s tag or %2$s CSS styles and does not appear to be a link.', 'accessibility-checker' ),
				'<code>&lt;u&gt;</code>',
				'<code>text-decoration: underline;</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %1$s is <code>&lt;u&gt;</code>, %2$s is <code>text-decoration: underline;</code.
				esc_html__( 'These elements contain underlined text using the %1$s tag or %2$s CSS styles and do not appear to be links.', 'accessibility-checker' ),
				'<code>&lt;u&gt;</code>',
				'<code>text-decoration: underline;</code>'
			),
			'why_it_matters'        => esc_html__( 'Underlined text is commonly associated with links. When non-link text is underlined, it can be confusing for users, especially those with cognitive disabilities or those relying on visual cues to identify links.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Remove the underline tag or CSS underline style from the text. If you want to emphasize text, consider using bold, italic, or color styling instead. If this text is part of a functional element like a link or button, keep the underline styling and dismiss this warning using the "Ignore" feature in Accessibility Checker.', 'accessibility-checker' ),
			'references'            => [],
			'ruleset'               => 'js',
			'wcag'                  => '1.3.1',
			'severity'              => 4, // Low.
			'affected_disabilities' => [
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::COGNITIVE,
			],
		];
	}
}
