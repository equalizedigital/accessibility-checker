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
		'title'     => esc_html__( 'Image Missing Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1927',
		'slug'      => 'img_alt_missing',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>alt=""</code>, %2$s is the <code>&lt;img&gt;</code> tag.
			esc_html__( 'This image does not have an alt attribute (%1$s) contained in the image tag (%2$s).', 'accessibility-checker' ),
			'<code>alt=""</code>',
			'<code>&lt;img&gt;</code>'
		),
		'summary_plural' => sprintf(
			// translators: %1$s is <code>alt=""</code>, %2$s is the <code>&lt;img&gt;</code> tag.
			esc_html__( 'These images do not have an alt attribute (%1$s) contained in their image tags (%2$s).', 'accessibility-checker' ),
			'<code>alt=""</code>',
			'<code>&lt;img&gt;</code>'
		),
		'why_it_matters' => esc_html__( 'Alternative text is used by screen readers to describe images to people who cannot see them. If the alt attribute is missing, the screen reader will only say \'Image\' or may read out the file URL. Alternative text is also used by search engines to understand the content of the image. If an image does not have alternative text, it can create a poor user experience for people with visual impairments and can negatively impact your site\'s SEO.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %1$s is <code>alt=""</code>
			esc_html__( 'Add an alt attribute to the image with appropriate text describing the purpose of the image in the page. If the image is decorative, the alt attribute can be empty, but the HTML %1$s attribute still needs to be present.', 'accessibility-checker' ),
			'<code>alt=""</code>',
		),
		'references' => [
			[
				'text' => __( 'HTML Standard: The img element', 'accessibility-checker' ),
				'url'  => 'https://html.spec.whatwg.org/multipage/images.html#the-img-element',
			],
			[
				'text' => __( 'Alt Decision Tree', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/tutorials/images/decision-tree/',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.1.1',
		'severity' => 1, // Critical.
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
		],
	],
	[
		'title'     => esc_html__( 'Image Empty Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help4991',
		'slug'      => 'img_alt_empty',
		'rule_type' => 'warning',
		'summary'   => sprintf(
			// translators: %s is <code>alt=""</code>.
			esc_html__( 'This image has an empty alt attribute (%s).', 'accessibility-checker' ),
			'<code>alt=""</code>',
		),
		'summary_plural' => sprintf(
			// translators: %s is <code>alt=""</code>.
			esc_html__( 'These images have empty alt attributes (%s).', 'accessibility-checker' ),
			'<code>alt=""</code>',
		),
		'why_it_matters' => esc_html__( 'Screen readers rely on alternative text to describe images to users who cannot see them. If the alt attribute is empty, it signals that the image is decorative and should be skipped. However, if a meaningful image has an empty alt attribute, users with visual impairments will miss important information. Proper use of alternative text improves accessibility and ensures all users can understand the content.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %1$s is <code>alt=""</code> and %2$s is <code>role="presentation"</code>.
			esc_html__( 'Review the image to determine if it is decorative. If it is dectorative, it is correct to use an empty %1$s attribute and you can dismiss this warning by using the "Ignore" feature in Accessibility Checker or adding %2$s to the image and rescanning the page. If the image conveys information, add descriptive alt text that communicates the image\'s purpose or meaning.', 'accessibility-checker' ),
			'<code>alt=""</code>',
			'<code>role="presentation"</code>',
		),
		'references' => [
			[
				'text' => __( 'W3C Tutorial on Images: Decorative Images', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/tutorials/images/decorative/',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.1.1',
		'severity'  => 2, // high
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
		],
	],
	[
		'title'     => esc_html__( 'Low-quality Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1977',
		'slug'      => 'img_alt_invalid',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'This image has alternative text that may be vague, redundant, or include unnecessary words or file names.', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These images have alternative text that may be vague, redundant, or include unnecessary words or file names.', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'Alternative text helps people using screen readers understand the purpose of an image. When alt text includes words like “image,” “graphic,” or a file extension, it adds no useful information and can create confusion or distraction. Clear and relevant alt text improves comprehension and user experience.', 'accessibility-checker' ),
		'how_to_fix' => esc_html__( 'Rewrite the alternative text to accurately and concisely describe the purpose of the image in context. Avoid including words like “image,” “graphic,” file names or extensions (e.g., .jpg, .png), or placeholder terms like “spacer” or “arrow.” If the image is decorative, leave the alt attribute empty.', 'accessibility-checker' ),
		'references' => [
			[
				'text' => __( 'W3C Tutorial on Images: Informative Images', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/tutorials/images/informative/',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.1.1',
		'severity'  => 3, // Medium.
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
		],
	],
	[
		'title'     => esc_html__( 'Linked Image Missing Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1930',
		'slug'      => 'img_linked_alt_missing',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'This image is inside a link but does not have an alt attribute and there is no other text within the link.', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These images are inside links but do not have alt attributes and there is no other text within the links.', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'Screen reader users rely on alternative text to understand the purpose of linked images. Without it, they cannot determine where the link leads, making navigation difficult or impossible.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %s is <code>aria-label"</code>.
			esc_html__( 'Add an alt attribute that describes the purpose of the link. Focus on where the link goes, not what the image looks like. Alternatively, add an %s to the link.', 'accessibility-checker' ),
			'<code>aria-label</code>',
		),
		'references' => [
			[
				'text' => __( 'W3C Tutorial on Images: Functional Images', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/tutorials/images/functional/',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.1.1',
		'severity'  => 1, // critical
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
		],
	],
	[
		'title'     => esc_html__( 'Linked Image Empty Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1930',
		'slug'      => 'img_linked_alt_empty',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'This image is inside a link and has an empty alt attribute and there is no other text within the link.', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These images are inside links and have empty alt attributes and there is no other text within the links.', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'An empty alt attribute on a linked image causes screen reader users to hear just "link" with no context. This makes navigation confusing and may cause them to skip important content.', 'accessibility-checker' ),
		'how_to_fix' => esc_html__( 'Replace the empty alt attribute with meaningful alternative text that clearly describes the link’s destination or purpose.', 'accessibility-checker' ),
		'references' => [
			[
				'text' => __( 'W3C Tutorial on Images: Functional Images', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/tutorials/images/functional/',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.1.1',
		'severity'  => 1, // critical
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
		],
	],
	[
		'title'     => esc_html__( 'Duplicate Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1976',
		'slug'      => 'img_alt_redundant',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'This image has alternative text that is identical to nearby content or to another image on the page.', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These images have alternative text that is identical to nearby content or to other images on the page.', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'When alternative text repeats nearby text or appears identically across multiple images, it creates confusion for screen reader users. Repetition provides no additional context and makes it difficult to distinguish between different elements or understand their purpose.', 'accessibility-checker' ),
		'how_to_fix' => esc_html__( 'If the alt text duplicates a nearby caption, heading, or visible text, remove the redundancy by shortening or omitting the alt text—especially if the surrounding text already provides the same information. If multiple images have the same alt text, revise each one to be unique and describe the purpose of that specific image in its context.', 'accessibility-checker' ),
		'references' => [
			[
				'text' => __( 'W3C Tutorial on Images: Informative Images', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/tutorials/images/informative/',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.1.1',
		'severity'  => 3, // medium
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
		],
	],
	[
		'title'     => esc_html__( 'Incorrect Heading Order', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1940',
		'slug'      => 'incorrect_heading_order',
		'rule_type' => 'error',
		'summary' => sprintf(
			// translators: %1$s is the found heading level (e.g., <h3>), %2$s is the previous heading level (e.g., <h1>)
			esc_html__( 'This page uses headings out of order. This is an %1$s after an %2$s, skipping one or more heading levels.', 'accessibility-checker' ),
			'<code>&lt;h3&gt;</code>',
			'<code>&lt;h1&gt;</code>'
		),
		'summary_plural' => sprintf(
			// translators: %1$s is <code>&lt;h1&gt;</code>, %2$s is <code>&lt;h3&gt;</code>, %3$s is <code>&lt;h2&gt;</code>
			esc_html__( 'These pages use headings out of order, skipping one or more heading levels, such as going from %1$s to %2$s without an %3$s between.', 'accessibility-checker' ),
			'<code>&lt;h1&gt;</code>',
			'<code>&lt;h3&gt;</code>',
			'<code>&lt;h2&gt;</code>'
		),
		'why_it_matters' => esc_html__( 'Headings help all users—especially those using screen readers or keyboard navigation—understand the structure of the page. Skipping heading levels can create confusion and make it harder to follow the content hierarchy or navigate efficiently.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %1$s is <code>&lt;h1&gt;</code>, %2$s is <code>&lt;h3&gt;</code>, %3$s is <code>&lt;h2&gt;</code>
			esc_html__( 'Revise your headings to follow a logical order without skipping levels. For example, if a %1$s is followed by a %2$s, change the %2$s to a %3$s or add an intervening %3$s section to preserve proper structure. If needed, use CSS or block/page builder settings to style headings rather than setting the incorrect level.', 'accessibility-checker' ),
			'<code>&lt;h1&gt;</code>',
			'<code>&lt;h3&gt;</code>',
			'<code>&lt;h2&gt;</code>'
		),
		'references' => [
			[
				'text' => __( 'W3C Page Structure Tutorial: Headings', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/tutorials/page-structure/headings/',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.3.1',
		'severity'  => 2, // high
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Cognitive',
			'Deafblind',
			'Mobility',
		],
		'combines'  => [
			'heading-order',
		],
	],
	[
		'title'     => esc_html__( 'Empty Paragraph Tag', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help7870',
		'slug'      => 'empty_paragraph_tag',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'This page contains a <p> tag with no content inside.', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These pages contain <p> tags with no content inside.', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'Empty paragraph tags can be announced by screen readers as blank lines or pauses, which may confuse users or disrupt reading flow. They can also interfere with visual layout or spacing, especially in assistive technology or mobile contexts.', 'accessibility-checker' ),
		'how_to_fix' => esc_html__( 'Remove the empty <p> tags from your content. If spacing is needed between sections, use margin, padding, or a visual spacer block instead of inserting blank paragraphs.', 'accessibility-checker' ),
		'references' => [
			[
				'text' => __( 'MDN Web Docs: The Paragraph Element - Accessibility' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/p#accessibility',
			],
			[
				'text' => __( 'WordPress Spacer Block', 'accessibility-checker' ),
				'url' => 'https://wordpress.org/documentation/article/spacer-block/',
			],
			[
				'text' => __( 'MDN Web Docs: CSS Padding', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/CSS/padding',
			],
			[
				'text' => __( 'MDN Web Docs: CSS Margin', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/CSS/margin',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '0.1',
		'severity'  => 4, // low
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Cognitive',
			'Deafblind',
		],
	],
	[
		'title'     => esc_html__( 'iframe Missing Title', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1953',
		'slug'      => 'iframe_missing_title',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;iframe&gt;</code>
			esc_html__( 'This %1$s does not have a descriptive title attribute.', 'accessibility-checker' ),
			'<code>&lt;iframe&gt;</code>'
		),
		'summary_plural' => sprintf(
			// translators: %1$s is <code>&lt;iframe&gt;</code>
			esc_html__( 'These %1$s do not have descriptive title attributes.', 'accessibility-checker' ),
			'<code>&lt;iframe&gt;</code>'
		),
		'why_it_matters' => esc_html__( 'Screen readers rely on the title attribute of an iframe to describe its purpose or content. Without a title, users may not understand what the embedded content is, making the page harder to navigate and use.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %1$s is <code>&lt;iframe&gt;</code>
			esc_html__( 'Add a title attribute to the %1$s tag that accurately describes the content or function of the embedded frame. If the iframe is added by a plugin and the title cannot be edited directly, check the plugin settings for an accessibility option, or contact the developer for support. Consider switching to a wordPress core embed instead.', 'accessibility-checker' ),
			'<code>&lt;iframe&gt;</code>'
		),
		'references' => [
			[
				'text' => __( 'W3C: Techniques for WCAG 2.1 - H64: Using the title attribute of the iframe element', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H64',
			],
			[
				'text' => __( 'MDN Web Docs: <iframe>', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/iframe',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '4.1.2',
		'severity'  => 2, // high
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
			'Mobility',
		],
		'combines'  => [
			'frame-title',
		],
	],
	[
		'title'     => esc_html__( 'Missing Subheadings', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1967',
		'slug'      => 'missing_headings',
		'rule_type' => 'warning',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;h1&gt;</code>, %2$s is <code>&lt;h6&gt;</code>, %3$s is the word count threshold.
			esc_html__( 'This page does not contain any heading elements between %1$s and %2$s in the main content area and has more than %3$s words.', 'accessibility-checker' ),
			'<code>&lt;h1&gt;</code>',
			'<code>&lt;h6&gt;</code>',
			'400'
		),
		'summary_plural' => sprintf(
			// translators: %1$s is <code>&lt;h1&gt;</code>, %2$s is <code>&lt;h6&gt;</code>, %3$s is '400' or the number defined in the settings
			esc_html__( 'These pages do not contain any heading elements between %1$s and %2$s in the main content area and have more than %3$s words.', 'accessibility-checker' ),
			'<code>&lt;h1&gt;</code>',
			'<code>&lt;h6&gt;</code>',
			'400'
		),
		'why_it_matters' => esc_html__( 'Headings provide structure and make content easier for users of all abilities to scan. They also help screen reader users and keyboard-only users navigate the page. Without headings, users must read through content linearly without an easy way to jump between sections.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %1$s is <code>&lt;h1&gt;</code>
			esc_html__( 'Add meaningful heading elements throughout your content to organize it into sections. At a minimum, include one %1$s tag as the main page title, and add subheadings where appropriate to break up content into logical parts.', 'accessibility-checker' ),
			'<code>&lt;h1&gt;</code>'
		),
		'references' => [
			[
				'text' => __( 'W3C Tutorial: Headings', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/tutorials/page-structure/headings/',
			],
		],
		'viewable'  => false,
		'ruleset'   => 'js',
		'wcag'      => '1.3.1',
		'severity'  => 3, // medium
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Cognitive',
			'Deafblind',
			'Mobility',
		],
	],
	[
		'title'     => esc_html__( 'Text Justified', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1980',
		'slug'      => 'text_justified',
		'rule_type' => 'warning',
		'summary'   => sprintf(
			// translators: %s is <code>text-align: justify</code>
			esc_html__( 'This page contains long blocks of text that are styled with %s.', 'accessibility-checker' ),
			'<code>text-align: justify</code>'
		),
		'summary_plural' => sprintf(
			// translators: %s is <code>text-align: justify</code>
			esc_html__( 'These pages contain long blocks of text that are styled with %s.', 'accessibility-checker' ),
			'<code>text-align: justify</code>'
		),
		'why_it_matters' => esc_html__( 'Justified text can create uneven spacing between words, forming distracting "rivers" of white space that make reading difficult—especially for people with dyslexia, low vision, or cognitive disabilities. Left-aligned text is more predictable and easier to read.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %s is <code>text-align: justify</code>
			esc_html__( 'Remove the %s CSS rule from long blocks of text, especially paragraphs over 200 characters. Use left-aligned text instead for better readability and accessibility.', 'accessibility-checker' ),
			'<code>text-align: justify</code>'
		),
		'references' => [
			[
				'text' => __( 'W3C: Making Content Usable for People with Cognitive and Learning Disabilities', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/TR/coga-usable/#use-left-and-right-alignment-consistently',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.4.8',
		'severity'  => 4, // low
		'affected_disabilities' => [
			'Low-vision',
			'Cognitive',
			'Dyslexia',
		],
	],
	[
		'title'     => esc_html__( 'Link Opens New Window or Tab', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1982',
		'slug'      => 'link_blank',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'This link is set to open in a new browser tab or window.', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These links are set to open in new browser tabs or windows.', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'When a link opens in a new tab or window without warning, it can be disorienting, especially for users with cognitive disabilities, screen reader users, or anyone relying on keyboard navigation. They may not realize a new context has opened or understand how to return.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %1$s is <code>target="_blank"</code>.
			esc_html__( 'Avoid using %1$s unless absolutely necessary. If a link must open in a new tab or window, add a visible icon and screen reader text such as "opens in a new window" or "opens in a new tab" to inform users. To automate this and dismiss the warning sitewide, you can activate the \'Add Label To Links That Open A New Tab/Window\' fix in Accessibility Checker settings.', 'accessibility-checker' ),
			'<code>target="_blank"</code>',
		),
		'references' => [
			[
				'text' => __( 'W3C Technique H83: Using the target attribute to open a new window on user request and indicating this in link text', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H83',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '3.2.2',
		'severity'  => 3, // medium
		'affected_disabilities' => [
			'Cognitive',
			'Blind',
			'Low-vision',
			'Motor',
		],
		'combines'  => [
			'link_blank',
		],
		'fixes'     => [
			PreventLinksOpeningNewWindowFix::get_slug(),
			AddNewWindowWarningFix::get_slug(),
		],
	],
	[
		'title'     => esc_html__( 'Image Map Missing Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1938',
		'slug'      => 'image_map_missing_alt_text',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;area&gt;</code>
			esc_html__( 'One or more %1$s elements in an image map are missing alternative text.', 'accessibility-checker' ),
			'<code>&lt;area&gt;</code>'
		),
		'summary_plural' => sprintf(
			// translators: %1$s is <code>&lt;area&gt;</code>
			esc_html__( 'One or more %1$s elements in image maps are missing alternative text.', 'accessibility-checker' ),
			'<code>&lt;area&gt;</code>'
		),
		'why_it_matters' => esc_html__( 'Image maps use area elements to define interactive regions. Without alternative text, screen reader users won\'t know what each clickable area does, making it impossible to understand or interact with the map.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %1$s is <code>&lt;area&gt;</code>, %2$s is <code>alt=""</code>
			esc_html__( 'Add a descriptive %2$s attribute to each %1$s element in your image map. The text should explain the function or destination of the link, not what the area looks like.', 'accessibility-checker' ),
			'<code>&lt;area&gt;</code>',
			'<code>alt=""</code>'
		),
		'references' => [
			[
				'text' => __( 'W3C: HTML Image Maps', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/tutorials/images/imagemap/',
			],
			[
				'text' => __( 'MDN Web Docs: <area>', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/area',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.1.1',
		'severity'  => 1, // critical
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
		],
		'combines'  => [
			'area-alt',
		],
	],
	[
		'title'     => esc_html__( 'Tab Order Modified', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1974',
		'slug'      => 'tab_order_modified',
		'rule_type' => 'warning',
		'summary'   => sprintf(
			// translators: %1$s is <code>tabindex="1"</code> and %2$s is <code>tabindex</code>.
			esc_html__( 'This page contains an element with %1$s or another positive %2$s, which modifies the natural tab order.', 'accessibility-checker' ),
			'<code>tabindex="1"</code>',
			'<code>tabindex</code>'
		),
		'summary_plural' => sprintf(
			// translators: %1$s is <code>tabindex="1"</code> and %2$s is <code>tabindex</code>.
			esc_html__( 'These pages contain elements with %1$s or other positive %2$s, which modify the natural tab order.', 'accessibility-checker' ),
			'<code>tabindex="1"</code>',
			'<code>tabindex</code>'
		),
		'why_it_matters' => esc_html__( 'The natural tab order of a page follows the structure of the HTML. Changing this order using positive tabindex values can cause confusion for keyboard-only users and screen reader users, especially if the focus moves in an unexpected way or skips important content.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %s is <code>tabindex</code>
			esc_html__( 'Remove positive %s values (greater than 0) from elements unless there is a very specific, user-tested reason to change the focus order. If needed, use tabindex="0" to include custom elements in the natural tab flow without disrupting order. To fix this and other elements site-wide, enable the \'Remove Tab Index\' fix in Accessibility Checker settings.', 'accessibility-checker' ),
			'<code>tabindex</code>'
		),
		'references' => [
			[
				'text' => __( 'ARIA Authoring Practices Guide: Developing a Keyboard Interface', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/ARIA/apg/practices/keyboard-interface/',
			],
			[
				'text' => __( 'W3C: Focus Order and Keyboard Navigation', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/general/G59',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '2.4.3',
		'severity'  => 2, // high
		'affected_disabilities' => [
			'Mobility',
			'Cognitive',
			'Blind',
			'Low-vision',
		],
		'combines'  => [ 'tabindex' ],
		'fixes'     => [
			TabindexFix::get_slug(),
		],
	],
	[
		'title'     => esc_html__( 'Empty Heading Tag', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1957',
		'slug'      => 'empty_heading_tag',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %s is <code>&lt;h1&gt;&lt;/h1&gt;</code>
			esc_html__( 'This is an empty %s heading that doesn\'t contain any content.', 'accessibility-checker' ),
			'<code>&lt;h1&gt;&lt;/h1&gt;</code>'
		),
		'summary_plural' => sprintf(
			// translators: %s is <code>&lt;h1&gt;&lt;/h1&gt;</code>
			esc_html__( 'These are empty %s headings that don\'t contain any content.', 'accessibility-checker' ),
			'<code>&lt;h1&gt;&lt;/h1&gt;</code>'
		),
		'why_it_matters' => esc_html__( 'Headings help structure content and provide important navigation points for screen reader users. An empty heading communicates no useful information and may cause confusion or disorientation when navigating by headings.', 'accessibility-checker' ),
		'how_to_fix' => esc_html__( 'Add meaningful content inside the heading tag to describe the section that follows. If the heading is not needed, remove it entirely to avoid misleading assistive technologies.', 'accessibility-checker' ),
		'references' => [
			[
				'text' => __( 'MDN: HTML heading elements', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/Heading_Elements',
			],
			[
				'text' => __( 'W3C: Techniques for WCAG 2.1 – H42: Using h1–h6 to identify headings', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H42',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.3.1',
		'severity'  => 3, // medium
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
		],
	],
	[
		'title'     => esc_html__( 'Empty Link', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help4108',
		'slug'      => 'empty_link',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'This is an empty link that does not contain any meaningful content for assitive technologies.', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These are empty links that do not contain any meaningful content for assistive technologies.', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'Screen reader users rely on link text to understand the purpose or destination of a link. An empty link provides no information, making it difficult or impossible for users to decide whether or not to follow it.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %1$s is <code>&lt;a&gt;</code>, %2$s is <code>aria-hidden="true"</code>, %3$s is <code>aria-label</code>
			esc_html__( 'Add descriptive text inside the %1$s element. If the link uses only an icon (e.g., SVG or webfont), hide the icon with %2$s and add a descriptive %3$s to the link or include screen reader-only text to explain where the link goes.', 'accessibility-checker' ),
			'<code>&lt;a&gt;</code>',
			'<code>aria-hidden="true"</code>',
			'<code>aria-label</code>'
		),
		'references' => [
						[
				'text' => __( 'W3C Technique H30: Providing link text that describes the purpose of a link', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H30',
			],
			[
				'text' => __( 'WebAIM: Links and Hypertext', 'accessibility-checker' ),
				'url'  => 'https://webaim.org/techniques/hypertext/',
			],
			[
				'text' => __( 'WordPress: The CSS class screen-reader-text', 'accessibility-checker' ),
				'url'  => 'https://make.wordpress.org/accessibility/handbook/markup/the-css-class-screen-reader-text/',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '2.4.4',
		'severity'  => 1, // critical
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
			'Cognitive',
		],
	],
	[
		'title'     => esc_html__( 'Empty Button', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1960',
		'slug'      => 'empty_button',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %s is <code>&lt;button&gt;</code>.
			esc_html__( 'This element is a %s with no accessible label or text.', 'accessibility-checker' ),
			'<code>&lt;button&gt;</code>',
			'<code>&lt;input&gt;</code>'
		),
		'summary_plural' => sprintf(
			// translators: %s is <code>&lt;button&gt;</code>.
			esc_html__( 'These elements are %s with no accessible label or text.', 'accessibility-checker' ),
			'<code>&lt;button&gt;</code>'
		),
		'why_it_matters' => esc_html__( 'Buttons must clearly describe the action they perform. An empty button provides no information to screen readers or keyboard users, making it impossible to understand what clicking the button will do. This creates a barrier for people relying on assistive technologies.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %1$s is <code>&lt;button&gt;</code>, %2$s is <code>value</code>, %3$s is <code>alt</code>, %4$s is <code>aria-hidden="true"</code>, %5$s is <code>aria-label</code>.
			esc_html__( 'Add visible text inside the %1$s element, set a %2$s attribute on an input button, or include an %3$s attribute on a button image. If the button uses only an icon (e.g., SVG or webfont), hide the icon with %4$s and add a descriptive %5$s to the button or include screen reader-only text.  Each button must have a label that clearly describes its purpose or action.', 'accessibility-checker' ),
			'<code>&lt;button&gt;</code>',
			'<code>value</code>',
			'<code>alt</code>',
			'<code>aria-hidden="true"</code>',
			'<code>aria-label</code>'
		),
		'references' => [
			[
				'text' => __( 'MDN Wed Docs: <button>', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/button',
			],
			[
				'text' => __( 'W3C Technique H44: Using the button element', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H44',
			],
			[
				'text' => __( 'WordPress: The CSS class screen-reader-text', 'accessibility-checker' ),
				'url'  => 'https://make.wordpress.org/accessibility/handbook/markup/the-css-class-screen-reader-text/',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '4.1.2',
		'severity'  => 3, // critical
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
			'Cognitive',
		],
	],
	[
		'title'     => esc_html__( 'Image Long Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1966',
		'slug'      => 'img_alt_long',
		'rule_type' => 'warning',
		'summary'   => sprintf(
			// translators: %s is the maximum character count for alt text.
			esc_html__( 'This image has alternative text longer than %s characters.', 'accessibility-checker' ),
			'300'
		),
		'summary_plural' => sprintf(
			// translators: %s is the maximum character count for alt text.
			esc_html__( 'These images have alternative text longer than %s characters.', 'accessibility-checker' ),
			'300'
		),
		'why_it_matters' => esc_html__( 'Alternative text should be concise and focused on describing the purpose or meaning of the image. Overly long alt text may overwhelm screen reader users, reduce readability, and distract from other content on the page.', 'accessibility-checker' ),
		'how_to_fix' => esc_html__( 'Shorten the alt text to fewer than characters while still describing the image\'s function or purpose. Keep descriptions simple and avoid repeating surrounding content. If the image\'s alt text does not need to be changed, dismiss this warning using the "Ignore" feature in Accessibility Checker.', 'accessibility-checker' ),
		'references' => [
			[
				'text' => __( 'W3C Tutorial: Informative Images', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/tutorials/images/informative/',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.1.1',
		'severity'  => 4, // low
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
			'Cognitive',
		],
	],
[
		'title'     => esc_html__( 'ARIA Hidden', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1979',
		'slug'      => 'aria_hidden',
		'rule_type' => 'warning',
		'summary'   => sprintf(
			// translators: %s is <code>aria-hidden="true"</code>
			esc_html__( 'This element uses %s, which hides it from screen readers.', 'accessibility-checker' ),
			'<code>aria-hidden="true"</code>'
		),
		'summary_plural' => sprintf(
			// translators: %s is <code>aria-hidden="true"</code>
			esc_html__( 'These elements use %s, which hides them from screen readers.', 'accessibility-checker' ),
			'<code>aria-hidden="true"</code>'
		),
		'why_it_matters' => esc_html__( 'The aria-hidden attribute is used to hide content from assistive technologies. While this is useful for decorative or redundant elements, it can cause accessibility issues if applied to important content that screen reader users need to access.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %s is <code>aria-hidden="true"</code>
			esc_html__( 'Check whether the element should truly be hidden from screen reader users. If it contains important content or functionality, remove %s. If it\'s decorative or redundant, leave the element alone and dismiss this warning using the "Ignore" feature in Accessibility Checker.', 'accessibility-checker' ),
			'<code>aria-hidden="true"</code>'
		),
		'references' => [
			[
				'text' => __( 'W3C: ARIA Authoring Practices - aria-hidden', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/ARIA/apg/practices/hiding/',
			],
			[
				'text' => __( 'MDN Web Docs: aria-hidden - Accessibility Attribute', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Attributes/aria-hidden',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.3.1',
		'severity'  => 3, // medium
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
		],
		'combines'  => [
			'aria_hidden_validation',
		],
	],
	[
		'title'     => esc_html__( 'Empty Table Header', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1963',
		'slug'      => 'empty_table_header',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %s is <code>&lt;th&gt;</code>
			esc_html__( 'This element is a table header cell (%s) with no text content.', 'accessibility-checker' ),
			'<code>&lt;th&gt;</code>',
		),
		'summary_plural' => sprintf(
			// translators: %s is <code>&lt;th&gt;</code>
			esc_html__( 'These elements are table header cells (%s) with no text content.', 'accessibility-checker' ),
			'<code>&lt;th&gt;</code>'
		),
		'why_it_matters' => esc_html__( 'Table headers provide context for the data in rows and columns. When a table header is empty, screen readers and other assistive technologies cannot convey the meaning of the associated data, making the table difficult or impossible to interpret.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %s is <code>&lt;th&gt;</code>
			esc_html__( 'Add descriptive text inside each %s element that explains the purpose of the row or column. Avoid using empty header cells or placeholder text that lacks meaning. If necessary, you can visually hide text in a table header cell with a screen-reader-text class.', 'accessibility-checker' ),
			'<code>&lt;th&gt;</code>'
		),
		'references' => [
			[
				'text' => __( 'W3C: Tables Tutorial', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/tutorials/tables/',
			],
			[
				'text' => __( 'MDN Web Docs: <th>', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/th',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.3.1',
		'severity'  => 2, // high
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
			'Cognitive',
		],
	],
	[
		'title'     => esc_html__( 'Link to MS Office File', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1970',
		'slug'      => 'link_ms_office_file',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'This is a link to a Microsoft Office file, such as a Word, Excel, or PowerPoint document. All linked documents must be manually tested for accessibility.', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These are links to Microsoft Office files, such as Word, Excel, or PowerPoint documents. All linked documents must be manually tested for accessibility.', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'Documents in Word, Excel, or PowerPoint format may not be accessible to all users, especially if they are not tagged correctly or do not follow accessibility best practices. Accessibility laws require that documents posted on web pages also conform to WCAG. Additionally, users should be warned when a link opens a downloadable file instead of a webpage.', 'accessibility-checker' ),
		'how_to_fix' => esc_html__( 'Ensure that the linked file is tested and remediated for accessibility. When you know it is accessible, dismiss this warning using the "Ignore" feature in Accessibility Checker. Include the file extension and size in the link text to inform users (enable the \'Add File Size & Type To Links\' fix in Accessibility Checker settings to do this site-wide). If the document is embedded using a plugin, also provide a direct link to download it. If making the document accessible is difficult, consider putting the content on a web page instead.', 'accessibility-checker' ),
		'references' => [
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
		'ruleset'   => 'js',
		'wcag'      => '0.3',
		'severity'  => 2, // high
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Cognitive',
			'Deafblind',
			'Mobility',
			'Colorblind',
		],
	],
	[
		'title'     => esc_html__( 'Link to PDF', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1972',
		'slug'      => 'link_pdf',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'This is a link to a PDF document. All linked documents must be manually tested for accessibility.', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These are links to PDF documents. All linked documents must be manually tested for accessibility.', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'PDFs may not be accessible to all users, especially if they are not tagged correctly or fail to meet WCAG requirements. Accessibility laws require that documents posted on websites meet the same accessibility standards as HTML content. Additionally, users should be warned when a link opens a downloadable file instead of a web page.', 'accessibility-checker' ),
		'how_to_fix' => esc_html__( 'Ensure the linked PDF is tested and remediated for accessibility. When you know it is accessible, dismiss this warning using the "Ignore" feature in Accessibility Checker. Include the file extension and size in the link text to inform users (enable the \'Add File Size & Type To Links\' fix in Accessibility Checker settings to do this site-wide). If the document is embedded using a plugin, also provide a direct link to download it. If making the PDF accessible is difficult, consider putting the content on a web page instead.', 'accessibility-checker' ),
		'references' => [
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
		'ruleset'   => 'js',
		'wcag'      => '0.3',
		'severity'  => 2, // high
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Cognitive',
			'Deafblind',
			'Mobility',
			'Colorblind',
		],
	],
	[
		'title'     => esc_html__( 'Link to Non-HTML File', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1973',
		'slug'      => 'link_non_html_file',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'This element is a link to a non-HTML document format with one of the following file extensions: .rtf, .wpd, .ods, .odt, .odp, .sxw, .sxc, .sxd, .sxi, .pages, or .key', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These elements are links to non-HTML document formats with one of the following file extensions: .rtf, .wpd, .ods, .odt, .odp, .sxw, .sxc, .sxd, .sxi, .pages, or .key', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'Non-HTML document formats may not be fully accessible to assistive technologies unless tested and remediated. Users should be warned when a link opens a downloadable file rather than a standard web page.', 'accessibility-checker' ),
		'how_to_fix' => esc_html__( 'Ensure the linked document is accessible. When you know it is accessible, dismiss this warning using the "Ignore" feature in Accessibility Checker. Include the file extension in the visible link text (e.g., “Meeting Notes (ODT)”) so users know what to expect (enable the \'Add File Size & Type To Links\' fix in Accessibility Checker settings to do this site-wide). If the document is embedded, provide a direct download link as well. If making the document accessible is difficult, consider putting the content on a web page instead.', 'accessibility-checker' ),
		'references' => [],
		'ruleset'   => 'js',
		'wcag'      => '0.3',
		'severity'  => 3, // medium
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Cognitive',
			'Deafblind',
			'Mobility',
			'Colorblind',
		],
	],
	[
		'title'     => esc_html__( 'Long Description Invalid', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1948',
		'slug'      => 'long_description_invalid',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %s is <code>longdesc=""</code>
			esc_html__( 'This element uses the %s attribute with an invalid or unsupported value.', 'accessibility-checker' ),
			'<code>longdesc=""</code>'
		),
		'summary_plural' => sprintf(
			// translators: %s is <code>longdesc=""</code>
			esc_html__( 'These elements use the %s attribute with invalid or unsupported values.', 'accessibility-checker' ),
			'<code>longdesc=""</code>'
		),
		'why_it_matters' => esc_html__( 'The longdesc attribute is intended to provide a URL to a long description of an image for screen reader users. However, it is no longer supported in HTML5 and is not reliably recognized by browsers or assistive technologies. Invalid values or blank longdesc attributes may confuse users or fail to convey important information.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %s is <code>longdesc</code>
			esc_html__( 'Remove the %s attribute from the image element. If a long description is needed, include it in nearby visible text like a caption, use a link to a separate description page, or use ARIA techniques such as aria-describedby.', 'accessibility-checker' ),
			'<code>longdesc</code>'
		),
		'references' => [
			[
				'text' => __( 'W3C: Techniques for WCAG 2.1 – H45: Using longdesc', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H45',
			],
			[
				'text' => __( 'MDN Web Docs: longdesc – HTML attribute', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/img#longdesc',
			],
			[
				'text' => __( 'MDN Web Docs: ARIA: aria-describedby attribute', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Attributes/aria-describedby',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.1.1',
		'severity'  => 2, // high
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Deafblind',
		],
	],
	[
		'title'     => esc_html__( 'Missing Form Label', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1949',
		'slug'      => 'missing_form_label',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;input&gt;</code>, %2$s is <code>&lt;textarea&gt;</code>, %3$s is <code>&lt;select&gt;</code>
			esc_html__( 'This element is an %1$s, %2$s, or %3$s form field without an associated label.', 'accessibility-checker' ),
			'<code>&lt;input&gt;</code>',
			'<code>&lt;textarea&gt;</code>',
			'<code>&lt;select&gt;</code>'
		),
		'summary_plural' => sprintf(
			// translators: %1$s is <code>&lt;input&gt;</code>, %2$s is <code>&lt;textarea&gt;</code>, %3$s is <code>&lt;select&gt;</code>
			esc_html__( 'These elements are %1$s, %2$s, or %3$s form fields without associated labels.', 'accessibility-checker' ),
			'<code>&lt;input&gt;</code>',
			'<code>&lt;textarea&gt;</code>',
			'<code>&lt;select&gt;</code>'
		),
		'why_it_matters' => esc_html__( 'Form fields must be clearly labeled so that users of screen readers and voice input technologies can understand their purpose. Without labels, users may not know what information to enter or how the form will behave.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %1$s is <code>&lt;label&gt;</code>, %2$s is <code>for=""</code>
			esc_html__( 'Add a visible %1$s element that describes the field. Connect it to the corresponding form element using the %2$s attribute, or use aria-label or aria-labelledby if needed. To fix all unlabelled fields automatically, enable the \'Label Form Fields\' fix in Accessibility Checker settings.', 'accessibility-checker' ),
			'<code>&lt;label&gt;</code>',
			'<code>for=""</code>'
		),
		'references' => [
			[
				'text' => __( 'W3C: Labeling Controls', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/tutorials/forms/labels/',
			],
			[
				'text' => __( 'MDN: label element', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/label',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.3.1',
		'severity'  => 1, // critical
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Cognitive',
			'Mobility',
		],
		'combines'  => [ 'label' ],
		'fixes'     => [
			AddLabelToUnlabelledFormFieldsFix::get_slug(),
			CommentSearchLabelFix::get_slug(),
		],
	],
	[
		'title'     => esc_html__( 'Ambiguous Anchor Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1944',
		'slug'      => 'link_ambiguous_text',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'This link uses generic or vague anchor text that does not describe its purpose.', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These links use generic or vague anchor text that does not describe their purpose.', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'Screen reader users often browse a list of links out of context. If links use ambiguous phrases like "click here" or "learn more," users will not understand where the link goes or what it does. This can lead to confusion and reduce the usability of your website.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %1$s is <code>aria-label</code>, %
			esc_html__( 'Revise the anchor text to clearly describe the destination or purpose of the link. For example, instead of "click here," use "download the annual report" or "learn more about us." Additional context can be added to links with an %1$s or screen-reader-text.', 'accessibility-checker' ),
			'<code>aria-label</code>'
		),
		'references' => [
			[
				'text' => __( 'MDN Web Docs: a element – Accessible names', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a#accessibility_concerns',
			],
			[
				'text' => __( 'MDN Web Docs: ARIA: aria-label attribute', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Attributes/aria-label',
			],
			[
				'text' => __( 'Screen Reader Text Format Plugin (for the block editor)', 'accessibility-checker' ),
				'url'  => 'https://wordpress.org/plugins/screen-reader-text-format/',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '2.4.4',
		'severity'  => 1, // critical
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Cognitive',
		],
	],
	[
		'title'     => esc_html__( 'Underlined Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1978',
		'slug'      => 'underlined_text',
		'rule_type' => 'warning',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;u&gt;</code>, %2$s is <code>text-decoration: underline;</code>
			esc_html__( 'This element contains underlined text using the %1$s tag or %2$s CSS styles and does not appear to be a link.', 'accessibility-checker' ),
			'<code>&lt;u&gt;</code>',
			'<code>text-decoration: underline;</code>'
		),
		'summary_plural' => sprintf(
			// translators: %1$s is <code>&lt;u&gt;</code>, %2$s is <code>text-decoration: underline;</code>
			esc_html__( 'These elements contain underlined text using the %1$s tag or %2$s CSS styles and do not appear to be links.', 'accessibility-checker' ),
			'<code>&lt;u&gt;</code>',
			'<code>text-decoration: underline;</code>'
		),
		'why_it_matters' => esc_html__( 'Underlined text is commonly associated with links. When non-link text is underlined, it can be confusing for users, especially those with cognitive disabilities or those relying on visual cues to identify links.', 'accessibility-checker' ),
		'how_to_fix' => esc_html__( 'Remove the underline tag or CSS underline style from the text. If you want to emphasize text, consider using bold, italic, or color styling instead. If this text is part of a functional element like a link or button, keep the underline styling and dismiss this warning using the "Ignore" feature in Accessibility Checker.', 'accessibility-checker' ),
		'references' => [],
		'ruleset'   => 'js',
		'wcag'      => '1.3.1',
		'severity'  => 4, // low
		'affected_disabilities' => [
			'Cognitive',
			'Low-vision',
		],
	],
	[
		'title'     => esc_html__( 'Broken Skip or Anchor Link', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1962',
		'slug'      => 'broken_skip_anchor_link',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'This link points to an anchor target on the same page, but no element with the referenced ID exists.', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These links point to anchor targets on the same page, but no elements with the referenced IDs exist.', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'Skip and anchor links are important for efficient keyboard and screen reader navigation. When these links are broken, users may be confused or lose their place on the page, leading to a frustrating experience—especially for people who rely on keyboard shortcuts or assistive technology.', 'accessibility-checker' ),
		'how_to_fix' => esc_html__( 'Confirm that the link is pointing to a valid ID on the page. If the target element exists, make sure it has a matching id attribute. To automatically fix broken skip links throughout your site, enable the \'Enable Skip Link\' fix in Accessibility Checker settings.', 'accessibility-checker' ),
		'references' => [
			[
				'text' => __( 'How to Make Your WordPress Site More Accessible With Skip Links', 'accessibility-checker' ),
				'url'  => 'https://equalizedigital.com/how-to-make-your-wordpress-site-more-accessible-with-skip-links/',
			],
			[
				'text' => __( 'How to Fix Broken Skip Links in Elementor', 'accessibility-checker' ),
				'url'  => 'https://equalizedigital.com/how-to-fix-broken-skip-links-in-elementor/',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '2.4.1',
		'severity'  => 2, // high
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Mobility',
		],
	],
	[
		'title'     => esc_html__( 'Missing Table Header', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1963',
		'slug'      => 'missing_table_header',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'This table contains data cells but does not have the required table header cells.', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These tables contain data cells but do not have the required table header cells.', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'Table headers are used by assistive technologies to help users understand the relationships between cells and navigate the data. Without table headers, screen reader users may not be able to determine what the data represents.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %1$s is <code>&lt;th&gt;</code>, %2$s is <code>&lt;td&gt;</code>.
			esc_html__( 'Add one or more %1$s elements that describe the columns or rows of data in your table. Each %2$s cell should be associated with a descriptive header to provide proper context for screen reader users.', 'accessibility-checker' ),
			'<code>&lt;th&gt;</code>',
			'<code>&lt;td&gt;</code>'
		),
		'references' => [
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
		'ruleset'   => 'js',
		'wcag'      => '1.3.1',
		'severity'  => 1, // critical
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Cognitive',
		],
	],
	[
		'title'     => esc_html__( 'Duplicate Form Label', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1954',
		'slug'      => 'duplicate_form_label',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'This form field has more than one label associated with it.', 'accessibility-checker' ),
		'summary_plural' => esc_html__( 'These form fields have more than one label associated with them.', 'accessibility-checker' ),
		'why_it_matters' => esc_html__( 'Assistive technologies rely on a one-to-one relationship between form fields and labels to provide accurate guidance to users. When multiple labels are associated with the same field, it can confuse screen reader users and make form completion difficult as the screen reader may read the incorrect label for a field.', 'accessibility-checker' ),
		'how_to_fix' => sprintf(
			// translators: %1$s is <code>&lt;label&gt;</code>, %2$s is <code>for=""</code>.
			esc_html__( 'Ensure each form field has only one %1$s element associated with it. If multiple labels are present, remove extras or consolidate them into a single %1$s with a clear description. Make sure each %1$s element correctly references its form field using the %2$s attribute and that all elements on the page have unique IDs. This error happen when you have the same form embedded on a page twice and can be resolved by creating unique forms for different parts of a page rather than repeating the same form throughout.', 'accessibility-checker' ),
			'<code>&lt;label&gt;</code>',
			'<code>for=""</code>'
		),
		'references' => [
			[
				'text' => __( 'MDN: <label> – The HTML Label element', 'accessibility-checker' ),
				'url'  => 'https://developer.mozilla.org/en-US/docs/Web/HTML/Element/label',
			],
			[
				'text' => __( 'W3C: H44 – Using label elements to associate text labels with form controls', 'accessibility-checker' ),
				'url'  => 'https://www.w3.org/WAI/WCAG21/Techniques/html/H44',
			],
		],
		'ruleset'   => 'js',
		'wcag'      => '1.3.1',
		'severity'  => 3, // medium
		'affected_disabilities' => [
			'Blind',
			'Low-vision',
			'Cognitive',
		],
		'combines'  => [ 'form-field-multiple-labels' ],
	],
	[
		'title'     => esc_html__( 'Text Too Small', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1975',
		'slug'      => 'text_small',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'A Text Too Small warning occurs when there is text on your website that is less than 10px in size. The warning is an indication that you may want to rethink the font size and make it larger so that it can be more easily read without a user needing zoom in on their browser. To fix text that is too small, you will need to ensure that all text elements on your website are at least 10 points.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Possible Heading', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1969',
		'slug'      => 'possible_heading',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'A Possible Heading warning occurs when there is text on a page that appears to be a heading, but has not been coded with proper heading tags. This warning is appears if there are short phrases or strings of text less than 50 characters in length that are formatted in a way which suggests they might be being used as headers (they are 20 pixels or bigger, or are 16 pixels or bigger and bold and/or italicized). To fix a Possible Heading warning, you will need to determine if the flagged text is indeed intended to be a heading. If so, you need to change it from a paragraph to a heading at the proper level. If it is not supposed to be a heading then you can safely “Ignore” the warning.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
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
	[
		'title'     => esc_html__( 'Insufficient Color Contrast', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1983',
		'slug'      => 'color_contrast_failure',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'Insufficient Color Contrast errors means that we have identified that one or more of the color combinations on your post or page do not meet the minimum color contrast ratio of 4.5:1. Depending upon how your site is built there may be "false positives" for this error as some colors are contained in different HTML layers on the page. To fix an Insufficient Color Contrast error, you will need to ensure that flagged elements meet the minimum required ratio of 4.5:1. To do so, you will need to find the hexadecimal codes of your foreground and background color, and test them in a color contrast checker. If these color codes have a ratio of 4.5:1 or greater you can “Ignore” this error. If the color codes do not have a ratio of at least 4.5:1, you will need to make adjustments to your colors.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Missing Transcript', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1947',
		'slug'      => 'missing_transcript',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'A missing transcript error means that there is an audio or video clip on your website that does not have a transcript or there is a transcript but it is not labelled as a transcript or is positioned more than 25 characters away from the embedded or linked to media. To fix a missing transcript error, you will need to create a transcript for any of the video or audio clips that have been flagged as missing a transcript. Once you have created the transcript, you can either add the transcript content directly within your post or page or link to the transcript if you’re including it as a downloadable doc or PDF file. You need to explicitly include the word “transcript” within a heading before the transcript on the page or in the link to your file, and it needs to be within 25 characters of the audio or video embed or link.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Broken ARIA Reference', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1956',
		'slug'      => 'broken_aria_reference',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'Broken ARIA Reference errors appear if an aria-labeledby or aria-describedby element is present on the page or post but its reference target does not exist. This means that the element being referred to by the specific ARIA attribute you are using either does not have a proper label or descriptor, or it is not present on the page. To fix a broken ARIA reference, you will need to find the ARIA elements that are being flagged, and ensure that their reference targets are present and properly labeled.', 'accessibility-checker' ),
		'ruleset'   => 'js',
		'combines'  => [ 'aria_broken_reference' ],
	],
	[
		'title'     => esc_html__( 'Missing Language Declaration', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help4429',
		'slug'      => 'missing_lang_attr',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;html&gt;</code>, %2$s is <code>lang=""</code>, %3$s is <code>xml:lang=""</code>.
			esc_html__( 'A language declaration is an HTML attribute that denotes the default language of the content on a page or post. Language declarations should be coded into your website theme and appear automatically in the head of the website. A Missing Language Declaration error appears if the %1$s tag on the page does not contain a %2$s or %3$s attribute, or one of these attributes is present but is empty. To fix a Missing Language Declaration error, you will need to edit your theme files to add the missing language attribute to the HTML tag at the very top of your website header. If you are using a theme that receives updates, then you will need to make the change in a child theme to ensure the fix does not get overwritten when you next update your theme.', 'accessibility-checker' ),
			'<code>&lt;html&gt;</code>',
			'<code>lang</code>',
			'<code>xml:lang</code>'
		),
		'ruleset'   => 'js',
		'combines'  => [ 'html-lang-valid', 'html-has-lang' ],
		'viewable'  => false,
		'fixes'     => [
			HTMLLangAndDirFix::get_slug(),
		],
	],
	// The name and slug of this rule references GIFs but the rule also checks webp.
	[
		'title'     => esc_html__( 'Image Animated GIF', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help4428',
		'slug'      => 'img_animated_gif',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'Image Animated GIF warnings appear when there is an animated GIF on your post or page. This warning is a reminder to manually review any animated GIFs on your website for their accessibility and/or to reconsider using animated GIFs, replacing them instead with static images or videos. To resolve this warning, you need to review any GIFs that are present to ensure that they meet all applicable guidelines for accessibility and then either “Ignore” the warning or remove the GIF from your page or post if it is not accessible.', 'accessibility-checker' ),
		'ruleset'   => 'js',
		'combines'  => [ 'img_animated' ],
	],
	[
		'title'     => esc_html__( 'A Video is Present', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help4414',
		'slug'      => 'video_present',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'Because videos frequently contain accessibility problems, many of which can only be identified by a person, The A Video is Present warning appears anytime a video is detected on a post or page as a reminder that you need to manually test your video for accessibility. To resolve this warning, you need to visit the front end of your website and confirm that the video in the warning is accessible. Once you have fully tested the video for accessibility, you need to fix any errors that may be present and then can “Ignore” the warning to mark it as complete.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'A Slider is Present', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help3264',
		'slug'      => 'slider_present',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'Because sliders frequently contain accessibility problems, many of which can only be identified by a person, the A Slider is Present warning appears anytime a slider is detected on a post or page as a reminder that you need to manually test your slider for accessibility. To resolve this warning, you need to visit the front end of your website and confirm all sliders on the page are accessible. Once you have fully tested your sliders for accessibility, you need to fix any errors that may be present and then can “Ignore” the warning to mark it as complete.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Missing Title', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help4431',
		'slug'      => 'missing_title',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;title&gt;</code>, %2$s is <code>og:title</code>.
			esc_html__( 'A Missing Title error means that your post or page does not contain a title element (%1$s or %2$s). This could happen if the theme fails to add a title tag, or if the title field of a post or page has been left blank in the WordPress edit screen. To fix a page with no titles, you will need to add title elements. First, determine if the title is missing because you left the page or post title field blank in the editor. If so, fill it in. If a title is filled in and visible on the backend then the code in the of your web page needs to be edited to include a %1$s tag or %2$s meta tag.', 'accessibility-checker' ),
			'<code>&lt;title&gt;</code>',
			'<code>og:title</code>'
		),
		'ruleset'   => 'js',
		'combines'  => [ 'document-title' ],
		'viewable'  => false,
		'fixes'     => [
			AddMissingOrEmptyPageTitleFix::get_slug(),
		],
	],
	[
		'title'     => esc_html__( 'Improper Use of Link', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help6473',
		'slug'      => 'link_improper',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>href</code>, %2$s is <code>#</code>, %3$s is <code>role="button"</code>, %4$s is <code>&lt;button&gt;</code>, %5$s is <code>role="button"</code>, %6$s is <code>&lt;a&gt;</code>.
			esc_html__( 'An Improper Use of Link error appears if you have links that are missing an %1$s attribute or are only linked to a %2$s, and do not have %3$s on them. Links should be used to direct people to other parts of your site. Any other functionality that triggers an action should be a button. To resolve this error you need to recode the link to use a %4$s tag (preferable) or add %5$s to the existing %6$s tag. If the element is a toggle button (such as an accordion), additional ARIA attributes are required.', 'accessibility-checker' ),
			'<code>href</code>',
			'<code>#</code>',
			'<code>role="button"</code>',
			'<code>&lt;button&gt;</code>',
			'<code>role="button"</code>',
			'<code>&lt;a&gt;</code>'
		),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Zooming and Scaling Disabled', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help7947',
		'slug'      => 'meta_viewport',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'Zooming is disabled via viewport meta tag that includes `user-scalable=no` or a `maximum-scale` value of less than 2. This limits low-vision users that want to increase text sizes, zoom into the page or who use a magnifier.', 'accessibility-checker' ),
		'ruleset'   => 'js',
		'combines'  => [ 'meta-viewport' ],
		'viewable'  => false,
		'fixes'     => [ MetaViewportScalableFix::get_slug() ],
	],
];
