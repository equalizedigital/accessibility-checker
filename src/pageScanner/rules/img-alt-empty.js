/**
 * Rule for detecting images with empty alt attributes.
 * Based on WCAG 1.1.1: Non-text Content (Level A)
 */

export default {
	id: 'img_alt_empty',
	selector: 'img[alt=""], input[type="image"][alt=""]',
	excludeHidden: true,
	tags: [ 'cat.text-alternatives', 'wcag1a', 'wcag111' ],
	all: [],
	any: [ 'img_alt_empty_check' ],
	none: [],
	metadata: {
		description: 'Ensures images with attributes alt="" are not used when they require alternative text',
		help: 'Images with empty alt attributes must be decorative or already described in context',
	},
};
