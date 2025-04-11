export const fontSizeInPx = ( node ) => {
	if ( ! node || node.nodeType !== Node.ELEMENT_NODE ) {
		return 0;
	}

	const fontSize = parseFloat( window.getComputedStyle( node ).fontSize );
	return typeof fontSize === 'number' ? fontSize : 0;
};

/**
 * Check if an element is visible in the DOM
 *
 * This is a recursive function so could be inefficient for deeply nested elements.
 *
 * @param {element} element an element to check for visibility.
 * @return {boolean|boolean|*} A boolean indicating if the element is visible or not.
 */
export const isElementVisible = ( element ) => {
	if ( ! element ) {
		return false;
	} // If the element doesn't exist
	const style = window.getComputedStyle( element );

	// Check if the element itself is hidden
	if ( style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0' ) {
		return false;
	}

	// Check the parent recursively
	return element.parentElement ? isElementVisible( element.parentElement ) : true;
};
