/**
 * Rule: Text small failure
 *
 * Text should have a minimum size and anything below that is a fail.
 */

export default {
	id: 'text_small',
	impact: 'moderate',
	selector: 'p, span, small, strong, b, i, h1, h2, h3, h4, h5, h6, a, label, button, th, td, li, div, blockquote, address, cite, code, pre, q, s, sub, sup, u, var, abbr, acronym, del, dfn, em, ins, kbd, input, select, textarea, caption, dl, dt, dd, li, figure, figcaption, details, dialog, summary, data, time',
	matches: ( element ) => {
		// only run checks on elements with text content
		return element.textContent.trim().length;
	},
	tags: [ 'wcag2aaa', 'wcag144', 'wcag148', 'cat.text' ],
	metadata: {
		description: 'Text elements should not be too small.',
	},
	all: [],
	any: [],
	none: [ 'text_size_too_small' ],
};
