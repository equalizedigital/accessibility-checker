<?php
/**
 * EmptyParagraphTag Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * EmptyParagraphTag Rule class.
 */
class EmptyParagraphTagRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Empty Paragraph Tag', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help7870',
			'slug'                  => 'empty_paragraph_tag',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This page contains a <p> tag with no content inside.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These pages contain <p> tags with no content inside.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Empty paragraph tags can be announced by screen readers as blank lines or pauses, which may confuse users or disrupt reading flow. They can also interfere with visual layout or spacing, especially in assistive technology or mobile contexts.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Remove the empty <p> tags from your content. If spacing is needed between sections, use margin, padding, or a visual spacer block instead of inserting blank paragraphs.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'MDN Web Docs: The Paragraph Element - Accessibility', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/p#accessibility',
				],
				[
					'text' => __( 'WordPress Spacer Block', 'accessibility-checker' ),
					'url'  => 'https://wordpress.org/documentation/article/spacer-block/',
				],
				[
					'text' => __( 'MDN Web Docs: CSS Padding', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/CSS/padding',
				],
				[
					'text' => __( 'MDN Web Docs: CSS Margin', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/CSS/margin',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '0.1',
			'severity'              => 4, // Low.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::COGNITIVE,
			],
		];
	}
}
