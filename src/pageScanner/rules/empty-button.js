export default {
	id: 'empty_button',
	selector: 'button, [role="button"], input[type="button"], input[type="submit"], input[type="reset"]',
	tags: [ 'accessibility', 'wcag2a', 'wcag2aa' ],
	metadata: {
		description: 'Ensures buttons have accessible labels or content.',
		help: 'Buttons must have accessible text, aria-label, or title attributes.',
		helpUrl: 'https://a11ychecker.com/help1960',
	},

	any: [],
	all: [],
	none: [ 'button_is_empty' ],
};
