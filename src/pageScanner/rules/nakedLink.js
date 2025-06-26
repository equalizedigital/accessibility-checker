export default {
	id: 'naked_link',
	selector: 'a[href]',
	tags: [ 'wcag2a', 'wcag2.4.4', 'usability' ], // Changed tags
	metadata: {
		description: 'Ensures links do not use the URL as link text.',
		help: 'Link text should be descriptive and not be the same as the URL.',
	},
	any: [],
	all: [],
	none: [ 'link-is-naked' ],
};
