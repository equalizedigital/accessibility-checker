/**
 * Rule: Underlined text failure
 *
 * Text elements should not use the `u` and text elements should not be
 * underlined unless they are links.
 */

export default {
	id: 'underlined_text',
	impact: 'moderate',
	selector: '*:not(a):not(html):not(head):not(body)',
	matches: ( element ) => {
		// U tags we will always check.
		if ( element.tagName.toLowerCase() === 'u' ) {
			return true;
		}

		// Traverse up the dom 3 levels and check if the element is inside an anchor.
		let parent = element.parentNode;
		let isInsideAnchor = false;
		for ( let i = 0; i < 3; i++ ) {
			// can't go further up the dom if the parent is the html element.
			if ( parent.tagName.toLowerCase() === 'html' ) {
				break;
			}
			if ( parent && parent.tagName.toLowerCase() === 'a' ) {
				isInsideAnchor = true;
				break;
			}
			parent = parent.parentNode;
		}

		// If in an anchor, don't check underline.
		return ! isInsideAnchor;
	},
	tags: [ 'wcag324', 'wcag21aa', 'cat.text', 'custom' ],
	metadata: {
		description: 'Text elements should not be underlined unless they are links.',
	},
	all: [],
	any: [],
	none: [ 'element_is_u_tag', 'element_has_computed_underline' ],
};
