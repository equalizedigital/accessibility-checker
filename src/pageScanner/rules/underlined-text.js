/**
 * Rule: Underlined text failure
 *
 * Text elements should not use the `u` and text elements should not be
 * underlined unless they are links.
 */

export default {
	id: 'underlined_text',
	excludeHidden: false,
	selector: '*:not(a):not([role="link"]):not(a *):not([role="link"] *)',
	metadata: {
		description: 'Text elements should not be underlined unless they are links.',
	},
	all: [],
	none: [ 'element_with_underline', 'element_is_u_tag' ],
};
