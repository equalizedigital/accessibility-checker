<?php
/**
 * LinkPdf Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * LinkPdf Rule class.
 */
class LinkPdfRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Link to PDF', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1972',
			'slug'                  => 'link_pdf',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This is a link to a PDF document. All linked documents must be manually tested for accessibility.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These are links to PDF documents. All linked documents must be manually tested for accessibility.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'PDFs may not be accessible to all users, especially if they are not tagged correctly or fail to meet WCAG requirements. Accessibility laws require that documents posted on websites meet the same accessibility standards as HTML content. Additionally, users should be warned when a link opens a downloadable file instead of a web page.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Ensure the linked PDF is tested and remediated for accessibility. When you know it is accessible, dismiss this warning using the "Ignore" feature in Accessibility Checker. Include the file extension and size in the link text to inform users (enable the \'Add File Size & Type To Links\' fix in Accessibility Checker settings to do this site-wide). If the document is embedded using a plugin, also provide a direct link to download it. If making the PDF accessible is difficult, consider putting the content on a web page instead.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'Adobe: Create and verify PDF accessibility (Acrobat Pro)', 'accessibility-checker' ),
					'url'  => 'https://helpx.adobe.com/acrobat/using/create-verify-pdf-accessibility.html',
				],
				[
					'text' => __( 'WebAIM: PDF Accessibility', 'accessibility-checker' ),
					'url'  => 'https://webaim.org/techniques/acrobat/',
				],
				[
					'text' => __( 'PDF Accessibility on the Web: Tricks and Traps (Webinar)', 'accessibility-checker' ),
					'url'  => 'https://equalizedigital.com/pdf-accessibility-on-the-web-tricks-and-traps-ricky-onsman/',
				],
				[
					'text' => __( 'InDesign & PDF Accessibility Mistakes and How to Fix Them (Webinar)', 'accessibility-checker' ),
					'url'  => 'https://equalizedigital.com/indesign-pdf-accessibility-colleen-gratzer/',
				],
			],
			'ruleset'               => 'js',
			'wcag'                  => '0.3',
			'severity'              => 2, // High.
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
