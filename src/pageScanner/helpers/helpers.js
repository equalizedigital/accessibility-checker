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
 * Check if an element or any of its ancestors is hidden with `display: none` or `opacity: 0`.
 *
 * Neither property is inherited, so a descendant cannot undo them.
 *
 * @param {Element} element The element to check.
 * @param {Map}     cache   Results by element, so shared ancestors are only read once.
 * @return {boolean} True if the element or an ancestor is hidden this way.
 */
const isHiddenByAncestry = ( element, cache ) => {
	if ( ! element ) {
		return false;
	}

	if ( ! cache.has( element ) ) {
		const style = window.getComputedStyle( element );
		cache.set(
			element,
			style.display === 'none' || style.opacity === '0' || isHiddenByAncestry( element.parentElement, cache )
		);
	}

	return cache.get( element );
};

/**
 * Check if an element is visible in the DOM
 *
 * Visibility is inherited, so the element's own computed value is used and an element
 * that sets `visibility: visible` inside a hidden ancestor is still visible. Display and
 * opacity are not inherited, so the element and all of its ancestors are checked for those.
 *
 * @param {Element} element an element to check for visibility.
 * @param {Map}     [cache] Optional Map shared between calls on an unchanged DOM, so ancestors are only read once.
 * @return {boolean} A boolean indicating if the element is visible or not.
 */
export const isElementVisible = ( element, cache = new Map() ) => {
	// A non-existent element can never be visible.
	if ( ! element ) {
		return false;
	}

	return window.getComputedStyle( element ).visibility !== 'hidden' && ! isHiddenByAncestry( element, cache );
};

/**
 * Check if an element (or any ancestor) is hidden using the screen-reader-only
 * technique — visually clipped to nothing but still in the accessibility tree.
 * Font-size checks are irrelevant for such elements.
 *
 * @param {HTMLElement} element The element to check.
 * @param {Map}         [cache] Optional Map shared between calls on an unchanged DOM, so ancestors are only read once.
 * @return {boolean} True if the element is screen-reader-only.
 */
export const isScreenReaderOnly = ( element, cache = new Map() ) => {
	if ( ! element || element.nodeType !== Node.ELEMENT_NODE ) {
		return false;
	}

	if ( ! cache.has( element ) ) {
		const style = window.getComputedStyle( element );
		const isPositional = style.position === 'absolute' || style.position === 'fixed';
		cache.set(
			element,
			( isPositional && style.clip === 'rect(0px, 0px, 0px, 0px)' ) ||
				style.clipPath === 'inset(50%)' ||
				// Catch sr-only patterns that rely on 1px dimensions rather than clip values
				// (e.g. Elementor's .elementor-screen-only uses width/height:1px + overflow:hidden).
				( isPositional && style.overflow === 'hidden' &&
					parseFloat( style.width ) <= 1 && parseFloat( style.height ) <= 1 ) ||
				isScreenReaderOnly( element.parentElement, cache )
		);
	}

	return cache.get( element );
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
