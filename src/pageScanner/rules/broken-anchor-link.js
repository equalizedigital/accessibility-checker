/**
 * Rule: Broken skip anchor link
 *
 * Description: Check if the skip anchor link is broken.
 */

export default {
	id: 'broken_skip_anchor_link',
	selector: 'a[href^="#"]:not([href="#"]):not([role="button"])',
	tags: [ 'wcag2a', 'wcag131', 'wcag241', 'custom' ],
	metadata: {
		description: 'Check if the skip anchor link is broken or missing its target.',
	},
	all: [],
	any: [ 'anchor_exists' ],
	none: [],
};
