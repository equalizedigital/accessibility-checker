import { exclusionsArray } from '../config/exclusions';

/**
 * Calculate page density metrics
 * @param {HTMLElement} body - The body element to analyze
 * @return {[number, number]} Array containing [elementCount, contentLength]
 */
export function getPageDensity( body = document.body ) {
	// If we can't get a body then return as if there was nothing.
	if ( ! body ) {
		return [ 0, 0 ];
	}

	// Remove elements we don't want to count from a clone to avoid modifying original
	const bodyClone = body.cloneNode( true );

	// Remove elements we don't want to count
	const selectorsToRemove = [ ...exclusionsArray, 'style', 'script' ];
	selectorsToRemove.forEach( ( selector ) => {
		bodyClone.querySelectorAll( selector ).forEach( ( el ) => el.remove() );
	} );

	// Count all elements within the clone
	const allElements = bodyClone.getElementsByTagName( '*' );
	const elementCount = allElements.length;

	// Get text content and count alphanumeric characters only
	const textContent = bodyClone.textContent || '';
	const contentTextLength = textContent.replace( /[^A-Za-z0-9]/g, '' ).length;

	return [ elementCount, contentTextLength ];
}
