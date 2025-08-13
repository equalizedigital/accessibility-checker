<?php
/**
 * MissingTitle Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\AddMissingOrEmptyPageTitleFix;

/**
 * MissingTitle Rule class.
 */
class MissingTitleRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Missing Title', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help4431',
			'slug'                  => 'missing_title',
			'rule_type'             => 'error',
			'summary'               => sprintf(
			// translators: %1$s is <code>&lt;title&gt;</code>, %2$s is <code>og:title</code>.
				esc_html__( 'This element is missing a title tag (%1$s or %2$s). This may happen if the post or page title field is empty or if the theme fails to output a title tag in the document head.', 'accessibility-checker' ),
				'<code>&lt;title&gt;</code>',
				'<code>og:title</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %1$s is <code>&lt;title&gt;</code>, %2$s is <code>og:title</code>.
				esc_html__( 'These elements are missing title tags (%1$s or %2$s). This may happen if post or page title fields are empty or if the theme fails to output title tags in the document head.', 'accessibility-checker' ),
				'<code>&lt;title&gt;</code>',
				'<code>og:title</code>'
			),
			'why_it_matters'        => esc_html__( 'The title element is used by screen readers, search engines, and browser tabs to identify the page. Without a title, users may have difficulty determining the purpose of the page.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Ensure the post or page title field is filled in on the backend. If the title field is not empty and the error persists, check your theme\'s code to make sure it outputs a valid <title> tag and/or og:title meta tag in the document head. To fix this issue site-wide, enable the \'Add Missing Page Title\' fix in Accessibility Checker settings.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'MDN: <title> element', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/title',
				],
			],
			'wcag'                  => '2.4.2',
			'severity'              => 2, // High.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::MOBILITY,
			],
			'ruleset'               => 'js',
			'combines'              => [ 'document-title' ],
			'viewable'              => false,
			'fixes'                 => [
				AddMissingOrEmptyPageTitleFix::get_slug(),
			],
		];
	}
}
