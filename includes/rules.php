<?php
/**
 * Returns an array of default rules.
 *
 * @package Accessibility_Checker
 */

use EqualizeDigital\AccessibilityChecker\Fixes\Fix\{
	TabindexFix,
	AddLabelToUnlabelledFormFieldsFix,
	CommentSearchLabelFix,
	PreventLinksOpeningNewWindowFix,
	AddNewWindowWarningFix,
	HTMLLangAndDirFix,
	AddMissingOrEmptyPageTitleFix,
	MetaViewportScalableFix,
};

return [
	[
		'title'     => esc_html__( 'Blinking or Scrolling Content', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1965',
		'slug'      => 'text_blinking_scrolling',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;blink&gt;</code>, %2$s is <code>&lt;marquee&gt;</code>, %3$s is <code>text-decoration: blink</code>.
			esc_html__( 'Blinking or Scrolling Content errors appear when elements on your website have a blinking or scrolling function applied to them either via CSS or in the HTML. Specifically, the following will create this error: the %1$s or %2$s HTML tags or CSS %3$s. To resolve this error remove the HTML tags or CSS that is causing content to blink.', 'accessibility-checker' ),
			'<code>&lt;blink&gt;</code>',
			'<code>&lt;marquee&gt;</code>',
			'<code>text-decoration: blink</code>'
		),
		'ruleset'   => 'js',
		'combines'  => [ 'blink', 'marquee' ],
	],
];
