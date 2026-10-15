/**
 * Check for two links placed immediately next to each other that point to the same destination.
 */

export default {
	id: 'link_duplicate_adjacent',
	selector: 'a[href]',
	excludeHidden: false,
	tags: [
		'cat.text',
		'wcag2a',
		'wcag244',
		'best-practice',
	],
	metadata: {
		description: 'Detects links placed directly next to another link that shares the same destination.',
		help: 'Adjacent links that point to the same destination should be combined into a single link.',
	},
	all: [],
	any: [],
	none: [ 'link_has_adjacent_duplicate' ],
};
