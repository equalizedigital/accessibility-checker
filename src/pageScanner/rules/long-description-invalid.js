/**
 * Rule to detect the presence of invalid longdesc attributes on images.
 * This rule ensures that the longdesc attribute points to a valid, non-image resource
 * that provides a detailed description for the image.
 */

export default {
	id: 'long_description_invalid',
	selector: 'img[longdesc]',
	tags: [
		'wcag2a',
		'wcag131',
		'cat.text-alternatives',
	],
	metadata: {
		description: 'Checks that longdesc attributes are valid and do not point to images.',
		help: 'longdesc should link to a non-image resource with a detailed description',
		impact: 'moderate',
	},
	all: [],
	any: [ 'longdesc_valid' ],
	none: [],
};
