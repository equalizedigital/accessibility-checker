<?php
/**
 * Example New Rule - demonstrates how to add a new rule to the system.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;

/**
 * Example New Rule class - demonstrates the structure for adding new rules.
 * 
 * To add this rule to the system:
 * 1. Add the class name to RuleRegistry::load_rules() method
 * 2. Or rely on the auto-discovery method RuleRegistry::load_rules_auto()
 */
class ExampleNewRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Example New Rule', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help-new-rule',
			'slug'                  => 'example_new_rule',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This is an example of a new rule added to the system.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These are examples of new rules added to the system.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'This example demonstrates how easy it is to add new accessibility rules to the checker using the class-based system.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'To implement this rule, you would add the actual accessibility checking logic and provide specific remediation steps.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'WCAG Guidelines', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/quickref/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '0.0.0', // Would be a real WCAG reference like '1.1.1'.
			'severity'              => 3, // Medium.
			'affected_disabilities' => [
				esc_html__( 'Example disability impact', 'accessibility-checker' ),
			],
		];
	}
}
