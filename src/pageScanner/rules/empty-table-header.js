export default {
	id: 'empty_table_header',
	selector: 'th, [role="columnheader"], [role="rowheader"]',
	excludeHidden: false,
	tags: [ 'wcag2a', 'wcag1.3.1', 'wcag4.1.2' ],
	metadata: {
		description: 'Ensures table headers have discernible text',
		help: 'Table headers must have discernible text',
		helpUrl: 'https://a11ychecker.com/help4109',
	},
	any: [],
	all: [],
	none: [ 'table_header_is_empty' ],
};
