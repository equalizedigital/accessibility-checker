export default {
	id: 'naked-link', // Changed from 'link-is-naked-text'
	selector: 'a[href]',
	tags: [ 'wcag2a', 'wcag2.4.4', 'usability' ], // Changed tags
	metadata: {
		description: 'Ensures links do not use the URL as link text.',
		help: 'Link text should be descriptive and not be the same as the URL.',
		helpUrl: 'https://dequeuniversity.com/rules/axe/4.7/link-name', // Updated helpUrl
	},
	any: [],
	all: [],
	none: [ 'link-is-naked' ],
};
