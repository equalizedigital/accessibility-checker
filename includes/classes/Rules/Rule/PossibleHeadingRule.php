<?php
/**
 * PossibleHeading Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * PossibleHeading Rule class.
 */
class PossibleHeadingRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Possible Heading', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1969',
			'slug'                  => 'possible_heading',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This text appears visually styled like a heading but is not marked up with a heading tag.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These text elements appear visually styled like headings but are not marked up with heading tags.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Screen reader users and other assistive technologies rely on heading tags to understand page structure and navigate efficiently. Text that looks like a heading but is not coded as one can make it difficult for screen reader users to navigate the page.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>&lt;h1&gt;</code>, %2$s is <code>&lt;h6&gt;</code.
				esc_html__( 'Review the flagged text and determine if it should be a heading. If it is, change the element to the appropriate %1$s–%2$s tag. If it is not intended as a heading, you can dismiss this warning by using the "Ignore" feature in Accessibility Checker.', 'accessibility-checker' ),
				'<code>&lt;h1&gt;</code>',
				'<code>&lt;h6&gt;</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C: Using HTML headings to identify headings', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H42',
				],
				[
					'text' => __( 'MDN: HTML heading elements', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/Heading_Elements',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.3.1',
			'severity'              => 2, // High.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::COGNITIVE,
			],
		];
	}
}
