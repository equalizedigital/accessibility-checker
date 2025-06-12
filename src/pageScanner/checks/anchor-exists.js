/**
 * Check if the anchor's target exists in the DOM.
 *
 * @param {Node} node The anchor node to evaluate.
 * @return {boolean} True if the target element exists, false otherwise.
 */

export default {
	id: 'anchor_exists',
	evaluate: ( node ) => {
		const href = node.getAttribute( 'href' );

		// Extract the fragment identifier (remove the # prefix)
		const fragment = href.slice( 1 );

		// Use CSS.escape() to safely handle special characters in selectors
		const idSelector = `#${ CSS.escape( fragment ) }`;

		// First try the standard CSS selector approach (finds elements with matching IDs)
		if ( document.querySelector( idSelector ) !== null ) {
			return true;
		}

		// If no ID match found, check for anchor elements with matching name attribute
		// Also escape the fragment for the name attribute selector
		const namedAnchor = document.querySelector( `a[name="${ CSS.escape( fragment ) }"]` );

		return namedAnchor !== null;
	},
};
