export default {
	id: 'possible_heading',
	selector: 'p',
	matches: ( node ) => {
		// Not inside a blockquote, figcaption or table cell
		return ! node.closest( 'blockquote, figcaption, td' );
	},
	excludeHidden: false,
	tags: [
		'wcag2a',
		'wcag131',
		'wcag241',
		'cat.semantics',
	],
	metadata: {
		description: 'Headings should be used to convey the structure of the page, not styled paragraphs',
		help: 'Paragraphs should not be styled to look like headings. Use the appropriate heading tag instead.',
	},
	all: [],
	any: [],
	none: [ 'paragraph_styled_as_header' ],
};
