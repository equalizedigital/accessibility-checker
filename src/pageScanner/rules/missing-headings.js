export default {
	id: 'missing_headings',
	selector: 'body',
	tags: [ 'wcag2a', 'best-practice' ],
	all: [],
	any: [ 'has_subheadings_if_long_content' ],
	none: [],
	metadata: {
		description: 'Ensures long content has appropriate heading structure',
		help: 'Content with more than 400 words should contain headings to improve readability and structure',
	},
};
