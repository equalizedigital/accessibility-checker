<?php
/**
 * AriaHidden Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * AriaHidden Rule class.
 */
class AriaHiddenRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'ARIA Hidden', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1979',
			'slug'                  => 'aria_hidden',
			'rule_type'             => 'warning',
			'summary'               => sprintf(
			// translators: %s is <code>aria-hidden="true"</code.
				esc_html__( 'This element uses %s, which hides it from screen readers.', 'accessibility-checker' ),
				'<code>aria-hidden="true"</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %s is <code>aria-hidden="true"</code.
				esc_html__( 'These elements use %s, which hides them from screen readers.', 'accessibility-checker' ),
				'<code>aria-hidden="true"</code>'
			),
			'why_it_matters'        => esc_html__( 'The aria-hidden attribute is used to hide content from assistive technologies. While this is useful for decorative or redundant elements, it can cause accessibility issues if applied to important content that screen reader users need to access.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %s is <code>aria-hidden="true"</code.
				esc_html__( 'Check whether the element should truly be hidden from screen reader users. If it contains important content or functionality, remove %s. If it\'s decorative or redundant, leave the element alone and dismiss this warning using the "Ignore" feature in Accessibility Checker.', 'accessibility-checker' ),
				'<code>aria-hidden="true"</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C: ARIA Authoring Practices - aria-hidden', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/ARIA/apg/practices/hiding/',
				],
				[
					'text' => __( 'MDN Web Docs: aria-hidden - Accessibility Attribute', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Attributes/aria-hidden',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.3.1',
			'severity'              => 3, // Medium.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
			],
			'combines'              => [
				'aria_hidden_validation',
			],
		];
	}
}
