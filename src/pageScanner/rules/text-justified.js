/**
 * Rule: Text Justified
 *
 * Text elements should not be justified because it interferes with readability.
 */

const TEXT_JUSTIFIED_CHECK_THRESHOLD = 200;
export default {
	id: 'text_justified',
	selector: 'p, span, small, strong, b, i, em, h1, h2, h3, h4, h5, h6, a, label, button, th, td, li, div, blockquote, address, cite, q, s, sub, sup, u, del, caption, dt, dd, figcaption, summary, data, time',
	matches: ( element ) => {
		return element.textContent.trim().length >= TEXT_JUSTIFIED_CHECK_THRESHOLD;
	},
	tags: [ 'wcag2aaa', 'wcag148', 'cat.text', 'custom' ],
	metadata: {
		description: 'Text elements inside containers should not be justified.',
	},
	all: [],
	any: [],
	none: [ 'text_is_justified' ],
};
