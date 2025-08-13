<?php
/**
 * EmptyHeadingTag Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * EmptyHeadingTag Rule class.
 */
class EmptyHeadingTagRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Empty Heading Tag', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1957',
			'slug'                  => 'empty_heading_tag',
			'rule_type'             => 'error',
			'summary'               => sprintf(
			// translators: %s represents any heading element like <code>&lt;h1&gt;&lt;/h1&gt;</code>, <code>&lt;h2&gt;&lt;/h2&gt;</code>, etc.
				esc_html__( 'This is an empty %s heading that doesn\'t contain any content.', 'accessibility-checker' ),
				'<code>&lt;h#&gt;&lt;/h#&gt;</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %s represents any heading element like <code>&lt;h1&gt;&lt;/h1&gt;</code>, <code>&lt;h2&gt;&lt;/h2&gt;</code>, etc.
				esc_html__( 'These are empty %s headings that don\'t contain any content.', 'accessibility-checker' ),
				'<code>&lt;h#&gt;&lt;/h#&gt;</code>'
			),
			'why_it_matters'        => esc_html__( 'Headings help structure content and provide important navigation points for screen reader users. An empty heading communicates no useful information and may cause confusion or disorientation when navigating by headings.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Add meaningful content inside the heading tag to describe the section that follows. If the heading is not needed, remove it entirely to avoid misleading assistive technologies.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'MDN: HTML heading elements', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/Heading_Elements',
				],
				[
					'text' => __( 'W3C: Techniques for WCAG 2.1 – H42: Using h1–h6 to identify headings', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H42',
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
		];
	}
}
