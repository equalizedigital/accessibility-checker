export const fontSizeInPx = ( node ) => {
	if ( ! node || node.nodeType !== Node.ELEMENT_NODE ) {
		return 0;
	}

	const fontSize = parseFloat( window.getComputedStyle( node ).fontSize );
	return typeof fontSize === 'number' ? fontSize : 0;
};

/**
 * Helper function to normalize text by trimming and replacing consecutive whitespace
 * @param {string} text - Text to normalize
 * @return {string} Normalized text
 */
export const normalizeText = ( text ) => {
	return ( text || '' ).trim().toLowerCase().replace( /\s+/g, ' ' );
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

/**
 * Check if an element is visible in the DOM
 *
 * This is a recursive function so could be inefficient for deeply nested elements.
 *
 * @param {element} element an element to check for visibility.
 * @return {boolean|boolean|*} A boolean indicating if the element is visible or not.
 */
export const isElementVisible = ( element ) => {
	// A non-existent element can never be visible.
	if ( ! element ) {
		return false;
	}

	// Check if the element itself is hidden.
	const style = window.getComputedStyle( element );
	if ( style.display === 'none' || style.visibility === 'hidden' || style.opacity === '0' ) {
		return false;
	}

	// If there is a parent then check it recursively till there is no more parents.
	return element.parentElement ? isElementVisible( element.parentElement ) : true;
};

/**
 * A Map that normalizes all keys to lowercase
 */
export class NormalizedMap extends Map {
	set( key, value ) {
		return super.set( typeof key === 'string' ? key.toLowerCase() : key, value );
	}

	get( key ) {
		return super.get( typeof key === 'string' ? key.toLowerCase() : key );
	}

	has( key ) {
		return super.has( typeof key === 'string' ? key.toLowerCase() : key );
	}

	delete( key ) {
		return super.delete( typeof key === 'string' ? key.toLowerCase() : key );
	}
}
