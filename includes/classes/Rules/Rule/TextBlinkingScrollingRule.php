<?php
/**
 * TextBlinkingScrolling Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * TextBlinkingScrolling Rule class.
 */
class TextBlinkingScrollingRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Blinking or Scrolling Content', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1965',
			'slug'                  => 'text_blinking_scrolling',
			'rule_type'             => 'error',
			'summary'               => esc_html__( 'This element uses blinking or scrolling effects that may be disruptive for users or cause seizures.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These elements use blinking or scrolling effects that may be disruptive for users or cause seizures.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Blinking or scrolling content can be distracting or trigger seizures in some users, especially those with photosensitive epilepsy or cognitive disabilities. These effects are deprecated and can negatively impact readability and focus.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>&lt;blink&gt;</code>, %2$s is <code>&lt;marquee&gt;</code>, %3$s is <code>text-decoration: blink</code>.
				esc_html__( 'Remove any HTML or CSS that causes blinking or scrolling effects, such as %1$s, %2$s, or %3$s.', 'accessibility-checker' ),
				'<code>&lt;blink&gt;</code>',
				'<code>&lt;marquee&gt;</code>',
				'<code>text-decoration: blink</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C: Techniques for WCAG 2.1 - Avoiding the use of the blink element', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/failures/F47',
				],
				[
					'text' => __( 'MDN: <marquee> element (obsolete)', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/marquee',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '2.2.2',
			'severity'              => 1, // Critical..
			'affected_disabilities' => [
				AffectedDisabilities::SEIZURE,
				AffectedDisabilities::COGNITIVE,
				AffectedDisabilities::ADHD,
			],
			'combines'              => [ 'blink', 'marquee' ],
		];
	}
}
