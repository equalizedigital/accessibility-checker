export default {
	id: 'img_linked_alt_empty',
	selector: 'a',
	tags: [
		'wcag2a',
		'wcag111',
		'cat.text-alternatives',
	],
	metadata: {
		description: 'Ensures linked images do not have empty alt text',
		help: 'Linked images must have meaningful alternative text describing the link purpose',
		impact: 'serious',
	},
	all: [],
	any: [ 'linked_image_alt_not_empty' ],
	none: [],
};
