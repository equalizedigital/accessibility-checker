/**
 * Rule to check if images have valid alt text.
 * Based on WCAG 1.1.1 Non-text Content (Level A)
 */

export default {
	id: 'img_alt_invalid',
	selector: 'img',
	excludeHidden: true,
	any: [],
	all: [ 'img_alt_invalid_check' ],
	none: [],
	tags: [ 'wcag1a', 'wcag111', 'cat.text-alternatives' ],
	metadata: {
		description: 'Ensures images have valid alternative text',
		help: 'Images must have meaningful alt text rather than filenames or generic text',
		helpUrl: 'https://www.w3.org/WAI/WCAG21/Understanding/non-text-content.html',
	},
};
