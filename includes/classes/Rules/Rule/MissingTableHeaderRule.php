<?php
/**
 * MissingTableHeader Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * MissingTableHeader Rule class.
 */
class MissingTableHeaderRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Missing Table Header', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1963',
			'slug'                  => 'missing_table_header',
			'rule_type'             => 'error',
			'summary'               => esc_html__( 'This table contains data cells but does not have the required table header cells.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These tables contain data cells but do not have the required table header cells.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Table headers are used by assistive technologies to help users understand the relationships between cells and navigate the data. Without table headers, screen reader users may not be able to determine what the data represents.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>&lt;th&gt;</code>, %2$s is <code>&lt;td&gt;</code>.
				esc_html__( 'Add one or more %1$s elements that describe the columns or rows of data in your table. Each %2$s cell should be associated with a descriptive header to provide proper context for screen reader users.', 'accessibility-checker' ),
				'<code>&lt;th&gt;</code>',
				'<code>&lt;td&gt;</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C: H51 - Using table markup to present tabular information', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H51',
				],
				[
					'text' => __( 'W3C: Tables Tutorial', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/tutorials/tables/',
				],
				[
					'text' => __( 'MDN Web Docs: <table> - Headers and accessibility', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/table#accessibility',
				],
				[
					'text' => __( 'WordPress: Table block documentation', 'accessibility-checker' ),
					'url'  => 'https://wordpress.org/documentation/article/table-block/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.3.1',
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
