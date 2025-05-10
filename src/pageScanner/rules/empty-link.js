export default {
	id: 'empty_link',
	selector: 'a[href]',
	tags: [ 'wcag2a', 'wcag2.4.4', 'wcag4.1.2' ],
	metadata: {
		description: 'Ensures links have discernible text',
		help: 'Links must have discernible text',
		helpUrl: 'https://a11ychecker.com/help4108',
	},
	any: [],
	all: [],
	none: [ 'link-is-empty' ],
};
