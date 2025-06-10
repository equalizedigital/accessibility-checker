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

		// First try the standard CSS selector approach (finds elements with matching IDs)
		if ( document.querySelector( href ) !== null ) {
			return true;
		}

		// If no ID match found, check for anchor elements with matching name attribute
		// Extract the fragment identifier (remove the # prefix)
		const fragment = href.substring( 1 );

		// Look for anchor elements with matching name attribute
		const namedAnchor = document.querySelector( `a[name="${ fragment }"]` );

		return namedAnchor !== null;
	},
};
