/**
 * Check if the given node is an anchor tag (<a>) and validate its content.
 *
 * This function evaluates whether an anchor tag contains sufficient descriptive
 * content, such as text, aria-label, title, or valid alt attributes for images.
 *
 * @param {Node} node The node to evaluate.
 * @return {boolean} True if the anchor tag has valid descriptive content, false otherwise.
 */

import { getVisibleImages, hasAccessibleText } from '../helpers/linkedImageUtils.js';

export default {
	id: 'linked_image_alt_present',
	evaluate: ( node ) => {
		if ( node.nodeName.toLowerCase() !== 'a' ) {
			return true;
		}

		if ( hasAccessibleText( node ) ) {
			return true;
		}

		const images = getVisibleImages( node );
		if ( images.length === 0 ) {
			return true;
		}

		// Check each visible image for alt attribute
		return images.every( ( img ) => {
			const hasAlt = img.hasAttribute( 'alt' );
			const role = img.getAttribute( 'role' );
			const ariaHidden = img.getAttribute( 'aria-hidden' );

			return hasAlt || role === 'presentation' || ariaHidden === 'true';
		} );
	},
};
