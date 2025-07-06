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
			// translators: %1$s is <code>alt=""</code>, %2$s is the <code>&lt;a&gt;</code> tag.
			esc_html__( 'This image does not have an alt attribute (%1$s) contained in the image tag (%2$s).', 'accessibility-checker' ),
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
		'severity'  => 1, // Critical.
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
			// translators: %1$s is <code>&lt;h1&gt;</code>, %2$s is <code>&lt;h6&gt;</code>
			esc_html__( 'This page does not contain any heading elements between %1$s and %2$s in the main content area and has more than 400 words.', 'accessibility-checker' ),
			'<code>&lt;h1&gt;</code>',
			'<code>&lt;h6&gt;</code>'
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
		'wcag'      => '3.2.5',
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
		'severity'  => 2, // high
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
			// translators: %s is <code>tabindex="1"</code>.
			esc_html__( 'A Tab Order Modified Warning appears when the logical tab order on the page has been changed by adding an attribute for tabindex that is greater than 0 to an HTML element (for example, %s). This can cause navigation issues for keyboard-only users. To resolve a Tab Order Modified warning you need to view the front end of your website on the page or post where the tab order has been modified and test to see if the modification is correct or not. If the tab order modification does not cause problems, then you can "Ignore" the warning. If the modified tab order causes information to be presented out of order, then you need to remove the tabindex attribute from the flagged element.', 'accessibility-checker' ),
			'<code>tabindex="1"</code>'
		),
		'ruleset'   => 'js',
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
			// translators: %s is <code>&lt;h1&gt;&lt;/h1&gt;</code>.
			esc_html__( 'An Empty Heading Tag error means that there is a heading tag present on your post or page that does not contain content. In code, this error would look like this: %s. To fix an empty heading, you will need to add content to the heading tag that has flagged the Empty Heading Tag error or remove the empty tag if it is not needed on your page.', 'accessibility-checker' ),
			'<code>&lt;h1&gt;&lt;/h1&gt;</code>'
		),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Empty Link', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help4108',
		'slug'      => 'empty_link',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;a&gt;</code>, %2$s is <code>aria-hidden="true"</code>, %3$s is <code>aria-label</code>.
			esc_html__( 'An Empty Link error means that one of the links present on the web page is empty or contains no text describing where the link will go if clicked. This commonly occurs with links that contain webfonts, icon fonts, and SVGs, or when a link has accidentally been created in the content editor. To fix an empty link error, you will need to find the link that is being flagged and add descriptive text to it. You will need to either: add text content within an empty %1$s element or, if your link contains an SVG or Webfont icon, hide that element with %2$s and add an %3$s attribute to the %1$s tag or screen reader text. The text or label you add should be descriptive of wherever the link points and not ambiguous.', 'accessibility-checker' ),
			'<code>&lt;a&gt;</code>',
			'code>aria-hidden="true"</code>',
			'<code>aria-label</code>'
		),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Empty Button', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1960',
		'slug'      => 'empty_button',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;button&gt;</code>, %2$s is <code>&lt;input&gt;</code>.
			esc_html__( 'An Empty Button error means that one of the buttons present on the web page is empty or contains no text describing the function of the button. Or, if it is an image button, the image contained in the button is missing alternative text. To fix an empty button error, you will need to find the button that is being flagged and add descriptive text to it. You will need to either: add text content within an empty %1$s element, add a value attribute to an %2$s that is missing one, or add alternative text to a button image. The text should be descriptive of whatever your button is being used for or the action that the button triggers.', 'accessibility-checker' ),
			'<code>&lt;button&gt;</code>',
			'<code>&lt;input&gt;</code>'
		),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Image Long Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1966',
		'slug'      => 'img_alt_long',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'An Image Long Alternative Text warning appears if there are more than 100 characters in your alternative text. Alternative text is meant to be descriptive of the image but in a succinct manner, without being too wordy. To fix this warning, you need to shorten your alt text for any images that have been flagged to 100 characters or less. If you have determined that your alternative text is good as-is, then "Ignore" the warning.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'ARIA Hidden', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1979',
		'slug'      => 'aria_hidden',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'The ARIA Hidden warning appears when content on your post or page has been hidden using the aria-hidden="true" attribute. When this attribute is added to an HTML element, screen readers will not read it out to users. Sometimes it is correct for the element to be hidden from screen readers (such as with a decorative icon) but other times this is not correct. When you see this warning, you need to determine if the element is supposed to be hidden from people who are blind or visually impaired. If it is correctly hidden, "Ignore" the warning. If it is incorrectly hidden and should be visible, remove the aria-hidden="true" attribute to resolve the warning.', 'accessibility-checker' ),
		'ruleset'   => 'js',
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
			// translators: %1$s is <code>&lt;th&gt;</code>, %2$s is <code>&lt;th&gt;&lt;/th&gt;</code>.
			esc_html__( 'An Empty Table Header error means that one of the table headers on your post or page does not contain any text. This means that the %1$s element is present but looks like this %2$s with nothing between the opening and closing tags. To fix an empty table header, you need to find the correct HTML element (%1$s) and add text to it that describes the row or column that it applies to.', 'accessibility-checker' ),
			'<code>&lt;th&gt;</code>',
			'<code>&lt;th&gt;&lt;/th&gt;</code>'
		),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Link to MS Office File', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1970',
		'slug'      => 'link_ms_office_file',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'A Link to MS Office File warning means that one or more of the links on your page or post directs to a file with one of the following file extensions: .doc, .docx, .xls, .xlsx, .ppt, .pptx, .pps or .ppsx. This warning appears when an MS Office file is present as a reminder to manually test your Word documents, PowerPoint presentations, and Excel spreadsheets for accessibility and to confirm that they conform to all relevant WCAG guidelines. To resolve a Link to MS Office File warning, you need to: (1) ensure a direct link to view or download the document is present if you\'re using a plugin to embed it on the page; (2) ensure the link to the document warns users it is a link to a document by displaying the specific file extension in the link anchor; and (3) test and remediate your MS Office file for accessibility errors. After determining your file is fully accessible, you can safely “Ignore” the warning.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Link to PDF', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1972',
		'slug'      => 'link_pdf',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'A Link to PDF warning means that one or more of the links on your page or post directs to a PDF file. This warning is a reminder to manually test the linked PDF for accessibility and to confirm that it conforms to all relevant WCAG guidelines. To resolve a Link to PDF warning, you need to: (1) ensure a direct link to view or download the document is present if you\'re using a plugin to embed it on the page; (2) ensure the link to the document warns users it is a link to a document by displaying the specific file extension in the link anchor; and (3) test and remediate your document for accessibility errors. After determining your file is fully accessible, you can safely “Ignore” the warning.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Link to Non-HTML File', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1973',
		'slug'      => 'link_non_html_file',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'A  Link to Non-HTML Document warning means that one or more of the links on your page or post directs to a file with one of the following file extensions: .rtf, .wpd, .ods, .odt, .odp, .sxw, .sxc, .sxd, .sxi, .pages, or .key. This warning is a reminder to manually test the linked document for accessibility and to confirm that it conforms to all relevant WCAG guidelines. To resolve a Link to Non-HTML Document warning, you need to: (1) ensure a direct link to view or download the document is present if you\'re using a plugin to embed it on the page; (2) ensure the link to the document warns users it is a link to a document by displaying the specific file extension in the link anchor; and (3) test and remediate your document for accessibility errors. After determining your file is fully accessible, you can safely “Ignore” the warning.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Long Description Invalid', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1948',
		'slug'      => 'long_description_invalid',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %s is <code>longdesc=""</code>.
			esc_html__( 'The Long Description Invalid error means that a long description attribute (%s) on an image does not have an appropriate URL, filename, or file extension. It may also mean that the long description is not a URL, or it has been left blank. The longdesc attribute is not fully supported opens a new window by HTML5, browsers, and all screen readers. Due to this lack of support, the best fix for this error is to remove longdesc from your image tag completely.', 'accessibility-checker' ),
			'<code>longdesc=""</code>'
		),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Missing Form Label', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1949',
		'slug'      => 'missing_form_label',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;input&gt;</code>, %2$s is <code>&lt;label&gt;</code>, %3$s is <code>for=""</code>.
			esc_html__( 'A Missing Form Label error is triggered when an %1$s (form field) is present in your form and but is not associated with a %2$s element. This could mean the label is present but is missing a %3$s attribute to connect it to the applicable field or there could be no label present at all and only an %1$s tag. To fix missing form label errors, you will need to determine how the field and form were created and then add field labels or a correct %3$s attribute to existing labels that are not connected to a field.', 'accessibility-checker' ),
			'<code>&lt;input&gt;</code>',
			'<code>&lt;label&gt;</code>',
			'<code>for=""</code>'
		),
		'ruleset'   => 'js',
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
		'summary'   => esc_html__( 'Ambiguous Anchor Text errors appear when there is linked text that has no meaning outside of its surrounding content. Common examples of this include linking phrases like "click here" or "learn more." To resolve this error, change the link text to be less generic so that it has meaning if heard on its own.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Underlined Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1978',
		'slug'      => 'underlined_text',
		'rule_type' => 'warning',
		'summary'   => sprintf(
			// translators: %s is <code>&lt;u&gt;</code>.
			esc_html__( 'An Underlined Text warning appears if any text on your page is wrapped in an HTML underline tag (%1$s). In an online environment, underlined text is generally used to indicate linked text and it is not considered a best practice to underline text for emphasis only. To fix underlined text, you will need to remove the %1$s element from the text or CSS styles that are making it underlined. Try using other stylization, such as italics, colored text, or bolding to emphasize or differentiate between words or phrases.', 'accessibility-checker' ),
			'<code>&lt;u&gt;</code>'
		),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Broken Skip or Anchor Link', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1962',
		'slug'      => 'broken_skip_anchor_link',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'An anchor link, sometimes called a jump link, is a link that, rather than opening a new page or URL when clicked, jumps or scrolls you to a different section on the same page. These links go to an element that starts with a hashtag rather than a full URL. For example, you might scroll someone to the about section of your home page by linking to #about. Broken Skip or Anchor Link errors appear when there is a link that targets another section on the same page but there is not an element present on the page that has the referenced id. This error will also appear if you are linking to just a #. To resolve this error, manually test the link to confirm it works and then either fix it or "Ignore" the error as applicable.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Missing Table Header', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1963',
		'slug'      => 'missing_table_header',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;td&gt;</code>, %2$s is <code>&lt;th&gt;</code>.
			esc_html__( 'A Missing Table Header error means that one of your tables contains data (information contained in a %1$s tag) that does not have a corresponding header (%2$s) tag. When looking at the HTML for your form, there will be more %1$s elements in a row than %2$s elements in the table. To fix a missing table header, you need to find the section of code that has fewer %2$s elements in it than should be present for the number of rows or columns of data, and add one or more additional %2$s elements containing text that describes the data in that row or column.', 'accessibility-checker' ),
			'<code>&lt;td&gt;</code>',
			'<code>&lt;th&gt;</code>'
		),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Duplicate Form Label', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1954',
		'slug'      => 'duplicate_form_label',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'Duplicate Form Label errors appear when there is more than one label associated with a single field on a form. If there are too many form labels present, a screen reader may not be able to successfully read the form fields to help a visually impaired user navigate through and complete the form. To fix duplicate form label errors, you will need to determine how the field and form were created and then ensure that each field has only one label associated with it.', 'accessibility-checker' ),
		'ruleset'   => 'js',
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
