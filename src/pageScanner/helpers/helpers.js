export const fontSizeInPx = ( node ) => {
	if ( ! node || node.nodeType !== Node.ELEMENT_NODE ) {
		return 0;
	}

	const fontSize = parseFloat( window.getComputedStyle( node ).fontSize );
	return typeof fontSize === 'number' ? fontSize : 0;
};

/**
 * Check if an element is visibly hidden via CSS or aria attributes
 * @param {HTMLElement} element The element to check
 * @return {boolean} True if element is hidden
 */
export const isVisiblyHidden = ( element ) => {
	const style = window.getComputedStyle( element );
	return style.display === 'none' ||
           style.visibility === 'hidden' ||
           element.closest( '[aria-hidden="true"]' ) !== null;
};
