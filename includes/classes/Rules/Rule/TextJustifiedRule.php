<?php
/**
 * TextJustified Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * TextJustified Rule class.
 */
class TextJustifiedRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Text Justified', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1980',
			'slug'                  => 'text_justified',
			'rule_type'             => 'warning',
			'summary'               => sprintf(
			// translators: %s is <code>text-align: justify</code.
				esc_html__( 'This page contains long blocks of text that are styled with %s.', 'accessibility-checker' ),
				'<code>text-align: justify</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %s is <code>text-align: justify</code.
				esc_html__( 'These pages contain long blocks of text that are styled with %s.', 'accessibility-checker' ),
				'<code>text-align: justify</code>'
			),
			'why_it_matters'        => esc_html__( 'Justified text can create uneven spacing between words, forming distracting "rivers" of white space that make reading difficult—especially for people with dyslexia, low vision, or cognitive disabilities. Left-aligned text is more predictable and easier to read.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %s is <code>text-align: justify</code.
				esc_html__( 'Remove the %s CSS rule from long blocks of text, especially paragraphs longer than 200 characters. Use left-aligned text instead for better readability and accessibility.', 'accessibility-checker' ),
				'<code>text-align: justify</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C: Making Content Usable for People with Cognitive and Learning Disabilities', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/TR/coga-usable/#use-left-and-right-alignment-consistently',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.4.8',
			'severity'              => 4, // Low.
			'affected_disabilities' => [
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DYSLEXIA,
				AffectedDisabilities::COGNITIVE,
			],
		];
	}
}
