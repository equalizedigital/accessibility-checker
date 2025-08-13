export default {
	id: 'aria_hidden_validation',
	selector: '[aria-hidden="true"]',
	excludeHidden: false,
	tags: [
		'wcag2a',
		'wcag131',
		'cat.aria',
		'cat.semantics',
	],
	metadata: {
		description: 'Ensures elements with aria-hidden="true" are used appropriately',
		help: 'Elements with aria-hidden="true" should not hide important content that is unavailable elsewhere',
		impact: 'serious',
	},
	all: [],
	any: [ 'aria_hidden_valid_usage' ],
	none: [],
};
