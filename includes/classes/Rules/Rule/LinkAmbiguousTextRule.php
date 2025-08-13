<?php
/**
 * LinkAmbiguousText Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * LinkAmbiguousText Rule class.
 */
class LinkAmbiguousTextRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Ambiguous Anchor Text', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1944',
			'slug'                  => 'link_ambiguous_text',
			'rule_type'             => 'error',
			'summary'               => esc_html__( 'This link uses generic or vague anchor text that does not describe its purpose.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These links use generic or vague anchor text that does not describe their purpose.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Screen reader users often browse a list of links out of context. If links use ambiguous phrases like "click here" or "learn more," users will not understand where the link goes or what it does. This can lead to confusion and reduce the usability of your website.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>aria-label</code>.
				esc_html__( 'Revise the anchor text to clearly describe the destination or purpose of the link. For example, instead of "click here," use "download the annual report" or "learn more about us." Additional context can be added to links with an %1$s attribute or a screen-reader-text class.', 'accessibility-checker' ),
				'<code>aria-label</code>'
			),
			'references'            => [
				[
					'text' => __( 'MDN Web Docs: a element – Accessible names', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a#accessibility_concerns',
				],
				[
					'text' => __( 'MDN Web Docs: ARIA: aria-label attribute', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Attributes/aria-label',
				],
				[
					'text' => __( 'Screen Reader Text Format Plugin (for the block editor)', 'accessibility-checker' ),
					'url'  => 'https://wordpress.org/plugins/screen-reader-text-format/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '2.4.4',
			'severity'              => 1, // Critical..
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::COGNITIVE,
				AffectedDisabilities::MOBILITY,
			],
		];
	}
}
