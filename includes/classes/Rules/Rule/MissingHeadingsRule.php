<?php
/**
 * MissingHeadings Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * MissingHeadings Rule class.
 */
class MissingHeadingsRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Missing Subheadings', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1967',
			'slug'                  => 'missing_headings',
			'rule_type'             => 'warning',
			'summary'               => sprintf(
			// translators: %1$s is <code>&lt;h1&gt;</code>, %2$s is <code>&lt;h6&gt;</code>, %3$s is the word count threshold.
				esc_html__( 'This page does not contain any heading elements between %1$s and %2$s in the main content area and has more than %3$s words.', 'accessibility-checker' ),
				'<code>&lt;h1&gt;</code>',
				'<code>&lt;h6&gt;</code>',
				'400'
			),
			'summary_plural'        => sprintf(
			// translators: %1$s is <code>&lt;h1&gt;</code>, %2$s is <code>&lt;h6&gt;</code>, %3$s is '400' or the number defined in the setting.
				esc_html__( 'These pages do not contain any heading elements between %1$s and %2$s in the main content area and have more than %3$s words.', 'accessibility-checker' ),
				'<code>&lt;h1&gt;</code>',
				'<code>&lt;h6&gt;</code>',
				'400'
			),
			'why_it_matters'        => esc_html__( 'Headings provide structure and make content easier for users of all abilities to scan. They also help screen reader users and keyboard-only users navigate the page. Without headings, users must read through content linearly without an easy way to jump between sections.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>&lt;h1&gt;</code.
				esc_html__( 'Add meaningful heading elements throughout your content to organize it into sections. At a minimum, include one %1$s tag as the main page title, and add subheadings where appropriate to break up content into logical parts.', 'accessibility-checker' ),
				'<code>&lt;h1&gt;</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C Tutorial: Headings', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/tutorials/page-structure/headings/',
				],
			],
			'viewable'              => false,
			'ruleset'               => 'js',
			'wcag'                  => '1.3.1',
			'severity'              => 3, // Medium.
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
