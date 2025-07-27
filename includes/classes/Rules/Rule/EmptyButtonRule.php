<?php
/**
 * EmptyButton Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * EmptyButton Rule class.
 */
class EmptyButtonRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Empty Button', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1960',
			'slug'                  => 'empty_button',
			'rule_type'             => 'error',
			'summary'               => sprintf(
			// translators: %1$s is <code>&lt;button&gt;</code>, %2$s is <code>&lt;input&gt;</code>.
				esc_html__( 'This element is a %1$s or %2$s with no accessible label or text.', 'accessibility-checker' ),
				'<code>&lt;button&gt;</code>',
				'<code>&lt;input&gt;</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %1$s is <code>&lt;button&gt;</code>, %2$s is <code>&lt;input&gt;</code>.
				esc_html__( 'These elements are %1$s or %2$s with no accessible label or text.', 'accessibility-checker' ),
				'<code>&lt;button&gt;</code>',
				'<code>&lt;input&gt;</code>'
			),
			'why_it_matters'        => esc_html__( 'Buttons must clearly describe the action they perform. An empty button provides no information to screen readers or keyboard users, making it impossible to understand what clicking the button will do. This creates a barrier for people relying on assistive technologies.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>&lt;button&gt;</code>, %2$s is <code>value</code>, %3$s is <code>alt</code>, %4$s is <code>aria-hidden="true"</code>, %5$s is <code>aria-label</code>.
				esc_html__( 'Add visible text inside the %1$s element, set a %2$s attribute on an input button, or include an %3$s attribute on a button image. If the button uses only an icon (e.g., SVG or webfont), hide the icon with %4$s and add a descriptive %5$s to the button or include screen reader-only text.  Each button must have a label that clearly describes its purpose or action.', 'accessibility-checker' ),
				'<code>&lt;button&gt;</code>',
				'<code>value</code>',
				'<code>alt</code>',
				'<code>aria-hidden="true"</code>',
				'<code>aria-label</code>'
			),
			'references'            => [
				[
					'text' => __( 'MDN Web Docs: <button>', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button',
				],
				[
					'text' => __( 'W3C Technique H44: Using the button element', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H44',
				],
				[
					'text' => __( 'WordPress: The CSS class screen-reader-text', 'accessibility-checker' ),
					'url'  => 'https://make.wordpress.org/accessibility/handbook/markup/the-css-class-screen-reader-text/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '4.1.2',
			'severity'              => 1, // Critical.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::COGNITIVE,
			],
		];
	}
}
