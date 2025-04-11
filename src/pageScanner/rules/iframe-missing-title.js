/**
 * Rule to detect iframes that are missing a title or aria-label attribute.
 * Iframes require a title or aria-label to be accessible to screen reader users.
 */

export default {
	id: 'iframe_missing_title',
	selector: 'iframe',
	excludeHidden: false,
	tags: [
		'wcag2a',
		'wcag241',
		'wcag412',
		'section508',
		'cat.text-alternatives',
	],
	metadata: {
		description: 'Ensures iframe elements have a title or aria-label attribute',
		help: 'Iframe elements must have a title or aria-label attribute',
		impact: 'serious',
	},
	all: [],
	any: [],
	none: [ 'is_iframe_missing_title' ],
};
