<?php
/**
 * IframeMissingTitle Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * IframeMissingTitle Rule class.
 */
class IframeMissingTitleRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'iframe Missing Title', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1953',
			'slug'                  => 'iframe_missing_title',
			'rule_type'             => 'error',
			'summary'               => sprintf(
			// translators: %1$s is <code>&lt;iframe&gt;</code.
				esc_html__( 'This %1$s does not have a descriptive title attribute.', 'accessibility-checker' ),
				'<code>&lt;iframe&gt;</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %1$s is <code>&lt;iframe&gt;</code.
				esc_html__( 'These %1$s do not have descriptive title attributes.', 'accessibility-checker' ),
				'<code>&lt;iframe&gt;</code>'
			),
			'why_it_matters'        => esc_html__( 'Screen readers rely on the title attribute of an iframe to describe its purpose or content. Without a title, users may not understand what the embedded content is, making the page harder to navigate and use.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %1$s is <code>&lt;iframe&gt;</code.
				esc_html__( 'Add a title attribute to the %1$s tag that accurately describes the content or function of the embedded frame. If the iframe is added by a plugin and the title cannot be edited directly, check the plugin settings for an accessibility option, or contact the developer for support. Consider switching to a WordPress core embed instead.', 'accessibility-checker' ),
				'<code>&lt;iframe&gt;</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C: Techniques for WCAG 2.1 - H64: Using the title attribute of the iframe element', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H64',
				],
				[
					'text' => __( 'MDN Web Docs: <iframe>', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/iframe',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '4.1.2',
			'severity'              => 2, // High.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::MOBILITY,
			],
			'combines'              => [
				'frame-title',
			],
		];
	}
}
