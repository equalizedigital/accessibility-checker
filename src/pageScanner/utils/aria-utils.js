/**
 * Check if ARIA attributes that reference elements by ID point to existing elements.
 *
 * @param {Node}   node          The node to evaluate.
 * @param {string} attributeName The ARIA attribute name to check (e.g., 'aria-labelledby').
 * @return {boolean} True if a non-empty references exist, false otherwise.
 */
export function checkAriaReferences( node, attributeName ) {
	const attrValue = node.getAttribute( attributeName ) || '';
	if ( ! attrValue.trim() ) {
		return false; // Skip empty values
	}
	const ids = attrValue.split( /\s+/ ).filter( ( id ) => id.trim() );
	return ids.length === 0 || ids.every( ( id ) => document.getElementById( id ) !== null );
}
