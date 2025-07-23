<?php
/**
 * LinkNonHtmlFile Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * LinkNonHtmlFile Rule class.
 */
class LinkNonHtmlFileRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Link to Non-HTML File', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1973',
			'slug'                  => 'link_non_html_file',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This element is a link to a non-HTML document format with one of the following file extensions: .rtf, .wpd, .ods, .odt, .odp, .sxw, .sxc, .sxd, .sxi, .pages, or .key', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These elements are links to non-HTML document formats with one of the following file extensions: .rtf, .wpd, .ods, .odt, .odp, .sxw, .sxc, .sxd, .sxi, .pages, or .key', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Non-HTML document formats may not be fully accessible to assistive technologies unless tested and remediated. Users should be warned when a link opens a downloadable file rather than a standard web page.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Ensure the linked document is accessible. When you know it is accessible, dismiss this warning using the "Ignore" feature in Accessibility Checker. Include the file extension in the visible link text (e.g., “Meeting Notes (ODT)”) so users know what to expect (enable the \'Add File Size & Type To Links\' fix in Accessibility Checker settings to do this site-wide). If the document is embedded, provide a direct download link as well. If making the document accessible is difficult, consider putting the content on a web page instead.', 'accessibility-checker' ),
			'references'            => [],
			'ruleset'               => 'js',
			'wcag'                  => '0.3',
			'severity'              => 3, // Medium.
			'affected_disabilities' => [
				AffectedDisabilities::BLIND,
				AffectedDisabilities::LOW_VISION,
				AffectedDisabilities::DEAFBLIND,
				AffectedDisabilities::COGNITIVE,
				AffectedDisabilities::MOBILITY,
				AffectedDisabilities::COLORBLIND,
			],
		];
	}
}
