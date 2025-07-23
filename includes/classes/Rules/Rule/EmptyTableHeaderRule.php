<?php
/**
 * EmptyTableHeader Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * EmptyTableHeader Rule class.
 */
class EmptyTableHeaderRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Empty Table Header', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1963',
			'slug'                  => 'empty_table_header',
			'rule_type'             => 'error',
			'summary'               => sprintf(
			/* translators: %s is <code>&lt;th&gt;</code>. */
				esc_html__( 'This element is a table header cell (%s) with no text content.', 'accessibility-checker' ),
				'<code>&lt;th&gt;</code>',
			),
			'summary_plural'        => sprintf(
			/* translators: %s is <code>&lt;th&gt;</code>. */
				esc_html__( 'These elements are table header cells (%s) with no text content.', 'accessibility-checker' ),
				'<code>&lt;th&gt;</code>'
			),
			'why_it_matters'        => esc_html__( 'Table headers provide context for the data in rows and columns. When a table header is empty, screen readers and other assistive technologies cannot convey the meaning of the associated data, making the table difficult or impossible to interpret.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			/* translators: %s is <code>&lt;th&gt;</code>. */
				esc_html__( 'Add descriptive text inside each %s element that explains the purpose of the row or column. Avoid using empty header cells or placeholder text that lacks meaning. If necessary, you can visually hide text in a table header cell with a screen-reader-text class.', 'accessibility-checker' ),
				'<code>&lt;th&gt;</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C: Tables Tutorial', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/tutorials/tables/',
				],
				[
					'text' => __( 'MDN Web Docs: <th>', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/th',
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
