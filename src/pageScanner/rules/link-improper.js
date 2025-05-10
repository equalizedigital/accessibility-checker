/**
 * Rule to detect improper use of <a> tags that are missing meaningful href attributes
 * and are not being used as buttons (via role="button").
 */

export default {
	id: 'link_improper',
	selector: 'a',
	tags: [
		'wcag2a',
		'wcag412',
		'cat.structure',
	],
	metadata: {
		description: 'Links must have a meaningful href or an appropriate role if used as buttons.',
		help: 'Avoid using <a> tags without href or with href="#" unless role="button" is used.',
		impact: 'serious',
	},
	all: [],
	any: [ 'link_has_valid_href_or_role' ],
	none: [],
};
