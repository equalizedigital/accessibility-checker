export default {
	id: 'img_alt_redundant',
	selector: 'img, figure img',
	any: [ 'img_alt_redundant_check' ],
	none: [],
	tags: [ 'duplicate', 'redundant', 'accessibility' ],
	metadata: {
		description: 'Checks for redundant alternative text on images, including duplicate alt text across images; alt text matching title, link text or figcaption.',
		help: 'Ensure that each image has unique, meaningful alt text that does not duplicate related text (such as its title, associated link text, or accompanying caption).',
		helpUrl: 'https://a11ychecker.com/help1976',
	},
};
