export default {
	id: 'aria_broken_reference',
	selector: '[aria-labelledby], [aria-describedby], [aria-owns]',
	excludeHidden: true,
	tags: [

	],
	metadata: {
		description: 'Ensures ARIA attributes reference existing elements',
		help: 'ARIA attributes that reference other elements must point to elements that exist in the DOM',
		impact: 'critical',
	},
	all: [],
	any: [ 'aria_label_not_found', 'aria_describedby_not_found', 'aria_owns_not_found' ],
	none: [],
};
