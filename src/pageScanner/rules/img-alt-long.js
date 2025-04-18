/**
 * Rule for detecting images with alt text that is too long.
 * Based on WCAG 1.1.1: Non-text Content (Level A)
 */

export default {
	id: 'img_alt_long',
	selector: 'img[alt]',
	excludeHidden: true,
	tags: [ 'cat.text-alternatives', 'wcag1a', 'wcag111' ],
	all: [],
	any: [ 'img_alt_long_check' ],
	none: [],
	metadata: {
		description: 'Ensures images do not have excessively long alt text',
		help: 'Image alt text should be concise and not exceed 300 characters',
	},
};
