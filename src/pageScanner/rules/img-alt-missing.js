/**
 * Rule to check if images have missing alt text.
 * Based on WCAG 1.1.1 Non-text Content (Level A)
 */

export default {
	id: 'img_alt_missing',
	selector: 'img, input[type="image"]',
	excludeHidden: true,
	any: [],
	all: [ 'img_alt_missing_check' ],
	none: [],
	tags: [ 'wcag1a', 'wcag111', 'cat.text-alternatives' ],
	metadata: {
		description: 'Ensures images have alt text',
		help: 'Images must have an alt attribute',
		helpUrl: 'https://www.w3.org/WAI/WCAG21/Understanding/non-text-content.html',
	},
};
