import { isVisiblyHidden } from './helpers.js';

/**
 * Get visible images from an anchor element
 * @param {HTMLElement} node Anchor element to check
 * @return {Array} Array of visible image elements
 */
export const getVisibleImages = ( node ) => {
	const allImages = node.querySelectorAll( 'img' );
	if ( allImages.length === 0 ) {
		return [];
	}

	// visibility is inherited, so compute the link's visibility once for all images.
	const isNodeHidden = window.getComputedStyle( node ).visibility === 'hidden';

	// A hidden value only belongs to the link itself when its parent is visible; when the
	// parent is hidden too the value is inherited from a closed panel and the link still scans.
	const parent = node.parentElement;
	if ( isNodeHidden && ( ! parent || window.getComputedStyle( parent ).visibility !== 'hidden' ) ) {
		// The link hides itself, so only images that override it with visibility:visible render.
		return Array.from( allImages ).filter( ( img ) => window.getComputedStyle( img ).visibility === 'visible' );
	}

	return Array.from( allImages ).filter( ( img ) => ! isVisiblyHidden( img, isNodeHidden ) );
};

/**
 * Check if anchor has sufficient accessible text
 * @param {HTMLElement} node Anchor element to check
 * @return {boolean} True if anchor has accessible text
 */
export const hasAccessibleText = ( node ) => {
	const textContent = ( node.textContent || '' ).trim();
	const hasText = textContent.length >= 5;
	const hasAriaLabel = node.getAttribute( 'aria-label' ) !== null && node.getAttribute( 'aria-label' ) !== '';
	const hasTitle = node.getAttribute( 'title' ) !== null && node.getAttribute( 'title' ) !== '';

	return hasText || hasAriaLabel || hasTitle;
};
