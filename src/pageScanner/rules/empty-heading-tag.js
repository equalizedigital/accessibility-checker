export default {
	id: 'empty_heading_tag',
	selector: 'h1, h2, h3, h4, h5, h6',
	metadata: {
		description: 'Ensures headings have discernible text',
		help: 'Headings must have discernible text',
		helpUrl: 'https://a11ychecker.com/help1957',
	},
	tags: [ 'wcag2a', 'best-practice' ],
	all: [],
	any: [ 'heading_is_empty' ],
	none: [],
};
