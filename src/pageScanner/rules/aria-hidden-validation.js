export default {
	id: 'aria_hidden_validation',
	// WordPress core block elements that legitimately use aria-hidden="true" on decorative content:
	//   wp-block-cover__background        — colour overlay span inside a cover block
	//   wp-block-cover__image-background  — background image inside a cover block
	//   wp-block-spacer                   — intentionally empty spacing element
	//   wp-block-separator                — decorative HR element
	selector: '[aria-hidden="true"]:not(.wp-block-cover__background):not(.wp-block-cover__image-background):not(.wp-block-spacer):not(.wp-block-separator)',
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
