import { isVisiblyHidden } from './helpers.js';

/**
 * Get visible images from an anchor element
 * @param {HTMLElement} node Anchor element to check
 * @return {Array} Array of visible image elements
 */
export const getVisibleImages = ( node ) => {
	const allImages = node.querySelectorAll( 'img' );
	return Array.from( allImages ).filter( ( img ) => ! isVisiblyHidden( img ) );
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
