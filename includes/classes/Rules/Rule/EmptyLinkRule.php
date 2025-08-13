<?php
/**
 * EmptyLink Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * EmptyLink Rule class.
 */
class EmptyLinkRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Empty Link', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help4108',
			'slug'                  => 'empty_link',
			'rule_type'             => 'error',
			'summary'               => esc_html__( 'This is an empty link that does not contain any meaningful content for assistive technologies.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These are empty links that do not contain any meaningful content for assistive technologies.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Screen reader users rely on link text to understand the purpose or destination of a link. An empty link provides no information, making it difficult or impossible for users to decide whether or not to follow it.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>&lt;a&gt;</code>, %2$s is <code>aria-hidden="true"</code>, %3$s is <code>aria-label</code.
				esc_html__( 'Add descriptive text inside the %1$s element. If the link uses only an icon (e.g., SVG or webfont), hide the icon with %2$s and add a descriptive %3$s to the link or include screen reader-only text to explain where the link goes.', 'accessibility-checker' ),
				'<code>&lt;a&gt;</code>',
				'<code>aria-hidden="true"</code>',
				'<code>aria-label</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C Technique H30: Providing link text that describes the purpose of a link', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H30',
				],
				[
					'text' => __( 'WebAIM: Links and Hypertext', 'accessibility-checker' ),
					'url'  => 'https://webaim.org/techniques/hypertext/',
				],
				[
					'text' => __( 'WordPress: The CSS class screen-reader-text', 'accessibility-checker' ),
					'url'  => 'https://make.wordpress.org/accessibility/handbook/markup/the-css-class-screen-reader-text/',
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
			],
		];
	}
}
