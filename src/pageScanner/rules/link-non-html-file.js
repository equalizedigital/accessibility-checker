/**
 * Rule to detect links pointing to non-HTML documents that may need a warning or alternate format.
 */

export default {
	id: 'link_non_html_file',
	selector: 'a[href]',
	tags: [
		'best-practice',
		'cat.structure',
	],
	metadata: {
		description: 'Links to non-HTML documents should be clearly labeled or avoided.',
		help: 'Avoid linking to non-HTML documents without warnings or alternatives.',
		impact: 'moderate',
	},
	all: [],
	any: [ 'link_points_to_html' ],
	none: [],
};
