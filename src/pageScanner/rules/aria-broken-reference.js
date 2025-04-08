export default {
	id: 'aria_broken_reference',
	selector: '[aria-labelledby], [aria-describedby], [aria-owns]',
	excludeHidden: true,
	tags: [

	],
	metadata: {
		description: '',
		help: '',
		impact: '',
	},
	all: [],
	any: [ 'aria_label_not_found', 'aria_describedby_not_found', 'aria_owns_not_found' ],
	none: [],
};
