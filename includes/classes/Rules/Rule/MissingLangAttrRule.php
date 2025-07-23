<?php
/**
 * MissingLangAttr Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;
use EqualizeDigital\AccessibilityChecker\Fixes\Fix\HTMLLangAndDirFix;

/**
 * MissingLangAttr Rule class.
 */
class MissingLangAttrRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Missing Language Declaration', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help4429',
			'slug'                  => 'missing_lang_attr',
			'rule_type'             => 'error',
			'summary'               => sprintf(
			// translators: %1$s is <code>&lt;html&gt;</code>, %2$s is <code>lang</code>, %3$s is <code>xml:lang</code>.
				esc_html__( 'This element is a top-level %1$s tag that is missing a language attribute such as %2$s or %3$s, or has an empty language attribute.', 'accessibility-checker' ),
				'<code>&lt;html&gt;</code>',
				'<code>lang</code>',
				'<code>xml:lang</code>'
			),
			'summary_plural'        => sprintf(
			// translators: %1$s is <code>&lt;html&gt;</code>, %2$s is <code>lang</code>, %3$s is <code>xml:lang</code>.
				esc_html__( 'These elements are top-level %1$s tags that are missing a language attribute such as %2$s or %3$s, or have an empty language attribute.', 'accessibility-checker' ),
				'<code>&lt;html&gt;</code>',
				'<code>lang</code>',
				'<code>xml:lang</code>'
			),
			'why_it_matters'        => esc_html__( 'Screen readers and other assistive technologies use the language attribute to determine how to pronounce and interpret the content. Without it, the content may be read incorrectly, which can be confusing or misleading for users.', 'accessibility-checker' ),
			'how_to_fix'            => sprintf(
			// translators: %s is an example of a valid lang attribute.
				esc_html__( 'Edit your theme\'s header file to ensure the <html> tag includes a valid lang attribute (e.g., %s). If your theme receives updates, make this change in a child theme to prevent it from being overwritten. To fix this automatically site-wide, enable the \'Add "lang" & "dir" Attributes\' fix in Accessibility Checker settings.', 'accessibility-checker' ),
				'<code>lang="en"</code>'
			),
			'references'            => [
				[
					'text' => __( 'W3C: Language of Page', 'accessibility-checker' ),
					'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H57',
				],
				[
					'text' => __( 'MDN: The HTML lang attribute', 'accessibility-checker' ),
					'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/lang',
				],
			],
			'wcag'                  => '3.1.1',
			'severity'              => 2, // High.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::DEAFBLIND,
			],
			'combines'              => [ 'html-lang-valid', 'html-has-lang' ],
			'ruleset'               => 'js',
			'viewable'              => false,
			'fixes'                 => [
				HTMLLangAndDirFix::get_slug(),
			],
		];
	}
}
