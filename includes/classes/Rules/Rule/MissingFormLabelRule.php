<?php
/**
 * MissingFormLabel Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\{
	AddLabelToUnlabelledFormFieldsFix,
	CommentSearchLabelFix
};

/**
 * MissingFormLabel Rule class.
 */
class MissingFormLabelRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Missing Form Label', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1949',
			'slug'                  => 'missing_form_label',
			'rule_type'             => 'error',
			'summary'               => sprintf(
			// translators: %1$s is <code>&lt;input&gt;</code>, %2$s is <code>&lt;textarea&gt;</code>, %3$s is <code>&lt;select&gt;</code.
				esc_html__( 'This element is an %1$s, %2$s, or %3$s form field without an associated label.', 'accessibility-checker' ),
				'<code>&lt;input&gt;</code>',
				'<code>&lt;textarea&gt;</code>',
				'<code>&lt;select&gt;</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %1$s is <code>&lt;input&gt;</code>, %2$s is <code>&lt;textarea&gt;</code>, %3$s is <code>&lt;select&gt;</code.
				esc_html__( 'These elements are %1$s, %2$s, or %3$s form fields without associated labels.', 'accessibility-checker' ),
				'<code>&lt;input&gt;</code>',
				'<code>&lt;textarea&gt;</code>',
				'<code>&lt;select&gt;</code>'
			),
			'why_it_matters'        => esc_html__( 'Form fields must be clearly labeled so that users of screen readers and voice input technologies can understand their purpose. Without labels, users may not know what information to enter or how the form will behave.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>&lt;label&gt;</code>, %2$s is <code>for=""</code.
				esc_html__( 'Add a visible %1$s element that describes the field. Connect it to the corresponding form element using the %2$s attribute, or use aria-label or aria-labelledby if needed. To fix all unlabelled fields automatically, enable the \'Label Form Fields\' fix in Accessibility Checker settings.', 'accessibility-checker' ),
				'<code>&lt;label&gt;</code>',
				'<code>for=""</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C: Labeling Controls', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/tutorials/forms/labels/',
				],
				[
					'text' => __( 'MDN: label element', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/label',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '1.3.1',
			'severity'              => 1, // Critical.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::COGNITIVE,
				AffectedDisabilities::MOBILITY,
			],
			'combines'              => [ 'label' ],
			'fixes'                 => [
				AddLabelToUnlabelledFormFieldsFix::get_slug(),
				CommentSearchLabelFix::get_slug(),
			],
		];
	}
}
