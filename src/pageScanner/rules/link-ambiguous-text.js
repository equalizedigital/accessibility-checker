export default {
	id: 'link_ambiguous_text',
	enabled: true,
	selector: 'a',
	excludeHidden: false,
	tags: [
		'cat.text',
		'best-practices',
	],
	metadata: {
		description: 'Detects ambiguous link text',
		help: 'Links should have descriptive text to help users understand their purpose.',
	},
	any: [],
	all: [],
	none: [ 'has_ambiguous_text' ],
};
