export default {
	id: 'possible_heading',
	selector: 'p',
	matches: ( node ) => {
		// Not inside a blockquote, figcaption or table cell.
		if ( node.closest( 'blockquote, figcaption, td' ) ) {
			return false;
		}

		// Elements with role="heading" are already semantic headings — skip them,
		// but only when aria-level is explicitly set to a valid integer in the
		// range 1–6.  aria-level is a required attribute for role="heading", so a
		// missing, out-of-range, or non-numeric value means the ARIA heading is
		// invalid and the element should still be checked.
		if ( node.getAttribute( 'role' ) === 'heading' ) {
			const ariaLevel = node.getAttribute( 'aria-level' );
			const level = parseInt( ariaLevel, 10 );
			if ( ! isNaN( level ) && level >= 1 && level <= 6 ) {
				return false; // Valid ARIA heading level — not a possible-heading issue.
			}
			// aria-level is missing, out of range, or non-numeric — fall through
			// to the check so this element can still be flagged.
		}

		return true;
	},
	excludeHidden: false,
	tags: [
		'wcag2a',
		'wcag131',
		'wcag241',
		'cat.semantics',
	],
	metadata: {
		description: 'Headings should be used to convey the structure of the page, not styled paragraphs',
		help: 'Paragraphs should not be styled to look like headings. Use the appropriate heading tag instead.',
	},
	all: [],
	any: [],
	none: [ 'paragraph_styled_as_header' ],
};
