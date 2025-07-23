<?php
/**
 * LinkMsOfficeFile Rule.
 *
 * @package EqualizeDigital\AccessibilityChecker
 */

namespace EqualizeDigital\AccessibilityChecker\Rules\Rule;

use EqualizeDigital\AccessibilityChecker\Rules\RuleInterface;
use EqualizeDigital\AccessibilityChecker\Rules\AffectedDisabilities;

/**
 * LinkMsOfficeFile Rule class.
 */
class LinkMsOfficeFileRule implements RuleInterface {
	/**
	 * Get the rule definition.
	 *
	 * @return array The rule definition array.
	 */
	public static function get_rule(): array {
		return [
			'title'                 => esc_html__( 'Link to MS Office File', 'accessibility-checker' ),
			'info_url'              => 'https://a11ychecker.com/help1970',
			'slug'                  => 'link_ms_office_file',
			'rule_type'             => 'warning',
			'summary'               => esc_html__( 'This is a link to a Microsoft Office file, such as a Word, Excel, or PowerPoint document. All linked documents must be manually tested for accessibility.', 'accessibility-checker' ),
			'summary_plural'        => esc_html__( 'These are links to Microsoft Office files, such as Word, Excel, or PowerPoint documents. All linked documents must be manually tested for accessibility.', 'accessibility-checker' ),
			'why_it_matters'        => esc_html__( 'Documents in Word, Excel, or PowerPoint format may not be accessible to all users, especially if they are not tagged correctly or do not follow accessibility best practices. Accessibility laws require that documents posted on web pages also conform to WCAG. Additionally, users should be warned when a link opens a downloadable file instead of a webpage.', 'accessibility-checker' ),
			'how_to_fix'            => esc_html__( 'Ensure that the linked file is tested and remediated for accessibility. When you know it is accessible, dismiss this warning using the "Ignore" feature in Accessibility Checker. Include the file extension and size in the link text to inform users (enable the \'Add File Size & Type To Links\' fix in Accessibility Checker settings to do this site-wide). If the document is embedded using a plugin, also provide a direct link to download it. If making the document accessible is difficult, consider putting the content on a web page instead.', 'accessibility-checker' ),
			'references'            => [
				[
					'text' => __( 'Microsoft: Accessibility best practices with Excel spreadsheets', 'accessibility-checker' ),
					'url'  => 'https://support.microsoft.com/en-us/office/accessibility-best-practices-with-excel-spreadsheets-6cc05fc5-1314-48b5-8eb3-683e49b3e593',
				],
				[
					'text' => __( 'Microsoft: Make your PowerPoint presentations accessible to people with disabilities', 'accessibility-checker' ),
					'url'  => 'https://support.microsoft.com/en-us/office/make-your-word-documents-accessible-to-people-with-disabilities-6f7772b2-2f33-4bd2-8ca7-dae3b2b3ef25',
				],
				[
					'text' => __( 'Microsoft: Make your Word documents accessible to people with disabilities', 'accessibility-checker' ),
					'url'  => 'https://support.microsoft.com/en-us/office/make-your-word-documents-accessible-to-people-with-disabilities-d9bf3683-87ac-47ea-b91a-78dcacb3c66d',
				],
				[
					'text' => __( 'Google: Make your document, presentation, sheets & videos more accessible', 'accessibility-checker' ),
					'url'  => 'https://support.google.com/docs/answer/6199477?hl=en',
				],
				[
					'text' => __( 'WebAIM: Creating Accessible Word Documents', 'accessibility-checker' ),
					'url'  => 'https://webaim.org/techniques/word/',
				],
				[
					'text' => __( 'WebAIM: Creating Accessible PowerPoint Presentations', 'accessibility-checker' ),
					'url'  => 'https://webaim.org/techniques/powerpoint/',
				],
				[
					'text' => __( 'WebAIM: Creating Accessible Excel Spreadsheets', 'accessibility-checker' ),
					'url'  => 'https://webaim.org/techniques/excel/',
				],
				[
					'text' => __( 'Section 508.gov: Create Accessible Documents (Webinar)', 'accessibility-checker' ),
					'url'  => 'https://www.section508.gov/create/documents/',
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
