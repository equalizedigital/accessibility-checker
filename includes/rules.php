<?php
/**
 * Returns an array of default rules.
 *
 * @package Accessibility_Checker
 */

return [
	[
		'title'     => esc_html__( 'Image Missing Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1927',
		'slug'      => 'img_alt_missing',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>alt=""</code>, %2$s is the <code>&lt;a&gt;</code> tag.
			esc_html__( 'An Image Missing Alternative Text error means that your image does not have an alt attribute (%1$s) contained in the image tag (%2$s) at all. To fix an Image Missing Alternative Text error, you will need to add an alt tag to the image with appropriate text describing the purpose of the image in the page. If the image is decorative, the alt attribute can be empty, but the HTML %1$s tag still needs to be present.', 'accessibility-checker' ),
			'<code>alt=""</code>',
			'<code>&lt;a&gt;</code>'
		),
	],
	[
		'title'     => esc_html__( 'Image Empty Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help4991',
		'slug'      => 'img_alt_empty',
		'rule_type' => 'warning',
		'summary'   => sprintf(
			// translators: %s is <code>alt=""</code>.
			esc_html__( 'An Image Empty Alternative Text warning appears if you have an image with an alt attribute (%s) that is empty. Alternative text tells people who cannot see what the images is and adds additional context to the post or page. It is only correct for alternative text to be empty if the image is purely decorative, like a border or decorative icon. To fix an Image Empty Alternative Text warning, you need to determine if the image is decorative or if adds something meaningful to the page. If it is not decorative, you need to add appropriate alternative text to describe the image\'s purpose. If the image is decorative, then you would leave the alternative text blank and “Ignore” the warning.', 'accessibility-checker' ),
			'<code>alt=""</code>',
		),
	],
	[
		'title'     => esc_html__( 'Low-quality Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1977',
		'slug'      => 'img_alt_invalid',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'A Low-quality Alternative Text warning appears when the alternative text on an image contains keywords that are unnecessary in alternative text (such as "image" or "graphic"), a file extension (such as .JPG), that may be describing a decorative image (such as "spacer" or "arrow"). To fix this warning, you will need to rewrite the alternative text for any images that flagged the Low-Quality Alternative Text warning, ensuring the alternative text is accurate, unique, contextually appropriate, and does not contain redundant or unnecessary descriptors. If the image is purely decorative, it is correct to leave the alternative text blank.', 'accessibility-checker' ),
	],
	[
		'title'     => esc_html__( 'Linked Image Missing Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1930',
		'slug'      => 'img_linked_alt_missing',
		'rule_type' => 'error',
		'summary'   => sprintf(
		// translators: %s is <code>alt=""</code>.
			esc_html__( 'A Linked Image Missing Alternative Text error appears when an image that is linked to a URL does not have an alt attribute (%s) in the image tag at all. Linked images must have accurate alternative text that describes where the link goes so that screen reader users know where the link is pointing. To resolve this error you need to add meaningful alt text to the image. Your alt text should describe the link purpose not what the image looks like.', 'accessibility-checker' ),
			'<code>alt=""</code>'
		),
	],
	[
		'title'     => esc_html__( 'Linked Image Empty Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1930',
		'slug'      => 'img_linked_alt_empty',
		'rule_type' => 'error',
		'summary'   => sprintf(
		// translators: %s is <code>alt=""</code>.
			esc_html__( 'A Linked Image Empty Alternative Text error appears when an image that is linked to a URL has an alt attribute (%s) with nothing in it. Linked images must have accurate alternative text that describes where the link goes so that screen reader users know where the link is pointing. To resolve this error you need to add meaningful alt text to the image. Your alt text should describe the link purpose not what the image looks like.', 'accessibility-checker' ),
			'<code>alt=""</code>'
		),
	],
	[
		'title'     => esc_html__( 'Duplicate Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1976',
		'slug'      => 'img_alt_redundant',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'Duplicate Alternative Text warnings appear when the alternative text for an image on your post or page is identical to nearby or adjacent text, including the image’s title or caption. This warning also occurs if two images on the page have the same alternative text. To resolve this warning, you will need to change the text of either one or both elements that flagged the Duplicate Alternative Text warning, ensuring that all images have unique alt text and that you are not repeating your alt text in your image titles and captions.', 'accessibility-checker' ),
	],
	[
		'title'     => esc_html__( 'Incorrect Heading Order', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1940',
		'slug'      => 'incorrect_heading_order',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;h3&gt;</code>, %2$s is <code>&lt;h1&gt;</code>, %3$s is <code>&lt;h2&gt;</code>.
			esc_html__( 'An Incorrect Heading Order error means your heading structure has skipped over a level. For example, if your page structure has a level 3 heading (%1$s) under a level 1 heading (%2$s), an "Incorrect Heading Order" error will be flagged because there is no %3$s tag between the H1 and H3. To fix incorrect heading order errors, you will need to either change the incorrect heading level to the correct heading level, or add content with the correct heading level in between the two already existing levels.', 'accessibility-checker' ),
			'<code>&lt;h3&gt;</code>',
			'<code>&lt;h1&gt;</code>',
			'<code>&lt;h2&gt;</code>'
		),
	],
	[
		'title'     => esc_html__( 'Empty Paragraph Tag', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help7870',
		'slug'      => 'empty_paragraph_tag',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'An Empty Paragraph Tag warning means there is a paragraph tag present that does not contain content. These may be announced by screen readers or create confusion for users. To fix this warning, remove the empty paragraphs from the page. If you need to add spacing between sections, this should be done with padding, margins, or a spacer block.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'iFrame Missing Title', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1953',
		'slug'      => 'iframe_missing_title',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;frame&gt;</code>.
			esc_html__( 'An iFrame Missing title error means that one or more of the iFrames on your post or page does not have an accessible title describing the contents of the iFrame. An iFrame title is an attribute that can be added to the %1$s tag to describe the contents of the frame to people using assistive technology. To fix a missing iFrame title, you will need to add a title or an aria-label attribute to the %1$s tag. The attribute should accurately describe the contents of the iFrame.', 'accessibility-checker' ),
			'<code>&lt;iframe&gt;</code>',
		),
	],
	[
		'title'     => esc_html__( 'Missing Subheadings', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1967',
		'slug'      => 'missing_headings',
		'rule_type' => 'warning',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;h1&gt;</code>, %2$s is <code>&lt;h6&gt;</code>.
			esc_html__( 'A warning about missing headings means that your post or page does not contain any heading elements (%1$s–%2$s) within the content of the post or page body section, which can make it especially difficult for screen reader users to navigate through the content on the page. To fix a page with no headings, you will need to add heading elements. At a minimum, every page should have one %1$s tag, which is typically the page title. Add additional subheadings as appropriate for your content. If you have determined that headings are definitely not needed on the page, then you can “Ignore” the warning.', 'accessibility-checker' ),
			'<code>&lt;h1&gt;</code>',
			'<code>&lt;h6&gt;</code>'
		),
		'viewable'  => false,
	],
	[
		'title'     => esc_html__( 'Text Justified', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1980',
		'slug'      => 'text_justified',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'A Text Justified warning appears in Accessibility Checker when text with more than 200 characters on your post or page is styled with justified alignment (text-align:justify). To fix this warning, remove the justified styling from the specified text elements.', 'accessibility-checker' ),
		'ruleset'   => 'js',
	],
	[
		'title'     => esc_html__( 'Link Opens New Window or Tab', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1982',
		'slug'      => 'link_blank',
		'rule_type' => 'warning',
		'summary'   => sprintf(
			// translators: %s is the link to the plugin, with text "Accessibility New Window Warnings" linked.
			esc_html__( 'A Link Opens New Window or Tab warning appears when there is a link on your website that has been set to open in a new tab or window when clicked. It is considered best practice to not open new tabs or windows with links. If links do open new tabs or windows, there must be a visual and auditory warning announcing that the link will open a new window or tab so that users will expect that behavior and know how to go back after clicking the link. To fix this warning, either change the link not to open in a new tab or ensure "opens new window" is included in the link text then "Ignore" the warning. To automatically add notices to all links on your site and dismiss all these warnings, install our free %s plugin.', 'accessibility-checker' ),
			'<a href="https://wordpress.org/plugins/accessibility-new-window-warnings/" target="_blank">' . esc_html__( 'Accessibility New Window Warnings', 'accessibility-checker' ) . '</a>'
		),
	],
	[
		'title'     => esc_html__( 'Image Map Missing Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1938',
		'slug'      => 'image_map_missing_alt_text',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %1$s is <code>&lt;area&gt;</code>, %2$s is <code>alt=""</code>.
			esc_html__( 'The Image Map Missing Alternative Text error means that one of the %1$s elements within your image map does not have alternative text added in an %2$s attribute. To fix this error, you will need to add missing alt text to any area tags that do not have alt text. The alt text needs to describe the function of the link contained in the area, not necessarily describe what the area looks like.', 'accessibility-checker' ),
			'<code>&lt;area&gt;</code>',
			'<code>alt=""</code>'
		),
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
	],
	[
		'title'     => esc_html__( 'Image Long Alternative Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1966',
		'slug'      => 'img_alt_long',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'An Image Long Alternative Text warning appears if there are more than 100 characters in your alternative text. Alternative text is meant to be descriptive of the image but in a succinct manner, without being too wordy. To fix this warning, you need to shorten your alt text for any images that have been flagged to 100 characters or less. If you have determined that your alternative text is good as-is, then "Ignore" the warning.', 'accessibility-checker' ),
	],
	[
		'title'     => esc_html__( 'ARIA Hidden', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1979',
		'slug'      => 'aria_hidden',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'The ARIA Hidden warning appears when content on your post or page has been hidden using the aria-hidden="true" attribute. When this attribute is added to an HTML element, screen readers will not read it out to users. Sometimes it is correct for the element to be hidden from screen readers (such as with a decorative icon) but other times this is not correct. When you see this warning, you need to determine if the element is supposed to be hidden from people who are blind or visually impaired. If it is correctly hidden, "Ignore" the warning. If it is incorrectly hidden and should be visible, remove the aria-hidden="true" attribute to resolve the warning.', 'accessibility-checker' ),
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
	],
	[
		'title'     => esc_html__( 'Link to MS Office File', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1970',
		'slug'      => 'link_ms_office_file',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'A Link to MS Office File warning means that one or more of the links on your page or post directs to a file with one of the following file extensions: .doc, .docx, .xls, .xlsx, .ppt, .pptx, .pps or .ppsx. This warning appears when an MS Office file is present as a reminder to manually test your Word documents, PowerPoint presentations, and Excel spreadsheets for accessibility and to confirm that they conform to all relevant WCAG guidelines. To resolve a Link to MS Office File warning, you need to: (1) ensure a direct link to view or download the document is present if you\'re using a plugin to embed it on the page; (2) ensure the link to the document warns users it is a link to a document by displaying the specific file extension in the link anchor; and (3) test and remediate your MS Office file for accessibility errors. After determining your file is fully accessible, you can safely “Ignore” the warning.', 'accessibility-checker' ),
	],
	[
		'title'     => esc_html__( 'Link to PDF', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1972',
		'slug'      => 'link_pdf',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'A Link to PDF warning means that one or more of the links on your page or post directs to a PDF file. This warning is a reminder to manually test the linked PDF for accessibility and to confirm that it conforms to all relevant WCAG guidelines. To resolve a Link to PDF warning, you need to: (1) ensure a direct link to view or download the document is present if you\'re using a plugin to embed it on the page; (2) ensure the link to the document warns users it is a link to a document by displaying the specific file extension in the link anchor; and (3) test and remediate your document for accessibility errors. After determining your file is fully accessible, you can safely “Ignore” the warning.', 'accessibility-checker' ),
	],
	[
		'title'     => esc_html__( 'Link to Non-HTML File', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1973',
		'slug'      => 'link_non_html_file',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'A  Link to Non-HTML Document warning means that one or more of the links on your page or post directs to a file with one of the following file extensions: .rtf, .wpd, .ods, .odt, .odp, .sxw, .sxc, .sxd, .sxi, .pages, or .key. This warning is a reminder to manually test the linked document for accessibility and to confirm that it conforms to all relevant WCAG guidelines. To resolve a Link to Non-HTML Document warning, you need to: (1) ensure a direct link to view or download the document is present if you\'re using a plugin to embed it on the page; (2) ensure the link to the document warns users it is a link to a document by displaying the specific file extension in the link anchor; and (3) test and remediate your document for accessibility errors. After determining your file is fully accessible, you can safely “Ignore” the warning.', 'accessibility-checker' ),
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
	],
	[
		'title'     => esc_html__( 'Empty Form Label', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help4109',
		'slug'      => 'empty_form_label',
		'rule_type' => 'error',
		'summary'   => sprintf(
			// translators: %s is <code>&lt;label&gt;</code>.
			esc_html__( 'An Empty Form Label error is triggered when a %s tag is present in your form and associated with an input (form field), but does not contain any text. To fix empty form label errors, you will need to determine how the field and form were created and then add text to the label for the field that is currently blank.', 'accessibility-checker' ),
			'<code>&lt;label&gt;</code>'
		),
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
	],
	[
		'title'     => esc_html__( 'Ambiguous Anchor Text', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1944',
		'slug'      => 'link_ambiguous_text',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'Ambiguous Anchor Text errors appear when there is linked text that has no meaning outside of its surrounding content. Common examples of this include linking phrases like "click here" or "learn more." To resolve this error, change the link text to be less generic so that it has meaning if heard on its own.', 'accessibility-checker' ),
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
	],
	[
		'title'     => esc_html__( 'Duplicate Form Label', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1954',
		'slug'      => 'duplicate_form_label',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'Duplicate Form Label errors appear when there is more than one label associated with a single field on a form. If there are too many form labels present, a screen reader may not be able to successfully read the form fields to help a visually impaired user navigate through and complete the form. To fix duplicate form label errors, you will need to determine how the field and form were created and then ensure that each field has only one label associated with it.', 'accessibility-checker' ),
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
	],
	[
		'title'     => esc_html__( 'Broken ARIA Reference', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help1956',
		'slug'      => 'broken_aria_reference',
		'rule_type' => 'error',
		'summary'   => esc_html__( 'Broken ARIA Reference errors appear if an aria-labeledby or aria-describedby element is present on the page or post but its reference target does not exist. This means that the element being referred to by the specific ARIA attribute you are using either does not have a proper label or descriptor, or it is not present on the page. To fix a broken ARIA reference, you will need to find the ARIA elements that are being flagged, and ensure that their reference targets are present and properly labeled.', 'accessibility-checker' ),
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
	],
	[
		'title'     => esc_html__( 'Image Animated GIF', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help4428',
		'slug'      => 'img_animated_gif',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'Image Animated GIF warnings appear when there is an animated GIF on your post or page. This warning is a reminder to manually review any animated GIFs on your website for their accessibility and/or to reconsider using animated GIFs, replacing them instead with static images or videos. To resolve this warning, you need to review any GIFs that are present to ensure that they meet all applicable guidelines for accessibility and then either “Ignore” the warning or remove the GIF from your page or post if it is not accessible.', 'accessibility-checker' ),
	],
	[
		'title'     => esc_html__( 'A Video is Present', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help4414',
		'slug'      => 'video_present',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'Because videos frequently contain accessibility problems, many of which can only be identified by a person, The A Video is Present warning appears anytime a video is detected on a post or page as a reminder that you need to manually test your video for accessibility. To resolve this warning, you need to visit the front end of your website and confirm that the video in the warning is accessible. Once you have fully tested the video for accessibility, you need to fix any errors that may be present and then can “Ignore” the warning to mark it as complete.', 'accessibility-checker' ),
	],
	[
		'title'     => esc_html__( 'A Slider is Present', 'accessibility-checker' ),
		'info_url'  => 'https://a11ychecker.com/help3264',
		'slug'      => 'slider_present',
		'rule_type' => 'warning',
		'summary'   => esc_html__( 'Because sliders frequently contain accessibility problems, many of which can only be identified by a person, the A Slider is Present warning appears anytime a slider is detected on a post or page as a reminder that you need to manually test your slider for accessibility. To resolve this warning, you need to visit the front end of your website and confirm all sliders on the page are accessible. Once you have fully tested your sliders for accessibility, you need to fix any errors that may be present and then can “Ignore” the warning to mark it as complete.', 'accessibility-checker' ),
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
	],
];
