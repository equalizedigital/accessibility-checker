/**
 * Rule: Underlined text failure
 *
 * Text elements should not use the `u` and text elements should not be
 * underlined unless they are links.
 */

export default {
	id: 'underlined_text_failure',
	impact: 'moderate',
	matches: ( element ) => {
		return (
			! element.hasAttribute( 'href' ) ||
			element.tagName.toLowerCase() === 'u'
		);
	},
	tags: [ 'wcag324', 'wcag21aa', 'cat.text', 'custom' ],
	metadata: {
		description: 'Text elements should not be underlined unless they are links.',
	},
	all: [],
	any: [],
	none: [ 'element_is_u_tag', 'element_has_computed_underline' ],
};
