/**
 * Rule to detect the presence of linked images missing alternative text.
 * This rule ensures that linked images have meaningful alternative text
 * to describe the purpose of the link for accessibility.
 */

export default {
	id: 'img_linked_alt_missing',
	selector: 'a',
	tags: [
		'wcag2a',
		'wcag111',
		'cat.text-alternatives',
	],
	metadata: {
		description: 'Checks that linked images have meaningful alternative text.',
		help: 'Linked images must have alternative text describing link purpose.',
		impact: 'serious',
	},
	all: [],
	any: [ 'linked_image_alt_present' ],
	none: [],
};
