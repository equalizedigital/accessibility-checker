export default {
	id: 'empty_paragraph_tag',
	selector: 'p',
	excludeHidden: false,
	tags: [
		'cat.text',
		'best-practices',
	],
	impact: 'moderate',
	metadata: {
		description: 'Detects empty paragraph tags',
		help: 'Paragraphs should not be used for layout purposes and should never be empty',
	},
	all: [],
	any: [ 'paragraph_not_empty' ],
	none: [],
};
