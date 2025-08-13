/**
 * Check to detect the presence of table headers.
 * Ensures that tables have <th> elements or appropriate scope attributes to define headers.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if table headers are present, false otherwise.
 */

export default {
	id: 'missing_table_header',
	selector: 'table',
	excludeHidden: false,
	tags: [
		'wcag2a',
		'wcag131',
		'cat.structure',
	],
	metadata: {
		description: 'Tables must have header cells to convey data relationships',
		help: 'Ensure that tables use <th> elements with text or appropriate scope attributes',
		impact: 'serious',
	},
	all: [],
	any: [ 'table_has_headers' ],
	none: [],
};
