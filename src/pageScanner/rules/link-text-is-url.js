export default {
	id: 'link_text_is_url',
	selector: 'a[href]',
	tags: [ 'wcag2a', 'wcag244', 'usability' ],
	metadata: {
		description: 'Ensures links do not use the URL as link text.',
		help: 'Link text should be descriptive and not be the same as the URL.',
	},
	any: [],
	all: [],
	none: [ 'link-text-is-url' ],
};
