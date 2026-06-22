export default {
	id: 'empty_link',
	selector: 'a[href]',
	tags: [ 'wcag2a', 'wcag244', 'wcag412' ],
	metadata: {
		description: 'Ensures links have discernible text',
		help: 'Links must have discernible text',
		helpUrl: 'https://a11ychecker.com/help4108',
	},
	any: [],
	all: [],
	none: [ 'link-is-empty' ],
};
