/**
 * Rule: Text Justified
 *
 * Text elements should not be justified because it interferes with readability.
 */

const TEXT_JUSTIFIED_CHECK_THRESHOLD = 500;
export default {
	id: 'text_justified',
	selector: 'p, div, td',
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
